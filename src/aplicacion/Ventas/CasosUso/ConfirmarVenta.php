<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\CasosUso;

use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\CarritoVentaRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\ConfiguracionVentaRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\FormularioVentaRepository;
use Ventas\Dominio\Ventas\Repositorios\VentaRepository;

final class ConfirmarVenta
{
    public function __construct(
        private readonly VentaRepository $ventaRepository,
        private readonly CarritoVentaRepository $carritoVentaRepository,
        private readonly FormularioVentaRepository $formularioVentaRepository,
        private readonly ConfiguracionVentaRepository $configuracionVentaRepository
    ) {
    }

    public function ejecutar(array $datos): array
    {
        $resultado = [
            'ok' => false,
            'error' => '',
            'id_venta' => 0,
            'redirigir' => 'index.php?c=ventas&a=nueva',
            'mensaje' => '',
            'tipo_comprobante' => (int) ($datos['tipo_comprobante'] ?? 98),
            'imprimir_ticket' => (bool) ($datos['imprimir_ticket'] ?? false),
            'generar_pdf' => false,
            'es_fiscal' => false,
        ];
        $tipoComprobante = $this->normalizarTipoComprobante((int) ($datos['tipo_comprobante'] ?? 98));
        $tipoInfo = $this->tipoComprobante($tipoComprobante);
        $idCliente = $this->normalizarIdCliente((int) ($datos['id_cliente'] ?? 1));
        $idUsuario = (int) ($datos['id_usuario'] ?? 0);
        $carrito = $this->carritoVentaRepository->obtener();
        $totalCarrito = $this->calcularTotalCarrito($carrito);
        $formaPago = strtolower(trim((string) ($datos['forma_pago'] ?? 'contado')));
        $error = $this->validarConfirmacion($idCliente, $tipoInfo, $formaPago, $totalCarrito);

        if ($error !== '') {
            $resultado['error'] = $error;
            $this->guardarFormularioError($datos, $idCliente, $tipoComprobante);
        } else {
            $confirmacion = $this->ventaRepository->confirmarVenta(
                $idCliente,
                $idUsuario,
                $carrito,
                $this->configuracionVentaRepository->controlarStockVentas()
            );

            if (($confirmacion['ok'] ?? false) === true) {
                $idVenta = (int) ($confirmacion['id_venta'] ?? 0);
                $resultado = $this->procesarPostConfirmacion($idVenta, $idCliente, $tipoComprobante, $tipoInfo, $formaPago, $totalCarrito, $datos);
            } else {
                $resultado['error'] = (string) ($confirmacion['error'] ?? 'No se pudo confirmar la venta.');
                $this->guardarFormularioError($datos, $idCliente, $tipoComprobante);
            }
        }

        return $resultado;
    }

    private function validarConfirmacion(int $idCliente, array $tipoInfo, string $formaPago, float $totalCarrito): string
    {
        $error = '';
        $operacion = (string) ($tipoInfo['operacion'] ?? 'factura_x');

        if ($operacion === 'presupuesto') {
            $error = 'La confirmacion de presupuestos no corresponde al bloque de venta.';
        } elseif ($formaPago === 'saldo_favor') {
            $saldoFavor = $this->ventaRepository->saldoFavorCliente($idCliente);

            if ($totalCarrito <= 0) {
                $error = 'El carrito esta vacio.';
            } elseif ($saldoFavor + 0.00001 < $totalCarrito) {
                $error = 'El saldo a favor del cliente es ' . $this->moneda($saldoFavor) . ' y no alcanza para pagar ' . $this->moneda($totalCarrito) . '.';
            }
        }

        if ($error === '' && in_array($operacion, ['nota_credito', 'nota_debito', 'nota_credito_exportacion', 'nota_debito_exportacion'], true)) {
            $error = 'Las notas de credito/debito deben referenciar un comprobante autorizado. Falta cargar el modulo de comprobante asociado.';
        } elseif ($error === '' && $operacion === 'exportacion') {
            $error = 'Factura E requiere datos de exportacion (pais, CUIT pais, moneda, incoterms y datos aduaneros si corresponden). Falta cargar el modulo de exportacion.';
        } elseif ($error === '' && $operacion !== 'presupuesto') {
            $cliente = $this->ventaRepository->buscarClienteFactura($idCliente);
            $error = $this->validarClienteComprobante((int) ($tipoInfo['codigo'] ?? 98), $cliente);
        }

        return $error;
    }

