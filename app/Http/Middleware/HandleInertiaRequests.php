<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Services\SucursalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        $user = Auth::user();
        $sucursalActiva = null;
        $empresaConfig = [
            'mostrar_campos_precio' => true,
            'enviar_facturas_automaticas' => false,
        ];
        $subscription = null;

        if ($user) {
            $sucursalId = SucursalService::getSucursalActiva();
            $sucursalCompleta = SucursalService::getSucursalActivaCompleta();

            if ($sucursalCompleta) {
                $sucursalCompleta->loadMissing('empresa');

                $sucursalActiva = [
                    'id' => $sucursalCompleta->id,
                    'nombre' => $sucursalCompleta->nombre,
                    'direccion' => $sucursalCompleta->direccion,
                    'esAdminEmpresa' => $user->tipo === 'adminEmpresa',
                    'sucursalesDisponibles' => []
                ];

                // Si es admin de empresa, agregar las sucursales disponibles
                if ($user->tipo === 'adminEmpresa') {
                    $sucursalesDisponibles = SucursalService::getSucursalesDisponibles();
                    $sucursalActiva['sucursalesDisponibles'] = $sucursalesDisponibles->map(function ($sucursal) {
                        return [
                            'id' => $sucursal->id,
                            'nombre' => $sucursal->nombre,
                            'direccion' => $sucursal->direccion
                        ];
                    })->toArray();
                }
            }

            $empresa = $user->empresa;
            if (!$empresa && $sucursalCompleta) {
                $empresa = $sucursalCompleta->empresa;
            }

            if (!$empresa && $user->id_empresa) {
                $empresa = Empresa::find($user->id_empresa);
            }

            if (!$empresa && $sucursalCompleta?->id_empresa) {
                $empresa = Empresa::find($sucursalCompleta->id_empresa);
            }

            if ($empresa) {
                $empresaConfig['mostrar_campos_precio'] = (bool) $empresa->mostrar_campos_precio;
                $empresaConfig['enviar_facturas_automaticas'] = (bool) $empresa->enviar_facturas_automaticas;

                $sub = $empresa->subscription('default');
                $subscription = [
                    'empresa_id' => $empresa->id,
                    'plan_code' => $empresa->plan_code,
                    'plan_cycle' => $empresa->plan_cycle,
                    'can_access' => $empresa->canAccessApp(),
                    'on_trial' => $empresa->onTrial(),
                    'trial_ends_at' => optional($empresa->trial_ends_at)->toIso8601String(),
                    'trial_days_left' => $empresa->diasRestantesTrial(),
                    'subscribed' => $empresa->subscribed('default'),
                    'subscription' => $sub ? [
                        'stripe_status' => $sub->stripe_status,
                        'stripe_price' => $sub->stripe_price,
                        'trial_ends_at' => optional($sub->trial_ends_at)->toIso8601String(),
                        'ends_at' => optional($sub->ends_at)->toIso8601String(),
                        'cancelled' => $sub->canceled(),
                        'active' => $sub->active(),
                    ] : null,
                ];
            }
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tipo' => $user->tipo,
                    'id_empresa' => $user->id_empresa,
                    'id_sucursal' => $user->id_sucursal,
                ] : null,
            ],
            'sucursalActiva' => $sucursalActiva,
            'empresaConfig' => $empresaConfig,
            'subscription' => $subscription,
        ]);
    }
}
