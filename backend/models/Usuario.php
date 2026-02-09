<?php
require_once 'Database.php';

class Usuario {
    private $db;
    private $table = 'usuarios';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // REGISTRO de nuevo usuario
    public function registrar($nombre, $email, $password) {
        // Encriptar contraseña (seguridad obligatoria)
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        $query = "INSERT INTO " . $this->table . " (nombre, email, password, rol) 
                  VALUES (:nombre, :email, :password, 'user')";
        
        $stmt = $this->db->prepare($query);
        
        try {
            return $stmt->execute([
                ':nombre' => $nombre,
                ':email'  => $email,
                ':password' => $password_hash
            ]);
        } catch (PDOException $e) {
            return false; // Probablemente email duplicado
        }
    }

    // LOGIN de usuario
    public function login($email, $password) {
    $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
    $stmt = $this->db->prepare($query);
    $stmt->execute([':email' => trim($email)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Solo usamos password_verify para máxima seguridad
    if ($user && password_verify(trim($password), $user['password'])) {
        unset($user['password']);
        return $user;
    }
    return false;
}

    //Listar todos los usuarios (para admin)
    public function listarTodos() {
        $query = "SELECT id, nombre, email, rol FROM " . $this->table . " ORDER BY nombre ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    //Eliminar usuario por ID (para admin)
    public function eliminarPorId($usuario_id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':id' => $usuario_id]);
    }

    //Modificar usuario (para admin)
    public function modificar($usuario_id, $nombre, $email, $password, $rol) {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $query = "UPDATE " . $this->table . " 
                SET nombre = :nombre, email = :email, password = :password, rol = :rol 
                WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':nombre' => $nombre,
            ':email'  => $email,
            ':password' => $password_hash,
            ':rol'    => $rol,
            ':id'     => $usuario_id
        ]);
    }

    // Verificar si un email ya existe (para evitar duplicados)
    public function existeEmail($email) {
        $query = "SELECT COUNT(*) FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':email' => trim($email)]);
        return $stmt->fetchColumn() > 0;
    }

    // Obtener usuario por ID
    public function obtenerPorId($usuario_id) {
        $query = "SELECT id, nombre, email, rol FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>