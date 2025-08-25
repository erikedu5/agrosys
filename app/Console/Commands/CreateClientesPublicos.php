<?php

namespace App\Console\Commands;

use App\Models\Clientes;
use App\Models\Sucursales;
use Illuminate\Console\Command;

class CreateClientesPublicos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sucursales:create-clientes-publicos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear clientes "Público en general" para todas las sucursales existentes que no los tengan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando la creación de clientes públicos para sucursales...');

        $sucursales = Sucursales::whereNull('id_cliente_publico')->get();

        if ($sucursales->count() === 0) {
            $this->info('Todas las sucursales ya tienen clientes públicos asignados.');
            return;
        }

        $this->info("Se encontraron {$sucursales->count()} sucursales sin cliente público.");

        foreach ($sucursales as $sucursal) {
            // Crear cliente "Público en general" para la sucursal
            $clientePublico = Clientes::create([
                'nombre' => 'Público en general',
                'porcentaje_descuento' => 0,
                'adeudo_total' => 0,
                'abono_total' => 0,
                'balance' => 0,
                'requiereFactura' => false,
                'activo' => true,
                'rfc' => null,
                'id_sucursal' => $sucursal->id,
            ]);

            // Actualizar la sucursal con el ID del cliente público
            $sucursal->update(['id_cliente_publico' => $clientePublico->id]);

            $this->info("✓ Cliente público creado para sucursal: {$sucursal->nombre} (ID: {$sucursal->id})");
        }

        $this->info('¡Proceso completado exitosamente!');
    }
}
