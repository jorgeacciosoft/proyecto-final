<?php
// 1. Detectar si estamos en local (Docker) o en el servidor (InfinityFree)
$isLocal = ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1');

if ($isLocal) {
    // --- CONFIGURACIÓN PARA DOCKER (Tu Main) ---
    define('DB_HOST', 'db');
    define('DB_USER', 'root');
    define('DB_PASSWORD', 'todo_reservas_password');
    define('DB_NAME', 'todo_reservas');
} else {
    // --- CONFIGURACIÓN PARA INFINITYFREE (Producción) ---
    // Reemplaza 'sqlXXX' con el host real que aparece en tu panel de InfinityFree
    define('DB_HOST', 'sql112.infinityfree.com'); 
    define('DB_USER', 'if0_41113488');
    define('DB_PASSWORD', 'dWjMxpUEG3Z'); 
    define('DB_NAME', 'if0_41113488_todo_reservas');
}

// Configuración común
define('DB_CHARSET', 'utf8mb4');

// Configuración SMTP (Se mantiene igual en ambos)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'jorge15bm1998@gmail.com');
define('SMTP_PASS', 'injz xcpu dmvw hfid');
define('SMTP_FROM', 'jorge15bm1998@gmail.com');
define('SMTP_FROM_NAME', 'Gestión de Reservas - TodoReservas');
define('MAIL_ENV', 'production'); 

// 2. Cabeceras CORS: Imprescindible para que Angular (GitHub) hable con PHP (InfinityFree)
header("Access-Control-Allow-Origin: https://jorgeacciosoft.github.io");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Si es una petición OPTIONS (verificación del navegador), salimos rápido
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}
?>