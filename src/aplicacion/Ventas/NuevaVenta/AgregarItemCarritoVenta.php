<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\NuevaVenta;

use Ventas\Aplicacion\Productos\CasosUso\ObtenerProductoParaVenta;
use Ventas\Aplicacion\Stock\CasosUso\BuscarStockPorId;
use Ventas\Dominio\Productos\Repositorios\ProductoRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\CarritoVentaRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\ConfiguracionVentaRepository;

final class AgregarItemCarritoVenta
{
    public function __construct(
        private readonly CarritoVentaRepository $carritoVentaRepository,
        private readonly ProductoRepository $productoRepository,
        private readonly ObtenerProductoParaVenta $obtenerProductoParaVenta,
        private readonly BuscarStockPorId $buscarStockPorId,
        private readonly ConfiguracionVentaRepository $configuracionVentaRepository
    ) {
    }

    public function ejecutar(
        int $idProducto,
        float $cantidad,
        float $descuento,
        string $precioManualRaw,
        float $precioManual,
        bool $aplicarListaExistente,
        string $buscarProducto,
        int $idListaPrecio
    ): array {
        $resultado = [
            'ok' => false,
            'error' => '',
            'carrito' => $this->carritoVentaRepository->obtener(),
        ];

        $datosProducto = $this->resolverProductoYCantidad($idProducto, $cantidad, $precioManualRaw, $precioManual, $buscarProducto, $idListaPrecio, $this->configuracionVentaRepository->configuracionBalanza());
        $idProducto = (int) $datosProducto['id_producto'];
        $cantidad = (float) $datosProducto['cantidad'];
        $precioManualRaw = (string) $datosProducto['precio_manual_raw'];
        $precioManual = (float) $datosProducto['precio_manual'];

        if ($idProducto <= 0 || $cantidad <= 0) {
            $resultado['error'] = 'Producto o cantidad invalidos.';
        } else {
            $descuento = $this->normalizarDescuento($descuento);
            $producto = $this->obtenerProductoParaVenta->ejecutar($idProducto);

            if ($producto === null || (int) ($producto['activo'] ?? 0) !== 1) {
                $resultado['error'] = 'Producto no disponible.';
            } else {
                $carrito = $resultado['carrito'];
                $precioListaInfo = $this->productoRepository->obtenerPrecioPorLista($idProducto, $idListaPrecio);
                $precioLista = $precioListaInfo !== null ? (float) $precioListaInfo['precio'] : null;
                $usaPrecioManual = $precioManualRaw !== '' && $precioManual >= 0;
                $precioUnitario = $usaPrecioManual ? $precioManual : (float) $precioLista;

                if (!$usaPrecioManual && ($precioLista === null || $precioLista <= 0)) {
                    $resultado['error'] = 'El producto no tiene precio cargado en la lista seleccionada.';
                } else {
                    $factor = $this->normalizarMinimoCero((float) ($producto['factor_conversion'] ?? 0));
                    $idStockConsumo = $this->obtenerIdStockConsumo($producto);
                    $errorStock = $this->validarStock($this->configuracionVentaRepository->controlarStockVentas(), $idStockConsumo, $carrito, $idProducto, $cantidad, $factor);

                    if ($errorStock !== '') {
                        $resultado['error'] = $errorStock;
                    } else {
                        $carrito = $this->actualizarPreciosExistentes($carrito, $aplicarListaExistente, $idListaPrecio);
                        $carrito[] = [
                            'id_producto' => $idProducto,
                            'nombre' => (string) $producto['nombre'],
                            'cantidad' => $cantidad,
                            'precio_unit' => $precioUnitario,
                            'descuento' => $descuento,
                        ];
                        $this->carritoVentaRepository->guardar($carrito);
                        $resultado = [
                            'ok' => true,
                            'error' => '',
                            'carrito' => $carrito,
                        ];
                    }
                }
            }
        }

        return $resultado;
    }

    private function resolverProductoYCantidad(
        int $idProducto,
        float $cantidad,
        string $precioManualRaw,
        float $precioManual,
        string $buscarProducto,
        int $idListaPrecio,
        array $configBalanza
    ): array {
        $datos = [
            'id_producto' => $idProducto,
            'cantidad' => $cantidad,
            'precio_manual_raw' => $precioManualRaw,
            'precio_manual' => $precioManual,
        ];
        $codigoBusqueda = $buscarProducto;
        $coincidencias = [];

        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*\*\s*(.+)$/', $codigoBusqueda, $coincidencias) === 1) {
            $cantidadCodigo = $this->normalizarNumeroTexto((string) $coincidencias[1], 1.0);

            if ($cantidadCodigo > 0) {
                $datos['cantidad'] = $cantidadCodigo;
            }

            $codigoBusqueda = trim((string) $coincidencias[2]);
        }

