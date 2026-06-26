-- Backup sistema_core
-- Fecha 2026-06-23T11:45:31+00:00

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `contactos`;
CREATE TABLE `contactos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contactos_telefono_index` (`telefono`),
  KEY `contactos_documento_index` (`documento`),
  KEY `contactos_nombre_index` (`nombre`),
  KEY `contactos_apellido_index` (`apellido`),
  KEY `contactos_activo_index` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `contactos` (`id`, `nombre`, `apellido`, `telefono`, `correo`, `documento`, `direccion`, `observaciones`, `activo`, `created_at`, `updated_at`) VALUES ('1', 'Cliente', 'Prueba', '0981000000', NULL, NULL, NULL, 'Migracion legacy reparaciones', '1', '2026-06-22 11:35:42', '2026-06-22 11:35:42');
INSERT INTO `contactos` (`id`, `nombre`, `apellido`, `telefono`, `correo`, `documento`, `direccion`, `observaciones`, `activo`, `created_at`, `updated_at`) VALUES ('2', 'Franco', 'Feling', '3743 556666', NULL, NULL, NULL, 'Migracion legacy reparaciones', '1', '2026-06-22 11:35:42', '2026-06-22 11:35:42');
INSERT INTO `contactos` (`id`, `nombre`, `apellido`, `telefono`, `correo`, `documento`, `direccion`, `observaciones`, `activo`, `created_at`, `updated_at`) VALUES ('3', 'Franco', NULL, '3743-559415', NULL, NULL, NULL, 'Migracion legacy reparaciones', '1', '2026-06-22 11:35:42', '2026-06-22 11:35:42');
INSERT INTO `contactos` (`id`, `nombre`, `apellido`, `telefono`, `correo`, `documento`, `direccion`, `observaciones`, `activo`, `created_at`, `updated_at`) VALUES ('4', 'Dani', NULL, NULL, NULL, NULL, NULL, 'Migracion legacy reparaciones', '1', '2026-06-22 11:35:42', '2026-06-22 11:35:42');

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES ('2', '2026_06_20_000001_create_core_contactos_table', '1');


SET FOREIGN_KEY_CHECKS=1;
