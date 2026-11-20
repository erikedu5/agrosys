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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\AuditorAlcance;
use Laravel\Fortify\Rules\Password;

class UsuarioController extends Controller
{
    /** Roles que cada editor puede asignar (validación en servidor; la UI sólo refleja esto). */
    private const ROLES_POR_EDITOR = [
        'superAdmin' => ['superAdmin', 'adminEmpresa', 'admin', 'vendedor', 'inventario', User::TIPO_AUDITOR],
        'adminEmpresa' => ['admin', 'vendedor', 'inventario', User::TIPO_AUDITOR],
        'admin' => ['admin', 'vendedor', 'inventario'],
    ];

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
            $empresaId = SucursalService::getEmpresaIdActiva();
            $esAdminEmpresa = Auth::user()->tipo === 'adminEmpresa';
            $query = $query->where(function ($q) use ($sucursal, $empresaId, $esAdminEmpresa) {
                $q->where('id_sucursal', $sucursal);
                if ($esAdminEmpresa && $empresaId) {
                    $q->orWhere(fn ($w) => $w->where('tipo', User::TIPO_AUDITOR)
                        ->whereHas('alcancesAuditor', fn ($a) => $a->where('id_empresa', $empresaId)));
                }
            });
        }

        if ($request->boolean('deleted')) {
            $query->onlyTrashed();
        }

        $usuarios = $query->latest()
            ->paginate(12);

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
            'isSuperAdmin' => $isSuperAdmin,
            'rolesPermitidos' => $this->rolesPermitidos(),
            'empresasAuditor' => $this->empresasAuditor(),
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
            'tipo' => ['required', Rule::in($this->rolesPermitidos())],
            'password' => 'required',
        ];

        $messages = [
            'name.required' => 'Agregar un nombre de usuario.',
            'email.required' => 'Agregar un correo electrónico.',
            'tipo.required' => 'Agregar un Rol.',
            'tipo.in' => 'No tienes permiso para asignar ese rol.',
            'password.required' => 'Agregar una contraseña.',
        ];

        // Para adminEmpresa validar empresa, para auditor su alcance, para otros validar sucursal
        if ($request->tipo === 'adminEmpresa') {
            $validation['id_empresa'] = 'required';
            $messages['id_empresa.required'] = 'Seleccionar una empresa.';
        } elseif ($request->tipo === User::TIPO_AUDITOR) {
            $validation += $this->reglasAuditor();
            $messages += $this->mensajesAuditor();
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

        $alcances = $request->tipo === User::TIPO_AUDITOR ? $this->normalizarAlcances($request->input('alcances', [])) : [];

        // Enforcement: limite de "dispositivos" por sucursal (modelado como usuarios activos por sucursal).
        if (!in_array($request->tipo, ['adminEmpresa', 'superAdmin', User::TIPO_AUDITOR], true) && $request->filled('id_sucursal')) {
            $sucursal = Sucursales::find($request->id_sucursal);
            $empresa = $sucursal ? Empresa::find($sucursal->id_empresa) : null;
            $limit = $empresa?->numero_dispositivos_por_sucursal;

            if ($empresa && $empresa->plan_code && $limit) {
                $actual = User::query()
                    ->where('id_sucursal', $sucursal->id)
                    ->whereNotIn('tipo', ['adminEmpresa', 'superAdmin', User::TIPO_AUDITOR])
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
            } elseif ($request->tipo === User::TIPO_AUDITOR) {
                // Auditor externo: sin sucursal ni empresa propia; su alcance vive en auditor_alcances.
                $data['id_sucursal'] = null;
                $data['id_empresa'] = null;
                $data['acceso_hasta'] = $request->input('acceso_hasta') ?: null;
            } else {
                $data['id_empresa'] = Sucursales::where('id', $request->id_sucursal)->first()->id_empresa;
                $data['id_sucursal'] = $request->id_sucursal;

                $logger->step('Usuario regular - empresa obtenida', [
                    'sucursal_id' => $request->id_sucursal,
                    'empresa_id' => $data['id_empresa'],
                ]);
            }

            if ($request->tipo !== User::TIPO_AUDITOR) {
                $data['acceso_hasta'] = null;
            }
            $request->merge($data);

            $usuario = DB::transaction(function () use ($request, $alcances) {
                $usuario = User::create($request->all());
                $this->guardarAlcances($usuario, $alcances);

                return $usuario;
            });

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

        $usuario = User::findOrFail($id);
        $usuario->setAttribute('acceso_hasta_fecha', $usuario->acceso_hasta?->format('Y-m-d'));
        $usuario->setAttribute('alcances', $usuario->alcancesAuditor()
            ->when(!$isSuperAdmin, fn ($q) => $q->where('id_empresa', SucursalService::getEmpresaIdActiva()))
            ->get(['id_empresa', 'id_sucursal']));
        return Inertia::render('Usuario/CreateUsuario', [
            'usuario' => $usuario,
            'sucursales' => $sucursales,
            'empresas' => $empresas,
            'isSuperAdmin' => $isSuperAdmin,
            'rolesPermitidos' => $this->rolesPermitidos(),
            'empresasAuditor' => $this->empresasAuditor(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $esAdminEmpresa = $request->tipo === 'adminEmpresa';
        $esAuditor = $request->tipo === User::TIPO_AUDITOR;

        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'tipo' => ['required', Rule::in($this->rolesPermitidos())],
            'id_sucursal' => ($esAdminEmpresa || $esAuditor) ? 'nullable' : 'required',
        ] + ($esAuditor ? $this->reglasAuditor() : []), [
            'name.required' => 'Agregar un nombre de usuario.',
            'email.required' => 'Agregar un correo electrónico.',
            'tipo.required' => 'Agregar un Rol.',
            'tipo.in' => 'No tienes permiso para asignar ese rol.',
            'id_sucursal.required' => 'Seleccionar una sucursal.',
        ] + ($esAuditor ? $this->mensajesAuditor() : []));

        // Iniciar logging del evento
        $logger = \App\Services\EventLogger::start('ACTUALIZAR_USUARIO', [
            'usuario_id' => $id,
            'name' => $request->name,
            'email' => $request->email,
        ]);

        $usuario = User::find($id);

        if (!$usuario) {
            $logger->warning('Usuario no encontrado', [
                'usuario_id' => $id,
            ]);
            return redirect()->route('usuario.index')->with('error', 'Usuario no encontrado.');
        }

        $editorEsSuperAdmin = Auth::user()->tipo === 'superAdmin';

        // Un editor no puede modificar usuarios con un rol que él no puede asignar.
        if (!in_array($usuario->tipo, $this->rolesPermitidos(), true)) {
            abort(403, 'No tienes permiso para editar este usuario.');
        }

        $alcances = [];
        if ($esAuditor) {
            if (!$editorEsSuperAdmin && $usuario->isAuditor()
                && !$usuario->alcancesAuditor()->where('id_empresa', SucursalService::getEmpresaIdActiva())->exists()) {
                abort(403, 'Este auditor no pertenece a tu empresa.');
            }
            $alcances = $this->normalizarAlcances($request->input('alcances', []));
            $empresaId = null;
            $sucursalId = null;
        } elseif ($esAdminEmpresa) {
            // Solo superAdmin puede mover un adminEmpresa de empresa.
            $empresaId = ($editorEsSuperAdmin ? $request->id_empresa : null) ?: $usuario->id_empresa;

            if (!$empresaId) {
                return back()->withErrors(['id_empresa' => 'Seleccionar una empresa.']);
            }

            // Conservar su sucursal si sigue activa y es de la empresa; si no, la primera activa.
            $sucursalId = Sucursales::where('id_empresa', $empresaId)->where('id', $usuario->id_sucursal)->value('id')
                ?? Sucursales::where('id_empresa', $empresaId)->orderByDesc('es_matriz')->orderBy('id')->value('id');
        } else {
            $sucursal = Sucursales::find($request->id_sucursal);

            if (!$sucursal) {
                return back()->withErrors(['id_sucursal' => 'La sucursal seleccionada no existe o esta desactivada.']);
            }

            if (!$editorEsSuperAdmin && (int) $sucursal->id_empresa !== (int) SucursalService::getEmpresaIdActiva()) {
                return back()->withErrors(['id_sucursal' => 'La sucursal seleccionada no pertenece a tu empresa.']);
            }

            $empresaId = $sucursal->id_empresa;
            $sucursalId = $sucursal->id;
        }

        try {
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
            $usuario->id_sucursal = $sucursalId;
            $usuario->id_empresa = $empresaId;
            $usuario->acceso_hasta = $esAuditor ? ($request->input('acceso_hasta') ?: null) : null;
            DB::transaction(function () use ($usuario, $alcances, $esAuditor, $editorEsSuperAdmin) {
                $usuario->save();
                if ($esAuditor) {
                    $this->guardarAlcances($usuario, $alcances, $editorEsSuperAdmin ? null : SucursalService::getEmpresaIdActiva());
                } else {
                    $usuario->alcancesAuditor()->delete();
                }
            });

            $logger->success([
                'usuario_id' => $usuario->id,
                'name' => $usuario->name,
                'tipo' => $usuario->tipo,
            ]);

            return redirect()->route('usuario.index');
        } catch (\Exception $e) {
            $logger->error($e, [
                'usuario_id' => $id,
            ]);

            return back()->with('error', 'Error al actualizar usuario: ' . $e->getMessage());
        }
    }

    private function rolesPermitidos(): array
    {
        return self::ROLES_POR_EDITOR[Auth::user()->tipo] ?? [];
    }

    /** Empresas que el editor puede asignar al alcance de un auditor. */
    private function empresasAuditor()
    {
        $user = Auth::user();
        if ($user->tipo === 'superAdmin') {
            return Empresa::orderBy('nombre')->get(['id', 'nombre']);
        }
        if ($user->tipo === 'adminEmpresa') {
            return Empresa::whereKey(SucursalService::getEmpresaIdActiva())->get(['id', 'nombre']);
        }

        return collect();
    }

    private function reglasAuditor(): array
    {
        return [
            'alcances' => 'required|array|min:1|max:200',
            'alcances.*.id_empresa' => 'required|integer',
            'alcances.*.id_sucursal' => 'nullable|integer',
            'acceso_hasta' => 'nullable|date|after_or_equal:today',
        ];
    }

    private function mensajesAuditor(): array
    {
        return [
            'alcances.required' => 'Asigna al menos una empresa al auditor.',
            'alcances.min' => 'Asigna al menos una empresa al auditor.',
            'acceso_hasta.after_or_equal' => 'La vigencia no puede estar en el pasado.',
        ];
    }

    /**
     * Valida el alcance contra lo que el editor puede asignar. Una empresa completa absorbe
     * sus sucursales puntuales.
     */
    private function normalizarAlcances(array $alcances): array
    {
        $permitidas = $this->empresasAuditor()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $completas = [];
        $puntuales = [];
        foreach ($alcances as $alcance) {
            $empresaId = (int) ($alcance['id_empresa'] ?? 0);
            if (!in_array($empresaId, $permitidas, true)) {
                throw ValidationException::withMessages(['alcances' => 'Sólo puedes asignar empresas dentro de tu administración.']);
            }
            $sucursalId = $alcance['id_sucursal'] ?? null;
            if ($sucursalId === null || $sucursalId === '') {
                $completas[$empresaId] = true;
                continue;
            }
            if (!Sucursales::whereKey((int) $sucursalId)->where('id_empresa', $empresaId)->exists()) {
                throw ValidationException::withMessages(['alcances' => 'La sucursal seleccionada no pertenece a la empresa del alcance.']);
            }
            $puntuales[$empresaId.'-'.$sucursalId] = ['id_empresa' => $empresaId, 'id_sucursal' => (int) $sucursalId];
        }

        $resultado = array_map(fn ($id) => ['id_empresa' => $id, 'id_sucursal' => null], array_keys($completas));
        foreach ($puntuales as $alcance) {
            if (!isset($completas[$alcance['id_empresa']])) {
                $resultado[] = $alcance;
            }
        }

        return $resultado;
    }

    /** Reemplaza el alcance; con $soloEmpresa conserva los alcances de otras empresas (editor adminEmpresa). */
    private function guardarAlcances(User $usuario, array $alcances, ?int $soloEmpresa = null): void
    {
        $usuario->alcancesAuditor()->when($soloEmpresa, fn ($q) => $q->where('id_empresa', $soloEmpresa))->delete();
        foreach ($alcances as $alcance) {
            AuditorAlcance::create($alcance + ['id_usuario' => $usuario->id]);
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
