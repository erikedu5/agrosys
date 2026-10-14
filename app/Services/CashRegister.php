<?php

namespace App\Services;

use App\Models\User;
use App\Services\Pos\Decimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashRegister
{
    public function current(int $user, int $branch): ?object
    {
        return DB::table('cash_sessions')->where('active_key', "$user:$branch")->first();
    }

    // All cash mutations lock the cashier first, then the session. Sales and
    // closing therefore cannot cross the cut-off inside a database transaction.
    public function saleSession(int $user, int $branch, mixed $id = null, bool $explicit = false): ?object
    {
        User::whereKey($user)->lockForUpdate()->firstOrFail();
        if ($id !== null && (! is_string($id) || ! Str::isUuid($id))) {
            throw ValidationException::withMessages(['cashSessionId' => 'Identificador de caja inválido.']);
        }
        if ($explicit && $id === null) {
            return null; // A queued sale made outside a session stays outside it.
        }
        $session = $id === null ? $this->current($user, $branch)
            : DB::table('cash_sessions')->where('id', $id)->first();
        if ($id !== null && (! $session || (int) $session->user_id !== $user || (int) $session->branch_id !== $branch || $session->closed_at)) {
            throw ValidationException::withMessages(['cashSessionId' => 'La caja no pertenece al cajero/sucursal o ya fue cerrada. Requiere revisión.']);
        }

        return $session;
    }

    public function record(?object $session, int $cents, string $kind, string $source, string $description): void
    {
        if (! $session || $cents === 0) {
            return;
        }
        $expected = (int) $session->opening_cents + (int) DB::table('cash_movements')->where('session_id', $session->id)->sum('amount_cents') + $cents;
        if (abs($expected) > 9999999999) {
            throw ValidationException::withMessages(['amount' => 'El efectivo esperado supera el importe máximo permitido.']);
        }
        DB::table('cash_movements')->insert(['session_id' => $session->id, 'kind' => $kind,
            'amount_cents' => $cents, 'source' => $source, 'description' => $description, 'created_at' => now()]);
    }

    public function requireSaleSession(int $user, int $branch, mixed $id = null, bool $explicit = false): object
    {
        $session = $this->saleSession($user, $branch, $id, $explicit);
        if (! $session) {
            throw ValidationException::withMessages(['cashSessionId' => 'Abre la caja antes de realizar una venta.']);
        }

        return $session;
    }

    public function summary(object $session): array
    {
        $totals = DB::table('cash_movements')->where('session_id', $session->id)
            ->selectRaw('kind, SUM(amount_cents) as cents')->groupBy('kind')->pluck('cents', 'kind');
        $expected = (int) $session->opening_cents + (int) $totals->sum();

        return ['id' => $session->id, 'openedAt' => Carbon::parse($session->opened_at)->toIso8601String(), 'closedAt' => $session->closed_at ? Carbon::parse($session->closed_at)->toIso8601String() : null,
            'opening' => Decimal::format($session->opening_cents), 'expected' => Decimal::format($session->expected_cents ?? $expected),
            'counted' => $session->counted_cents === null ? null : Decimal::format($session->counted_cents),
            'difference' => $session->difference_cents === null ? null : Decimal::format($session->difference_cents),
            'notes' => $session->notes, 'totals' => $totals->map(fn ($value) => Decimal::format((int) $value))->all()];
    }

    public function status(int $user, int $branch): array
    {
        $current = $this->current($user, $branch);

        return ['current' => $current ? $this->summary($current) : null,
            'history' => DB::table('cash_sessions')->where('user_id', $user)->where('branch_id', $branch)
                ->whereNotNull('closed_at')->orderByDesc('closed_at')->limit(20)->get()->map(fn ($row) => $this->summary($row))->all(),
            'movements' => $current ? DB::table('cash_movements')->where('session_id', $current->id)->orderByDesc('id')->limit(100)
                ->get()->map(fn ($row) => ['id' => (string) $row->id, 'kind' => $row->kind,
                    'amount' => Decimal::format($row->amount_cents), 'description' => $row->description, 'date' => Carbon::parse($row->created_at)->toIso8601String()])->all() : []];
    }

    public function write(int $user, int $branch, string $action, array $data): array
    {
        $id = strtolower($data['operation_id']);
        unset($data['operation_id']);
        ksort($data);
        $hash = hash('sha256', json_encode([$action, $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($user, $branch, $action, $data, $id, $hash) {
            User::whereKey($user)->lockForUpdate()->firstOrFail();
            if ($stored = DB::table('cash_requests')->where('id', $id)->first()) {
                abort_unless((int) $stored->user_id === $user && (int) $stored->branch_id === $branch, 403, 'Solicitud de otra caja.');
                abort_unless(hash_equals($stored->request_hash, $hash), 409, 'El identificador ya se utilizó con datos diferentes.');

                return json_decode($stored->result, true, flags: JSON_THROW_ON_ERROR);
            }
            $current = $this->current($user, $branch);
            if ($action === 'open') {
                abort_if($current, 409, 'Ya tienes una caja abierta en esta sucursal.');
                $sessionId = (string) Str::uuid();
                DB::table('cash_sessions')->insert(['id' => $sessionId, 'user_id' => $user, 'branch_id' => $branch,
                    'active_key' => "$user:$branch", 'opening_cents' => Decimal::units($data['opening'], 'opening'), 'opened_at' => now()]);
            } else {
                abort_unless($current && $current->id === $data['session_id'], 409, 'La caja cambió o ya fue cerrada. Actualiza antes de continuar.');
                $sessionId = $current->id;
                if ($action === 'movement') {
                    $amount = Decimal::units($data['amount'], 'amount');
                    abort_unless($amount > 0, 422, 'El importe debe ser mayor a cero.');
                    $signed = $data['kind'] === 'out' ? -$amount : $amount;
                    abort_if($signed < 0 && Decimal::units($this->summary($current)['expected'], 'expected', true) < $amount, 422, 'La salida supera el efectivo esperado.');
                    $this->record($current, $signed, $data['kind'], 'manual:'.$id, trim($data['description']));
                } else {
                    // Pending operations already received by the server must be resolved.
                    $pending = DB::table('offline_operations')->where('user_id', $user)->where('branch_id', $branch)
                        ->whereNotIn('status', ['confirmed'])->get(['payload']);
                    foreach ($pending as $operation) {
                        if ((json_decode($operation->payload, true)['cashSessionId'] ?? null) === $current->id) {
                            abort(409, 'Hay ventas de esta caja pendientes o en revisión. Resuélvelas antes del corte.');
                        }
                    }
                    $expected = Decimal::units($this->summary($current)['expected'], 'expected', true);
                    abort_unless($expected === Decimal::units($data['expected'], 'expected', true), 409, 'Cambió el efectivo esperado. Actualiza y revisa el corte.');
                    $counted = Decimal::units($data['counted'], 'counted');
                    abort_if($counted !== $expected && trim($data['notes'] ?? '') === '', 422, 'Explica la diferencia de efectivo en las observaciones.');
                    DB::table('cash_sessions')->where('id', $sessionId)->update(['active_key' => null, 'closed_at' => now(),
                        'expected_cents' => $expected, 'counted_cents' => $counted, 'difference_cents' => $counted - $expected, 'notes' => $data['notes'] ?? null]);
                }
            }
            $result = ['operationId' => $id, 'status' => 'confirmed', 'sessionId' => $sessionId,
                'session' => $this->summary(DB::table('cash_sessions')->where('id', $sessionId)->first())];
            DB::table('cash_requests')->insert(['id' => $id, 'user_id' => $user, 'branch_id' => $branch,
                'request_hash' => $hash, 'result' => json_encode($result, JSON_THROW_ON_ERROR), 'created_at' => now()]);

            return $result;
        }, 3);
    }
}
