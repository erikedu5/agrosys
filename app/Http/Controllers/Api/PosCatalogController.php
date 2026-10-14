<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Pos\CatalogSnapshots;
use App\Services\Pos\PosAccess;
use Illuminate\Http\Request;

class PosCatalogController extends Controller
{
    public function bootstrap(Request $request, PosAccess $access, CatalogSnapshots $snapshots)
    {
        return $this->download($request, $access, $snapshots, false);
    }

    public function pull(Request $request, PosAccess $access, CatalogSnapshots $snapshots)
    {
        return $this->download($request, $access, $snapshots, true);
    }

    private function download(Request $request, PosAccess $access, CatalogSnapshots $snapshots, bool $incremental)
    {
        $data = $request->validate(['device_id' => 'required|uuid', 'branch_id' => 'required|integer|min:1', 'cursor' => 'nullable|string|max:4096', 'page_token' => 'nullable|string|max:4096', 'page_size' => 'sometimes|integer|min:1|max:500', 'known_operation_ids' => 'sometimes|array|max:2000', 'known_operation_ids.*' => 'required|uuid|distinct']);
        $data['known_operation_ids'] = array_map('strtolower', $data['known_operation_ids'] ?? []);
        $device = $access->device($request, strtolower($data['device_id']), $data['branch_id']);

        return response()->json($snapshots->page($request->user(), $device, $data, $incremental));
    }
}
