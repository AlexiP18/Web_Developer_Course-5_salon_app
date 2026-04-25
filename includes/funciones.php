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

    $savePath = getenv('SESSION_SAVE_PATH') ?: sys_get_temp_dir();
    if(is_string($savePath) && $savePath !== '' && is_dir($savePath) && is_writable($savePath)) {
        session_save_path($savePath);
    }

    session_name('APPSALONSESSID');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

function esHttps() : bool {
    return (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    );
}

function authSecret() : string {
    $secret = getenv('AUTH_COOKIE_SECRET') ?: getenv('APP_KEY') ?: '';
    if($secret === '') {
        $secret = 'appsalon-dev-secret';
    }
    return $secret;
}

function b64urlEncode(string $data) : string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function b64urlDecode(string $data) : string|false {
    $padding = strlen($data) % 4;
    if($padding > 0) {
        $data .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function setAuthCookie(int $usuarioId) : void {
    $expiraEn = time() + (60 * 60 * 24 * 7); // 7 días
    $payload = json_encode([
        'id' => $usuarioId,
        'exp' => $expiraEn
    ]);

    if($payload === false) {
        return;
    }

    $payload64 = b64urlEncode($payload);
    $firma = hash_hmac('sha256', $payload64, authSecret());
    $token = $payload64 . '.' . $firma;

    setcookie('APPSALON_AUTH', $token, [
        'expires' => $expiraEn,
        'path' => '/',
        'secure' => esHttps(),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function clearAuthCookie() : void {
    setcookie('APPSALON_AUTH', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => esHttps(),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function restaurarSesionDesdeCookie() : bool {
    if(isset($_SESSION['login']) && $_SESSION['login'] === true) {
        return true;
    }

    $token = $_COOKIE['APPSALON_AUTH'] ?? '';
    if($token === '' || !str_contains($token, '.')) {
        return false;
    }

    [$payload64, $firma] = explode('.', $token, 2);
    $firmaEsperada = hash_hmac('sha256', $payload64, authSecret());

    if(!hash_equals($firmaEsperada, $firma)) {
        return false;
    }

    $payloadJson = b64urlDecode($payload64);
    if($payloadJson === false) {
        return false;
    }

    $payload = json_decode($payloadJson, true);
    if(!is_array($payload) || !isset($payload['id'], $payload['exp'])) {
        return false;
    }

    $usuarioId = (int) $payload['id'];
    $exp = (int) $payload['exp'];

    if($usuarioId <= 0 || $exp < time()) {
        return false;
    }

    if(!class_exists('\Model\Usuario')) {
        return false;
    }

    $usuario = \Model\Usuario::find($usuarioId);
    if(!$usuario) {
        return false;
    }

    $_SESSION['id'] = $usuario->id;
    $_SESSION['nombre'] = trim($usuario->nombre . ' ' . $usuario->apellido);
    $_SESSION['email'] = $usuario->email;
    $_SESSION['login'] = true;
    $_SESSION['admin'] = ((int) $usuario->admin === 1) ? 1 : 0;

    return true;
}

// Función que revisa que el usuario este autenticado
function isAuth() : void {
    iniciarSesion();
    restaurarSesionDesdeCookie();

    if(!isset($_SESSION['login'])) {
        header('Location: /');
        exit;
    }
}

function isAdmin() : void {
    iniciarSesion();
    restaurarSesionDesdeCookie();

    if(isset($_SESSION['admin']) && (int) $_SESSION['admin'] === 1) {
        return;
    }

    // Recuperar rol admin si la sesión quedó incompleta pero existe usuario autenticado
    if(class_exists('\Model\Usuario')) {
        $usuario = null;

        if(isset($_SESSION['id'])) {
            $usuario = \Model\Usuario::find((int) $_SESSION['id']);
        } elseif(isset($_SESSION['email'])) {
            $usuario = \Model\Usuario::where('email', (string) $_SESSION['email']);
        }

        if($usuario && (int) $usuario->admin === 1) {
            $_SESSION['id'] = $usuario->id;
            $_SESSION['email'] = $usuario->email;
            $_SESSION['nombre'] = trim($usuario->nombre . ' ' . $usuario->apellido);
            $_SESSION['login'] = true;
            $_SESSION['admin'] = 1;
            return;
        }
    }

    header('Location: /');
    exit;
}
