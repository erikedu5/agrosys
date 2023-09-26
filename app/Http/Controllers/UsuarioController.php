<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
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
        $query = User::where('name', 'LIKE', "%$request->q%");

        if (Auth::user()->tipo !== 'superAdmin') {
           $usuarios = $query->where('id_sucursal',  Auth::user()->id_sucursal);
        }

        $usuarios = $query->latest()
        ->paginate(10);

        return Inertia::render('Usuario/Usuario', [
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sucursales = [];
        if (Auth::user()->tipo == 'superAdmin') {
            $sucursales = Sucursales::get();
            foreach ($sucursales as $sucursal) {
                $empresa = Empresa::where('id',  $sucursal->id_empresa)->first();
                $sucursal->nombre = $empresa->nombre. ' - '.$sucursal->nombre ;
            }
        } else {
            $sucursalUser = Sucursales::where('id',  Auth::user()->id_sucursal)->first();
            $sucursales = Sucursales::where('id_empresa', $sucursalUser->id_empresa)->get();

        }

        return Inertia::render('Usuario/CreateUsuario', [
            'sucursales' => $sucursales
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'tipo' => 'required',
            'password' => 'required',
        ]);

        if ($request->password !== null) {
            $data['password'] = Hash::make($request->password);
        }

        $request->merge($data);

        User::create($request->all());
        return redirect()->route('usuario.index');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit( $id)
    {
        $usuario = User::find($id);
        dd($usuario);
        return Inertia::render('Usuario/CreateUsuario', compact('usuario'));
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
        $usuario->delete();
        return redirect()->route('usuario.index');
    }
}
