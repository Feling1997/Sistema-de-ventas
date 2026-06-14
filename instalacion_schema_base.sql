
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sistema_ventas` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `sistema_ventas`;
DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `tipo_documento` varchar(20) NOT NULL DEFAULT 'DNI',
  `condicion_iva` varchar(40) NOT NULL DEFAULT 'Consumidor Final',
  `email` varchar(120) DEFAULT NULL,
  `id_lista_precio` int(11) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cliente_dni` (`dni`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `clientes` (`id`, `nombre`, `dni`, `tipo_documento`, `condicion_iva`, `email`, `id_lista_precio`, `telefono`, `direccion`)
VALUES (1, 'Consumidor Final', NULL, 'DNI', 'Consumidor Final', NULL, NULL, NULL, NULL)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `condicion_iva` = VALUES(`condicion_iva`);
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuraciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuraciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(120) NOT NULL,
  `valor` longtext DEFAULT NULL,
  `tipo` varchar(40) NOT NULL DEFAULT 'texto',
  `grupo` varchar(60) NOT NULL DEFAULT 'sistema',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_configuraciones_clave` (`clave`),
  KEY `idx_configuraciones_grupo` (`grupo`)
) ENGINE=InnoDB AUTO_INCREMENT=482 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_corrientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuentas_corrientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `id_venta` int(11) DEFAULT NULL,
  `concepto` varchar(180) NOT NULL,
  `total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `saldo` decimal(14,2) NOT NULL DEFAULT 0.00,
  `estado` varchar(20) NOT NULL DEFAULT 'ABIERTA',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cc_cliente` (`id_cliente`),
  KEY `idx_cc_saldo` (`saldo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_corrientes_alertas_lecturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuentas_corrientes_alertas_lecturas` (
  `id_usuario` int(11) NOT NULL,
  `leido_hasta` date NOT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_corrientes_cuotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuentas_corrientes_cuotas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cuenta` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `vencimiento` date NOT NULL,
  `monto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `pagado` decimal(14,2) NOT NULL DEFAULT 0.00,
  `estado` varchar(20) NOT NULL DEFAULT 'PENDIENTE',
  `pagado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cc_cuota_vto` (`vencimiento`),
  KEY `idx_cc_cuota_cuenta` (`id_cuenta`),
  KEY `idx_cc_cuota_estado_vto` (`estado`,`vencimiento`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_corrientes_recibos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cuentas_corrientes_recibos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cuenta` int(11) DEFAULT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `tipo` varchar(20) NOT NULL DEFAULT 'PAGO_CUENTA',
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `monto` decimal(14,2) NOT NULL DEFAULT 0.00,
  `forma_pago` varchar(40) NOT NULL DEFAULT 'contado',
  `observacion` varchar(220) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_cc_recibo_cuenta` (`id_cuenta`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_presupuesto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_presupuesto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_presupuesto` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `producto_nombre` varchar(150) NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `precio_unit` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_detalle_presupuesto` (`id_presupuesto`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `detalle_venta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_venta` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` decimal(12,3) NOT NULL,
  `precio_unit` decimal(12,2) NOT NULL,
  `costo_unit` decimal(14,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalle_venta` (`id_venta`),
  KEY `fk_detalle_producto` (`id_producto`),
  KEY `idx_detalle_producto_venta` (`id_producto`,`id_venta`),
  CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fiscal_cola`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_cola` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_comprobante` int(11) NOT NULL,
  `estado` enum('PENDIENTE','EN_PROCESO','FINALIZADO','ERROR') NOT NULL DEFAULT 'PENDIENTE',
  `intentos` int(11) NOT NULL DEFAULT 0,
  `ultimo_error` text DEFAULT NULL,
  `proximo_intento` datetime DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fiscal_cola_estado` (`estado`,`proximo_intento`),
  KEY `fk_fiscal_cola_comprobante` (`id_comprobante`),
  CONSTRAINT `fk_fiscal_cola_comprobante` FOREIGN KEY (`id_comprobante`) REFERENCES `fiscal_comprobantes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fiscal_comprobantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fiscal_comprobantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_venta` int(11) NOT NULL,
  `tipo_operacion` enum('factura','presupuesto') NOT NULL DEFAULT 'factura',
  `estado` enum('PENDIENTE','EN_PROCESO','APROBADO','RECHAZADO','ERROR') NOT NULL DEFAULT 'PENDIENTE',
  `proveedor` varchar(30) NOT NULL DEFAULT 'api_rest',
  `punto_venta` int(11) DEFAULT NULL,
  `tipo_comprobante` int(11) DEFAULT NULL,
  `numero_comprobante` bigint(20) DEFAULT NULL,
  `cae` varchar(30) DEFAULT NULL,
  `cae_vencimiento` date DEFAULT NULL,
  `payload_json` longtext NOT NULL,
  `respuesta_json` longtext DEFAULT NULL,
  `ultimo_error` text DEFAULT NULL,
  `intentos` int(11) NOT NULL DEFAULT 0,
  `proximo_intento` datetime DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fiscal_comprobantes_venta` (`id_venta`),
  KEY `idx_fiscal_estado` (`estado`),
  CONSTRAINT `fk_fiscal_comprobantes_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historial_precios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historial_precios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `id_lista` int(11) NOT NULL,
  `precio_anterior` decimal(14,2) NOT NULL DEFAULT 0.00,
  `precio_nuevo` decimal(14,2) NOT NULL DEFAULT 0.00,
  `origen` varchar(40) NOT NULL DEFAULT 'manual',
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_historial_precios_fecha` (`creado_en`),
  KEY `idx_historial_precios_lista` (`id_lista`),
  KEY `idx_historial_precios_producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=3005 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `listas_precios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `listas_precios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `presupuestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `presupuestos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` varchar(20) NOT NULL DEFAULT 'ABIERTO',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_presupuestos_cliente` (`id_cliente`),
  KEY `idx_presupuestos_fecha` (`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `producto_precios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `producto_precios` (
  `id_producto` int(11) NOT NULL,
  `id_lista` int(11) NOT NULL,
  `porcentaje` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `precio` decimal(14,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id_producto`,`id_lista`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `cod_barras` varchar(80) NOT NULL,
  `id_stock` int(11) NOT NULL,
  `id_asociado` int(11) DEFAULT NULL,
  `factor_conversion` decimal(12,4) NOT NULL DEFAULT 1.0000,
  `ganancia` decimal(12,2) NOT NULL DEFAULT 0.00,
  `precio_final` decimal(12,2) NOT NULL DEFAULT 0.00,
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cod_barras` (`cod_barras`),
  KEY `fk_producto_stock` (`id_stock`),
  KEY `fk_producto_asociado` (`id_asociado`),
  KEY `idx_productos_activo_nombre` (`activo`,`nombre`),
  KEY `idx_productos_activo_id` (`activo`,`id`),
  KEY `idx_productos_stock_activo` (`id_stock`,`activo`),
  CONSTRAINT `fk_producto_asociado` FOREIGN KEY (`id_asociado`) REFERENCES `stock` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_producto_stock` FOREIGN KEY (`id_stock`) REFERENCES `stock` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_productos_stock` FOREIGN KEY (`id_stock`) REFERENCES `stock` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reparaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reparaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `cliente_nombre` varchar(150) NOT NULL,
  `cliente_telefono` varchar(30) DEFAULT '',
  `marca` varchar(50) DEFAULT '',
  `modelo` varchar(50) DEFAULT '',
  `imei` varchar(30) DEFAULT '',
  `falla` text DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `garantia` varchar(100) DEFAULT '',
  `estado` enum('PENDIENTE','EN_REPARACION','ESP_REPUESTOS','REPARADO','ENTREGADO','CANCELADO') DEFAULT 'PENDIENTE',
  `precio` decimal(10,2) DEFAULT 0.00,
  `fecha_ingreso` date NOT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `id_usuario` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_estado` (`estado`),
  KEY `idx_codigo` (`codigo`),
  KEY `idx_fecha_ingreso` (`fecha_ingreso`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `unidad` varchar(20) NOT NULL DEFAULT 'u',
  `tipo_stock` varchar(20) NOT NULL DEFAULT 'general',
  `cantidad` decimal(12,3) NOT NULL DEFAULT 0.000,
  `stock_minimo` decimal(14,3) NOT NULL DEFAULT 0.000,
  `stock_maximo` decimal(14,3) NOT NULL DEFAULT 0.000,
  `precio_costo` decimal(12,2) NOT NULL DEFAULT 0.00,
  `moneda_costo` varchar(3) NOT NULL DEFAULT 'ARS',
  `costo_origen` decimal(14,4) NOT NULL DEFAULT 0.0000,
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stock_alerta_menu` (`activo`,`tipo_stock`,`cantidad`,`stock_minimo`)
) ENGINE=InnoDB AUTO_INCREMENT=1199 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stock_alertas_leidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_alertas_leidas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_producto` int(11) NOT NULL,
  `fecha_lectura` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario` int(11) NOT NULL DEFAULT 0,
  `cantidad_leida` decimal(14,3) NOT NULL DEFAULT 0.000,
  `stock_minimo_leido` decimal(14,3) NOT NULL DEFAULT 0.000,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_alerta_producto_usuario` (`id_producto`,`usuario`),
  KEY `idx_stock_alertas_producto` (`id_producto`),
  KEY `idx_stock_alertas_usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `unidades_medida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unidades_medida` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `abreviatura` varchar(20) NOT NULL,
  `tipo` varchar(30) NOT NULL DEFAULT 'cantidad',
  `decimales` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unidades_abreviatura` (`abreviatura`)
) ENGINE=InnoDB AUTO_INCREMENT=3211 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `rol` enum('ADMIN','VENDEDOR') NOT NULL DEFAULT 'VENDEDOR',
  `activo` tinyint(4) NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `permisos` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `usuarios` (`id`, `usuario`, `clave`, `rol`, `activo`)
VALUES (0, 'Sin login', '', 'ADMIN', 1)
ON DUPLICATE KEY UPDATE `usuario` = VALUES(`usuario`), `rol` = VALUES(`rol`), `activo` = VALUES(`activo`);
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ventas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ventas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT current_timestamp(),
  `id_cliente` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_venta_cliente` (`id_cliente`),
  KEY `fk_venta_usuario` (`id_usuario`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
