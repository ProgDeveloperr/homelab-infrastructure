<?php

declare(strict_types=1);

namespace CentroDesarrollo;

function iniciarSesionPanel(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');

    $rutaScript = str_replace(
        '\\',
        '/',
        dirname($_SERVER['SCRIPT_NAME'] ?? '/centro-desarrollo/index.php')
    );

    $rutaCookie = rtrim($rutaScript, '/') . '/';

    session_name('JGG_CENTRO_DESARROLLO');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $rutaCookie,
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    if (!session_start()) {
        throw new \RuntimeException('No fue posible iniciar la sesión.');
    }
}

function obtenerTokenCsrf(): string
{
    iniciarSesionPanel();

    $token = $_SESSION['token_csrf'] ?? null;

    if (!is_string($token) || strlen($token) !== 64) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['token_csrf'] = $token;
    }

    return $token;
}

function validarTokenCsrf(?string $token): bool
{
    iniciarSesionPanel();

    $tokenGuardado = $_SESSION['token_csrf'] ?? '';

    return is_string($token)
        && is_string($tokenGuardado)
        && strlen($token) === 64
        && hash_equals($tokenGuardado, $token);
}

function renovarTokenCsrf(): string
{
    iniciarSesionPanel();

    $token = bin2hex(random_bytes(32));
    $_SESSION['token_csrf'] = $token;

    return $token;
}

function guardarMensajePanel(
    string $tipo,
    string $texto,
    array $datos = []
): void {
    iniciarSesionPanel();

    $tiposPermitidos = ['exito', 'error', 'aviso'];

    if (!in_array($tipo, $tiposPermitidos, true)) {
        $tipo = 'aviso';
    }

    $_SESSION['mensaje_panel'] = [
        'tipo' => $tipo,
        'texto' => $texto,
        'datos' => $datos,
    ];
}

function extraerMensajePanel(): ?array
{
    iniciarSesionPanel();

    $mensaje = $_SESSION['mensaje_panel'] ?? null;
    unset($_SESSION['mensaje_panel']);

    return is_array($mensaje) ? $mensaje : null;
}