    private function procesarPostConfirmacion(
        int $idVenta,
        int $idCliente,
        int $tipoComprobante,
        array $tipoInfo,
        string $formaPago,
        float $totalCarrito,
        array $datos
    ): array {
        $esFiscal = (($tipoInfo['fiscal'] ?? true) === true);
        $okFiscal = true;

        if ($esFiscal) {
            $okFiscal = $this->ventaRepository->crearFiscalPendiente($idVenta, (string) $tipoInfo['operacion'], $tipoComprobante, $this->configuracionVentaRepository->configuracionFiscal());
        }

        if ($formaPago === 'cuenta_corriente') {
            $this->ventaRepository->crearCuentaCorriente(
                $idCliente,
                'Venta #' . $idVenta,
                $totalCarrito,
                max(1, (int) ($datos['cc_cuotas'] ?? 1)),
                trim((string) (($datos['cc_vencimientos'][0] ?? date('Y-m-d')))),
                $idVenta,
                (array) ($datos['cc_vencimientos'] ?? [])
            );
        } elseif ($formaPago === 'saldo_favor') {
            $this->ventaRepository->aplicarSaldoFavor($idCliente, $idVenta, $totalCarrito);
        }

        $this->carritoVentaRepository->guardar([]);
        $this->formularioVentaRepository->guardar([]);

        $resultado = [
            'ok' => true,
            'error' => '',
            'id_venta' => $idVenta,
            'redirigir' => 'index.php?c=ventas&a=lista',
            'mensaje' => $this->mensajeConfirmacion($esFiscal, $okFiscal),
            'tipo_comprobante' => $tipoComprobante,
            'imprimir_ticket' => (bool) ($datos['imprimir_ticket'] ?? false),
            'generar_pdf' => true,
            'es_fiscal' => $esFiscal,
        ];

        if ((bool) ($datos['imprimir_ticket'] ?? false)) {
            $resultado['redirigir'] = 'index.php?c=ventas&a=ticket&auto_print=1&id=' . $idVenta;
        }

        return $resultado;
    }

    private function guardarFormularioError(array $datos, int $idCliente, int $tipoComprobante): void
    {
        $this->formularioVentaRepository->guardar([
            'id_cliente' => $idCliente,
            'buscar_cliente' => (string) ($datos['buscar_cliente'] ?? ''),
            'id_producto' => '',
            'cantidad' => 1,
            'descuento' => 0,
            'tipo_comprobante' => $tipoComprobante,
            'buscar_producto' => '',
        ]);
    }

    private function calcularTotalCarrito(array $carrito): float
    {
        $total = 0.0;

        foreach ($carrito as $item) {
            $cantidad = $this->normalizarMinimoCero((float) ($item['cantidad'] ?? 0));
            $precioUnitario = $this->normalizarMinimoCero((float) ($item['precio_unit'] ?? 0));
            $descuento = $this->normalizarDescuento((float) ($item['descuento'] ?? 0));
            $bruto = $cantidad * $precioUnitario;
            $subtotal = $bruto - (($bruto * $descuento) / 100);
            $total += $this->normalizarMinimoCero($subtotal);
        }

        return $total;
    }

    private function validarClienteComprobante(int $tipoComprobante, ?array $cliente): string
    {
        $error = '';
        $tipo = $this->tipoComprobante($tipoComprobante);

        if ((string) ($tipo['letra'] ?? '') === 'A') {
            if ($cliente === null) {
                $error = 'Cliente invalido.';
            } else {
                $documento = preg_replace('/\D+/', '', (string) ($cliente['dni'] ?? '')) ?? '';
                $condicionIva = (string) ($cliente['condicion_iva'] ?? '');

                if ($condicionIva !== 'Responsable Inscripto' || strlen($documento) !== 11) {
                    $error = 'Para Factura A selecciona un cliente Responsable Inscripto con CUIT.';
                }
            }
        }

        return $error;
    }

