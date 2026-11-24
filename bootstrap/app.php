<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Error 419 (token CSRF vencido o sesión no guardada, p. ej. navegador interno de WhatsApp):
        // en lugar de la página "419 Page Expired", se vuelve al formulario con un aviso y los datos escritos.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419 && ! $request->expectsJson()) {
                return redirect()->back()
                    ->withInput($request->except(['password', 'password_confirmation', '_token']))
                    ->with('aviso', 'La página estuvo abierta mucho tiempo o tu navegador no guardó la sesión. Vuelve a intentarlo. '
                        .'Si se repite, abre la página en Chrome o Safari (no dentro de WhatsApp).');
            }
        });
    })->create();
