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
require_once 'models/Pago.php';
require_once 'models/Usuario.php';
require_once 'models/Mailer.php';

function checkAuth() {
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "No autorizado"]);
        exit;
    }
}

function convertirAWebP(string $tmpPath, string $extension, int $calidad = 82): string|false {
    $imagen = match($extension) {
        'jpg', 'jpeg' => imagecreatefromjpeg($tmpPath),
        'png'         => imagecreatefrompng($tmpPath),
        'gif'         => imagecreatefromgif($tmpPath),
        default       => false
    };

    if ($imagen === false) return false;

    if ($extension === 'png') {
        imagepalettetotruecolor($imagen);
        imagealphablending($imagen, true);
        imagesavealpha($imagen, true);
    }

    // Redimensionar si supera 1200px de ancho
    $maxAncho = 1200;
    if (imagesx($imagen) > $maxAncho) {
        $alto = (int)(imagesy($imagen) * $maxAncho / imagesx($imagen));
        $redimensionada = imagescale($imagen, $maxAncho, $alto);
        imagedestroy($imagen);
        $imagen = $redimensionada;
    }

    ob_start();
    $ok = imagewebp($imagen, null, $calidad);
    $datos = ob_get_clean();
    imagedestroy($imagen);

    return ($ok && $datos !== false) ? $datos : false;
}

