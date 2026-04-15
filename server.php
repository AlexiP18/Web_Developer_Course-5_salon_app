<?php

// Router para el servidor embebido de PHP (útil en Railway)
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$fullPath = __DIR__ . '/public' . $path;

// Si es un archivo estático existente, dejar que PHP lo sirva directo
if ($path !== '/' && is_file($fullPath)) {
    return false;
}

// Simular PATH_INFO para el Router MVC de la app
$_SERVER['PATH_INFO'] = $path;

require __DIR__ . '/public/index.php';
