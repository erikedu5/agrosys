<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Support\SubscriptionPlans;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmpresaController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        abort_unless(Auth::user()?->tipo === 'superAdmin', 403);
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Empresa::query()
            ->where('nombre', 'LIKE', "%$request->q%");
        
        Log::info(Auth::user());

        if (Auth::user()->tipo === 'adminEmpresa') {
            $query->where('id', Auth::user()->id_empresa);
        }

        if ($request->boolean('deleted')) {
            $query->onlyTrashed();
        }

        $empresas = $query->latest()->paginate(10);

        return Inertia::render('Empresa/Empresa', [
            'empresas' => $empresas,
            'all' => Auth::user()->tipo === 'adminEmpresa',
            'isSuperAdmin' => Auth::user()->tipo === 'superAdmin',
            'showDeleted' => $request->boolean('deleted'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Empresa/CrearEmpresa', [
            'isSuperAdmin' => Auth::user()->tipo === 'superAdmin',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required',
            'direccion' => 'required',
            'mostrar_campos_precio' => 'nullable|boolean',
            'enviar_facturas_automaticas' => 'nullable|boolean',
            'ventas_bloqueadas' => 'nullable|boolean',
            'motivo_bloqueo' => 'nullable|string|max:500',
        ],[
            'nombre.required' => 'Agregar un nombre de empresa.',
            'direccion.required' => 'Agregar una dirección de la empresa.',
            'motivo_bloqueo.max' => 'El motivo de bloqueo debe tener máximo 500 caracteres.',
        ]);

        $empresa = new Empresa();
        $empresa->nombre = $validated['nombre'];
        $empresa->direccion = $validated['direccion'];
        $empresa->telefono = $request->telefono;
        $empresa->email = $request->email;
        $empresa->rfc = $request->rfc;
        $empresa->aviso = $request->aviso;
        $empresa->mostrar_campos_precio = $request->boolean('mostrar_campos_precio', true);
        if (in_array(Auth::user()->tipo, ['superAdmin', 'adminEmpresa'])) {
            $empresa->enviar_facturas_automaticas = $request->boolean('enviar_facturas_automaticas', false);
        } else {
            $empresa->enviar_facturas_automaticas = false;
        }
        if (Auth::user()->tipo === 'superAdmin') {
            $empresa->ventas_bloqueadas = $request->boolean('ventas_bloqueadas', false);
            $motivoBloqueo = trim((string) $request->input('motivo_bloqueo', ''));
            $empresa->motivo_bloqueo = $empresa->ventas_bloqueadas
                ? ($motivoBloqueo !== '' ? $motivoBloqueo : 'Esta sección está bloqueada, Contacte a su administrador.')
                : null;
        } else {
            $empresa->ventas_bloqueadas = false;
            $empresa->motivo_bloqueo = null;
        }

        if (Auth::user()->tipo === 'superAdmin') {
            $empresa->numero_sucursales = $request->numero_sucursales ?? 1;
        } else {
            $empresa->numero_sucursales = 1;
        }

        $empresa->save();

        return redirect()->route('empresa.index');
    }

     /**
     * Show the form for edit a resource.
     */
    public function edit(Empresa $empresa)
    {
        $empresa = Empresa::where('id', $empresa->id)->first();
        return Inertia::render('Empresa/CrearEmpresa',
        [
            'empresa' => $empresa,
            'all' => Auth::user()->tipo === 'adminEmpresa',
            'isSuperAdmin' => Auth::user()->tipo === 'superAdmin',
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required',
            'direccion' => 'required',
            'mostrar_campos_precio' => 'nullable|boolean',
            'enviar_facturas_automaticas' => 'nullable|boolean',
            'ventas_bloqueadas' => 'nullable|boolean',
            'motivo_bloqueo' => 'nullable|string|max:500',
        ],[
            'nombre.required' => 'Agregar un nombre de empresa.',
            'direccion.required' => 'Agregar una dirección de la empresa.',
            'motivo_bloqueo.max' => 'El motivo de bloqueo debe tener máximo 500 caracteres.',
        ]);

        $empresa = Empresa::find($request->id);
        $empresa->nombre = $validated['nombre'];
        $empresa->direccion = $validated['direccion'];
        $empresa->telefono = $request->telefono;
        $empresa->email= $request->email;
        $empresa->rfc= $request->rfc;
        $empresa->aviso = $request->aviso;
        $empresa->mostrar_campos_precio = $request->boolean('mostrar_campos_precio', true);
        if (in_array(Auth::user()->tipo, ['superAdmin', 'adminEmpresa'])) {
            $empresa->enviar_facturas_automaticas = $request->boolean('enviar_facturas_automaticas', false);
        }
        if (Auth::user()->tipo === 'superAdmin') {
            $empresa->ventas_bloqueadas = $request->boolean('ventas_bloqueadas', false);
            $motivoBloqueo = trim((string) $request->input('motivo_bloqueo', ''));
            $empresa->motivo_bloqueo = $empresa->ventas_bloqueadas
                ? ($motivoBloqueo !== '' ? $motivoBloqueo : 'Esta sección está bloqueada, Contacte a su administrador.')
                : null;
        }
        if (Auth::user()->tipo === 'superAdmin') {
            $empresa->numero_sucursales = $request->numero_sucursales;
        }
        $empresa->save();

        return redirect()->route('empresa.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Empresa $empresa)
    {
        $empresa->delete(); // Soft delete
        return redirect()->route('empresa.index');
    }

    public function restore($id)
    {
        $empresa = Empresa::onlyTrashed()->findOrFail($id);
        $empresa->restore();
        return redirect()->route('empresa.index');
    }

    public function subscriptions(Request $request)
    {
        $this->ensureSuperAdmin();

        $q = trim((string) $request->input('q', ''));
        $availablePlans = collect(SubscriptionPlans::all())->values();
        $planNames = $availablePlans->mapWithKeys(function (array $plan) {
            return [(string) $plan['code'] => (string) $plan['name']];
        });

        $empresas = Empresa::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nombre', 'LIKE', "%{$q}%");
            })
            ->with(['subscriptions' => function ($query) {
                $query->where('type', 'default')->latest();
            }])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $empresas->through(function (Empresa $empresa) use ($planNames) {
            $subscription = $empresa->subscriptions->first();
            $hasStripe = $subscription ? $subscription->active() : false;
            $hasManual = $empresa->hasActiveManualSubscription();

            $status = 'Sin suscripcion';
            if ($empresa->manual_subscription_blocked) {
                $status = 'Bloqueada manualmente';
            } elseif ($hasManual && $hasStripe) {
                $status = 'Suscripcion hibrida activa';
            } elseif ($hasManual) {
                $status = 'Suscripcion manual activa';
            } elseif ($empresa->hasManualSubscriptionWindow()) {
                $status = 'Suscripcion manual vencida';
            } elseif ($hasStripe) {
                $status = 'Suscripcion Stripe activa';
            } elseif ($empresa->onTrial()) {
                $status = 'En periodo de prueba';
            }

            return [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
                'plan_code' => $empresa->plan_code,
                'plan_name' => $empresa->plan_code ? ($planNames[$empresa->plan_code] ?? $empresa->plan_code) : null,
                'plan_cycle' => $empresa->plan_cycle,
                'manual_subscription_starts_at' => optional($empresa->manual_subscription_starts_at)->format('Y-m-d\TH:i'),
                'manual_subscription_ends_at' => optional($empresa->manual_subscription_ends_at)->format('Y-m-d\TH:i'),
                'manual_subscription_blocked' => (bool) $empresa->manual_subscription_blocked,
                'stripe_active' => $hasStripe,
                'trial_ends_at' => optional($empresa->trial_ends_at)->toIso8601String(),
                'can_access' => $empresa->canAccessApp(),
                'status' => $status,
            ];
        });

        return Inertia::render('Empresa/Suscripciones', [
            'empresas' => $empresas,
            'filters' => [
                'q' => $q,
            ],
            'plans' => $availablePlans->map(function (array $plan) {
                return [
                    'code' => $plan['code'],
                    'name' => $plan['name'],
                    'has_yearly' => !empty($plan['prices']['yearly_mxn']),
                ];
            })->values(),
        ]);
    }

    public function updateSubscription(Request $request, Empresa $empresa)
    {
        $this->ensureSuperAdmin();

        $planCodes = array_keys(SubscriptionPlans::all());

        $data = $request->validate([
            'manual_subscription_starts_at' => ['nullable', 'date'],
            'manual_subscription_ends_at' => ['nullable', 'date', 'after_or_equal:manual_subscription_starts_at'],
            'manual_subscription_blocked' => ['required', 'boolean'],
            'clear_manual_dates' => ['nullable', 'boolean'],
            'plan_code' => ['nullable', Rule::in($planCodes)],
            'plan_cycle' => ['nullable', Rule::in(['monthly', 'yearly'])],
        ], [
            'manual_subscription_ends_at.after_or_equal' => 'La fecha de vencimiento debe ser mayor o igual a la fecha de inicio.',
        ]);

        $clearDates = (bool) ($data['clear_manual_dates'] ?? false);

        $startsAt = $clearDates || empty($data['manual_subscription_starts_at'])
            ? null
            : Carbon::parse($data['manual_subscription_starts_at']);

        $endsAt = $clearDates || empty($data['manual_subscription_ends_at'])
            ? null
            : Carbon::parse($data['manual_subscription_ends_at']);

        if (!$clearDates && $startsAt && $startsAt->isFuture()) {
            return back()->withErrors([
                'manual_subscription_starts_at' => 'La activacion manual solo aplica para pagos previos a hoy.',
            ])->withInput();
        }

        $planCode = $data['plan_code'] ?? null;
        $planCycle = $planCode ? ($data['plan_cycle'] ?? 'monthly') : null;

        $payload = [
            'manual_subscription_starts_at' => $startsAt,
            'manual_subscription_ends_at' => $endsAt,
            'manual_subscription_blocked' => (bool) $data['manual_subscription_blocked'],
        ];

        if ($planCode) {
            $plan = SubscriptionPlans::find($planCode);

            if ($planCycle === 'yearly' && empty($plan['prices']['yearly_mxn'])) {
                return back()->withErrors([
                    'plan_cycle' => 'El plan seleccionado no tiene ciclo anual.',
                ])->withInput();
            }

            $payload['plan_code'] = $planCode;
            $payload['plan_cycle'] = $planCycle;
            $payload['numero_sucursales'] = (int) ($plan['limits']['max_sucursales'] ?? $empresa->numero_sucursales);
            $payload['numero_dispositivos_por_sucursal'] = (int) ($plan['limits']['devices_per_sucursal'] ?? $empresa->numero_dispositivos_por_sucursal);
        } else {
            $payload['plan_code'] = null;
            $payload['plan_cycle'] = null;
        }

        $empresa->forceFill($payload)->save();

        return back()->with('success', 'Configuracion de suscripcion manual actualizada.');
    }
}
