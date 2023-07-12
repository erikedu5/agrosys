<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
        $usuarios = User::where('name', 'LIKE', "%$request->q%")
        ->latest()
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
        return Inertia::render('Usuario/CreateUsuario');
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

        User::create($request->all());
        return redirect()->route('usuario.index');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit( $id)
    {
        $usuario = User::find($id);
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
