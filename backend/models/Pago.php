<?php
require_once 'Database.php';

class Pago
{
    private $db;
    private $table = 'pagos';

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Registrar un pago asociado a una reserva
     * @param int $reserva_id ID de la reserva
     * @param float $importe Cantidad pagada
     * @param string $metodo_pago Método: tarjeta_credito, paypal, etc.
     * @param string $estado Estado: completado, pendiente, fallido
     * @return array Resultado de la operación
     */
    public function registrar($reserva_id, $importe, $metodo_pago = 'tarjeta_credito', $estado = 'completado')
    {
        // Generar referencia única simulada (en producción vendría del gateway de pago)
        $referencia = 'TXN-' . strtoupper(uniqid());
        
        $query = "INSERT INTO " . $this->table . " 
                  (reserva_id, importe, metodo_pago, estado, referencia_transaccion) 
                  VALUES (:reserva_id, :importe, :metodo, :estado, :ref)";
        
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute([
            ':reserva_id' => $reserva_id,
            ':importe' => $importe,
            ':metodo' => $metodo_pago,
            ':estado' => $estado,
            ':ref' => $referencia
        ])) {
            return [
                "status" => "success", 
                "message" => "Pago registrado correctamente",
                "referencia" => $referencia
            ];
        }
        
        return ["status" => "error", "message" => "Error al registrar el pago"];
    }

    /**
     * Obtener el pago de una reserva específica
     * @param int $reserva_id
     * @return array|false
     */
    public function obtenerPorReserva($reserva_id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE reserva_id = :reserva_id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':reserva_id' => $reserva_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar el estado de un pago (por ejemplo, para reembolsos)
     * @param int $pago_id
     * @param string $nuevo_estado
     * @return bool
     */
    public function actualizarEstado($pago_id, $nuevo_estado)
    {
        $query = "UPDATE " . $this->table . " SET estado = :estado WHERE id = :id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':estado' => $nuevo_estado, ':id' => $pago_id]);
    }

    /**
     * Reembolsar pago de una reserva
     * @param int $reserva_id
     * @return bool
     */
    public function reembolsarPorReserva($reserva_id)
    {
        $query = "UPDATE " . $this->table . " SET estado = 'reembolsado' WHERE reserva_id = :reserva_id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':reserva_id' => $reserva_id]);
    }

    /**
     * Obtener estadísticas de ingresos (excluyendo reembolsos)
     * @return array
     */
    public function obtenerEstadisticas()
    {
        try {
            // Ingresos totales
            $queryTotal = "SELECT COALESCE(SUM(importe), 0) as total 
                           FROM " . $this->table . " 
                           WHERE estado = 'completado'";
            $stmtTotal = $this->db->prepare($queryTotal);
            $stmtTotal->execute();
            $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

            // Ingresos del mes actual
            $queryMes = "SELECT COALESCE(SUM(importe), 0) as mes_actual 
                         FROM " . $this->table . " 
                         WHERE estado = 'completado' 
                         AND YEAR(fecha_pago) = YEAR(CURDATE()) 
                         AND MONTH(fecha_pago) = MONTH(CURDATE())";
            $stmtMes = $this->db->prepare($queryMes);
            $stmtMes->execute();
            $mesActual = $stmtMes->fetch(PDO::FETCH_ASSOC)['mes_actual'] ?? 0;

            // Ingresos de hoy
            $queryHoy = "SELECT COALESCE(SUM(importe), 0) as hoy 
                         FROM " . $this->table . " 
                         WHERE estado = 'completado' 
                         AND DATE(fecha_pago) = CURDATE()";
            $stmtHoy = $this->db->prepare($queryHoy);
            $stmtHoy->execute();
            $hoy = $stmtHoy->fetch(PDO::FETCH_ASSOC)['hoy'] ?? 0;

            // Ingresos por mes (últimos 6 meses)
            $queryMeses = "SELECT 
                            DATE_FORMAT(fecha_pago, '%Y-%m') as mes,
                            COALESCE(SUM(importe), 0) as total
                           FROM " . $this->table . "
                           WHERE estado = 'completado'
                           AND fecha_pago >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                           GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m')
                           ORDER BY mes DESC";
            $stmtMeses = $this->db->prepare($queryMeses);
            $stmtMeses->execute();
            $porMesRaw = $stmtMeses->fetchAll(PDO::FETCH_ASSOC) ?? [];
            
            // Formatear nombres de meses en PHP
            $porMes = [];
            foreach ($porMesRaw as $row) {
                $d = \DateTime::createFromFormat('Y-m', $row['mes']);
                $row['mes_nombre'] = $d ? $d->format('F Y') : $row['mes'];
                $porMes[] = $row;
            }

            // Ingresos por instalación
            $queryInstalaciones = "SELECT 
                                    i.nombre,
                                    COALESCE(SUM(p.importe), 0) as total,
                                    COUNT(p.id) as cantidad_reservas
                                   FROM " . $this->table . " p
                                   JOIN reservas r ON p.reserva_id = r.id
                                   JOIN instalaciones i ON r.instalacion_id = i.id
                                   WHERE p.estado = 'completado'
                                   GROUP BY i.id, i.nombre
                                   ORDER BY total DESC";
            $stmtInstalaciones = $this->db->prepare($queryInstalaciones);
            $stmtInstalaciones->execute();
            $porInstalacion = $stmtInstalaciones->fetchAll(PDO::FETCH_ASSOC) ?? [];

            // Total de reembolsos
            $queryReembolsos = "SELECT COALESCE(SUM(importe), 0) as total_reembolsado 
                                FROM " . $this->table . " 
                                WHERE estado = 'reembolsado'";
            $stmtReembolsos = $this->db->prepare($queryReembolsos);
            $stmtReembolsos->execute();
            $totalReembolsado = $stmtReembolsos->fetch(PDO::FETCH_ASSOC)['total_reembolsado'] ?? 0;

            return [
                'total' => floatval($total),
                'mes_actual' => floatval($mesActual),
                'hoy' => floatval($hoy),
                'por_mes' => $porMes,
                'por_instalacion' => $porInstalacion,
                'total_reembolsado' => floatval($totalReembolsado)
            ];
        } catch (Exception $e) {
            return [
                'error' => true,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Listar todos los pagos (para admin)
     * @return array
     */
    public function listarTodos()
    {
        $query = "SELECT p.*, r.fecha, r.hora_inicio, u.nombre as usuario_nombre, i.nombre as instalacion_nombre
                  FROM " . $this->table . " p
                  JOIN reservas r ON p.reserva_id = r.id
                  JOIN usuarios u ON r.usuario_id = u.id
                  JOIN instalaciones i ON r.instalacion_id = i.id
                  ORDER BY p.fecha_pago DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contar total de pagos
     * @return int
     */
    public function contarTodos()
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}
