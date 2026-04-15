<?php

// Defaults para entorno local
$host = 'localhost';
$port = 3306;
$user = 'root';
$password = '';
$database = 'appsalon_mvc_php';

// Railway (y otros proveedores) pueden inyectar una URL completa
$databaseUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if ($parts !== false) {
        $host = $parts['host'] ?? $host;
        $port = $parts['port'] ?? $port;
        $user = $parts['user'] ?? $user;
        $password = $parts['pass'] ?? $password;
        $database = isset($parts['path']) ? ltrim($parts['path'], '/') : $database;
    }
}

// Variables explícitas (prioridad alta) para Railway/MySQL
$host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: $host;
$port = (int) (getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: $port);
$user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: $user;
$password = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: $password;
$database = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: $database;

$db = mysqli_connect($host, $user, $password, $database, $port);

if ($db) {
    mysqli_set_charset($db, 'utf8mb4');
}

if (!$db) {
    echo "Error: No se pudo conectar a MySQL.";
    echo "errno de depuración: " . mysqli_connect_errno();
    echo "error de depuración: " . mysqli_connect_error();
    exit;
}
