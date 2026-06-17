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

    private function valoresDefecto(): array
    {
        $datos = [
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
            'url_reparaciones' => 'index.php?c=reparaciones&a=index',
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
        $normalizados['productos_cotizacion_dolar'] = $this->normalizarBooleano($datos['productos_cotizacion_dolar'] ?? '0', '0');
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
