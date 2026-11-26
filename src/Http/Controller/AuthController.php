<?php

declare(strict_types=1);

namespace WooPanel\Http\Controller;

use WooPanel\Application\Error\InvalidCredentials;
use WooPanel\Application\Error\MissingCapability;
use WooPanel\Application\Error\StoreUnavailable;
use WooPanel\Application\Port\StoreConnector;
use WooPanel\Domain\Credentials;
use WooPanel\Http\ErrorReporter;
use WooPanel\Http\LoginThrottle;
use WooPanel\Http\Request;
use WooPanel\Http\Responder;
use WooPanel\Http\Response;
use WooPanel\Http\SessionState;
use WooPanel\Infrastructure\Security\CredentialVault;

final readonly class AuthController
{
    public function __construct(
        private StoreConnector $connector,
        private CredentialVault $vault,
        private LoginThrottle $throttle,
        private SessionState $state,
        private Responder $respond,
        private ErrorReporter $reporter,
    ) {}

    public function showLogin(): Response
    {
        if ($this->state->auth() !== null) {
            return $this->respond->redirect('/');
        }

        return $this->form('', '', 200);
    }

    public function login(Request $request): Response
    {
        $username = trim($request->input('usuario'));
        $keys = ['ip:' . $request->clientIp, 'user:' . mb_strtolower($username)];
        foreach ($keys as $key) {
            if ($this->throttle->tooManyAttempts($key)) {
                return $this->form($username, 'Demasiados intentos fallidos. Espera 15 minutos antes de volver a intentarlo.', 429);
            }
        }

        try {
            $credentials = new Credentials($username, $request->input('clave_aplicacion'));
        } catch (\InvalidArgumentException) {
            return $this->form($username, 'Escribe tu usuario de WordPress y una contraseña de aplicación.', 422);
        }

        try {
            $user = $this->connector->authenticate($credentials);
        } catch (InvalidCredentials) {
            foreach ($keys as $key) {
                $this->throttle->recordFailure($key);
            }

            return $this->form($username, 'Usuario o contraseña de aplicación incorrectos.', 422);
        } catch (MissingCapability) {
            return $this->form($username, 'Tu usuario no tiene permiso para gestionar la tienda. Pide el rol «Gestor de la tienda» o «Administrador».', 403);
        } catch (StoreUnavailable $e) {
            $this->reporter->report($e, $request);

            return $this->form($username, 'La tienda no responde en este momento. Intenta de nuevo en unos minutos.', 503);
        }

        foreach ($keys as $key) {
            $this->throttle->clear($key);
        }
        $this->state->signIn($user, $this->vault->seal($credentials));

        return $this->respond->redirect('/', 'success', \sprintf('Hola, %s.', $user->displayName));
    }

    public function logout(): Response
    {
        $this->state->signOut();

        return $this->respond->redirect('/ingresar', 'info', 'Cerraste sesión.');
    }

    private function form(string $username, string $error, int $status): Response
    {
        return $this->respond->page('login', ['username' => $username, 'error' => $error], 'Ingresar', $status);
    }
}
