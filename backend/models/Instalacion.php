<?php
require_once 'Database.php';

class Instalacion {
    private $db;
    private $table = 'instalaciones';

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    // Leer todas las instalaciones (Para el catálogo de Angular)
    public function leerTodas() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY nombre ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Buscar por tipo (Filtro)
    public function buscarPorTipo($tipo) {
        $query = "SELECT * FROM " . $this->table . " WHERE tipo = :tipo";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    //Crear Instalación (Para admin)
    public function crearInstalacion($nombre, $tipo,  $precio_hora, $descripcion, $imagen_url) {
        $query = "INSERT INTO " . $this->table . " (nombre, tipo, precio_hora, descripcion, imagen_url) 
                  VALUES (:nombre, :tipo, :precio_hora, :descripcion, :imagen_url)";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':nombre' => $nombre,
            ':tipo'   => $tipo,
            ':precio_hora' => $precio_hora,
            ':descripcion' => $descripcion,
            ':imagen_url' => $imagen_url
        ]);
    }


    //Eliminar instalación por ID (Para admin)
    public function eliminarPorId($instalacion_id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
             ':id'     => $instalacion_id
         ]);  
    }

    //Modificar instalación por ID (Para admin)
    public function modificarInstalacion($instalacion_id, $nombre, $tipo, $precio_hora, $descripcion,$imagen_url) {
        $query = "UPDATE " . $this->table . " SET nombre = :nombre, tipo = :tipo, precio_hora = :precio_hora, descripcion = :descripcion, imagen_url = :imagen_url 
                  WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            
            ':nombre' => $nombre,
            ':tipo'   => $tipo,
            ':precio_hora' => $precio_hora,
            ':descripcion' => $descripcion,
            ':imagen_url' => $imagen_url,
            ':id'     => $instalacion_id
        ]);
    }

    // Obtener instalación por ID
    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

}