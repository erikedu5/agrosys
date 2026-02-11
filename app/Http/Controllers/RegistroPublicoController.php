<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Carbon\Carbon;

class RegistroPublicoController extends Controller
{
    /**
     * Muestra el formulario de registro público
     */
    public function showRegistrationForm()
    {
        return Inertia::render('Auth/RegistroPublico', [
            'trialDays' => config('TRIAL_DAYS', 15),
        ]);
    }

    /**
     * Procesa el registro de una nueva empresa
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nombre_empresa' => 'required|string|max:255|unique:empresas,nombre',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'nombre_administrador' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'required|string|max:500',
            'rfc' => 'nullable|string|max:20',
        ], [
            'nombre_empresa.required' => 'El nombre de la empresa es obligatorio.',
            'nombre_empresa.unique' => 'Ya existe una empresa con este nombre.',
            'email.required' => 'El email es obligatorio.',
            'email.unique' => 'Este email ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'nombre_administrador.required' => 'El nombre del administrador es obligatorio.',
            'direccion.required' => 'La dirección es obligatoria.',
        ]);

        try {
            DB::beginTransaction();

            // Crear la empresa con trial de 15 días
            $trialDays = config('TRIAL_DAYS', 15);
            $empresa = Empresa::create([
                'nombre' => $validated['nombre_empresa'],
                'direccion' => $validated['direccion'],
                'telefono' => $validated['telefono'] ?? null,
                'email' => $validated['email'],
                'rfc' => $validated['rfc'] ?? null,
                'aviso' => 'Bienvenido a ' . $validated['nombre_empresa'],
                'numero_sucursales' => 1,
                'mostrar_campos_precio' => true,
                'ventas_bloqueadas' => false,
                'enviar_facturas_automaticas' => false,
                'trial_ends_at' => Carbon::now()->addDays($trialDays),
            ]);

            // Crear sucursal principal
            $sucursal = Sucursales::create([
                'nombre' => 'Sucursal Principal',
                'direccion' => $validated['direccion'],
                'telefono' => $validated['telefono'] ?? null,
                'id_empresa' => $empresa->id,
                'es_matriz' => true,
            ]);

            // Crear usuario administrador de empresa
            $user = User::create([
                'name' => $validated['nombre_administrador'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'tipo' => 'adminEmpresa',
                'id_empresa' => $empresa->id,
                'id_sucursal' => $sucursal->id,
            ]);

            // Verificar que Stripe esté configurado antes de commitear
            // Esto evita que se guarden los datos si Stripe no está disponible
            try {
                $empresa->createSetupIntent();
            } catch (\Exception $stripeError) {
                // Si Stripe no está configurado, hacer rollback
                DB::rollBack();

                Log::error('Error al verificar Stripe durante registro', [
                    'error' => $stripeError->getMessage(),
                    'empresa' => $validated['nombre_empresa'],
                ]);

                return back()->withErrors([
                    'error' => 'El sistema de pagos no está configurado correctamente. Por favor, contacta al administrador del sistema.',
                ])->withInput();
            }

            DB::commit();

            // Autenticar al usuario
            Auth::login($user);

            // Log del registro exitoso
            Log::info('Nueva empresa registrada', [
                'empresa_id' => $empresa->id,
                'empresa_nombre' => $empresa->nombre,
                'user_id' => $user->id,
                'trial_ends_at' => $empresa->trial_ends_at,
            ]);

            // Redirigir a la página de suscripción para agregar método de pago
            return redirect()->route('suscripcion.create')->with('success', [
                'message' => '¡Bienvenido! Para activar tu período de prueba de ' . $trialDays . ' días, necesitamos tu información de pago.',
                'info' => 'No se realizará ningún cargo hasta que finalice tu período de prueba.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error en registro de empresa', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors([
                'error' => 'Ocurrió un error durante el registro. Por favor, inténtalo de nuevo.',
            ])->withInput();
        }
    }
}
