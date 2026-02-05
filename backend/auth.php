<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once 'models/Usuario.php';

// Leemos el JSON una sola vez y lo guardamos
$json = file_get_contents('php://input');
$data = json_decode($json);
$action = $_GET['action'] ?? '';

$userModel = new Usuario();

switch($action) {
    case 'login':
        // Verificamos que los datos lleguen
        if(!$data || empty($data->email) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
            exit;
        }

        $user = $userModel->login($data->email, $data->password);

        if($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['rol'] = $user['rol'];
            echo json_encode(["status" => "success", "user" => $user]);
        } else {
            // Si llega aquí, el email no existe o la password no coincide
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Credenciales incorrectas"]);
        }
        break;
    
    case 'registrar_usuario':
    if (!$data || empty($data->nombre) || empty($data->email) || empty($data->password)) {
        echo json_encode(["status" => "error", "message" => "Faltan datos obligatorios"]);
        break;
    }

    // 1. Primero comprobamos si el email ya existe
    if ($userModel->existeEmail($data->email)) {
        echo json_encode(["status" => "error", "message" => "Este correo electrónico ya está registrado"]);
        break;
    }

    // 2. Si no existe, procedemos al registro
    $exito = $userModel->registrar($data->nombre, $data->email, $data->password);

    if ($exito) {
        echo json_encode(["status" => "success", "message" => "Usuario creado correctamente"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error interno al crear el usuario"]);
    }
    break;

    case 'verificar_sesion':
        if(isset($_SESSION['user_id'])) {
            echo json_encode(["isLoggedIn" => true, "rol" => $_SESSION['rol']]);
        } else {
            echo json_encode(["isLoggedIn" => false]);
        }
        break;

    case 'cerrar_sesion':
        session_destroy();
        echo json_encode(["status" => "success"]);
        break;
}