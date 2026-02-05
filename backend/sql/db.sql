CREATE DATABASE IF NOT EXISTS todo_reservas;
USE todo_reservas;

-- 1. Tabla de Usuarios (Cumple con Gestión de Roles)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabla de Instalaciones
CREATE TABLE instalaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    tipo VARCHAR(30) NOT NULL, -- Tenis, Pádel, Fútbol
    precio_hora DECIMAL(5,2) NOT NULL,
    imagen_url VARCHAR(255)
) ENGINE=InnoDB;

-- 3. Tabla de Reservas (Relacional)
CREATE TABLE reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    instalacion_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    estado ENUM('confirmada', 'cancelada') DEFAULT 'confirmada',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (instalacion_id) REFERENCES instalaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Datos iniciales para pruebas
INSERT INTO instalaciones (nombre, tipo, precio_hora) VALUES 
('Pista Central', 'Tenis', 12.50),
('Pista Azul', 'Pádel', 15.00),
('Campo Municipal', 'Fútbol', 30.00);

-- Admin por defecto (password: admin123)
INSERT INTO usuarios (nombre, email, password, rol) 
VALUES ('Administrador', 'admin@test.com', '$2y$10$8KTMY8LzD.fG2y6ZqI5uOuWpTzN9vB.k7P1vGzD8Q7T5n5B.W5W', 'admin')
ON DUPLICATE KEY UPDATE nombre=nombre;