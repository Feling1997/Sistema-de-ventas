<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Infrastructure;

use PDO;
use Throwable;
use Ventas\Configuracion\Domain\Repositorios\ConfiguracionRepository;

final class MySQLConfiguracionRepository implements ConfiguracionRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $archivoConfiguracion,
        private readonly string $archivoArca
    ) {
    }

    public function obtenerGeneral(): array
    {
        $configuracion = $this->normalizar(
            array_merge(
                $this->valoresDefecto(),
                $this->obtenerDesdeArchivo(),
                $this->obtenerDesdeBaseDatos()
            )
        );

        return $configuracion;
    }

    public function obtenerFiscal(): array
    {
        $datos = $this->obtenerGeneral();
        $configuracion = $this->configuracionArcaBase();
        $empresa = is_array($configuracion['empresa'] ?? null) ? $configuracion['empresa'] : [];
        $configuracion['empresa'] = array_merge($empresa, [
            'nombre_comercio' => (string) $datos['nombre_comercio'],
            'cuit' => (string) $datos['cuit'],
            'punto_venta' => (int) $datos['punto_venta'],
            'condicion_iva' => (string) $datos['condicion_iva'],
            'razon_social' => $this->razonSocialParaComprobante($datos),
            'domicilio' => $this->domicilioCompleto($datos),
            'ingresos_brutos' => (string) $datos['ingresos_brutos'],
            'inicio_actividades' => (string) $datos['inicio_actividades'],
            'telefonos' => (string) $datos['telefonos'],
            'whatsapp' => (string) $datos['whatsapp'],
            'email' => (string) $datos['email'],
            'sitio_web' => (string) $datos['sitio_web'],
            'formato_impresion_ticket' => (string) $datos['formato_impresion_ticket'],
            'texto_pie_ticket' => (string) $datos['texto_pie_ticket'],
            'ticket_fuente' => (string) $datos['ticket_fuente'],
            'ticket_tamano_fuente' => (string) $datos['ticket_tamano_fuente'],
            'logo_ticket' => (string) $datos['logo_ticket'],
            'ticket_imagen_completa' => (string) $datos['ticket_imagen_completa'],
            'ticket_logo_termico' => (string) $datos['ticket_logo_termico'],
        ]);

        return $configuracion;
    }

    public function obtenerVenta(): array
    {
        $datos = $this->obtenerGeneral();
        $configuracion = [
            'controlar_stock_ventas' => (string) $datos['controlar_stock_ventas'] === '1',
            'formato_impresion_ticket' => (string) $datos['formato_impresion_ticket'],
            'texto_pie_ticket' => (string) $datos['texto_pie_ticket'],
            'ticket_fuente' => (string) $datos['ticket_fuente'],
            'ticket_tamano_fuente' => (string) $datos['ticket_tamano_fuente'],
            'logo_ticket' => (string) $datos['logo_ticket'],
            'ticket_imagen_completa' => (string) $datos['ticket_imagen_completa'],
            'ticket_logo_termico' => (string) $datos['ticket_logo_termico'],
            'mostrar_reparaciones' => (string) $datos['mostrar_reparaciones'],
            'url_reparaciones' => (string) $datos['url_reparaciones'],
        ];

        return $configuracion;
    }

    public function obtenerBalanza(): array
    {
        $datos = $this->obtenerGeneral();
        $modo = (string) $datos['balanza_modo'];
        $configuracion = [
            'modo' => in_array($modo, ['auto', 'cantidad', 'importe'], true) ? $modo : 'auto',
            'plu_digitos' => max(1, min(8, (int) $datos['balanza_plu_digitos'])),
            'valor_decimales' => max(0, min(4, (int) $datos['balanza_valor_decimales'])),
            'importe_decimales' => max(0, min(4, (int) $datos['balanza_importe_decimales'])),
            'prefijos_cantidad' => $this->normalizarPrefijos((string) $datos['balanza_prefijos_cantidad']),
            'prefijos_importe' => $this->normalizarPrefijos((string) $datos['balanza_prefijos_importe']),
        ];

        return $configuracion;
    }

    public function obtenerAuth(): array
    {
        $datos = $this->obtenerGeneral();
        $modo = (string) $datos['auth_modo'];
        $configuracion = [
            'auth_modo' => $modo,
            'sin_login_habilitado' => $modo === 'sin_login',
        ];

        return $configuracion;
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function guardar(array $datos): bool
    {
        $ok = false;

        if ($this->inicializarEsquema()) {
            try {
                $this->pdo->beginTransaction();
                $metadatos = $this->obtenerMetadatos();
                $statement = $this->pdo->prepare('INSERT INTO configuraciones (clave, valor, tipo, grupo) VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), grupo = VALUES(grupo)');

                if (array_key_exists('nombre_comercio', $datos)) {
                    $datos['navbar_marca_texto'] = trim((string) $datos['nombre_comercio']);
                }

                foreach ($datos as $clave => $valor) {
                    $claveNormalizada = trim((string) $clave);

                    if ($claveNormalizada !== '' && isset($metadatos[$claveNormalizada])) {
                        $valorLimpio = $this->normalizarValor($claveNormalizada, $valor, $metadatos[$claveNormalizada]);
                        $statement->execute([
                            $claveNormalizada,
                            $valorLimpio,
                            (string) $metadatos[$claveNormalizada]['tipo'],
                            (string) $metadatos[$claveNormalizada]['grupo'],
                        ]);
                    }
                }

                $this->pdo->commit();
                $this->resetearCacheConfiguracion();
                $this->sincronizarConfiguracionLegacy();
                $ok = true;
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }

                $this->registrarLog('Configuracion::guardar', $e->getMessage());
                $this->registrarOperacion('configuracion.modelo.guardar.error', [
                    'error' => $e->getMessage(),
                    'campos' => array_keys($datos),
                    'datos' => $datos,
                ]);
            }
        } else {
            $this->registrarOperacion('configuracion.modelo.guardar.error', [
                'error' => 'No se pudo asegurar tabla configuraciones',
                'campos' => array_keys($datos),
                'datos' => $datos,
            ]);
        }

        return $ok;
    }

    public function restablecerGrupo(string $grupo): bool
    {
        $ok = false;

        if ($this->inicializarEsquema()) {
            try {
                $metadatos = $this->obtenerMetadatos();
                $statement = $this->pdo->prepare('DELETE FROM configuraciones WHERE clave = ?');

                foreach ($metadatos as $clave => $item) {
                    if ((string) ($item['grupo'] ?? '') === $grupo) {
                        $statement->execute([$clave]);
                    }
                }

                $this->resetearCacheConfiguracion();
                $this->sincronizarConfiguracionLegacy();
                $ok = true;
            } catch (Throwable $e) {
                $this->registrarLog('Configuracion::restablecer_grupo', $e->getMessage());
            }
        }

        return $ok;
    }

    public function obtenerGrupo(string $grupo): array
    {
        $datos = [];
        $todos = $this->obtenerGeneral();

        foreach ($this->obtenerMetadatos() as $clave => $meta) {
            if ((string) ($meta['grupo'] ?? '') === $grupo) {
                $datos[$clave] = (string) ($todos[$clave] ?? ($meta['defecto'] ?? ''));
            }
        }

        return $datos;
    }

    public function obtenerMetadatos(): array
    {
        $metadatos = [];

        foreach ($this->valoresDefecto() as $clave => $valor) {
            $metadatos[$clave] = [
                'tipo' => $this->tipoPorClave($clave),
                'grupo' => $this->grupoPorClave($clave),
                'defecto' => (string) $valor,
            ];
        }

        return $metadatos;
    }

    public function inicializarEsquema(): bool
    {
        $ok = false;

        try {
            if (function_exists('asegurar_tabla_configuraciones')) {
                $ok = (bool) \asegurar_tabla_configuraciones($this->pdo);
            } else {
                $ok = $this->asegurarTablaConfiguraciones();
            }

            if ($ok) {
                $this->sembrarDefectos();
            }
        } catch (Throwable $e) {
            $this->registrarLog('Configuracion::asegurar_tabla', $e->getMessage());
            $this->registrarOperacion('configuracion.asegurar_tabla.error', [
                'error' => $e->getMessage(),
            ]);
        }

        return $ok;
    }

    private function obtenerDesdeArchivo(): array
    {
        $datos = [];

        if (is_file($this->archivoConfiguracion)) {
            $contenido = file_get_contents($this->archivoConfiguracion);

            if ($contenido !== false) {
                $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido) ?? '';
                $json = json_decode($contenido, true);

                if (is_array($json)) {
                    $datos = $this->filtrarClaves($json);
                }
            }
        }

        return $datos;
    }

    private function obtenerDesdeBaseDatos(): array
    {
        $datos = [];

        try {
            $statement = $this->pdo->query('SELECT clave, valor FROM configuraciones');
            $filas = $statement !== false ? $statement->fetchAll() : [];

            foreach ($filas as $fila) {
                $clave = (string) ($fila['clave'] ?? '');

                if (array_key_exists($clave, $this->valoresDefecto())) {
                    $datos[$clave] = (string) ($fila['valor'] ?? '');
                }
            }
        } catch (Throwable) {
            $datos = [];
        }

        return $datos;
    }

    private function filtrarClaves(array $entrada): array
    {
        $datos = [];
        $permitidas = $this->valoresDefecto();

        foreach ($entrada as $clave => $valor) {
            $claveNormalizada = (string) $clave;

            if (array_key_exists($claveNormalizada, $permitidas)) {
                $datos[$claveNormalizada] = is_scalar($valor) ? (string) $valor : $permitidas[$claveNormalizada];
            }
        }

        return $datos;
    }

    private function asegurarTablaConfiguraciones(): bool
    {
        $ok = false;

        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS configuraciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                clave VARCHAR(120) NOT NULL,
                valor LONGTEXT NULL,
                tipo VARCHAR(40) NOT NULL DEFAULT 'texto',
                grupo VARCHAR(60) NOT NULL DEFAULT 'sistema',
                UNIQUE KEY uq_configuraciones_clave (clave),
                KEY idx_configuraciones_grupo (grupo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $ok = true;
            $ok = $this->asegurarColumna('configuraciones', 'clave', 'ALTER TABLE configuraciones ADD COLUMN clave VARCHAR(120) NOT NULL AFTER id') && $ok;
            $ok = $this->asegurarColumna('configuraciones', 'valor', 'ALTER TABLE configuraciones ADD COLUMN valor LONGTEXT NULL AFTER clave') && $ok;
            $ok = $this->asegurarColumna('configuraciones', 'tipo', "ALTER TABLE configuraciones ADD COLUMN tipo VARCHAR(40) NOT NULL DEFAULT 'texto' AFTER valor") && $ok;
            $ok = $this->asegurarColumna('configuraciones', 'grupo', "ALTER TABLE configuraciones ADD COLUMN grupo VARCHAR(60) NOT NULL DEFAULT 'sistema' AFTER tipo") && $ok;
            $this->asegurarIndice('configuraciones', 'idx_configuraciones_grupo', 'ALTER TABLE configuraciones ADD KEY idx_configuraciones_grupo (grupo)');
            $this->asegurarIndice('configuraciones', 'uq_configuraciones_clave', 'ALTER TABLE configuraciones ADD UNIQUE KEY uq_configuraciones_clave (clave)');
        } catch (Throwable $e) {
            $this->registrarLog('Migracion::configuraciones', $e->getMessage());
            $this->registrarOperacion('migracion.configuraciones.error', ['error' => $e->getMessage()]);
            $ok = false;
        }

        return $ok;
    }

    private function asegurarColumna(string $tabla, string $columna, string $sql): bool
    {
        $ok = false;

        try {
            $statement = $this->pdo->prepare("SHOW COLUMNS FROM `{$tabla}` LIKE ?");
            $statement->execute([$columna]);
            $existe = (bool) $statement->fetch();

            if (!$existe) {
                $this->pdo->exec($sql);
            }

            $ok = true;
        } catch (Throwable $e) {
            $this->registrarLog('Migracion::columna', $tabla . '.' . $columna . ' ' . $e->getMessage());
        }

        return $ok;
    }

    private function asegurarIndice(string $tabla, string $indice, string $sql): bool
    {
        $ok = false;

        try {
            $statement = $this->pdo->prepare("SHOW INDEX FROM `{$tabla}` WHERE Key_name = ?");
            $statement->execute([$indice]);
            $existe = (bool) $statement->fetch();

            if (!$existe) {
                $this->pdo->exec($sql);
            }

            $ok = true;
        } catch (Throwable $e) {
            $this->registrarLog('Migracion::indice', $tabla . '.' . $indice . ' ' . $e->getMessage());
        }

        return $ok;
    }

    private function sembrarDefectos(): void
    {
        $statement = $this->pdo->query('SELECT COUNT(*) AS total FROM configuraciones');
        $total = $statement !== false ? (int) ($statement->fetch()['total'] ?? 0) : 0;

        if ($total === 0) {
            $datos = $this->valoresDefecto();
            $datos = array_merge($datos, $this->obtenerDesdeArchivo());

            $metadatos = $this->obtenerMetadatos();
            $insert = $this->pdo->prepare('INSERT INTO configuraciones (clave, valor, tipo, grupo) VALUES (?, ?, ?, ?)');

            foreach ($datos as $clave => $valor) {
                if (isset($metadatos[$clave])) {
                    $insert->execute([
                        $clave,
                        (string) $valor,
                        (string) $metadatos[$clave]['tipo'],
                        (string) $metadatos[$clave]['grupo'],
                    ]);
                }
            }
        }
    }

    private function valoresDefecto(): array
    {
        $datos = function_exists('configuraciones_defecto_db') ? \configuraciones_defecto_db() : [
            'nombre_comercio' => 'MI COMERCIO',
            'razon_social' => '',
            'cuit' => '',
            'condicion_iva' => '',
            'domicilio' => '',
            'localidad' => '',
            'provincia' => '',
            'telefonos' => '',
            'whatsapp' => '',
            'email' => '',
            'sitio_web' => '',
            'ingresos_brutos' => '',
            'inicio_actividades' => '',
            'punto_venta' => '1',
            'formato_impresion_ticket' => '80',
            'texto_pie_ticket' => 'Gracias por su compra',
            'ticket_fuente' => 'Arial',
            'ticket_tamano_fuente' => '12',
            'controlar_stock_ventas' => '1',
            'moneda_principal' => 'ARS',
            'dolar_compra' => '1220',
            'dolar_venta' => '1220',
            'dolar_fecha_actualizacion' => '',
            'productos_cotizacion_dolar' => '0',
            'balanza_modo' => 'auto',
            'balanza_plu_digitos' => '5',
            'balanza_valor_decimales' => '3',
            'balanza_importe_decimales' => '2',
            'balanza_prefijos_cantidad' => '20,21,23,25,27,29',
            'balanza_prefijos_importe' => '22,24,26,28',
            'logo_ticket' => '',
            'ticket_imagen_completa' => '0',
            'ticket_logo_termico' => '1',
            'url_reparaciones' => '/Sistema-de-ventas/laravel/public/reparaciones',
            'mostrar_reparaciones' => '1',
            'configuracion_separada' => '1',
            'navbar_mostrar_marca' => '1',
            'navbar_mostrar_config' => '1',
            'navbar_mostrar_usuario' => '1',
            'navbar_mostrar_rol' => '1',
            'navbar_mostrar_cambio_modulo' => '1',
            'navbar_mostrar_salir' => '1',
            'backup_b2_habilitado' => '0',
            'backup_automatico' => '0',
            'backup_local_habilitado' => '1',
            'backup_auto_local' => '1',
            'backup_auto_backblaze' => '0',
            'backup_frecuencia' => 'diario',
            'backup_hora' => '23:00',
            'backup_ruta_local' => 'almacenamiento/backups',
            'backup_b2_bucket' => '',
            'backup_b2_key_id' => '',
            'backup_b2_application_key' => '',
            'backup_b2_region' => '',
            'backup_b2_prefijo' => 'sistema-ventas',
            'auth_modo' => 'login',
            'tema_color_primario' => '#2563eb',
            'tema_color_secundario' => '#0f172a',
            'tema_modo' => 'claro',
        ];

        return $datos;
    }

    private function tipoPorClave(string $clave): string
    {
        $tipo = 'texto';

        if (str_contains($clave, 'color')) {
            $tipo = 'color';
        } elseif (in_array($clave, ['ticket_imagen_completa', 'ticket_logo_termico'], true) || str_starts_with($clave, 'mostrar_') || str_contains($clave, '_habilitado') || str_contains($clave, '_auto') || str_contains($clave, '_sonido') || str_contains($clave, '_toasts') || str_contains($clave, '_alertas') || str_contains($clave, '_logs') || str_contains($clave, '_animaciones') || str_contains($clave, '_sombras') || str_contains($clave, '_escaner') || str_contains($clave, '_etiquetas')) {
            $tipo = 'booleano';
        } elseif (str_contains($clave, 'decimales') || str_contains($clave, 'tiempo') || str_contains($clave, 'tamano') || $clave === 'punto_venta' || $clave === 'navbar_boton_opacidad' || $clave === 'productos_cotizacion_dolar' || str_starts_with($clave, 'dolar_')) {
            $tipo = 'numero';
        } elseif (str_contains($clave, 'logo') || str_contains($clave, 'favicon') || str_contains($clave, 'imagen')) {
            $tipo = 'archivo';
        }

        return $tipo;
    }

    private function grupoPorClave(string $clave): string
    {
        $grupo = 'sistema';

        if (in_array($clave, ['nombre_comercio', 'razon_social', 'cuit', 'condicion_iva', 'domicilio', 'localidad', 'provincia', 'telefonos', 'whatsapp', 'email', 'sitio_web', 'ingresos_brutos', 'inicio_actividades', 'punto_venta'], true)) {
            $grupo = 'comercio';
        } elseif (str_starts_with($clave, 'ventas_') || in_array($clave, ['controlar_stock_ventas'], true)) {
            $grupo = 'ventas';
        } elseif (str_starts_with($clave, 'productos_') || str_starts_with($clave, 'balanza_') || $clave === 'moneda_principal' || str_starts_with($clave, 'dolar_')) {
            $grupo = 'productos';
        } elseif (str_starts_with($clave, 'clientes_')) {
            $grupo = 'clientes';
        } elseif (str_starts_with($clave, 'listas_')) {
            $grupo = 'listas';
        } elseif (str_starts_with($clave, 'notificaciones_')) {
            $grupo = 'notificaciones';
        } elseif (str_starts_with($clave, 'seguridad_') || $clave === 'auth_modo') {
            $grupo = 'seguridad';
        } elseif ($clave === 'configuracion_separada') {
            $grupo = 'sistema';
        } elseif (str_starts_with($clave, 'backup_')) {
            $grupo = 'backup';
        } elseif (str_contains($clave, 'ticket') || str_starts_with($clave, 'formato_impresion')) {
            $grupo = 'impresion';
        } elseif (str_starts_with($clave, 'navbar_')) {
            $grupo = 'menu';
        } elseif (in_array($clave, ['logo', 'favicon', 'color_acento', 'color_secundario', 'color_fondo', 'color_fondo_secundario', 'color_tarjetas', 'color_texto', 'color_texto_suave', 'color_borde', 'color_panel_inicio', 'color_panel_inicio_2', 'tema_paneles', 'tema_modo', 'ui_radio_bordes', 'ui_tamano_tarjetas', 'ui_sombras', 'ui_animaciones', 'imagen_panel'], true)) {
            $grupo = 'apariencia';
        }

        return $grupo;
    }

    /**
     * @param array<string, string> $metadatos
     */
    private function normalizarValor(string $clave, mixed $valor, array $metadatos): string
    {
        $tipo = (string) ($metadatos['tipo'] ?? 'texto');
        $texto = trim(is_scalar($valor) || $valor === null ? (string) $valor : '');

        if ($tipo === 'booleano') {
            $texto = $texto === '1' ? '1' : '0';
        } elseif ($tipo === 'color') {
            $texto = preg_match('/^#[0-9a-fA-F]{6}$/', $texto) ? $texto : (string) ($metadatos['defecto'] ?? '#000000');
        } elseif ($clave === 'moneda_principal') {
            $texto = in_array($texto, ['ARS', 'USD'], true) ? $texto : 'ARS';
        } elseif (in_array($clave, ['dolar_compra', 'dolar_venta', 'productos_cotizacion_dolar'], true)) {
            $texto = (string) max(0.0001, (float) str_replace(',', '.', $texto));
        } elseif ($clave === 'dolar_fecha_actualizacion') {
            $texto = preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto) ? $texto : date('Y-m-d');
        } elseif ($tipo === 'numero') {
            $texto = (string) max(0, (int) $texto);
        } elseif ($clave === 'tema_modo') {
            $texto = in_array($texto, ['claro', 'oscuro', 'automatico'], true) ? $texto : 'claro';
        } elseif ($clave === 'tema_paneles') {
            $texto = in_array($texto, ['claro', 'compacto', 'alto_contraste'], true) ? $texto : 'claro';
        } elseif ($clave === 'formato_impresion_ticket') {
            $texto = in_array($texto, ['58', '80', 'a4'], true) ? $texto : '80';
        } elseif ($clave === 'backup_frecuencia') {
            $texto = in_array($texto, ['diario', 'semanal', 'manual'], true) ? $texto : 'diario';
        } elseif ($clave === 'backup_hora') {
            $texto = preg_match('/^\d{2}:\d{2}$/', $texto) ? $texto : '18:55';
        } elseif ($clave === 'backup_aviso_minutos') {
            $texto = (string) max(0, min(180, (int) $texto));
        } elseif ($clave === 'ui_tamano_tarjetas') {
            $texto = in_array($texto, ['compacto', 'medio', 'grande'], true) ? $texto : 'medio';
        }

        return $texto;
    }

    private function resetearCacheConfiguracion(): void
    {
        if (function_exists('config_cache_reset')) {
            \config_cache_reset();
        }
    }

    private function sincronizarConfiguracionLegacy(): void
    {
        $this->resetearCacheConfiguracion();
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function registrarOperacion(string $evento, array $datos): void
    {
        if (function_exists('registrar_operacion')) {
            \registrar_operacion($evento, $datos);
        }
    }

    private function registrarLog(string $origen, string $mensaje): void
    {
        if (function_exists('registrar_log')) {
            \registrar_log($origen, $mensaje);
        }
    }

    private function configuracionArcaBase(): array
    {
        $configuracion = [
            'habilitado' => false,
            'modo' => 'homologacion',
            'proveedor' => 'api_rest',
            'timeout_segundos' => 20,
            'api_rest' => ['endpoint' => '', 'token' => ''],
            'empresa' => [],
            'comprobante_defecto' => [
                'tipo' => 6,
                'concepto' => 1,
                'moneda' => 'PES',
                'cotizacion' => 1,
                'iva_porcentaje' => 21,
                'copia' => 'ORIGINAL',
                'remito' => '',
            ],
        ];

        if (is_file($this->archivoArca)) {
            $configuracionArchivo = require $this->archivoArca;

            if (is_array($configuracionArchivo)) {
                $configuracion = array_replace_recursive($configuracion, $configuracionArchivo);
            }
        }

        return $configuracion;
    }

    private function normalizar(array $datos): array
    {
        $normalizados = $datos;
        $normalizados['punto_venta'] = (string) max(1, (int) ($datos['punto_venta'] ?? 1));
        $normalizados['controlar_stock_ventas'] = $this->normalizarBooleano($datos['controlar_stock_ventas'] ?? '1', '1');
        $normalizados['moneda_principal'] = $this->normalizarOpcion((string) ($datos['moneda_principal'] ?? 'ARS'), ['ARS', 'USD'], 'ARS');
        $normalizados['dolar_compra'] = (string) max(0.0001, (float) str_replace(',', '.', (string) ($datos['dolar_compra'] ?? '1220')));
        $normalizados['dolar_venta'] = (string) max(0.0001, (float) str_replace(',', '.', (string) ($datos['dolar_venta'] ?? '1220')));
        $normalizados['dolar_fecha_actualizacion'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($datos['dolar_fecha_actualizacion'] ?? '')) ? (string) $datos['dolar_fecha_actualizacion'] : '';
        $normalizados['productos_cotizacion_dolar'] = (string) max(0.0001, (float) str_replace(',', '.', (string) ($datos['productos_cotizacion_dolar'] ?? $normalizados['dolar_venta'])));
        $normalizados['ticket_imagen_completa'] = $this->normalizarBooleano($datos['ticket_imagen_completa'] ?? '0', '0');
        $normalizados['ticket_logo_termico'] = $this->normalizarBooleano($datos['ticket_logo_termico'] ?? '1', '1');
        $normalizados['mostrar_reparaciones'] = $this->normalizarBooleano($datos['mostrar_reparaciones'] ?? '1', '1');
        $normalizados['configuracion_separada'] = $this->normalizarBooleano($datos['configuracion_separada'] ?? '1', '1');
        $normalizados['backup_b2_habilitado'] = $this->normalizarBooleano($datos['backup_b2_habilitado'] ?? '0', '0');
        $normalizados['backup_automatico'] = $this->normalizarBooleano($datos['backup_automatico'] ?? '0', '0');
        $normalizados['backup_local_habilitado'] = $this->normalizarBooleano($datos['backup_local_habilitado'] ?? '1', '1');
        $normalizados['backup_auto_local'] = $this->normalizarBooleano($datos['backup_auto_local'] ?? '1', '1');
        $normalizados['backup_auto_backblaze'] = $this->normalizarBooleano($datos['backup_auto_backblaze'] ?? '0', '0');
        $normalizados['formato_impresion_ticket'] = $this->normalizarOpcion((string) ($datos['formato_impresion_ticket'] ?? '80'), ['a4', '80', '58'], '80');
        $normalizados['ticket_fuente'] = $this->normalizarOpcion((string) ($datos['ticket_fuente'] ?? 'Arial'), ['Arial', 'Courier', 'Verdana', 'Tahoma'], 'Arial');
        $normalizados['ticket_tamano_fuente'] = (string) max(8, min(16, (int) ($datos['ticket_tamano_fuente'] ?? 12)));
        $normalizados['balanza_modo'] = $this->normalizarOpcion((string) ($datos['balanza_modo'] ?? 'auto'), ['auto', 'cantidad', 'importe'], 'auto');
        $normalizados['balanza_plu_digitos'] = (string) max(1, min(8, (int) ($datos['balanza_plu_digitos'] ?? 5)));
        $normalizados['balanza_valor_decimales'] = (string) max(0, min(4, (int) ($datos['balanza_valor_decimales'] ?? 3)));
        $normalizados['balanza_importe_decimales'] = (string) max(0, min(4, (int) ($datos['balanza_importe_decimales'] ?? 2)));
        $normalizados['auth_modo'] = $this->normalizarOpcion((string) ($datos['auth_modo'] ?? 'login'), ['login', 'sin_login'], 'login');

        return $normalizados;
    }

    private function normalizarBooleano(mixed $valor, string $defecto): string
    {
        $texto = strtolower(trim((string) $valor));
        $normalizado = $defecto;

        if (in_array($texto, ['1', 'true', 'si', 'sí', 'on'], true)) {
            $normalizado = '1';
        } elseif (in_array($texto, ['0', 'false', 'no', 'off'], true)) {
            $normalizado = '0';
        }

        return $normalizado;
    }

    private function normalizarOpcion(string $valor, array $permitidos, string $defecto): string
    {
        $texto = trim($valor);
        $normalizado = in_array($texto, $permitidos, true) ? $texto : $defecto;

        return $normalizado;
    }

    private function normalizarPrefijos(string $valor): array
    {
        $prefijos = [];
        $partes = preg_split('/[,\s;]+/', $valor) ?: [];

        foreach ($partes as $parte) {
            $prefijo = preg_replace('/\D+/', '', (string) $parte) ?? '';

            if ($prefijo !== '' && strlen($prefijo) <= 4 && !in_array($prefijo, $prefijos, true)) {
                $prefijos[] = $prefijo;
            }
        }

        return $prefijos;
    }

    private function razonSocialParaComprobante(array $datos): string
    {
        $razonSocial = trim((string) ($datos['razon_social'] ?? ''));

        if ($razonSocial === '') {
            $razonSocial = trim((string) ($datos['nombre_comercio'] ?? ''));
        }

        return $razonSocial;
    }

    private function domicilioCompleto(array $datos): string
    {
        $partes = [];
        $claves = ['domicilio', 'localidad', 'provincia'];

        foreach ($claves as $clave) {
            $valor = trim((string) ($datos[$clave] ?? ''));

            if ($valor !== '') {
                $partes[] = $valor;
            }
        }

        $domicilio = implode(', ', $partes);

        return $domicilio;
    }
}
