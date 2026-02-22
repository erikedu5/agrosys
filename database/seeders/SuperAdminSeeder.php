<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('SUPERADMIN_EMAIL', 'superadmin@agrosys.local');
        $name = (string) env('SUPERADMIN_NAME', 'Super Administrador');
        $password = (string) env('SUPERADMIN_PASSWORD', 'Admin1234.');

        $empresaId = Empresa::query()->value('id');
        $sucursalId = Sucursales::query()->value('id');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'tipo' => 'superAdmin',
                'id_empresa' => $empresaId,
                'id_sucursal' => $sucursalId,
            ]
        );
    }
}