        $codigoBalanza = $this->interpretarCodigoBalanza($codigoBusqueda, $idListaPrecio, $configBalanza);

        if ((int) $datos['id_producto'] <= 0 && $codigoBalanza !== null) {
            $datos['id_producto'] = (int) $codigoBalanza['producto']['id'];
            $datos['cantidad'] = (float) $codigoBalanza['cantidad'];

            if ((string) $datos['precio_manual_raw'] === '' && (float) $codigoBalanza['precio_unit'] > 0) {
                $datos['precio_manual'] = (float) $codigoBalanza['precio_unit'];
                $datos['precio_manual_raw'] = (string) $codigoBalanza['precio_unit'];
            }
        }

        if ((int) $datos['id_producto'] <= 0 && $codigoBusqueda !== '') {
            $productoCodigo = $this->productoRepository->buscarPorCodigoOPluVenta($codigoBusqueda);

            if ($productoCodigo !== null) {
                $datos['id_producto'] = (int) $productoCodigo['id'];
            }
        }

        return $datos;
    }

    private function interpretarCodigoBalanza(string $codigo, int $idListaPrecio, array $configBalanza): ?array
    {
        $mejor = null;
        $codigoNormalizado = $this->sanitizarCodigo($codigo);

        if (strlen($codigoNormalizado) >= 8) {
            $cuerpo = strlen($codigoNormalizado) >= 13 ? substr($codigoNormalizado, 0, 12) : $codigoNormalizado;
            $pluDigitos = (int) ($configBalanza['plu_digitos'] ?? 5);
            $formatos = [
                [2, $pluDigitos, 12 - 2 - $pluDigitos],
                [1, $pluDigitos, 12 - 1 - $pluDigitos],
                [2, 5, 5],
                [2, 4, 6],
                [2, 6, 4],
                [2, 3, 7],
                [1, 5, 6],
            ];
            $prefijosImporte = $configBalanza['prefijos_importe'] ?? [];
            $prefijosCantidad = $configBalanza['prefijos_cantidad'] ?? [];

            foreach ($formatos as $formato) {
                $mejor = $this->evaluarFormatoBalanza($mejor, $formato, $cuerpo, $idListaPrecio, $configBalanza, $prefijosImporte, $prefijosCantidad);
            }
        }

        return $mejor;
    }

    private function evaluarFormatoBalanza(
        ?array $mejor,
        array $formato,
        string $cuerpo,
        int $idListaPrecio,
        array $configBalanza,
        array $prefijosImporte,
        array $prefijosCantidad
    ): ?array {
        $resultado = $mejor;
        $largoPrefijo = (int) ($formato[0] ?? 0);
        $largoPlu = (int) ($formato[1] ?? 0);
        $largoValor = (int) ($formato[2] ?? 0);

        if ($largoValor > 0 && strlen($cuerpo) >= $largoPrefijo + $largoPlu + $largoValor) {
            $prefijo = substr($cuerpo, 0, $largoPrefijo);
            $plu = substr($cuerpo, $largoPrefijo, $largoPlu);
            $valor = substr($cuerpo, $largoPrefijo + $largoPlu, $largoValor);
            $producto = $this->productoRepository->buscarPorCodigoOPluVenta($plu);
            $raw = (int) $valor;

            if ($producto !== null && $raw > 0) {
                $precioListaInfo = $this->productoRepository->obtenerPrecioPorLista((int) $producto['id'], $idListaPrecio);
                $precioUnitario = $precioListaInfo !== null ? (float) $precioListaInfo['precio'] : (float) ($producto['precio_final'] ?? 0);
                $candidatos = $this->candidatosBalanza($raw, $precioUnitario, $configBalanza);

                foreach ($candidatos as $candidato) {
                    $resultado = $this->elegirMejorCandidatoBalanza($resultado, $candidato, $prefijo, $producto, $prefijosImporte, $prefijosCantidad, $configBalanza);
                }
            }
        }

        return $resultado;
    }

    private function candidatosBalanza(int $raw, float $precioUnitario, array $configBalanza): array
    {
        $candidatos = [];
        $cantidad = $raw / (10 ** (int) ($configBalanza['valor_decimales'] ?? 3));

        if ($cantidad > 0) {
            $candidatos[] = [
                'modo' => 'cantidad',
                'cantidad' => $cantidad,
                'precio_unit' => $precioUnitario,
            ];
        }

        if ($precioUnitario > 0) {
            $importe = $raw / (10 ** (int) ($configBalanza['importe_decimales'] ?? 2));
            $cantidadImporte = $importe / $precioUnitario;

            if ($cantidadImporte > 0) {
                $candidatos[] = [
                    'modo' => 'importe',
                    'cantidad' => $cantidadImporte,
                    'precio_unit' => $precioUnitario,
                ];
            }
        }

        return $candidatos;
    }

    private function elegirMejorCandidatoBalanza(
        ?array $mejor,
        array $candidato,
        string $prefijo,
        array $producto,
        array $prefijosImporte,
        array $prefijosCantidad,
        array $configBalanza
    ): ?array {
        $resultado = $mejor;
        $modoConfig = (string) ($configBalanza['modo'] ?? 'auto');
        $modoCandidato = (string) ($candidato['modo'] ?? '');
        $modoAceptado = $modoConfig === $modoCandidato || $modoConfig === 'auto';

        if ($modoAceptado) {
            $score = 10;
            $prefijoDos = substr($prefijo, 0, 2);

            if ($modoConfig === $modoCandidato) {
                $score += 100;
            }

            if ($modoCandidato === 'importe' && in_array($prefijoDos, $prefijosImporte, true)) {
                $score += 50;
            }

            if ($modoCandidato === 'cantidad' && in_array($prefijoDos, $prefijosCantidad, true)) {
                $score += 50;
            }

            if ($modoCandidato === 'cantidad' && $resultado === null) {
                $score += 5;
            }

            if ((float) $candidato['cantidad'] > 0 && (float) $candidato['cantidad'] <= 9999) {
                $score += 5;
            }

            if ($resultado === null || $score > (int) $resultado['score']) {
                $resultado = [
                    'score' => $score,
                    'producto' => $producto,
                    'cantidad' => (float) $candidato['cantidad'],
                    'precio_unit' => (float) $candidato['precio_unit'],
                    'modo' => $modoCandidato,
                ];
            }
        }

        return $resultado;
    }

    private function validarStock(
        bool $controlarStock,
        ?int $idStockConsumo,
        array $carrito,
        int $idProducto,
        float $cantidad,
        float $factor
    ): string {
        $error = '';

        if ($controlarStock && $idStockConsumo !== null) {
            $stock = $this->buscarStockPorId->ejecutar($idStockConsumo);

            if ($stock === null) {
                $error = 'Stock no encontrado para el producto.';
            } else {
                $consumo = $this->calcularConsumoStock($cantidad, $factor);

                foreach ($carrito as $itemCargado) {
                    if ((int) ($itemCargado['id_producto'] ?? 0) === $idProducto) {
                        $consumo += $this->calcularConsumoStock((float) ($itemCargado['cantidad'] ?? 0), $factor);
                    }
                }

                $disponible = $stock->cantidad();

                if ($consumo > $disponible + 0.0000001) {
                    $error = 'Stock insuficiente. Disponible: ' . $disponible;
                }
            }
        }

        return $error;
    }

    private function actualizarPreciosExistentes(array $carrito, bool $aplicarListaExistente, int $idListaPrecio): array
    {
        $actualizado = $carrito;

        if ($aplicarListaExistente) {
            foreach ($actualizado as $indice => $item) {
                $precioListaItemInfo = $this->productoRepository->obtenerPrecioPorLista((int) $item['id_producto'], $idListaPrecio);
                $precioListaItem = $precioListaItemInfo !== null ? (float) $precioListaItemInfo['precio'] : null;

                if ($precioListaItem !== null && $precioListaItem > 0) {
                    $actualizado[$indice]['precio_unit'] = $precioListaItem;
                }
            }
        }

        return $actualizado;
    }

    private function obtenerIdStockConsumo(array $producto): ?int
    {
        $id = null;
        $idStock = $producto['id_stock'] ?? null;
        $idAsociado = $producto['id_asociado'] ?? null;

        if ($idStock !== null && (int) $idStock > 0) {
            $id = (int) $idStock;
        }

        if ($id === null && $idAsociado !== null && (int) $idAsociado > 0) {
            $id = (int) $idAsociado;
        }

        return $id;
    }

    private function calcularConsumoStock(float $cantidadProducto, float $factorConversion): float
    {
        $cantidad = $this->normalizarMinimoCero($cantidadProducto);
        $factor = $this->normalizarMinimoCero($factorConversion);
        $consumo = $cantidad * $factor;

        return $consumo;
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

    private function normalizarNumeroTexto(string $valor, float $default): float
    {
        $normalizado = trim(str_replace(',', '.', $valor));
        $numero = is_numeric($normalizado) ? (float) $normalizado : $default;

        return $numero;
    }

    private function sanitizarCodigo(string $codigo): string
    {
        $normalizado = preg_replace('/\D+/', '', $codigo) ?? '';

        return $normalizado;
    }
}
