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

// Función que revisa que el usuario este autenticado
function isAuth() : void {
    if(!isset($_SESSION['login'])) {
        header('Location: /');
        exit;
    }
}

function isAdmin() : void {
    if(!isset($_SESSION['admin']) || (int) $_SESSION['admin'] !== 1) {
        header('Location: /');
        exit;
    }
}
