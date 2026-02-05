<?php
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
require_once 'models/Instalacion.php';
require_once 'models/Reserva.php';

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "No autorizado"]);
        exit;
    }
}

$action = $_GET['action'] ?? '';
$reservaModel = new Reserva();
$instalacionModel = new Instalacion();

// Leemos el JSON una sola vez
$data = json_decode(file_get_contents("php://input"));

switch($action) {
    case 'obtener_pistas':
        echo json_encode($instalacionModel->leerTodas());
        break;

    case 'crear_reserva':
        checkAuth();
        // Verificamos que los datos existan antes de usarlos para evitar el Warning
        if (!isset($data->instalacion_id) || !isset($data->fecha) || !isset($data->hora_inicio)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
            break;
        }
        
        $resultado = $reservaModel->crear(
            $_SESSION['user_id'], 
            $data->instalacion_id, 
            $data->fecha, 
            $data->hora_inicio
        );
        echo json_encode($resultado);
        break;

    case 'mis_reservas':
        checkAuth();
        echo json_encode($reservaModel->listarPorUsuario($_SESSION['user_id']));
        break;

    case 'cancelar_reserva':
        checkAuth();
        if(!isset($data->reserva_id)) {
            echo json_encode(["status" => "error", "message" => "ID de reserva ausente"]);
            break;
        }
        $exito = $reservaModel->cancelar($data->reserva_id);
        echo json_encode(["status" => $exito ? "success" : "error"]);
        break;

    case 'todas_reservas':
        checkAuth();
        if ($_SESSION['rol'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["message" => "Acceso denegado"]);
            exit;
        }
        // Limpiamos la lógica: el modelo ya tiene la query, no hace falta repetirla aquí
        echo json_encode($reservaModel->listarTodasAdmin());
        break;

    case 'listar_todos_usuarios':
        checkAuth();
        if ($_SESSION['rol'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["message" => "Acceso denegado"]);
            exit;
        }
        require_once 'models/Usuario.php';
        $usuarioModel = new Usuario();
        echo json_encode($usuarioModel->listarTodos());
        break;

    case 'eliminar_usuario':
    checkAuth();
    if ($_SESSION['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Acceso denegado"]);
        exit;
    }

    // Leemos el ID desde el objeto $data (JSON)
    $id_usuario = $data->user_id ?? $data->usuario_id ?? null;

    if (!$id_usuario) {
        echo json_encode(["status" => "error", "message" => "ID de usuario ausente"]);
        break;
    }

    require_once 'models/Usuario.php';
    $usuarioModel = new Usuario();
    $exito = $usuarioModel->eliminarPorId($id_usuario);
    
    echo json_encode(["status" => $exito ? "success" : "error"]);
    break;
    case 'modificar_usuario':
    checkAuth();
    if ($_SESSION['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Acceso denegado"]);
        exit;
    }

    // Leemos el ID y el ROL
    $id = $data->user_id ?? null;
    $nombre = $data->nombre ?? null;
    $email = $data->email ?? null;
    $password = $data->password ?? null;
    $rol = $data->nuevo_rol ?? null;

    if (!$id || !$rol) {
        echo json_encode([
            "status" => "error", 
            "message" => "Faltan datos críticos", 
            "debug_recibido" => $data // Esto te ayudará a ver qué llega realmente
        ]);
        break;
    }

    require_once 'models/Usuario.php';
    $usuarioModel = new Usuario();
    $exito = $usuarioModel->modificar($id, $nombre, $email, $password, $rol);
    
    echo json_encode(["status" => $exito ? "success" : "error"]);
    break;
    
    case 'actualizar_usuario':
        checkAuth();
        if (!isset($data->nombre) || !isset($data->email) || !isset($data->password)) {
            echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
            break;
        }
        require_once 'models/Usuario.php';
        $usuarioModel = new Usuario();
        $exito = $usuarioModel->modificar($_SESSION['user_id'], $data->nombre, $data->email, $data->password, null);
        echo json_encode(["status" => $exito ? "success" : "error"]);
        break;
    default:
        http_response_code(404);
        echo json_encode(["message" => "Acción no encontrada"]);
        break;

    case 'crear_pista':
    checkAuth();
    if ($_SESSION['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Acceso denegado"]);
        exit;
    }

    // Los datos de texto ahora vienen en $_POST y el archivo en $_FILES
    if (!isset($_POST['nombre']) || !isset($_FILES['imagen_url'])) {
        echo json_encode(["status" => "error", "message" => "Datos o imagen faltantes"]);
        break;
    }

    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'] ?? 'Pádel';
    $precio = $_POST['precio_hora'] ?? 0;
    $descripcion = $_POST['descripcion'] ?? '';
    $file = $_FILES['imagen_url'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($extension, $allowed_extensions)) {
        echo json_encode(["status" => "error", "message" => "Formato no permitido"]);
        break;
    }

    // Generamos un nombre único para que no se sobrescriban fotos
    $nombreImagen = time() . "_" . basename($file['name']);
    
    // Ruta donde Docker tiene mapeado el volumen
    $rutaDestino = "uploads/" . $nombreImagen;

    if (move_uploaded_file($file['tmp_name'], $rutaDestino)) {
        // Guardamos en la BD el nombre del archivo, no la ruta completa
        $exito = $instalacionModel->crearInstalacion($nombre, $tipo, $precio, $descripcion, $nombreImagen);
        echo json_encode(["status" => $exito ? "success" : "error"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al guardar el archivo físico"]);
    }
    break;

    // Modificar pista por ID (Para admin)
case 'modificar_pista':
    checkAuth();
    if ($_SESSION['rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["message" => "Acceso denegado"]);
        exit;
    }

    // Usamos $_POST porque desde Angular enviamos FormData
    if (!isset($_POST['id']) || !isset($_POST['nombre'])) {
        echo json_encode(["status" => "error", "message" => "ID o Nombre faltantes"]);
        break;
    }

    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $precio = $_POST['precio_hora'];
    $descripcion = $_POST['descripcion'];
    
    // 1. Recuperamos el nombre de la imagen que ya tiene la pista en la BD
    $nombreImagenActual = $_POST['imagen_actual'] ?? 'campo-aguadulce.png';

    // 2. Si el usuario sube una imagen nueva...
    if (isset($_FILES['imagen_url']) && $_FILES['imagen_url']['error'] === UPLOAD_ERR_OK) {
        
        // --- LÓGICA DE LIMPIEZA ---
        // Borramos la foto vieja del servidor si no es la genérica
        if ($nombreImagenActual !== 'campo-aguadulce.png' && !empty($nombreImagenActual)) {
            $rutaVieja = "uploads/" . $nombreImagenActual;
            if (file_exists($rutaVieja)) {
                unlink($rutaVieja); // Elimina el archivo físico de Docker
            }
        }
        // --------------------------

        $file = $_FILES['imagen_url'];
        // Generamos el nuevo nombre con tiempo para que sea único
        $nombreImagenActual = time() . "_" . basename($file['name']);
        move_uploaded_file($file['tmp_name'], "uploads/" . $nombreImagenActual);
    }

    // 3. Guardamos los cambios (usando el nombre nuevo o el antiguo si no cambió)
    $exito = $instalacionModel->modificarInstalacion($id, $nombre, $tipo, $precio, $descripcion, $nombreImagenActual);
    
    echo json_encode(["status" => $exito ? "success" : "error"]);
    break;

case 'eliminar_pista':
    checkAuth();
    // IMPORTANTE: Si vienes de una petición FormData, el ID puede venir en $_POST o en el JSON
    $id_eliminar = $_POST['instalacion_id'] ?? $data->instalacion_id ?? null;
    
    if (!$id_eliminar) {
        echo json_encode(["status" => "error", "message" => "ID de instalación ausente"]);
        break;
    }
    $exito = $instalacionModel->eliminarPorId($id_eliminar);
    echo json_encode(["status" => $exito ? "success" : "error"]);
    break;
}
?>