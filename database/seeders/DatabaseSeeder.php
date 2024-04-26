<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatEnfermedades;
use App\Models\CatMarca;
use App\Models\CatTipoFlor;
use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\EnfermedadesTipoFlor;
use App\Models\Producto;
use App\Models\ProductoVenta;
use App\Models\SolucionEnfermedad;
use App\Models\Sucursales;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Empresa::factory()->create([
            'nombre' => 'MeztliTech',
            'direccion' => 'Calle dos de marzo s/n, San Lucas, Villa Guerrero.',
            'telefono' => '7228259581',
            'email' => 'meztlitechsolutions@gmail.com',
            'rfc' => 'JIDE930407AS4',
            'aviso' => 'Si tiene algun requerimiento contactenos por whatsapp'
        ]);

        Sucursales::factory()->create([
            'nombre' => 'Matriz',
            'direccion' => 'Calle dos de marzo s/n, San Lucas, Villa Guerrero.',
            'telefono' => '7228259581',
            'email' => 'meztlitechsolutions@gmail.com',
            'es_matriz' => true,
            'id_empresa' => 1
        ]);

        User::factory()->create([
            'name' => 'Erik Jimenez',
            'email' => 'erikedu5@gmail.com',
            'password' => bcrypt('123456789'),
            'tipo' => 'superAdmin',
            'id_sucursal' => 1

       ]);
    }
}
