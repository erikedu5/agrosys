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

                $sucursal = Sucursales::withTrashed()->find($user->id_sucursal);
                if (!$sucursal || $sucursal->trashed()) {
                    throw ValidationException::withMessages([
                        Fortify::username() => ['La sucursal asociada a esta cuenta esta desactivada.'],
                    ]);
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
}
