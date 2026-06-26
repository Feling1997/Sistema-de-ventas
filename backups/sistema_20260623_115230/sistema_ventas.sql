-- Backup sistema_ventas
-- Fecha 2026-06-23T11:52:30+00:00

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `clientes`;
CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_documento` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DNI',
  `condicion_iva` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Consumidor Final',
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_lista_precio` int DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cliente_dni` (`dni`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `clientes` (`id`, `nombre`, `dni`, `tipo_documento`, `condicion_iva`, `email`, `id_lista_precio`, `telefono`, `direccion`, `creado_en`) VALUES ('1', 'Consumidor Final', NULL, 'DNI', 'Consumidor Final', NULL, NULL, NULL, NULL, '2026-06-15 01:53:19');
INSERT INTO `clientes` (`id`, `nombre`, `dni`, `tipo_documento`, `condicion_iva`, `email`, `id_lista_precio`, `telefono`, `direccion`, `creado_en`) VALUES ('4', 'FELING FRANCO', '40337241', 'DNI', 'Consumidor Final', NULL, '11', NULL, NULL, '2026-06-15 00:11:27');

DROP TABLE IF EXISTS `configuraciones`;
CREATE TABLE `configuraciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `valor` longtext COLLATE utf8mb4_general_ci,
  `tipo` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'texto',
  `grupo` varchar(60) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'sistema',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_configuraciones_clave` (`clave`),
  KEY `idx_configuraciones_grupo` (`grupo`)
) ENGINE=InnoDB AUTO_INCREMENT=695 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('482', 'nombre_comercio', 'FELING CARLOS RODOLFO', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('483', 'razon_social', 'FELING CARLOS RODOLFO', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('484', 'cuit', '20-11950408-3', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('485', 'condicion_iva', 'Responsable Inscripto', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('486', 'domicilio', 'Lopez y Planes 2480, Puerto Rico, Misiones', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('487', 'localidad', '', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('488', 'provincia', '', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('489', 'telefonos', '', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('490', 'whatsapp', '', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('491', 'email', '', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('492', 'sitio_web', '', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('493', 'ingresos_brutos', 'Ingresos Brutos', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('494', 'inicio_actividades', '2026-04-27', 'texto', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('495', 'punto_venta', '1', 'numero', 'comercio');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('496', 'logo', '', 'archivo', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('497', 'favicon', '', 'archivo', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('498', 'logo_ticket', 'publico/assets/img/ticket_logo.png', 'archivo', 'impresion');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('499', 'ticket_imagen_completa', '1', 'booleano', 'impresion');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('500', 'ticket_logo_termico', '0', 'booleano', 'impresion');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('501', 'color_acento', '#000000', 'color', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('502', 'color_secundario', '#51706f', 'color', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('503', 'color_fondo', '#a1a257', 'color', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('504', 'color_fondo_secundario', '#f9fbfc', 'color', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('505', 'color_tarjetas', '#ffffff', 'color', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('506', 'color_texto', '#203040', 'color', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('507', 'color_texto_suave', '#657789', 'color', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('508', 'color_borde', '#dbe3ea', 'color', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('509', 'color_panel_inicio', '#155e75', 'color', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('510', 'color_panel_inicio_2', '#48aaa5', 'color', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('511', 'navbar_marca_texto', 'FELING CARLOS RODOLFO', 'texto', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('512', 'navbar_mostrar_marca', '1', 'texto', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('513', 'navbar_mostrar_config', '1', 'texto', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('514', 'navbar_mostrar_usuario', '1', 'texto', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('515', 'navbar_mostrar_rol', '1', 'texto', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('516', 'navbar_mostrar_cambio_modulo', '0', 'texto', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('517', 'navbar_mostrar_salir', '1', 'texto', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('518', 'navbar_color_1', '#161845', 'color', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('519', 'navbar_color_2', '#8a8742', 'color', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('520', 'navbar_texto_color', '#ffffff', 'color', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('521', 'navbar_boton_fondo', '#ffffff', 'texto', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('522', 'navbar_boton_borde', '#ffffff', 'texto', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('523', 'navbar_boton_opacidad', '10', 'numero', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('524', 'navbar_modulos_orden', 'ventas,nueva_venta,clientes,stock,productos,listas_precios,exportaciones,cuentas_corrientes,reparaciones', 'texto', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('525', 'navbar_modulos_visibles', 'ventas,nueva_venta,clientes,stock,productos,listas_precios,exportaciones,cuentas_corrientes', 'texto', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('526', 'tema_paneles', 'claro', 'texto', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('527', 'tema_modo', 'claro', 'texto', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('528', 'ui_radio_bordes', '8', 'numero', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('529', 'ui_tamano_tarjetas', 'medio', 'numero', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('530', 'ui_sombras', '1', 'booleano', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('531', 'ui_animaciones', '1', 'booleano', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('532', 'imagen_panel', 'publico/assets/img/panel_fondo.jpeg', 'archivo', 'apariencia');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('533', 'ventas_cantidad_decimales', '2', 'numero', 'ventas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('534', 'ventas_descuento_automatico', '0', 'booleano', 'ventas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('535', 'ventas_rapidas', '1', 'texto', 'ventas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('536', 'ventas_cliente_defecto', 'Consumidor Final', 'texto', 'ventas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('537', 'ventas_consumidor_final_auto', '1', 'booleano', 'ventas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('538', 'ventas_confirmar_cierre', '1', 'texto', 'ventas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('539', 'ventas_sonido_confirmacion', '0', 'booleano', 'ventas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('540', 'ventas_color_pendiente', '#f59e0b', 'color', 'ventas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('541', 'ventas_color_completada', '#16a34a', 'color', 'ventas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('542', 'ventas_color_cancelada', '#dc2626', 'color', 'ventas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('543', 'formato_impresion_ticket', '58', 'texto', 'impresion');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('544', 'texto_pie_ticket', 'Gracias por elegirnos', 'texto', 'impresion');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('545', 'ticket_fuente', 'Courier New', 'texto', 'impresion');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('546', 'ticket_tamano_fuente', '12', 'numero', 'impresion');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('547', 'controlar_stock_ventas', '0', 'texto', 'ventas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('548', 'productos_multiples_listas', '1', 'texto', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('549', 'productos_mostrar_stock_minimo', '1', 'texto', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('550', 'productos_permitir_stock_negativo', '0', 'texto', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('551', 'productos_activar_escaner', '1', 'booleano', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('552', 'productos_formato_codigo_barras', 'ean13', 'texto', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('553', 'productos_etiquetas', '1', 'booleano', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('554', 'productos_importacion_excel', '1', 'texto', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('555', 'productos_reglas_automaticas', '0', 'booleano', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('556', 'productos_cotizacion_dolar', '2000', 'numero', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('557', 'clientes_campos_extra', '0', 'texto', 'clientes');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('558', 'clientes_validar_documento', '0', 'texto', 'clientes');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('559', 'clientes_lista_defecto', '', 'texto', 'clientes');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('560', 'listas_redondeo', '0', 'texto', 'listas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('561', 'listas_actualizar_costo', '0', 'texto', 'listas');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('562', 'notificaciones_sonidos', '0', 'booleano', 'notificaciones');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('563', 'notificaciones_toasts', '1', 'booleano', 'notificaciones');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('564', 'notificaciones_alertas', '1', 'booleano', 'notificaciones');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('565', 'notificaciones_stock_bajo', '1', 'texto', 'notificaciones');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('566', 'notificaciones_ventas', '1', 'texto', 'notificaciones');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('567', 'notificaciones_backup', '1', 'texto', 'notificaciones');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('568', 'seguridad_tiempo_sesion', '120', 'numero', 'seguridad');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('569', 'seguridad_2fa_futuro', '0', 'texto', 'seguridad');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('570', 'seguridad_ips_permitidas', '', 'texto', 'seguridad');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('571', 'seguridad_bloqueos', '1', 'texto', 'seguridad');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('572', 'seguridad_logs', '1', 'booleano', 'seguridad');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('573', 'auth_modo', 'login', 'texto', 'seguridad');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('574', 'backup_b2_habilitado', '1', 'booleano', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('575', 'backup_b2_key_id', '00553bcd9b5fc810000000001', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('576', 'backup_b2_application_key', 'K005XQJEjN5wOsgmsFQfn2nBcQhbru8', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('577', 'backup_b2_bucket_id', 'e5e3eb4c7d792b659fec0811', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('578', 'backup_b2_bucket_name', 'preubaelectron', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('579', 'backup_b2_carpeta', 'ventas-reparaciones', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('580', 'backup_google_drive_futuro', '0', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('581', 'backup_automatico', '1', 'booleano', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('582', 'backup_frecuencia', 'diario', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('583', 'backup_hora', '09:17', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('584', 'backup_aviso_minutos', '5', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('585', 'backup_local_habilitado', '1', 'booleano', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('586', 'backup_local_carpeta', '', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('587', 'backup_auto_local', '1', 'booleano', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('588', 'backup_auto_backblaze', '0', 'booleano', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('589', 'backup_ultimo', '2026-05-26 14:21:30', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('590', 'backup_ultimo_estado', 'ok', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('591', 'backup_ultimo_error', '', 'texto', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('592', 'backup_auto_ultimo_dia', '', 'booleano', 'backup');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('593', 'url_reparaciones', 'index.php?c=reparaciones&a=index', 'texto', 'sistema');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('594', 'mostrar_reparaciones', '1', 'booleano', 'sistema');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('595', 'atajo_reparaciones', 'F9', 'texto', 'sistema');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('596', 'configuracion_separada', '1', 'texto', 'sistema');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('597', 'balanza_modo', 'auto', 'texto', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('598', 'balanza_plu_digitos', '1', 'texto', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('599', 'balanza_valor_decimales', '0', 'numero', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('600', 'balanza_importe_decimales', '0', 'numero', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('601', 'balanza_prefijos_cantidad', '', 'texto', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('602', 'balanza_prefijos_importe', '', 'texto', 'productos');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('665', 'navbar_fondo_modo', 'colores', 'texto', 'menu');
INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `tipo`, `grupo`) VALUES ('672', 'navbar_imagen', '', 'archivo', 'menu');

DROP TABLE IF EXISTS `cuentas_corrientes`;
CREATE TABLE `cuentas_corrientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `id_venta` int DEFAULT NULL,
  `concepto` varchar(180) COLLATE utf8mb4_general_ci NOT NULL,
  `total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `saldo` decimal(14,2) NOT NULL DEFAULT '0.00',
  `estado` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ABIERTA',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cc_cliente` (`id_cliente`),
  KEY `idx_cc_saldo` (`saldo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `cuentas_corrientes_alertas_lecturas`;
CREATE TABLE `cuentas_corrientes_alertas_lecturas` (
  `id_usuario` int NOT NULL,
  `leido_hasta` date NOT NULL,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `cuentas_corrientes_cuotas`;
CREATE TABLE `cuentas_corrientes_cuotas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_cuenta` int NOT NULL,
  `numero` int NOT NULL,
  `vencimiento` date NOT NULL,
  `monto` decimal(14,2) NOT NULL DEFAULT '0.00',
  `pagado` decimal(14,2) NOT NULL DEFAULT '0.00',
  `estado` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDIENTE',
  `pagado_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cc_cuota_vto` (`vencimiento`),
  KEY `idx_cc_cuota_cuenta` (`id_cuenta`),
  KEY `idx_cc_cuota_estado_vto` (`estado`,`vencimiento`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `cuentas_corrientes_recibos`;
CREATE TABLE `cuentas_corrientes_recibos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_cuenta` int DEFAULT NULL,
  `id_cliente` int DEFAULT NULL,
  `tipo` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PAGO_CUENTA',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `monto` decimal(14,2) NOT NULL DEFAULT '0.00',
  `forma_pago` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'contado',
  `observacion` varchar(220) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_cc_recibo_cuenta` (`id_cuenta`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `detalle_presupuesto`;
CREATE TABLE `detalle_presupuesto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_presupuesto` int NOT NULL,
  `id_producto` int NOT NULL,
  `producto_nombre` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `precio_unit` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_detalle_presupuesto` (`id_presupuesto`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `detalle_venta`;
CREATE TABLE `detalle_venta` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` decimal(12,3) NOT NULL,
  `precio_unit` decimal(12,2) NOT NULL,
  `costo_unit` decimal(14,2) NOT NULL DEFAULT '0.00',
  `descuento` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_detalle_venta` (`id_venta`),
  KEY `fk_detalle_producto` (`id_producto`),
  KEY `idx_detalle_producto_venta` (`id_producto`,`id_venta`),
  CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `detalle_venta` (`id`, `id_venta`, `id_producto`, `cantidad`, `precio_unit`, `costo_unit`, `descuento`, `subtotal`) VALUES ('51', '31', '1201', '1.000', '2250.00', '1500.00', '0.00', '2250.00');

DROP TABLE IF EXISTS `fiscal_cola`;
CREATE TABLE `fiscal_cola` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_comprobante` int NOT NULL,
  `estado` enum('PENDIENTE','EN_PROCESO','FINALIZADO','ERROR') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDIENTE',
  `intentos` int NOT NULL DEFAULT '0',
  `ultimo_error` text COLLATE utf8mb4_general_ci,
  `proximo_intento` datetime DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fiscal_cola_estado` (`estado`,`proximo_intento`),
  KEY `fk_fiscal_cola_comprobante` (`id_comprobante`),
  CONSTRAINT `fk_fiscal_cola_comprobante` FOREIGN KEY (`id_comprobante`) REFERENCES `fiscal_comprobantes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `fiscal_comprobantes`;
CREATE TABLE `fiscal_comprobantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `tipo_operacion` enum('factura','presupuesto') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'factura',
  `estado` enum('PENDIENTE','EN_PROCESO','APROBADO','RECHAZADO','ERROR') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PENDIENTE',
  `proveedor` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'api_rest',
  `punto_venta` int DEFAULT NULL,
  `tipo_comprobante` int DEFAULT NULL,
  `numero_comprobante` bigint DEFAULT NULL,
  `cae` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cae_vencimiento` date DEFAULT NULL,
  `payload_json` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `respuesta_json` longtext COLLATE utf8mb4_general_ci,
  `ultimo_error` text COLLATE utf8mb4_general_ci,
  `intentos` int NOT NULL DEFAULT '0',
  `proximo_intento` datetime DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fiscal_comprobantes_venta` (`id_venta`),
  KEY `idx_fiscal_estado` (`estado`),
  CONSTRAINT `fk_fiscal_comprobantes_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `historial_precios`;
CREATE TABLE `historial_precios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_producto` int NOT NULL,
  `id_lista` int NOT NULL,
  `precio_anterior` decimal(14,2) NOT NULL DEFAULT '0.00',
  `precio_nuevo` decimal(14,2) NOT NULL DEFAULT '0.00',
  `origen` varchar(40) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'manual',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_historial_precios_fecha` (`creado_en`),
  KEY `idx_historial_precios_lista` (`id_lista`),
  KEY `idx_historial_precios_producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=3007 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `historial_precios` (`id`, `id_producto`, `id_lista`, `precio_anterior`, `precio_nuevo`, `origen`, `creado_en`) VALUES ('3005', '1201', '10', '0.00', '1500.00', 'manual', '2026-06-15 00:08:54');
INSERT INTO `historial_precios` (`id`, `id_producto`, `id_lista`, `precio_anterior`, `precio_nuevo`, `origen`, `creado_en`) VALUES ('3006', '1201', '11', '0.00', '2250.00', 'manual', '2026-06-15 00:08:54');

DROP TABLE IF EXISTS `listas_precios`;
CREATE TABLE `listas_precios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `listas_precios` (`id`, `nombre`, `activo`, `creado_en`) VALUES ('10', 'Costo', '1', '2026-06-14 23:49:42');
INSERT INTO `listas_precios` (`id`, `nombre`, `activo`, `creado_en`) VALUES ('11', 'PÚBLICO', '1', '2026-06-15 00:07:40');

DROP TABLE IF EXISTS `presupuestos`;
CREATE TABLE `presupuestos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_cliente` int NOT NULL,
  `id_usuario` int NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ABIERTO',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_presupuestos_cliente` (`id_cliente`),
  KEY `idx_presupuestos_fecha` (`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `producto_precios`;
CREATE TABLE `producto_precios` (
  `id_producto` int NOT NULL,
  `id_lista` int NOT NULL,
  `porcentaje` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `precio` decimal(14,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_producto`,`id_lista`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `producto_precios` (`id_producto`, `id_lista`, `porcentaje`, `precio`) VALUES ('1201', '10', '0.0000', '1500.00');
INSERT INTO `producto_precios` (`id_producto`, `id_lista`, `porcentaje`, `precio`) VALUES ('1201', '11', '50.0000', '2250.00');

DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cod_barras` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_stock` int NOT NULL,
  `id_asociado` int DEFAULT NULL,
  `factor_conversion` decimal(12,4) NOT NULL DEFAULT '1.0000',
  `ganancia` decimal(12,2) NOT NULL DEFAULT '0.00',
  `precio_final` decimal(12,2) NOT NULL DEFAULT '0.00',
  `activo` tinyint NOT NULL DEFAULT '1',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB AUTO_INCREMENT=1202 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `productos` (`id`, `nombre`, `cod_barras`, `id_stock`, `id_asociado`, `factor_conversion`, `ganancia`, `precio_final`, `activo`, `creado_en`) VALUES ('1201', 'COCA COLA', 'P2026061503082060', '1199', NULL, '1.0000', '0.00', '1500.00', '1', '2026-06-15 00:08:54');

DROP TABLE IF EXISTS `reparaciones`;
CREATE TABLE `reparaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `marca` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `modelo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `imei` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `falla` text COLLATE utf8mb4_unicode_ci,
  `diagnostico` text COLLATE utf8mb4_unicode_ci,
  `garantia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `estado` enum('PENDIENTE','EN_REPARACION','ESP_REPUESTOS','REPARADO','ENTREGADO','CANCELADO') COLLATE utf8mb4_unicode_ci DEFAULT 'PENDIENTE',
  `precio` decimal(10,2) DEFAULT '0.00',
  `fecha_ingreso` date NOT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `id_usuario` int DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_estado` (`estado`),
  KEY `idx_codigo` (`codigo`),
  KEY `idx_fecha_ingreso` (`fecha_ingreso`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `stock`;
CREATE TABLE `stock` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'u',
  `tipo_stock` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `cantidad` decimal(12,3) NOT NULL DEFAULT '0.000',
  `stock_minimo` decimal(14,3) NOT NULL DEFAULT '0.000',
  `stock_maximo` decimal(14,3) NOT NULL DEFAULT '0.000',
  `precio_costo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `moneda_costo` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ARS',
  `costo_origen` decimal(14,4) NOT NULL DEFAULT '0.0000',
  `activo` tinyint NOT NULL DEFAULT '1',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_alerta_menu` (`activo`,`tipo_stock`,`cantidad`,`stock_minimo`)
) ENGINE=InnoDB AUTO_INCREMENT=1201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `stock` (`id`, `nombre`, `unidad`, `tipo_stock`, `cantidad`, `stock_minimo`, `stock_maximo`, `precio_costo`, `moneda_costo`, `costo_origen`, `activo`, `creado_en`) VALUES ('1199', 'COCA COLA', 'u', 'propio', '4.000', '5.000', '15.000', '1500.00', 'ARS', '1500.0000', '1', '2026-06-15 00:08:54');
INSERT INTO `stock` (`id`, `nombre`, `unidad`, `tipo_stock`, `cantidad`, `stock_minimo`, `stock_maximo`, `precio_costo`, `moneda_costo`, `costo_origen`, `activo`, `creado_en`) VALUES ('1200', 'AZUCAR', 'kg', 'general', '50.000', '10.000', '50.000', '1000.00', 'ARS', '1000.0000', '1', '2026-06-15 00:10:52');

DROP TABLE IF EXISTS `stock_alertas_leidas`;
CREATE TABLE `stock_alertas_leidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_producto` int NOT NULL,
  `fecha_lectura` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario` int NOT NULL DEFAULT '0',
  `cantidad_leida` decimal(14,3) NOT NULL DEFAULT '0.000',
  `stock_minimo_leido` decimal(14,3) NOT NULL DEFAULT '0.000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_alerta_producto_usuario` (`id_producto`,`usuario`),
  KEY `idx_stock_alertas_producto` (`id_producto`),
  KEY `idx_stock_alertas_usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


DROP TABLE IF EXISTS `unidades_medida`;
CREATE TABLE `unidades_medida` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `abreviatura` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `tipo` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cantidad',
  `decimales` tinyint unsigned NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_en` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unidades_abreviatura` (`abreviatura`)
) ENGINE=InnoDB AUTO_INCREMENT=3621 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `unidades_medida` (`id`, `nombre`, `abreviatura`, `tipo`, `decimales`, `activo`, `creado_en`) VALUES ('3211', 'Kilogramo', 'kg', 'peso', '3', '1', '2026-06-14 23:49:34');
INSERT INTO `unidades_medida` (`id`, `nombre`, `abreviatura`, `tipo`, `decimales`, `activo`, `creado_en`) VALUES ('3212', 'Gramo', 'g', 'peso', '0', '1', '2026-06-14 23:49:34');
INSERT INTO `unidades_medida` (`id`, `nombre`, `abreviatura`, `tipo`, `decimales`, `activo`, `creado_en`) VALUES ('3213', 'Litro', 'l', 'volumen', '3', '1', '2026-06-14 23:49:34');
INSERT INTO `unidades_medida` (`id`, `nombre`, `abreviatura`, `tipo`, `decimales`, `activo`, `creado_en`) VALUES ('3214', 'Mililitro', 'ml', 'volumen', '0', '1', '2026-06-14 23:49:34');
INSERT INTO `unidades_medida` (`id`, `nombre`, `abreviatura`, `tipo`, `decimales`, `activo`, `creado_en`) VALUES ('3215', 'Metro', 'm', 'longitud', '2', '1', '2026-06-14 23:49:34');
INSERT INTO `unidades_medida` (`id`, `nombre`, `abreviatura`, `tipo`, `decimales`, `activo`, `creado_en`) VALUES ('3216', 'Centimetro', 'cm', 'longitud', '0', '1', '2026-06-14 23:49:34');
INSERT INTO `unidades_medida` (`id`, `nombre`, `abreviatura`, `tipo`, `decimales`, `activo`, `creado_en`) VALUES ('3217', 'Unidad', 'u', 'cantidad', '0', '1', '2026-06-14 23:49:34');
INSERT INTO `unidades_medida` (`id`, `nombre`, `abreviatura`, `tipo`, `decimales`, `activo`, `creado_en`) VALUES ('3218', 'Caja', 'cj', 'cantidad', '0', '1', '2026-06-14 23:49:34');
INSERT INTO `unidades_medida` (`id`, `nombre`, `abreviatura`, `tipo`, `decimales`, `activo`, `creado_en`) VALUES ('3219', 'Pack', 'pack', 'cantidad', '0', '1', '2026-06-14 23:49:34');
INSERT INTO `unidades_medida` (`id`, `nombre`, `abreviatura`, `tipo`, `decimales`, `activo`, `creado_en`) VALUES ('3220', 'Docena', 'doc', 'cantidad', '0', '1', '2026-06-14 23:49:34');

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clave` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('ADMIN','VENDEDOR') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'VENDEDOR',
  `activo` tinyint NOT NULL DEFAULT '1',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `permisos` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id`, `usuario`, `clave`, `rol`, `activo`, `creado_en`, `permisos`) VALUES ('0', 'Sin login', '', 'ADMIN', '1', '2026-06-15 01:53:20', NULL);
INSERT INTO `usuarios` (`id`, `usuario`, `clave`, `rol`, `activo`, `creado_en`, `permisos`) VALUES ('5', 'sistema', '$2y$10$lwekQxLq.i6foe7D53/hG.qKsag5so3lE/79PEmBnAiFzAVF1kalW', 'ADMIN', '1', '2026-06-15 00:16:01', '[]');

DROP TABLE IF EXISTS `ventas`;
CREATE TABLE `ventas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_cliente` int NOT NULL,
  `id_usuario` int NOT NULL,
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_venta_cliente` (`id_cliente`),
  KEY `fk_venta_usuario` (`id_usuario`),
  CONSTRAINT `fk_venta_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ventas` (`id`, `fecha`, `id_cliente`, `id_usuario`, `total`, `creado_en`) VALUES ('31', '2026-06-15 00:16:01', '1', '5', '2250.00', '2026-06-15 00:16:01');


SET FOREIGN_KEY_CHECKS=1;