$action = $_GET['action'] ?? '';
$reservaModel = new Reserva();
$instalacionModel = new Instalacion();
$pagoModel = new Pago();
$usuarioModel = new Usuario();
$mailer = new Mailer();

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
        $id_reserva = $data->reserva_id ?? $_GET['id'] ?? null;

        if (!$id_reserva) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID de reserva ausente"]);
            break;
        }

        $reserva = $reservaModel->obtenerPorId($id_reserva);

        if (!$reserva) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Reserva no encontrada"]);
            break;
        }

        $fecha_reserva = new DateTime($reserva['fecha']);
        $hoy = new DateTime();

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

        $exito = $reservaModel->cancelar($id_reserva);
        if ($exito) {
            $pagoModel->reembolsarPorReserva($id_reserva);
            echo json_encode(["status" => "success", "message" => "Reserva cancelada y pago reembolsado"]);
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
        echo json_encode($reservaModel->listarTodasAdmin());
        break;

    case 'obtener_reservas_confirmadas':
        echo json_encode($reservaModel->listarReservasConfirmadas());
        break;

    case 'estadisticas':
        checkAuth();
        if ($_SESSION['rol'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["message" => "Acceso denegado"]);
            exit;
        }
        $stats = $pagoModel->obtenerEstadisticas();
        if (isset($stats['error'])) {
            http_response_code(500);
        }
        echo json_encode($stats);
        break;

    case 'debug_pagos':
        checkAuth();
        if ($_SESSION['rol'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["message" => "Acceso denegado"]);
            exit;
        }
        echo json_encode([
            'total_pagos' => $pagoModel->contarTodos(),
            'pagos' => $pagoModel->listarTodos(),
            'fecha_actual' => date('Y-m-d H:i:s')
        ]);
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

        $id       = $data->user_id ?? null;
        $nombre   = $data->nombre ?? null;
        $email    = $data->email ?? null;
        $password = $data->password ?? null;
        $rol      = $data->nuevo_rol ?? null;

        if (!$id || !$rol) {
            echo json_encode([
                "status" => "error",
                "message" => "Faltan datos críticos",
                "debug_recibido" => $data
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

    case 'crear_pista':
        checkAuth();
        if ($_SESSION['rol'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["message" => "Acceso denegado"]);
            exit;
        }

        if (!isset($_POST['nombre']) || !isset($_FILES['imagen_url'])) {
            echo json_encode(["status" => "error", "message" => "Datos o imagen faltantes"]);
            break;
        }

        $nombre      = $_POST['nombre'];
        $tipo        = $_POST['tipo'] ?? 'Pádel';
        $precio      = $_POST['precio_hora'] ?? 0;
        $descripcion = $_POST['descripcion'] ?? '';
        $file        = $_FILES['imagen_url'];
        $extension   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            echo json_encode(["status" => "error", "message" => "Formato no permitido"]);
            break;
        }

        // Nombre único con extensión .webp
        $nombreImagen = time() . "_" . pathinfo(basename($file['name']), PATHINFO_FILENAME) . ".webp";
        $rutaDestino  = "uploads/" . $nombreImagen;

        // Convertir y guardar
        $imagenConvertida = convertirAWebP($file['tmp_name'], $extension);

        if ($imagenConvertida !== false && file_put_contents($rutaDestino, $imagenConvertida)) {
            $exito = $instalacionModel->crearInstalacion($nombre, $tipo, $precio, $descripcion, $nombreImagen);
            echo json_encode(["status" => $exito ? "success" : "error"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al convertir o guardar la imagen"]);
        }
        break;

    case 'modificar_pista':
        checkAuth();
        if ($_SESSION['rol'] !== 'admin') {
            http_response_code(403);
            echo json_encode(["message" => "Acceso denegado"]);
            exit;
        }

        if (!isset($_POST['id']) || !isset($_POST['nombre'])) {
            echo json_encode(["status" => "error", "message" => "ID o Nombre faltantes"]);
            break;
        }

        $id                 = $_POST['id'];
        $nombre             = $_POST['nombre'];
        $tipo               = $_POST['tipo'];
        $precio             = $_POST['precio_hora'];
        $descripcion        = $_POST['descripcion'];
        $nombreImagenActual = $_POST['imagen_actual'] ?? 'campo-aguadulce.png';

        // Si el usuario sube una imagen nueva
        if (isset($_FILES['imagen_url']) && $_FILES['imagen_url']['error'] === UPLOAD_ERR_OK) {

            // Borrar imagen vieja si no es la genérica
            if ($nombreImagenActual !== 'campo-aguadulce.png' && !empty($nombreImagenActual)) {
                $rutaVieja = "uploads/" . $nombreImagenActual;
                if (file_exists($rutaVieja)) {
                    unlink($rutaVieja);
                }
            }

            $file      = $_FILES['imagen_url'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Nombre único con extensión .webp
            $nombreImagenActual = time() . "_" . pathinfo(basename($file['name']), PATHINFO_FILENAME) . ".webp";
            $rutaDestino        = "uploads/" . $nombreImagenActual;

            // Convertir y guardar
            $imagenConvertida = convertirAWebP($file['tmp_name'], $extension);

            if ($imagenConvertida === false || !file_put_contents($rutaDestino, $imagenConvertida)) {
                echo json_encode(["status" => "error", "message" => "Error al convertir o guardar la imagen"]);
                break;
            }
        }

        $exito = $instalacionModel->modificarInstalacion($id, $nombre, $tipo, $precio, $descripcion, $nombreImagenActual);
        echo json_encode(["status" => $exito ? "success" : "error"]);
        break;

    case 'eliminar_pista':
        checkAuth();
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

        $instalacion_id = $data->instalacion_id ?? null;
        $fecha          = $data->fecha ?? null;
        $hora_inicio    = $data->hora_inicio ?? null;

        if (!$instalacion_id || !$fecha || !$hora_inicio) {
            http_response_code(400);
            echo json_encode([
                "status"  => "error",
                "message" => "Datos incompletos: se requiere instalacion_id, fecha y hora_inicio"
            ]);
            break;
        }

        // 1. Crear la reserva
        $resultado = $reservaModel->crear($_SESSION['usuario_id'], $instalacion_id, $fecha, $hora_inicio);

        // 2. Si la reserva fue exitosa, registrar el pago
        if ($resultado['status'] === 'success' && isset($resultado['reserva_id'])) {
            $instalacion = $instalacionModel->obtenerPorId($instalacion_id);
            $importe     = $instalacion['precio_hora'] ?? 0;

            $resultadoPago = $pagoModel->registrar(
                $resultado['reserva_id'],
                $importe,
                'tarjeta_credito',
                'completado'
            );

            if ($resultadoPago['status'] === 'success') {
                $resultado['pago'] = [
                    'referencia' => $resultadoPago['referencia'],
                    'importe'    => $importe
                ];
            }

            // 3. Enviar correo de confirmación
            $usuario = $usuarioModel->obtenerPorId($_SESSION['usuario_id']);
            if ($usuario) {
                $reserva_data = [
                    'id'          => $resultado['reserva_id'],
                    'fecha'       => $fecha,
                    'hora_inicio' => $hora_inicio
                ];

                $mailer->enviarConfirmacionReserva(
                    $usuario['email'],
                    $usuario['nombre'],
                    $reserva_data,
                    $instalacion['nombre'],
                    $resultado['pago'] ?? null
                );
            }
        }

        echo json_encode($resultado);
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Acción no encontrada"]);
        break;
}
?>