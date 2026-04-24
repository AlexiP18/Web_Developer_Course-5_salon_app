<?php

function debuguear($variable) : string {
    echo "<pre>";
    var_dump($variable);
    echo "</pre>";
    exit;
}

// Escapa / Sanitizar el HTML
function s($html) : string {
    $s = htmlspecialchars((string) $html, ENT_QUOTES, 'UTF-8');
    return $s;
}

function esUltimo(string $actual, string $proximo): bool {

    if($actual !== $proximo) {
        return true;
    }
    return false;
}

function iniciarSesion() : void {
    if(session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Función que revisa que el usuario este autenticado
function isAuth() : void {
    iniciarSesion();

    if(!isset($_SESSION['login'])) {
        header('Location: /');
        exit;
    }
}

function isAdmin() : void {
    iniciarSesion();

    if(isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1) {
        return;
    }

    // Recuperar rol admin si la sesión quedó incompleta pero existe usuario autenticado
    if(isset($_SESSION['id']) && class_exists('\Model\Usuario')) {
        $usuario = \Model\Usuario::find((int) $_SESSION['id']);
        if($usuario && (int) $usuario->admin === 1) {
            $_SESSION['admin'] = 1;
            return;
        }
    }

    header('Location: /');
    exit;
}
