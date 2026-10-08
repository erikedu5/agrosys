<?php

namespace Tests\Feature;

use App\Models\AbonoCuenta;
use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatMarca;
use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Sucursales;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class NativePosConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }
        if (config('database.connections.mysql.database') !== 'agrosys_pos_test' || (string) config('database.connections.mysql.port') !== '33079') {
            throw new \RuntimeException('Concurrency tests require isolated agrosys_pos_test on port 33079.');
        }
        $this->artisan('migrate:fresh')->assertExitCode(0);
        // Historical product migrations have a MySQL-only down() defect.
        // Wipe only this explicitly isolated test schema instead of rolling them back.
        $this->beforeApplicationDestroyed(function () {
            $this->artisan('db:wipe')->assertExitCode(0);
            \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = false;
        });
    }

    private User $user;

    private Sucursales $branch;

    private Clientes $customer;

    private Producto $product;

    private string $device;

    private string $token;

    private array $lease;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDriverName() != 'mysql') {
            $this->markTestSkipped('Requires isolated MySQL (see backend-phase-1.md).');
        }
        config(['pos.enabled' => true, 'offline.require_activation' => false]);
        $company = Empresa::factory()->create();
        $this->branch = Sucursales::factory()->create(['id_empresa' => $company->id]);
        $this->user = User::factory()->create(['tipo' => 'vendedor', 'id_empresa' => $company->id, 'id_sucursal' => $this->branch->id, 'password' => Hash::make('test-password')]);
        $this->customer = Clientes::factory()->create(['id_sucursal' => $this->branch->id, 'activo' => true, 'porcentaje_descuento' => 0]);
        $this->branch->update(['id_cliente_publico' => $this->customer->id]);
        $this->product = Producto::factory()->create(['id_empresa' => $company->id, 'id_usuario' => $this->user->id, 'id_marca' => CatMarca::factory()->create()->id, 'id_clasificacion' => CatClasificacion::factory()->create()->id, 'tamano' => '1L', 'precio_unitario' => 50, 'precio_ieps' => 50]);
        AltaInventario::create(['id_producto' => $this->product->id, 'id_sucursal' => $this->branch->id, 'id_usuario' => $this->user->id, 'cantidad_actual' => 10, 'cantidad_nueva' => 10]);
        $this->device = (string) Str::uuid();
    }

    private function credentials(): array
    {
        return ['email' => $this->user->email, 'password' => 'test-password', 'device_id' => $this->device, 'device_name' => 'Test POS'];
    }

    private function login(): void
    {
        Auth::forgetGuards();
        $this->token = $this->postJson('/api/v1/pos/auth/login', $this->credentials())->assertOk()->json('token');
    }

    private function api(string $method, string $path, array $data = [])
    {
        Auth::forgetGuards();

        return $this->json($method, '/api/v1/pos/'.$path, $data, ['Authorization' => 'Bearer '.$this->token]);
    }

    private function context(): array
    {
        return ['device_id' => $this->device, 'branch_id' => (string) $this->branch->id];
    }

    private function activate(): void
    {
        $this->login();
        $this->lease = $this->api('POST', 'devices/activate', $this->context())->assertOk()->json('offlineLease');
    }

    private function operation(string $type = 'Contado', string $payment = '100.00', int $sequence = 1): array
    {
        return ['operation_id' => (string) Str::uuid(), 'aggregate_id' => (string) Str::uuid(), 'aggregate_type' => 'sale', 'event_type' => 'SALE_COMPLETED', 'sequence' => $sequence, 'occurred_at' => now()->toIso8601String(), 'offline_lease_id' => $this->lease['claims']['leaseId'], 'payload' => ['customerId' => (string) $this->customer->id, 'saleType' => $type, 'total' => '100.00', 'localFolio' => 'LOCAL-'.$sequence, 'items' => [['productId' => (string) $this->product->id, 'quantity' => '2.00', 'unitPrice' => '50.00', 'total' => '100.00']], 'payments' => [['method' => 'cash', 'amount' => $payment]]]];
    }

    private function push(array $operations)
    {
        return $this->api('POST', 'sync/push', [...$this->context(), 'schemaVersion' => 2, 'operations' => $operations]);
    }

    public function test_concurrent_replay_and_sequence_are_atomic_in_mysql(): void
    {
        $this->activate();
        $operation = $this->operation('Credito', '30.00');
        $results = $this->race([$operation, $operation]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $this->assertSame(['confirmed', 'duplicate'], $statuses);
        $this->assertSame(1, Venta::count());
        $this->assertSame(1, AbonoCuenta::count());
        $this->assertEquals(70, $this->customer->fresh()->balance);
        $this->assertEquals(8, AltaInventario::latest('id')->first()->cantidad_nueva);

        $a = $this->operation('Credito', '0.00', 2);
        $b = $this->operation('Credito', '0.00', 2);
        $results = $this->race([$a, $b]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $this->assertSame(['confirmed', 'rejected'], $statuses);
        $this->assertSame(2, Venta::count());
        $this->assertEquals(170, $this->customer->fresh()->balance);
        $this->assertEquals(6, AltaInventario::latest('id')->first()->cantidad_nueva);

        $changed = $operation;
        $changed['payload']['payments'][0]['amount'] = '20.00';
        $results = $this->race([$operation, $changed]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $this->assertSame(['conflict', 'duplicate'], $statuses);
        $this->assertSame(2, Venta::count());
    }

    private function race(array $operations): array
    {
        $start = microtime(true) + 0.5;
        $processes = [];
        $connection = config('database.connections.mysql');
        foreach ($operations as $operation) {
            $process = new \Symfony\Component\Process\Process([PHP_BINARY, base_path('tests/Support/pos_concurrent_request.php')], base_path(), [
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $connection['database'],
                'DB_HOST' => $connection['host'], 'DB_PORT' => (string) $connection['port'],
                'DB_USERNAME' => $connection['username'], 'DB_PASSWORD' => $connection['password'],
                'POS_NATIVE_ENABLED' => 'true', 'POS_RACE_START' => (string) $start,
                'POS_RACE_TOKEN' => $this->token,
                'POS_RACE_BODY' => json_encode([...$this->context(), 'schemaVersion' => 2, 'operations' => [$operation]], JSON_THROW_ON_ERROR),
            ]);
            $process->setTimeout(20)->start();
            $processes[] = $process;
        }

        return array_map(function ($process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
            $response = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(200, $response['httpStatus'], json_encode($response));

            return $response['body']['results'][0];
        }, $processes);
    }
}
