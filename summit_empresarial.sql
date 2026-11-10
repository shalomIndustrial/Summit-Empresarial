-- ===================================================
-- Base de Datos: summit_empresarial
-- Proyecto: Summit Empresarial México
-- ===================================================

-- Si ejecutas en un entorno local vacío, puedes descomentar estas dos líneas:
-- CREATE DATABASE IF NOT EXISTS `summit_empresarial` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- USE `summit_empresarial`;

-- ---------------------------------------------------
-- Estructura de la tabla: boletos
-- ---------------------------------------------------

CREATE TABLE IF NOT EXISTS `boletos` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `codigo_boleto` VARCHAR(50) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido_paterno` VARCHAR(100) NOT NULL,
  `apellido_materno` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(20) NOT NULL,
  `correo` VARCHAR(150) NOT NULL,
  `precio` DECIMAL(10,2) NOT NULL DEFAULT 500.00,
  `estado_pago` ENUM('PENDIENTE','PAGADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE',
  `usado` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_compra` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_boleto` (`codigo_boleto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------
-- Datos de prueba / iniciales (opcional)
-- ---------------------------------------------------

-- INSERT INTO `boletos` (`codigo_boleto`, `nombre`, `apellido_paterno`, `apellido_materno`, `telefono`, `correo`, `precio`, `estado_pago`, `usado`) VALUES
-- ('SUMMIT-DEMO0001', 'Juan', 'Pérez', 'García', '8440000000', 'demo@summitempresarial.com', 500.00, 'PAGADO', 0);
