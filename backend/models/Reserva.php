<?php
require_once 'Database.php';

class Reserva
{
    private $db;
    private $table = 'reservas';

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // CREAR una reserva (C de CRUD)
    public function crear($usuario_id, $instalacion_id, $fecha, $hora)
    {
        // Primero verificamos si ya existe una reserva a esa hora
        $check = "SELECT id FROM " . $this->table . " 
                WHERE instalacion_id = :inst AND fecha = :fecha AND hora_inicio = :hora AND estado = 'confirmada'";
        $stmtCheck = $this->db->prepare($check);
        $stmtCheck->execute([
            ':inst' => $instalacion_id,
            ':fecha' => $fecha,
            ':hora' => $hora
        ]);

        if ($stmtCheck->rowCount() > 0) {
            return ["status" => "error", "message" => "La pista ya está ocupada en ese horario."];
        }

        // Si está libre, insertamos
        $query = "INSERT INTO " . $this->table . " (usuario_id, instalacion_id, fecha, hora_inicio) 
                VALUES (:user, :inst, :fecha, :hora)";
        $stmt = $this->db->prepare($query);

        if ($stmt->execute([
            ':user' => $usuario_id,
            ':inst' => $instalacion_id,
            ':fecha' => $fecha,
            ':hora' => $hora
        ])) {
            // Devolvemos el ID de la reserva creada para poder registrar el pago
            return [
                "status" => "success", 
                "message" => "Reserva realizada con éxito.",
                "reserva_id" => $this->db->lastInsertId()
            ];
        }
        return ["status" => "error", "message" => "Error al procesar la reserva."];
    }

    // LEER reservas de un usuario (R de CRUD)
    public function listarPorUsuario($usuario_id)
    {
        $query = "SELECT r.*, i.nombre as pista_nombre 
                FROM " . $this->table . " r
                JOIN instalaciones i ON r.instalacion_id = i.id
                WHERE r.usuario_id = :user ORDER BY r.fecha DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':user' => $usuario_id]);
        return $stmt->fetchAll();
    }

    // ELIMINAR/CANCELAR reserva (D de CRUD)
    public function cancelar($id)
    {
        $query = "UPDATE " . $this->table . " SET estado = 'cancelada' WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $id]);
    }

    // Obtener reserva por ID
    public function obtenerPorId($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // Listar todas las reservas (Para admin)
    public function listarTodasAdmin() {
        $query = "SELECT r.*, u.nombre as usuario_nombre, i.nombre as pista_nombre 
                FROM reservas r
                JOIN usuarios u ON r.usuario_id = u.id
                JOIN instalaciones i ON r.instalacion_id = i.id
                ORDER BY r.fecha DESC, r.hora_inicio DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Obtener solo las reservas confirmadas (público, sin datos de usuario)
    public function listarReservasConfirmadas() {
        $query = "SELECT r.id, r.instalacion_id, r.fecha, r.hora_inicio, r.estado 
                FROM " . $this->table . " r
                WHERE r.estado = 'confirmada'
                ORDER BY r.fecha DESC, r.hora_inicio DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
