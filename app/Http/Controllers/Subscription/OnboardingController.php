<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Mail\TrialStartedMail;
use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use App\Support\SubscriptionPlans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Rules\Password;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function create(Request $request)
    {
        $step = (int) $request->query('step', 1);
        if (Auth::check()) {
            $step = max($step, 2);
        }

        $defaults = config('subscriptions.defaults');
        $planCode = (string) $request->query('plan', $defaults['plan'] ?? 'campo');
        $cycle = strtolower((string) $request->query('cycle', $defaults['cycle'] ?? 'monthly'));

        if (!SubscriptionPlans::exists($planCode)) {
            $planCode = $defaults['plan'] ?? 'campo';
        }

        if (!in_array($cycle, ['monthly', 'yearly'], true)) {
            $cycle = $defaults['cycle'] ?? 'monthly';
        }

        $plan = SubscriptionPlans::find($planCode);
        if ($cycle === 'yearly' && empty($plan['prices']['yearly_mxn'])) {
            $cycle = 'monthly';
        }

        $plans = collect(SubscriptionPlans::all())->map(function (array $p) {
            return [
                'code' => $p['code'],
                'name' => $p['name'],
                'prices' => $p['prices'],
                'limits' => $p['limits'],
                'features' => $p['features'],
                'has_yearly' => !empty($p['prices']['yearly_mxn']),
            ];
        })->values();

        return Inertia::render('Subscription/Onboarding', [
            'plans' => $plans,
            'selected' => [
                'plan' => $planCode,
                'cycle' => $cycle,
            ],
            'step' => $step,
            'trialDays' => SubscriptionPlans::trialDays(),
            'termsVersion' => config('legal.terms_version'),
            'privacyVersion' => config('legal.privacy_version'),
        ]);
    }

    public function store(Request $request)
    {
        $planCodes = array_keys(SubscriptionPlans::all());

        $validator = validator($request->all(), [
            'plan' => ['required', Rule::in($planCodes)],
            'cycle' => ['required', Rule::in(['monthly', 'yearly'])],

            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', new Password],

            'empresa_nombre' => ['required', 'string', 'max:255', 'unique:empresas,nombre'],
            'empresa_direccion' => ['required', 'string', 'max:255'],
            'empresa_telefono' => ['nullable', 'string', 'max:50'],
            'empresa_email' => ['nullable', 'string', 'email', 'max:255'],

            'sucursal_nombre' => ['nullable', 'string', 'max:255'],
            'sucursal_direccion' => ['nullable', 'string', 'max:255'],

            'billing_requires_invoice' => ['boolean'],
            'billing_email' => ['nullable', 'string', 'email', 'max:255'],
            'billing_razon_social' => ['nullable', 'string', 'max:255'],
            'billing_rfc' => ['nullable', 'string', 'max:32'],
            'billing_regimen_fiscal' => ['nullable', 'string', 'max:8'],
            'billing_uso_cfdi' => ['nullable', 'string', 'max:8'],
            'billing_codigo_postal' => ['nullable', 'string', 'max:12'],

            'terms_accepted' => ['accepted'],
            'privacy_accepted' => ['accepted'],
        ], [
            'plan.required' => 'Selecciona un plan.',
            'cycle.required' => 'Selecciona un periodo.',
            'email.unique' => 'Este correo ya esta registrado.',
            'empresa_nombre.unique' => 'Este nombre de empresa ya existe.',
            'terms_accepted.accepted' => 'Debes aceptar los terminos.',
            'privacy_accepted.accepted' => 'Debes aceptar el aviso de privacidad.',
        ]);

        $validator->after(function ($v) use ($request) {
            $planCode = (string) $request->input('plan');
            $cycle = (string) $request->input('cycle');
            $plan = SubscriptionPlans::find($planCode);

            if ($cycle === 'yearly' && empty($plan['prices']['yearly_mxn'])) {
                $v->errors()->add('cycle', 'Este plan no tiene pago anual.');
            }

            if ($request->boolean('billing_requires_invoice')) {
                $required = [
                    'billing_email' => 'Email de facturacion es requerido.',
                    'billing_razon_social' => 'Razon social es requerida.',
                    'billing_rfc' => 'RFC es requerido.',
                    'billing_regimen_fiscal' => 'Regimen fiscal es requerido.',
                    'billing_uso_cfdi' => 'Uso CFDI es requerido.',
                    'billing_codigo_postal' => 'Codigo postal es requerido.',
                ];

                foreach ($required as $field => $message) {
                    if (!filled($request->input($field))) {
                        $v->errors()->add($field, $message);
                    }
                }
            }
        });

        $data = $validator->validate();

        $plan = SubscriptionPlans::find($data['plan']);

        $trialEndsAt = now()->addDays(SubscriptionPlans::trialDays());
        $empresaAvisoDefault = 'Gracias por su compra.';

        $empresa = null;
        $sucursal = null;
        $user = null;

        DB::transaction(function () use (&$empresa, &$sucursal, &$user, $data, $plan, $trialEndsAt, $empresaAvisoDefault) {
            $empresa = Empresa::create([
                'nombre' => $data['empresa_nombre'],
                'direccion' => $data['empresa_direccion'],
                'telefono' => $data['empresa_telefono'] ?? null,
                'email' => $data['empresa_email'] ?? null,
                'rfc' => null,
                'aviso' => $empresaAvisoDefault,
                'numero_sucursales' => (int) ($plan['limits']['max_sucursales'] ?? 1),
                'numero_dispositivos_por_sucursal' => (int) ($plan['limits']['devices_per_sucursal'] ?? 1),
                'plan_code' => $data['plan'],
                'plan_cycle' => $data['cycle'],
                'trial_ends_at' => $trialEndsAt,
                'billing_requires_invoice' => (bool) ($data['billing_requires_invoice'] ?? false),
                'billing_email' => $data['billing_email'] ?? null,
                'billing_razon_social' => $data['billing_razon_social'] ?? null,
                'billing_rfc' => $data['billing_rfc'] ?? null,
                'billing_regimen_fiscal' => $data['billing_regimen_fiscal'] ?? null,
                'billing_uso_cfdi' => $data['billing_uso_cfdi'] ?? null,
                'billing_codigo_postal' => $data['billing_codigo_postal'] ?? null,
                'terms_version' => config('legal.terms_version'),
                'terms_accepted_at' => now(),
                'privacy_version' => config('legal.privacy_version'),
                'privacy_accepted_at' => now(),
            ]);

            $sucursalNombre = $data['sucursal_nombre'] ?: 'Matriz';
            $sucursalDireccion = $data['sucursal_direccion'] ?: $empresa->direccion;

            $sucursal = Sucursales::create([
                'nombre' => $sucursalNombre,
                'direccion' => $sucursalDireccion,
                'telefono' => $empresa->telefono,
                'email' => $empresa->email,
                'es_matriz' => true,
                'id_empresa' => $empresa->id,
                'ticket_width_mm' => 80,
            ]);

            $clientePublico = Clientes::create([
                'nombre' => 'Publico en general',
                'porcentaje_descuento' => '0',
                'adeudo_total' => 0,
                'abono_total' => 0,
                'balance' => 0,
                'requiereFactura' => false,
                'activo' => true,
                'rfc' => null,
                'id_sucursal' => $sucursal->id,
            ]);

            $sucursal->update(['id_cliente_publico' => $clientePublico->id]);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'tipo' => 'adminEmpresa',
                'id_sucursal' => $sucursal->id,
                'id_empresa' => $empresa->id,
            ]);
        });

        Auth::login($user);
        session(['sucursal_activa' => $sucursal->id]);
        session(['sucursal_nombre' => $sucursal->nombre]);

        Mail::to($user->email)->send(new TrialStartedMail($empresa, $user));

        return redirect()
            ->route('subscription.onboarding', ['step' => 2])
            ->with('success', 'Cuenta creada. Tu prueba gratuita ya esta activa. (Paso 2: pago opcional)');
    }
}
