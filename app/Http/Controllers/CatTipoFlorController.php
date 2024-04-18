<?php

namespace App\Http\Controllers;

use App\Models\CatTipoFlor;
use App\Models\CatEnfermedades;
use App\Models\EnfermedadesTipoFlor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CatTipoFlorController extends Controller
{
     /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $catTipoFlores = CatTipoFlor::where('nombre', 'LIKE', "%$request->q%")
        ->latest('created_at')
        ->paginate(10);

        foreach ($catTipoFlores as $tipoFlor) {
            $relacion = EnfermedadesTipoFlor::where('id_tipo_flor', $tipoFlor->id)->get();
            $enfermedadesArray = [];
            foreach ($relacion as $rel) {
                $enfermedades = CatEnfermedades::where('id', $rel->id_enfermedad)->first();
                array_push($enfermedadesArray, $enfermedades->nombre);
            }
            $tipoFlor->enfermedades = implode(', ', $enfermedadesArray);
        }

        return Inertia::render('Catalogos/TipoFlor/TipoFlor', [
            'tipoFlor' => $catTipoFlores
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $enfermedades = CatEnfermedades::get();
        return Inertia::render('Catalogos/TipoFlor/CreateTipoFlor', [
            'enfermedades' => $enfermedades
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'selectedOptions' => 'required'
        ]);

        $tipoFlor = CatTipoFlor::create($request->all());
        foreach($request->selectedOptions as $enfermedadId) {
            $enfermedadTipoFlor = [];
            $enfermedadTipoFlor['id_tipo_flor'] = $tipoFlor->id;
            $enfermedadTipoFlor['id_enfermedad'] = CatEnfermedades::where("nombre",$enfermedadId)->first()->id;
            EnfermedadesTipoFlor::create($enfermedadTipoFlor);
        }
        return redirect()->route('tipoFlor.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CatTipoFlor $tipoFlor)
    {
        $enfermedades = CatEnfermedades::get();
        $relacion = EnfermedadesTipoFlor::where('id_tipo_flor', $tipoFlor->id)->get();
        $id_enfermedades = [];
        foreach ($relacion as $enf) {
            array_push($id_enfermedades, $enf->id_enfermedad);
        }
        $tipoFlor->selectedOptions = $id_enfermedades;
        return Inertia::render('Catalogos/TipoFlor/CreateTipoFlor', [
            'tipoFlor' => $tipoFlor,
            'enfermedades' => $enfermedades,
            'enfermedadesSelected' => $relacion
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'nombre' => 'required'
        ]);

        $catTipoFlor = CatTipoFlor::find($request->id);
        $catTipoFlor->nombre = $request->nombre;
        $catTipoFlor->save();

        $enfermedadTipoFlorObj = new EnfermedadesTipoFlor();
        $enfermedadTipoFlorObj->where('id_tipo_flor', $catTipoFlor->id)->delete();
        foreach($request->selectedOptions as $enfermedadId) {
            $enfermedadTipoFlor = [];
            $enfermedadTipoFlor['id_tipo_flor'] = $catTipoFlor->id;
            $enfermedadTipoFlor['id_enfermedad'] = CatEnfermedades::where("nombre",$enfermedadId)->first()->id;
            $enfermedadTipoFlorObj->create($enfermedadTipoFlor);
        }
        return redirect()->route('tipoFlor.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(String $idTipoflor)
    {
        $catTipoFlor = CatTipoFlor::find($idTipoflor);
        $catTipoFlor->delete();
        return redirect()->route('tipoFlor.index');
    }
}
