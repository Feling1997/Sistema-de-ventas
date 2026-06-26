-- Backup sistema_reparaciones
-- Fecha 2026-06-23T11:45:31+00:00

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `configuracion_reparaciones`;
CREATE TABLE `configuracion_reparaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `clave` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `tipo` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `grupo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `configuracion_reparaciones_clave_unique` (`clave`),
  KEY `configuracion_reparaciones_grupo_index` (`grupo`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `configuracion_reparaciones` (`id`, `clave`, `valor`, `tipo`, `grupo`, `created_at`, `updated_at`) VALUES ('1', 'nombre_comercio', 'Taller Laravel 33K', 'string', 'tickets', '2026-06-22 12:16:27', '2026-06-22 12:50:22');
INSERT INTO `configuracion_reparaciones` (`id`, `clave`, `valor`, `tipo`, `grupo`, `created_at`, `updated_at`) VALUES ('2', 'telefono_comercio', '', 'string', 'tickets', '2026-06-22 12:16:27', '2026-06-22 12:16:27');
INSERT INTO `configuracion_reparaciones` (`id`, `clave`, `valor`, `tipo`, `grupo`, `created_at`, `updated_at`) VALUES ('3', 'direccion_comercio', '', 'string', 'tickets', '2026-06-22 12:16:27', '2026-06-22 12:16:27');
INSERT INTO `configuracion_reparaciones` (`id`, `clave`, `valor`, `tipo`, `grupo`, `created_at`, `updated_at`) VALUES ('4', 'impresora_predeterminada', '', 'string', 'tickets', '2026-06-22 12:16:27', '2026-06-22 12:16:27');
INSERT INTO `configuracion_reparaciones` (`id`, `clave`, `valor`, `tipo`, `grupo`, `created_at`, `updated_at`) VALUES ('5', 'mostrar_logo', '0', 'boolean', 'tickets', '2026-06-22 12:16:27', '2026-06-22 12:16:27');
INSERT INTO `configuracion_reparaciones` (`id`, `clave`, `valor`, `tipo`, `grupo`, `created_at`, `updated_at`) VALUES ('6', 'texto_ticket', 'Gracias por su visita', 'string', 'tickets', '2026-06-22 12:16:27', '2026-06-22 12:16:27');
INSERT INTO `configuracion_reparaciones` (`id`, `clave`, `valor`, `tipo`, `grupo`, `created_at`, `updated_at`) VALUES ('7', 'observaciones_ticket', 'Convivencia hibrida 33K', 'string', 'tickets', '2026-06-22 12:16:27', '2026-06-22 12:50:22');

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('1', '2026_06_21_000001_create_reparaciones_estados_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('2', '2026_06_21_000002_create_reparaciones_equipos_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('3', '2026_06_21_000003_create_reparaciones_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('4', '2026_06_21_000004_create_reparaciones_adjuntos_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('5', '2026_06_21_000005_create_reparaciones_tickets_table', '1');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('6', '2026_06_22_000001_create_configuracion_reparaciones_table', '2');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('7', '2026_06_22_000002_create_reparaciones_auditoria_table', '3');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('8', '2026_06_22_000004_add_miniatura_to_reparaciones_adjuntos_table', '4');
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('9', '2026_06_22_000003_add_production_indexes_reparaciones', '4');

DROP TABLE IF EXISTS `reparaciones`;
CREATE TABLE `reparaciones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacto_id` bigint unsigned NOT NULL,
  `equipo_id` bigint unsigned DEFAULT NULL,
  `estado_id` bigint unsigned DEFAULT NULL,
  `problema` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `diagnostico` text COLLATE utf8mb4_unicode_ci,
  `garantia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio` decimal(15,2) NOT NULL DEFAULT '0.00',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `fecha_ingreso` timestamp NULL DEFAULT NULL,
  `fecha_entrega` timestamp NULL DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reparaciones_codigo_unique` (`codigo`),
  KEY `reparaciones_contacto_id_index` (`contacto_id`),
  KEY `reparaciones_equipo_id_index` (`equipo_id`),
  KEY `reparaciones_estado_id_index` (`estado_id`),
  KEY `reparaciones_fecha_ingreso_index` (`fecha_ingreso`),
  KEY `reparaciones_activo_index` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reparaciones` (`id`, `codigo`, `contacto_id`, `equipo_id`, `estado_id`, `problema`, `diagnostico`, `garantia`, `precio`, `observaciones`, `fecha_ingreso`, `fecha_entrega`, `activo`, `created_at`, `updated_at`) VALUES ('1', 'REP-20260504-5516', '1', '1', '1', 'No carga', 'Pin de carga danado', '30 dias', '150000.00', 'Prueba automatica', '2026-05-04 00:00:00', '2026-05-04 00:00:00', '0', '2026-06-22 11:45:05', '2026-06-22 11:45:05');
INSERT INTO `reparaciones` (`id`, `codigo`, `contacto_id`, `equipo_id`, `estado_id`, `problema`, `diagnostico`, `garantia`, `precio`, `observaciones`, `fecha_ingreso`, `fecha_entrega`, `activo`, `created_at`, `updated_at`) VALUES ('2', 'REP-20260504-2760', '2', '2', '1', 'No anda la pantalla', 'Cambio de modulo', NULL, '0.00', NULL, '2026-05-04 00:00:00', NULL, '1', '2026-06-22 11:45:05', '2026-06-22 11:45:05');
INSERT INTO `reparaciones` (`id`, `codigo`, `contacto_id`, `equipo_id`, `estado_id`, `problema`, `diagnostico`, `garantia`, `precio`, `observaciones`, `fecha_ingreso`, `fecha_entrega`, `activo`, `created_at`, `updated_at`) VALUES ('3', 'REP-20260515-7683', '3', NULL, '1', 'Sin falla registrada', NULL, NULL, '0.00', NULL, '2026-05-15 00:00:00', NULL, '1', '2026-06-22 11:45:05', '2026-06-22 11:45:05');
INSERT INTO `reparaciones` (`id`, `codigo`, `contacto_id`, `equipo_id`, `estado_id`, `problema`, `diagnostico`, `garantia`, `precio`, `observaciones`, `fecha_ingreso`, `fecha_entrega`, `activo`, `created_at`, `updated_at`) VALUES ('4', 'REP-20260515-7297', '4', NULL, '1', 'Sin falla registrada', NULL, NULL, '0.00', NULL, '2026-05-15 00:00:00', NULL, '1', '2026-06-22 11:45:05', '2026-06-22 11:45:05');
INSERT INTO `reparaciones` (`id`, `codigo`, `contacto_id`, `equipo_id`, `estado_id`, `problema`, `diagnostico`, `garantia`, `precio`, `observaciones`, `fecha_ingreso`, `fecha_entrega`, `activo`, `created_at`, `updated_at`) VALUES ('5', 'REP-20260526-4911', '3', NULL, '1', 'Sin falla registrada', NULL, NULL, '0.00', NULL, '2026-05-26 00:00:00', NULL, '1', '2026-06-22 11:45:05', '2026-06-22 11:45:05');
INSERT INTO `reparaciones` (`id`, `codigo`, `contacto_id`, `equipo_id`, `estado_id`, `problema`, `diagnostico`, `garantia`, `precio`, `observaciones`, `fecha_ingreso`, `fecha_entrega`, `activo`, `created_at`, `updated_at`) VALUES ('6', 'REP-20260622-120441-3677', '1', NULL, '1', 'Prueba CRUD Laravel actualizada', 'Diagnostico test', '7 dias', '54321.00', 'Actualizada por 33H', '2026-06-22 00:00:00', NULL, '0', '2026-06-22 12:04:41', '2026-06-22 12:04:42');
INSERT INTO `reparaciones` (`id`, `codigo`, `contacto_id`, `equipo_id`, `estado_id`, `problema`, `diagnostico`, `garantia`, `precio`, `observaciones`, `fecha_ingreso`, `fecha_entrega`, `activo`, `created_at`, `updated_at`) VALUES ('7', 'REP-20260622-123012-5157', '3', '2', '1', 'Prueba controlada 33J con equipo existente', 'Diagnostico inicial 33J', 'Sin garantia', '0.00', 'Registro controlado 33J', '2026-06-22 09:30:12', NULL, '1', '2026-06-22 12:30:12', '2026-06-22 12:30:12');
INSERT INTO `reparaciones` (`id`, `codigo`, `contacto_id`, `equipo_id`, `estado_id`, `problema`, `diagnostico`, `garantia`, `precio`, `observaciones`, `fecha_ingreso`, `fecha_entrega`, `activo`, `created_at`, `updated_at`) VALUES ('8', 'REP-20260622-123013-4468', '3', '4', '2', 'Prueba controlada 33J editada', 'Diagnostico editado 33J', 'Controlada', '1500.75', 'Editada y estado cambiado en 33J', '2026-06-22 09:30:13', NULL, '1', '2026-06-22 12:30:13', '2026-06-22 12:30:13');

DROP TABLE IF EXISTS `reparaciones_adjuntos`;
CREATE TABLE `reparaciones_adjuntos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reparacion_id` bigint unsigned NOT NULL,
  `nombre` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `miniatura` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tamano` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reparaciones_adjuntos_reparacion_id_index` (`reparacion_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `reparaciones_auditoria`;
CREATE TABLE `reparaciones_auditoria` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `accion` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reparacion_id` bigint unsigned DEFAULT NULL,
  `tiempo_ms` int unsigned NOT NULL DEFAULT '0',
  `resultado` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ok',
  `severidad` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bajo',
  `mensaje` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reparaciones_auditoria_accion_index` (`accion`),
  KEY `reparaciones_auditoria_resultado_index` (`resultado`),
  KEY `reparaciones_auditoria_severidad_index` (`severidad`),
  KEY `reparaciones_auditoria_reparacion_id_index` (`reparacion_id`),
  KEY `reparaciones_auditoria_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('1', 'buscar', NULL, NULL, '94', 'ok', 'bajo', NULL, '2026-06-22 12:50:21', '2026-06-22 12:50:21');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('2', 'buscar_contacto', NULL, NULL, '32', 'ok', 'bajo', NULL, '2026-06-22 12:50:21', '2026-06-22 12:50:21');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('3', 'buscar_equipo', NULL, NULL, '24', 'ok', 'bajo', NULL, '2026-06-22 12:50:21', '2026-06-22 12:50:21');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('4', 'ticket', NULL, '1', '64', 'ok', 'bajo', NULL, '2026-06-22 12:50:22', '2026-06-22 12:50:22');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('5', 'configuracion', NULL, NULL, '38', 'ok', 'bajo', NULL, '2026-06-22 12:50:22', '2026-06-22 12:50:22');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('6', 'buscar', NULL, NULL, '76', 'ok', 'bajo', NULL, '2026-06-22 13:01:42', '2026-06-22 13:01:42');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('7', 'buscar_equipo', NULL, NULL, '45', 'ok', 'bajo', NULL, '2026-06-22 13:01:42', '2026-06-22 13:01:42');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('8', 'buscar_contacto', NULL, NULL, '39', 'ok', 'bajo', NULL, '2026-06-22 13:01:43', '2026-06-22 13:01:43');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('9', 'ticket', NULL, '1', '65', 'ok', 'bajo', NULL, '2026-06-22 13:01:45', '2026-06-22 13:01:45');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('10', 'buscar', NULL, NULL, '68', 'ok', 'bajo', NULL, '2026-06-23 11:41:08', '2026-06-23 11:41:08');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('11', 'buscar_contacto', NULL, NULL, '13', 'ok', 'bajo', NULL, '2026-06-23 11:41:08', '2026-06-23 11:41:08');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('12', 'buscar_equipo', NULL, NULL, '26', 'ok', 'bajo', NULL, '2026-06-23 11:41:09', '2026-06-23 11:41:09');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('13', 'buscar', NULL, NULL, '61', 'ok', 'bajo', NULL, '2026-06-23 11:42:32', '2026-06-23 11:42:32');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('14', 'buscar_contacto', NULL, NULL, '14', 'ok', 'bajo', NULL, '2026-06-23 11:42:32', '2026-06-23 11:42:32');
INSERT INTO `reparaciones_auditoria` (`id`, `accion`, `usuario`, `reparacion_id`, `tiempo_ms`, `resultado`, `severidad`, `mensaje`, `created_at`, `updated_at`) VALUES ('15', 'buscar_equipo', NULL, NULL, '25', 'ok', 'bajo', NULL, '2026-06-23 11:42:32', '2026-06-23 11:42:32');

DROP TABLE IF EXISTS `reparaciones_equipos`;
CREATE TABLE `reparaciones_equipos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contacto_id` bigint unsigned NOT NULL,
  `tipo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marca` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modelo` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serie` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reparaciones_equipos_contacto_id_index` (`contacto_id`),
  KEY `reparaciones_equipos_serie_index` (`serie`),
  KEY `reparaciones_equipos_marca_index` (`marca`),
  KEY `reparaciones_equipos_modelo_index` (`modelo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reparaciones_equipos` (`id`, `contacto_id`, `tipo`, `marca`, `modelo`, `serie`, `observaciones`, `created_at`, `updated_at`) VALUES ('1', '1', 'Telefono', 'Samsung', 'A32', '123456789012345', NULL, '2026-06-22 11:45:05', '2026-06-22 11:45:05');
INSERT INTO `reparaciones_equipos` (`id`, `contacto_id`, `tipo`, `marca`, `modelo`, `serie`, `observaciones`, `created_at`, `updated_at`) VALUES ('2', '2', 'Telefono', 'Samsung', 'A54', NULL, NULL, '2026-06-22 11:45:05', '2026-06-22 11:45:05');
INSERT INTO `reparaciones_equipos` (`id`, `contacto_id`, `tipo`, `marca`, `modelo`, `serie`, `observaciones`, `created_at`, `updated_at`) VALUES ('3', '1', 'Telefono', 'Motorola', 'G Test 2', 'SERIE-33H-2', 'Equipo prueba 33H', '2026-06-22 12:05:40', '2026-06-22 12:05:40');
INSERT INTO `reparaciones_equipos` (`id`, `contacto_id`, `tipo`, `marca`, `modelo`, `serie`, `observaciones`, `created_at`, `updated_at`) VALUES ('4', '3', 'Celular', 'Samsung', 'A54 33J', 'SERIE-33J-093012', 'Equipo nuevo prueba 33J', '2026-06-22 12:30:13', '2026-06-22 12:30:13');

DROP TABLE IF EXISTS `reparaciones_estados`;
CREATE TABLE `reparaciones_estados` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` smallint unsigned NOT NULL DEFAULT '0',
  `finaliza` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reparaciones_estados_nombre_index` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reparaciones_estados` (`id`, `nombre`, `orden`, `finaliza`, `activo`, `created_at`, `updated_at`) VALUES ('1', 'PENDIENTE', '0', '0', '1', '2026-06-22 11:45:05', '2026-06-22 11:45:05');
INSERT INTO `reparaciones_estados` (`id`, `nombre`, `orden`, `finaliza`, `activo`, `created_at`, `updated_at`) VALUES ('2', 'EN_REPARACION', '2', '0', '1', '2026-06-22 12:04:41', '2026-06-22 12:04:41');
INSERT INTO `reparaciones_estados` (`id`, `nombre`, `orden`, `finaliza`, `activo`, `created_at`, `updated_at`) VALUES ('3', 'REPARADO', '3', '0', '1', '2026-06-22 12:04:41', '2026-06-22 12:04:41');
INSERT INTO `reparaciones_estados` (`id`, `nombre`, `orden`, `finaliza`, `activo`, `created_at`, `updated_at`) VALUES ('4', 'ENTREGADO', '4', '1', '1', '2026-06-22 12:04:41', '2026-06-22 12:04:41');
INSERT INTO `reparaciones_estados` (`id`, `nombre`, `orden`, `finaliza`, `activo`, `created_at`, `updated_at`) VALUES ('5', 'CANCELADO', '5', '1', '1', '2026-06-22 12:04:41', '2026-06-22 12:04:41');

DROP TABLE IF EXISTS `reparaciones_tickets`;
CREATE TABLE `reparaciones_tickets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reparacion_id` bigint unsigned NOT NULL,
  `codigo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emitido_en` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reparaciones_tickets_codigo_unique` (`codigo`),
  KEY `reparaciones_tickets_reparacion_id_index` (`reparacion_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reparaciones_tickets` (`id`, `reparacion_id`, `codigo`, `emitido_en`, `created_at`, `updated_at`) VALUES ('1', '1', 'REP-20260504-5516', '2026-05-04 13:06:14', '2026-06-22 11:45:05', '2026-06-22 11:45:05');
INSERT INTO `reparaciones_tickets` (`id`, `reparacion_id`, `codigo`, `emitido_en`, `created_at`, `updated_at`) VALUES ('2', '2', 'REP-20260504-2760', '2026-05-04 13:41:06', '2026-06-22 11:45:05', '2026-06-22 11:45:05');
INSERT INTO `reparaciones_tickets` (`id`, `reparacion_id`, `codigo`, `emitido_en`, `created_at`, `updated_at`) VALUES ('3', '3', 'REP-20260515-7683', '2026-05-15 13:54:32', '2026-06-22 11:45:05', '2026-06-22 11:45:05');
INSERT INTO `reparaciones_tickets` (`id`, `reparacion_id`, `codigo`, `emitido_en`, `created_at`, `updated_at`) VALUES ('4', '4', 'REP-20260515-7297', '2026-05-15 13:55:09', '2026-06-22 11:45:05', '2026-06-22 11:45:05');
INSERT INTO `reparaciones_tickets` (`id`, `reparacion_id`, `codigo`, `emitido_en`, `created_at`, `updated_at`) VALUES ('5', '5', 'REP-20260526-4911', '2026-05-26 12:47:59', '2026-06-22 11:45:05', '2026-06-22 11:45:05');


SET FOREIGN_KEY_CHECKS=1;
