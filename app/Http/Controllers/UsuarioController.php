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
                $empresa = Empresa::where('id',  $sucursal->id_empresa)->first();
                $sucursal->nombre = $empresa->nombre . ' - ' . $sucursal->nombre;
            }

            $empresas = Empresa::get();
        } else {
            // Para admin empresa: solo sucursales de su empresa
            $sucursalUser = Sucursales::where('id',  SucursalService::getSucursalActiva())->first();
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
            'password.required'  => 'Agregar una contraseña.',
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

        $data = [];
        if ($request->password !== null) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->tipo === 'adminEmpresa') {
            $primeraSucursal = Sucursales::where('id_empresa', $request->id_empresa)->first();
            $data['id_sucursal'] = $primeraSucursal ? $primeraSucursal->id : null;
        } else {
            $data['id_empresa'] = Sucursales::where('id', $request->id_sucursal)->first()->id_empresa;
        }

        $request->merge($data);

        User::create($request->all());
        return redirect()->route('usuario.index');
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
                $empresa = Empresa::where('id',  $sucursal->id_empresa)->first();
                $sucursal->nombre = $empresa->nombre . ' - ' . $sucursal->nombre;
            }

            $empresas = Empresa::get();
        } else {
            // Para admin empresa: solo sucursales de su empresa
            $sucursalUser = Sucursales::where('id',  SucursalService::getSucursalActiva())->first();
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

        $usuario = User::find($request->id);
        $usuario->name = $request->name;
        if ($request->password !== null) {
            $usuario->password = Hash::make($request->password);
        }
        $usuario->email = $request->email;
        $usuario->tipo = $request->tipo;
        $usuario->id_sucursal = $request->id_sucursal;
        $usuario->save();
        return redirect()->route('usuario.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $usuario)
    {
        $usuario->delete(); // Soft delete
        return redirect()->route('usuario.index');
    }

    public function restore($id)
    {
        $usuario = User::onlyTrashed()->findOrFail($id);
        $usuario->restore();
        return redirect()->route('usuario.index');
    }
}
