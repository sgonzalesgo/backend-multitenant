<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Obtener el encabezado 'Accept-Language'
        $locale = $request->header('Accept-Language');


        // Validar y establecer el idioma
        if (in_array($locale, ['en', 'es', 'fr','ar'])) { // Asegúrate de incluir todos los idiomas soportados
            App::setLocale($locale);
        } else {
            // Establecer un idioma predeterminado si no se especifica o es inválido
            App::setLocale(env('APP_LOCALE', 'es'));
        }

        return $next($request);
    }
}
