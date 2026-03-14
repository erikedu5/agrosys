<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Rules\Password;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with([
            'sucursal' => function ($relation) {
                $relation->withTrashed()->select('id', 'nombre');
            },
            'empresa' => function ($relation) {
                $relation->withTrashed()->select('id', 'nombre');
            }
        ])
            ->where('name', 'LIKE', "%$request->q%");

        if (Auth::user()->tipo !== 'superAdmin') {
            $sucursal = SucursalService::getSucursalActiva();
            $query = $query->where('id_sucursal', $sucursal);
        }

        if ($request->boolean('deleted')) {
            $query->onlyTrashed();
        }

        $usuarios = $query->latest()
            ->paginate(10);

        return Inertia::render('Usuario/Usuario', [
            'usuarios' => $usuarios,
            'showDeleted' => $request->boolean('deleted'),
            'isSuperAdmin' => Auth::user()->tipo === 'superAdmin',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sucursales = [];
        $empresas = [];
        $isSuperAdmin = Auth::user()->tipo == 'superAdmin';

        if ($isSuperAdmin) {
            // Para superAdmin: todas las sucursales y todas las empresas
            $sucursales = Sucursales::get();
            foreach ($sucursales as $sucursal) {
                $empresa = Empresa::where('id', $sucursal->id_empresa)->first();
                $sucursal->nombre = $empresa->nombre . ' - ' . $sucursal->nombre;
            }

            $empresas = Empresa::get();
        } else {
            // Para admin empresa: solo sucursales de su empresa
            $sucursalUser = Sucursales::where('id', SucursalService::getSucursalActiva())->first();
            $sucursales = Sucursales::where('id_empresa', $sucursalUser->id_empresa)->get();
        }

        return Inertia::render('Usuario/CreateUsuario', [
            'sucursales' => $sucursales,
            'empresas' => $empresas,
            'isSuperAdmin' => $isSuperAdmin
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validation = [
            'name' => 'required',
            'email' => 'required|email',
            'tipo' => 'required',
            'password' => 'required',
        ];

        $messages = [
            'name.required' => 'Agregar un nombre de usuario.',
            'email.required' => 'Agregar un correo electrónico.',
            'tipo.required' => 'Agregar un Rol.',
            'password.required' => 'Agregar una contraseña.',
        ];

        // Para adminEmpresa validar empresa, para otros validar sucursal
        if ($request->tipo === 'adminEmpresa') {
            $validation['id_empresa'] = 'required';
            $messages['id_empresa.required'] = 'Seleccionar una empresa.';
        } else {
            $validation['id_sucursal'] = 'required';
            $messages['id_sucursal.required'] = 'Seleccionar una sucursal.';
        }

        $request->validate($validation, $messages);

        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('CREAR_USUARIO', [
            'name' => $request->name,
            'email' => $request->email,
            'tipo' => $request->tipo,
        ]);

        // Enforcement: limite de "dispositivos" por sucursal (modelado como usuarios activos por sucursal).
        if (!in_array($request->tipo, ['adminEmpresa', 'superAdmin'], true) && $request->filled('id_sucursal')) {
            $sucursal = Sucursales::find($request->id_sucursal);
            $empresa = $sucursal ? Empresa::find($sucursal->id_empresa) : null;
            $limit = $empresa?->numero_dispositivos_por_sucursal;

            if ($empresa && $empresa->plan_code && $limit) {
                $actual = User::query()
                    ->where('id_sucursal', $sucursal->id)
                    ->whereNotIn('tipo', ['adminEmpresa', 'superAdmin'])
                    ->count();

                if ($actual >= (int) $limit) {
                    $logger->warning('Limite de dispositivos alcanzado', [
                        'empresa_id' => $empresa->id,
                        'sucursal_id' => $sucursal->id,
                        'max' => (int) $limit,
                        'actual' => $actual,
                    ]);
                    return back()->with('error', 'Limite de dispositivos alcanzado para tu plan. Actualiza tu suscripcion para agregar mas.');
                }
            }
        }

        try {
            $data = [];
            if ($request->password !== null) {
                $data['password'] = Hash::make($request->password);
            }

            if ($request->tipo === 'adminEmpresa') {
                $primeraSucursal = Sucursales::where('id_empresa', $request->id_empresa)->first();
                $data['id_sucursal'] = $primeraSucursal ? $primeraSucursal->id : null;
                $data['id_empresa'] = $request->id_empresa;

                $logger->step('AdminEmpresa - sucursal asignada', [
                    'empresa_id' => $request->id_empresa,
                    'sucursal_id' => $data['id_sucursal'],
                ]);
            } else {
                $data['id_empresa'] = Sucursales::where('id', $request->id_sucursal)->first()->id_empresa;
                $data['id_sucursal'] = $request->id_sucursal;

                $logger->step('Usuario regular - empresa obtenida', [
                    'sucursal_id' => $request->id_sucursal,
                    'empresa_id' => $data['id_empresa'],
                ]);
            }

            $request->merge($data);

            $usuario = User::create($request->all());

            $logger->success([
                'usuario_id' => $usuario->id,
                'name' => $usuario->name,
                'tipo' => $usuario->tipo,
            ]);

            return redirect()->route('usuario.index');
        } catch (\Exception $e) {
            $logger->error($e, [
                'name' => $request->name,
                'email' => $request->email,
            ]);

            return back()->with('error', 'Error al crear usuario: ' . $e->getMessage());
        }
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $sucursales = [];
        $empresas = [];
        $isSuperAdmin = Auth::user()->tipo == 'superAdmin';

        if ($isSuperAdmin) {
            // Para superAdmin: todas las sucursales y todas las empresas
            $sucursales = Sucursales::get();
            foreach ($sucursales as $sucursal) {
                $empresa = Empresa::where('id', $sucursal->id_empresa)->first();
                $sucursal->nombre = $empresa->nombre . ' - ' . $sucursal->nombre;
            }

            $empresas = Empresa::get();
        } else {
            // Para admin empresa: solo sucursales de su empresa
            $sucursalUser = Sucursales::where('id', SucursalService::getSucursalActiva())->first();
            $sucursales = Sucursales::where('id_empresa', $sucursalUser->id_empresa)->get();
        }

        $usuario = User::find($id);
        return Inertia::render('Usuario/CreateUsuario', [
            'usuario' => $usuario,
            'sucursales' => $sucursales,
            'empresas' => $empresas,
            'isSuperAdmin' => $isSuperAdmin
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'tipo' => 'required'
        ], [
            'name.required' => 'Agregar un nombre de usuario.',
            'email.required' => 'Agregar un correo electrónico.',
            'tipo.required' => 'Agregar un Rol.',
        ]);

        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('ACTUALIZAR_USUARIO', [
            'usuario_id' => $request->id,
            'name' => $request->name,
            'email' => $request->email,
        ]);

        try {
            $usuario = User::find($request->id);

            if (!$usuario) {
                $logger->warning('Usuario no encontrado', [
                    'usuario_id' => $request->id,
                ]);
                return redirect()->route('usuario.index')->with('error', 'Usuario no encontrado.');
            }

            $logger->step('Valores anteriores', [
                'name' => $usuario->name,
                'email' => $usuario->email,
                'tipo' => $usuario->tipo,
                'sucursal_id' => $usuario->id_sucursal,
            ]);

            $usuario->name = $request->name;
            if ($request->password !== null) {
                $usuario->password = Hash::make($request->password);
                $logger->step('Contraseña actualizada');
            }
            $usuario->email = $request->email;
            $usuario->tipo = $request->tipo;
            $usuario->id_sucursal = $request->id_sucursal;
            $usuario->save();

            $logger->success([
                'usuario_id' => $usuario->id,
                'name' => $usuario->name,
                'tipo' => $usuario->tipo,
            ]);

            return redirect()->route('usuario.index');
        } catch (\Exception $e) {
            $logger->error($e, [
                'usuario_id' => $request->id,
            ]);

            return back()->with('error', 'Error al actualizar usuario: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $usuario)
    {
        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('ELIMINAR_USUARIO', [
            'usuario_id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
        ]);

        try {
            $usuario->delete(); // Soft delete

            $logger->success([
                'usuario_id' => $usuario->id,
                'operacion' => 'soft_delete',
            ]);

            return redirect()->route('usuario.index');
        } catch (\Exception $e) {
            $logger->error($e, [
                'usuario_id' => $usuario->id,
            ]);

            return back()->with('error', 'Error al eliminar usuario: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('RESTAURAR_USUARIO', [
            'usuario_id' => $id,
        ]);

        try {
            $usuario = User::onlyTrashed()->findOrFail($id);

            $logger->step('Usuario encontrado', [
                'name' => $usuario->name,
                'email' => $usuario->email,
            ]);

            $usuario->restore();

            $logger->success([
                'usuario_id' => $usuario->id,
                'name' => $usuario->name,
            ]);

            return redirect()->route('usuario.index');
        } catch (\Exception $e) {
            $logger->error($e, [
                'usuario_id' => $id,
            ]);

            return back()->with('error', 'Error al restaurar usuario: ' . $e->getMessage());
        }
    }
}
