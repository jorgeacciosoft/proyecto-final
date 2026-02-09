-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.0.45 - MySQL Community Server - GPL
-- SO del servidor:              Linux
-- HeidiSQL Versión:             12.15.0.7171
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- -- Volcando estructura de base de datos para todo_reservas
CREATE DATABASE IF NOT EXISTS `todo_reservas` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
 USE `todo_reservas`;

-- Volcando estructura para tabla todo_reservas.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla todo_reservas.usuarios: ~4 rows (aproximadamente)
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `created_at`) VALUES
	(1, 'Administrador', 'admin@test.com', '$2y$10$8KTMY8LzD.fG2y6ZqI5uOuWpTzN9vB.k7P1vGzD8Q7T5n5B.W5W', 'admin', '2026-02-06 07:55:48'),
	(2, 'Jorge Rodriguez Luiz', 'jorge@gmail.com', '$2y$10$C1RoURY6FLreYIASd8LlIeoOvTMLrebEEUM9YX2BTiot.wa0fbmJu', 'admin', '2026-02-06 08:05:10'),
	(3, 'Elias Salmerón', 'elias@gmail.com', '$2y$10$53qmKeZNgVHp7t3.PTbuLOpU9saHy1eEHchv3q3oxxW.Ik1Gg3nYC', 'user', '2026-02-09 08:01:29'),
	(4, 'Maria Pomares', 'maria@gmail.com', '$2y$10$ceQ4sYXiyKn90jPd0JCMOu/nwY.aNcqtyu2ExqOz36yDfa1J1qBHS', 'user', '2026-02-09 08:11:43');

-- Volcando estructura para tabla todo_reservas.instalaciones
CREATE TABLE IF NOT EXISTS `instalaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `precio_hora` decimal(5,2) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla todo_reservas.instalaciones: ~3 rows (aproximadamente)
INSERT INTO `instalaciones` (`id`, `nombre`, `tipo`, `precio_hora`, `descripcion`, `imagen_url`) VALUES
	(1, 'Pista Central', 'Padel', 12.50, 'Pista de Pijo Padel', '1770365825_pijo-padel.jpeg'),
	(2, 'Pista Azul', 'Padel', 15.00, 'Pista de Open World Tour Padel', '1770366001_Pista-de-padel-Panoramic-cesped-azul.jpg'),
	(3, 'Campo Municipal', 'Fútbol', 30.00, 'Campo municipal de Aguadulce', 'null');


-- Volcando estructura para tabla todo_reservas.reservas
CREATE TABLE IF NOT EXISTS `reservas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `instalacion_id` int NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `estado` enum('confirmada','cancelada') DEFAULT 'confirmada',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `instalacion_id` (`instalacion_id`),
  CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`instalacion_id`) REFERENCES `instalaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla todo_reservas.reservas: ~0 rows (aproximadamente)
INSERT INTO `reservas` (`id`, `usuario_id`, `instalacion_id`, `fecha`, `hora_inicio`, `estado`) VALUES
	(1, 2, 3, '2026-02-13', '10:00:00', 'cancelada'),
	(2, 2, 3, '2026-02-20', '18:00:00', 'cancelada'),
	(3, 2, 3, '2026-02-11', '14:00:00', 'confirmada'),
	(4, 2, 3, '2026-02-21', '19:00:00', 'cancelada'),
	(5, 3, 2, '2026-02-19', '19:00:00', 'confirmada'),
	(6, 4, 1, '2026-02-20', '21:00:00', 'confirmada'),
	(7, 2, 3, '2026-02-28', '20:00:00', 'cancelada');



-- Volcando estructura para tabla todo_reservas.pagos
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reserva_id` int NOT NULL,
  `importe` decimal(6,2) NOT NULL,
  `metodo_pago` varchar(30) NOT NULL DEFAULT 'tarjeta_credito',
  `estado` enum('pendiente','completado','fallido','reembolsado') DEFAULT 'completado',
  `referencia_transaccion` varchar(100) DEFAULT NULL,
  `fecha_pago` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reserva` (`reserva_id`),
  KEY `idx_estado` (`estado`),
  CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Volcando datos para la tabla todo_reservas.pagos: ~0 rows (aproximadamente)
INSERT INTO `pagos` (`id`, `reserva_id`, `importe`, `metodo_pago`, `estado`, `referencia_transaccion`, `fecha_pago`) VALUES
	(1, 3, 30.00, 'tarjeta_credito', 'completado', 'TXN-6989915838682', '2026-02-09 07:48:40'),
	(2, 4, 30.00, 'tarjeta_credito', 'completado', 'TXN-698991D8DF8C7', '2026-02-09 07:50:48'),
	(3, 5, 15.00, 'tarjeta_credito', 'completado', 'TXN-6989947CB4D1A', '2026-02-09 08:02:04'),
	(4, 6, 12.50, 'tarjeta_credito', 'completado', 'TXN-698997F468179', '2026-02-09 08:16:52'),
	(5, 7, 30.00, 'tarjeta_credito', 'reembolsado', 'TXN-6989984F7CBA6', '2026-02-09 08:18:23');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
