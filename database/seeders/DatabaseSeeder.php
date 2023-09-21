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
        Empresa::factory(1)->create();

        Sucursales::factory(2)->create();

        User::factory()->create([
            'name' => 'Erik Jimenez',
            'email' => 'erikedu5@gmail.com',
            'password' => bcrypt('123456789'),
            'tipo' => 'admin',
            'id_sucursal' => 1

       ]);


       User::factory(9)->create();
       CatClasificacion::factory(10)->create();
       CatMarca::factory(10)->create();
       CatEnfermedades::factory(10)->create();
       CatTipoFlor::factory(10)->create();
       EnfermedadesTipoFlor::factory(10)->create();
       Producto::factory(10)->create();
       SolucionEnfermedad::factory(10)->create();
       Clientes::factory(10)->create();
       Venta::factory(10)->create();
       ProductoVenta::factory(10)->create();
       AltaInventario::factory(10)->create();
    }
}
