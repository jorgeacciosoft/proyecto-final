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
    if (!isset($_SESSION['usuario_id'])) {
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

    case 'mis_reservas':
        checkAuth();
        echo json_encode($reservaModel->listarPorUsuario($_SESSION['usuario_id']));
        break;

    case 'cancelar_reserva':
    checkAuth();
    // Aceptamos el ID desde JSON (`reserva_id`) o desde la querystring (`id`) por compatibilidad
    $id_reserva = $data->reserva_id ?? $_GET['id'] ?? null;

    if (!$id_reserva) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "ID de reserva ausente"]);
        break;
    }

    // Obtenemos la reserva usando el modelo
    $reserva = $reservaModel->obtenerPorId($id_reserva);

    if (!$reserva) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Reserva no encontrada"]);
        break;
    }

    $fecha_reserva = new DateTime($reserva['fecha']);
    $hoy = new DateTime();

    // Si la fecha ya pasó o faltan menos de 2 días, rechazamos
    if ($fecha_reserva < $hoy) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "La reserva ya ha pasado"]);
        break;
    }

    $intervalo = $hoy->diff($fecha_reserva);
    if ($intervalo->days < 2) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "No se puede cancelar con menos de 48h de antelación"]);
        break;
    }

    // Usamos el método del modelo para marcar como cancelada
    $exito = $reservaModel->cancelar($id_reserva);
    if ($exito) {
        echo json_encode(["status" => "success", "message" => "Reserva cancelada"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "No se pudo cancelar la reserva"]);
    }
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
        $exito = $usuarioModel->modificar($_SESSION['usuario_id'], $data->nombre, $data->email, $data->password, null);
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

    case 'obtener_instalacion':
        checkAuth();
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(["status" => "error", "message" => "ID no proporcionado"]);
            exit;
        }
        $instalacion = $instalacionModel->obtenerPorId($id);
        if (!$instalacion) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Instalación no encontrada"]);
            break;
        }
        echo json_encode($instalacion);
        break;

    case 'crear_reserva':
    checkAuth();
    
    // Forzamos la limpieza del buffer para que solo salga el JSON
    if (ob_get_length()) ob_clean();

    $data = json_decode(file_get_contents("php://input"), true);
    
    // PRUEBA DE FUEGO: 
    // Si $_SESSION['usuario_id'] está vacío, el sistema fallará.
    // Vamos a ver qué tiene la sesión realmente.
    $user_id = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? null;
    
    if (!$user_id) {
        // Si entra aquí, es que el Login NO guardó bien el ID o la sesión se perdió
        echo json_encode([
            "status" => "error", 
            "message" => "No hay usuario en la sesión. Sesión actual: " . json_encode($_SESSION)
        ]);
        exit;
    }

    $instalacion_id = $data['instalacion_id'];
    $fecha = $data['fecha'];
    $hora_inicio = $data['hora']; 

    try {
        // 3. Usamos los nombres exactos de tu tabla: usuario_id, instalacion_id, fecha, hora_inicio, estado
        $stmt = $pdo->prepare("INSERT INTO reservas (usuario_id, instalacion_id, fecha, hora_inicio, estado) VALUES (?, ?, ?, ?, 'confirmada')");
        $stmt->execute([
            $user_id, 
            $instalacion_id, 
            $fecha, 
            $hora_inicio, 
            'confirmada'
        ]);
        
        echo json_encode(["status" => "success", "message" => "Reserva guardada correctamente"]);
        exit; // Cerramos aquí para evitar que se envíe nada más

    } catch (PDOException $e) {
        // 4. Si la base de datos da error (ej: el usuario no existe), lo enviamos como JSON
        echo json_encode(["status" => "error", "message" => "Error de Base de Datos: " . $e->getMessage()]);
        exit;
    }
}

?>