    private function normalizarTipoComprobante(int $tipoComprobante): int
    {
        $normalizado = $tipoComprobante;
        $tipos = $this->tiposComprobante();

        if (!isset($tipos[$normalizado])) {
            $normalizado = 98;
        }

        return $normalizado;
    }

    private function normalizarIdCliente(int $idCliente): int
    {
        $normalizado = $idCliente;

        if ($normalizado <= 0) {
            $normalizado = 1;
        }

        return $normalizado;
    }

    private function tipoComprobante(int $tipoComprobante): array
    {
        $tipos = $this->tiposComprobante();
        $tipo = $tipos[$this->normalizarTipoComprobante($tipoComprobante)];
        $tipo['codigo'] = $this->normalizarTipoComprobante($tipoComprobante);

        return $tipo;
    }

    private function tiposComprobante(): array
    {
        $tipos = [
            98 => ['letra' => 'X', 'texto' => 'Factura X', 'operacion' => 'factura_x', 'fiscal' => false],
            1 => ['letra' => 'A', 'texto' => 'Factura A', 'operacion' => 'factura', 'fiscal' => true],
            6 => ['letra' => 'B', 'texto' => 'Factura B', 'operacion' => 'factura', 'fiscal' => true],
            11 => ['letra' => 'C', 'texto' => 'Factura C', 'operacion' => 'factura', 'fiscal' => true],
            19 => ['letra' => 'E', 'texto' => 'Factura E', 'operacion' => 'exportacion', 'fiscal' => true],
            3 => ['letra' => 'A', 'texto' => 'Nota de credito A', 'operacion' => 'nota_credito', 'fiscal' => true],
            8 => ['letra' => 'B', 'texto' => 'Nota de credito B', 'operacion' => 'nota_credito', 'fiscal' => true],
            13 => ['letra' => 'C', 'texto' => 'Nota de credito C', 'operacion' => 'nota_credito', 'fiscal' => true],
            21 => ['letra' => 'E', 'texto' => 'Nota de credito E', 'operacion' => 'nota_credito_exportacion', 'fiscal' => true],
            2 => ['letra' => 'A', 'texto' => 'Nota de debito A', 'operacion' => 'nota_debito', 'fiscal' => true],
            7 => ['letra' => 'B', 'texto' => 'Nota de debito B', 'operacion' => 'nota_debito', 'fiscal' => true],
            12 => ['letra' => 'C', 'texto' => 'Nota de debito C', 'operacion' => 'nota_debito', 'fiscal' => true],
            20 => ['letra' => 'E', 'texto' => 'Nota de debito E', 'operacion' => 'nota_debito_exportacion', 'fiscal' => true],
            99 => ['letra' => 'X', 'texto' => 'Presupuesto', 'operacion' => 'presupuesto', 'fiscal' => false],
        ];

        return $tipos;
    }

    private function mensajeConfirmacion(bool $esFiscal, bool $okFiscal): string
    {
        $mensaje = 'Venta confirmada. Revisar PDF y cola fiscal en logs.';

        if ($okFiscal && $esFiscal) {
            $mensaje = 'Venta confirmada. PDF generado y factura fiscal en cola.';
        } elseif (!$esFiscal) {
            $mensaje = 'Factura X generada. Descuenta stock y no se envia a AFIP.';
        } elseif ($okFiscal) {
            $mensaje = 'Venta confirmada. PDF generado. Revisar cola fiscal.';
        }

        return $mensaje;
    }

    private function normalizarDescuento(float $descuento): float
    {
        $normalizado = $this->normalizarMinimoCero($descuento);

        if ($normalizado > 100) {
            $normalizado = 100;
        }

        return $normalizado;
    }

    private function normalizarMinimoCero(float $valor): float
    {
        $normalizado = $valor;

        if ($normalizado < 0) {
            $normalizado = 0;
        }

        return $normalizado;
    }

    private function moneda(float $valor): string
    {
        $moneda = '$' . number_format($valor, 2, ',', '.');

        return $moneda;
    }
}
