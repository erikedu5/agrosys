<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\Empresa;
use App\Models\Sucursales;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Throwable;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request) {
            try {
                $user = User::where('email', $request->email)->first();

                if (!$user || !Hash::check($request->password, $user->password)) {
                    throw ValidationException::withMessages([
                        Fortify::username() => ['El correo o la contrasena no son correctos.'],
                    ]);
                }

                if ($user->trashed()) {
                    throw ValidationException::withMessages([
                        Fortify::username() => ['Este usuario esta desactivado.'],
                    ]);
                }

                if ($user->isAuditor()) {
                    $this->validarAuditor($user);

                    return $user;
                }

                $sucursal = Sucursales::withTrashed()->find($user->id_sucursal);
                if (!$sucursal || $sucursal->trashed()) {
                    if ($user->tipo !== 'adminEmpresa') {
                        throw ValidationException::withMessages([
                            Fortify::username() => ['La sucursal asociada a esta cuenta esta desactivada.'],
                        ]);
                    }

                    // adminEmpresa opera sobre cualquier sucursal de su empresa:
                    // si la asignada ya no esta activa, se reasigna a otra activa.
                    $sucursal = $this->reasignarSucursalAdminEmpresa($user, $sucursal);
                    if (!$sucursal) {
                        throw ValidationException::withMessages([
                            Fortify::username() => ['La empresa asociada a esta cuenta no tiene sucursales activas.'],
                        ]);
                    }
                }

                $empresa = Empresa::withTrashed()->find($sucursal->id_empresa);
                if (!$empresa || $empresa->trashed()) {
                    throw ValidationException::withMessages([
                        Fortify::username() => ['La empresa asociada a esta cuenta esta desactivada.'],
                    ]);
                }

                return $user;
            } catch (ValidationException $e) {
                throw $e;
            } catch (Throwable $e) {
                Log::error('Error during login authentication.', [
                    'email' => $request->email,
                    'exception' => $e,
                ]);

                throw ValidationException::withMessages([
                    Fortify::username() => ['No se pudo iniciar sesion. Intenta de nuevo o contacta al administrador.'],
                ]);
            }
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }

    /** Auditor externo: sin sucursal propia; requiere vigencia y al menos un alcance en empresa activa. */
    private function validarAuditor(User $user): void
    {
        if (!$user->accesoVigente()) {
            throw ValidationException::withMessages([
                Fortify::username() => ['El acceso de este auditor ya vencio. Contacta al administrador.'],
            ]);
        }

        $tieneAlcance = $user->alcancesAuditor()->whereHas('empresa')->exists();
        if (!$tieneAlcance) {
            throw ValidationException::withMessages([
                Fortify::username() => ['Este auditor no tiene empresas asignadas o estan desactivadas.'],
            ]);
        }
    }

    private function reasignarSucursalAdminEmpresa(User $user, ?Sucursales $anterior): ?Sucursales
    {
        $empresaId = $user->id_empresa ?: $anterior?->id_empresa;
        if (!$empresaId) {
            return null;
        }

        $nueva = Sucursales::where('id_empresa', $empresaId)
            ->orderByRaw('COALESCE(es_bodega, 0) asc')
            ->orderByDesc('es_matriz')
            ->orderBy('id')
            ->first();

        if (!$nueva) {
            return null;
        }

        Log::warning('Sucursal de adminEmpresa reasignada durante login.', [
            'user_id' => $user->id,
            'empresa_id' => $empresaId,
            'sucursal_anterior' => $user->id_sucursal,
            'sucursal_nueva' => $nueva->id,
        ]);

        $user->forceFill([
            'id_sucursal' => $nueva->id,
            'id_empresa' => $empresaId,
        ])->save();

        return $nueva;
    }
}
