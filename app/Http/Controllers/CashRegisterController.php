<?php

namespace App\Http\Controllers;

use App\Services\CashRegister;
use App\Services\Pos\PosAccess;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CashRegisterController extends Controller
{
    private function branch(Request $request, PosAccess $access): int
    {
        if ($request->is('api/v1/pos/*')) {
            $data = $request->validate(['device_id' => 'required|uuid', 'branch_id' => 'required|integer|min:1']);
            $access->device($request, strtolower($data['device_id']), $data['branch_id']);
            $branch = $access->branch($request->user(), $data['branch_id']);
        } else {
            $branch = $access->branch($request->user(), (int) SucursalService::getSucursalActiva());
        }
        abort_unless(in_array('cash.manage', $access->permissions($request->user(), $branch), true), 403, 'No tienes permiso para manejar caja.');

        return (int) $branch->id;
    }

    public function index(Request $request, PosAccess $access, CashRegister $cash)
    {
        $branch = $this->branch($request, $access);

        return Inertia::render('Caja/Index', ['cash' => $cash->status($request->user()->id, $branch)]);
    }

    public function status(Request $request, PosAccess $access, CashRegister $cash)
    {
        return response()->json($cash->status($request->user()->id, $this->branch($request, $access)));
    }

    public function write(Request $request, string $action, PosAccess $access, CashRegister $cash)
    {
        $branch = $this->branch($request, $access);
        $money = ['required', 'regex:/^\d{1,8}(?:\.\d{1,2})?$/'];
        $rules = ['operation_id' => 'required|uuid'];
        if ($action === 'open') {
            $rules['opening'] = $money;
        } else {
            $rules['session_id'] = 'required|uuid';
            if ($action === 'movement') {
                $rules += ['kind' => 'required|in:in,out', 'amount' => $money, 'description' => ['required', 'string', 'max:255', 'regex:/\S/u']];
            } else {
                $rules += ['counted' => $money, 'expected' => ['required', 'regex:/^-?\d{1,8}(?:\.\d{1,2})?$/'],
                    'notes' => 'nullable|string|max:2000', 'confirmed' => 'required|accepted'];
            }
        }

        return response()->json($cash->write($request->user()->id, $branch, $action, $request->validate($rules)));
    }
}
