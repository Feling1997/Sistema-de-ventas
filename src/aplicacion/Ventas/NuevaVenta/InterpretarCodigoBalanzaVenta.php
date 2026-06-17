<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Ventas\NuevaVenta;

use Ventas\Productos\Domain\Repositorios\ProductoRepository;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\ConfiguracionVentaRepository;

final class InterpretarCodigoBalanzaVenta
{
    public function __construct(
        private readonly ProductoRepository $productoRepository,
        private readonly ConfiguracionVentaRepository $configuracionVentaRepository
    ) {
    }

    public function ejecutar(string $codigo, int $idListaPrecio): ?array
    {
        $mejor = null;
        $codigoNormalizado = $this->sanitizarCodigo($codigo);

        if (strlen($codigoNormalizado) >= 8) {
            $configBalanza = $this->configuracionVentaRepository->configuracionBalanza();
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

            foreach ($formatos as $formato) {
                $mejor = $this->evaluarFormato($mejor, $formato, $cuerpo, $idListaPrecio, $configBalanza);
            }
        }

        return $mejor;
    }

    private function evaluarFormato(?array $mejor, array $formato, string $cuerpo, int $idListaPrecio, array $configBalanza): ?array
    {
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
                $candidatos = $this->candidatos($raw, $precioUnitario, $configBalanza);

                foreach ($candidatos as $candidato) {
                    $resultado = $this->elegirMejor($resultado, $candidato, $prefijo, $producto, $configBalanza);
                }
            }
        }

        return $resultado;
    }

    private function candidatos(int $raw, float $precioUnitario, array $configBalanza): array
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

    private function elegirMejor(?array $mejor, array $candidato, string $prefijo, array $producto, array $configBalanza): ?array
    {
        $resultado = $mejor;
        $modoConfig = (string) ($configBalanza['modo'] ?? 'auto');
        $modoCandidato = (string) ($candidato['modo'] ?? '');
        $modoAceptado = $modoConfig === $modoCandidato || $modoConfig === 'auto';

        if ($modoAceptado) {
            $score = 10;
            $prefijoDos = substr($prefijo, 0, 2);
            $prefijosImporte = $configBalanza['prefijos_importe'] ?? [];
            $prefijosCantidad = $configBalanza['prefijos_cantidad'] ?? [];

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

    private function sanitizarCodigo(string $codigo): string
    {
        $normalizado = preg_replace('/\D+/', '', $codigo) ?? '';

        return $normalizado;
    }
}
