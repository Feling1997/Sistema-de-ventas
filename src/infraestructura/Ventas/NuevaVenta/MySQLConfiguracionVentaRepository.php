<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Ventas\NuevaVenta;

use PDO;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\ConfiguracionVentaRepository;

final class MySQLConfiguracionVentaRepository implements ConfiguracionVentaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function configuracionFiscal(): array
    {
        $datos = $this->obtenerConfiguracion();
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

    public function controlarStockVentas(): bool
    {
        $datos = $this->obtenerConfiguracion();
        $controlar = (string) ($datos['controlar_stock_ventas'] ?? '1') === '1';

        return $controlar;
    }

    public function configuracionInicio(): array
    {
        $datos = $this->obtenerConfiguracion();
        $configuracion = [
            'mostrar_reparaciones' => (string) ($datos['mostrar_reparaciones'] ?? '1'),
            'url_reparaciones' => (string) ($datos['url_reparaciones'] ?? 'index.php?c=reparaciones&a=index'),
        ];

        return $configuracion;
    }

    public function configuracionBalanza(): array
    {
        $datos = $this->obtenerConfiguracion();
        $modo = (string) ($datos['balanza_modo'] ?? 'auto');
        $configuracion = [
            'modo' => in_array($modo, ['auto', 'cantidad', 'importe'], true) ? $modo : 'auto',
            'plu_digitos' => max(1, min(8, (int) ($datos['balanza_plu_digitos'] ?? 5))),
            'valor_decimales' => max(0, min(4, (int) ($datos['balanza_valor_decimales'] ?? 3))),
            'importe_decimales' => max(0, min(4, (int) ($datos['balanza_importe_decimales'] ?? 2))),
            'prefijos_cantidad' => $this->normalizarPrefijos((string) ($datos['balanza_prefijos_cantidad'] ?? '20,21,23,25,27,29')),
            'prefijos_importe' => $this->normalizarPrefijos((string) ($datos['balanza_prefijos_importe'] ?? '22,24,26,28')),
        ];

        return $configuracion;
    }

    private function obtenerConfiguracion(): array
    {
        $datos = $this->valoresDefecto();
        $claves = array_keys($datos);
        $statement = $this->pdo->prepare(
            "SELECT clave, valor
             FROM configuraciones
             WHERE clave IN (
                 ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                 ?, ?, ?, ?, ?, ?, ?, ?
             )"
        );
        $statement->execute($claves);
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $clave = (string) ($fila['clave'] ?? '');

            if (array_key_exists($clave, $datos)) {
                $datos[$clave] = (string) ($fila['valor'] ?? '');
            }
        }

        $datos = $this->normalizar($datos);

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

        return $configuracion;
    }

    private function normalizar(array $datos): array
    {
        $normalizados = $datos;
        $normalizados['punto_venta'] = (string) max(1, (int) ($datos['punto_venta'] ?? 1));
        $normalizados['controlar_stock_ventas'] = (string) ($datos['controlar_stock_ventas'] ?? '1') === '1' ? '1' : '0';
        $normalizados['ticket_imagen_completa'] = (string) ($datos['ticket_imagen_completa'] ?? '0') === '1' ? '1' : '0';
        $normalizados['ticket_logo_termico'] = (string) ($datos['ticket_logo_termico'] ?? '1') === '1' ? '1' : '0';
        $normalizados['mostrar_reparaciones'] = (string) ($datos['mostrar_reparaciones'] ?? '1') === '1' ? '1' : '0';
        $formato = trim((string) ($datos['formato_impresion_ticket'] ?? '80'));
        $normalizados['formato_impresion_ticket'] = in_array($formato, ['a4', '80', '58'], true) ? $formato : '80';
        $modoBalanza = trim((string) ($datos['balanza_modo'] ?? 'auto'));
        $normalizados['balanza_modo'] = in_array($modoBalanza, ['auto', 'cantidad', 'importe'], true) ? $modoBalanza : 'auto';
        $normalizados['balanza_plu_digitos'] = (string) max(1, min(8, (int) ($datos['balanza_plu_digitos'] ?? 5)));
        $normalizados['balanza_valor_decimales'] = (string) max(0, min(4, (int) ($datos['balanza_valor_decimales'] ?? 3)));
        $normalizados['balanza_importe_decimales'] = (string) max(0, min(4, (int) ($datos['balanza_importe_decimales'] ?? 2)));

        return $normalizados;
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
