<?php

declare(strict_types=1);

namespace Ventas\Importacion\Application;

use Throwable;
use Ventas\Importacion\Domain\Repositorios\ImportacionExcelRepository;
use Ventas\Importacion\Domain\Repositorios\ImportacionHistorialRepository;
use Ventas\Importacion\Domain\Repositorios\ImportacionLogRepository;
use Ventas\Importacion\Domain\Repositorios\ImportacionPreciosRepository;
use Ventas\Importacion\Domain\Repositorios\ImportacionProductosRepository;

final class ImportarProductosDesdeExcel
{
    public function __construct(
        private readonly ImportacionExcelRepository $excelRepository,
        private readonly ImportacionProductosRepository $productosRepository,
        private readonly ImportacionPreciosRepository $preciosRepository,
        private readonly ImportacionHistorialRepository $historialRepository,
        private readonly ImportacionLogRepository $logRepository
    ) {
    }

    public function ejecutar(string $ruta, int $indiceHoja, string $archivoNombre, int $idUsuario): array
    {
        $resultado = $this->excelRepository->analizar($ruta, $indiceHoja);
        $resultado["ok"] = false;
        $resultado["mensaje"] = "";

        if (count($resultado["errores"]) > 0) {
            $resultado["mensaje"] = "El archivo tiene errores graves.";
        } else {
            try {
                $resultado["resumen"]["nuevos"] = 0;
                $resultado["resumen"]["actualizados"] = 0;
                $resultado["resumen"]["omitidos"] = 0;
                $this->productosRepository->iniciarTransaccion();
                $resultado = $this->procesarFilas($resultado);
                $this->logRepository->guardarLog($idUsuario, $archivoNombre, $resultado["resumen"]);
                $this->productosRepository->confirmarTransaccion();
                $resultado["ok"] = true;
                $resultado["mensaje"] = "Importacion completada.";
            } catch (Throwable $exception) {
                $this->productosRepository->revertirTransaccionSiActiva();
                $this->registrarLog("ImportacionProductos::importar", $exception->getMessage());
                $resultado["mensaje"] = "Error grave durante la importacion. No se guardaron cambios.";
                $resultado["errores"][] = ["fila" => 0, "mensaje" => $resultado["mensaje"]];
            }
        }

        $resultado["resumen"]["advertencias"] = count($resultado["advertencias"]);
        $resultado["resumen"]["errores"] = count($resultado["errores"]);

        return $resultado;
    }

    private function procesarFilas(array $resultado): array
    {
        foreach ($resultado["filas"] as $indice => $fila) {
            if ((string) $fila["accion"] === "Ignorar") {
                $resultado["resumen"]["omitidos"]++;
            } else {
                $resultado = $this->guardarFilaImportacion($resultado, (int) $indice, $fila);
            }
        }

        return $resultado;
    }

    private function guardarFilaImportacion(array $resultado, int $indice, array $fila): array
    {
        $idProducto = (int) ($fila["id_producto"] ?? 0);
        $codigo = (string) $fila["codigo"];
        $nombre = (string) $fila["nombre"];

        if ($idProducto > 0) {
            $this->productosRepository->actualizarProducto($idProducto, $nombre, $codigo);
            $resultado["resumen"]["actualizados"]++;
            $resultado["filas"][$indice]["accion"] = "Actualizar";
            $resultado["filas"][$indice]["detalle"][] = "Producto actualizado";
        } else {
            $precioFinal = $this->precioFinalParaProducto($fila["precios"]);
            $idProducto = $this->productosRepository->crearProducto($nombre, $codigo, $precioFinal);
            $resultado["filas"][$indice]["id_producto"] = $idProducto;
            $resultado["resumen"]["nuevos"]++;
            $resultado["filas"][$indice]["accion"] = "Nuevo";
            $resultado["filas"][$indice]["detalle"][] = "Producto creado";
        }

        $resultado = $this->guardarPreciosFila($resultado, $indice, $idProducto);

        return $resultado;
    }

    private function guardarPreciosFila(array $resultado, int $indice, int $idProducto): array
    {
        foreach ($resultado["filas"][$indice]["precios"] as $precio) {
            $idLista = (int) $precio["id_lista"];
            $valor = (float) $precio["precio"];
            $precioAnterior = $this->preciosRepository->obtenerPrecioActual($idProducto, $idLista);
            $this->preciosRepository->guardarPrecio($idProducto, $idLista, $valor);

            if (abs($precioAnterior - $valor) >= 0.01) {
                $this->historialRepository->guardarCambioPrecio($idProducto, $idLista, $precioAnterior, $valor);
            }
        }

        return $resultado;
    }

    private function precioFinalParaProducto(array $precios): float
    {
        $precio = 0.0;

        foreach ($precios as $filaPrecio) {
            if ($precio <= 0.0 && (float) $filaPrecio["precio"] > 0) {
                $precio = (float) $filaPrecio["precio"];
            }
        }

        return $precio;
    }

    private function registrarLog(string $contexto, string $mensaje): void
    {
        if (function_exists("\\registrar_log")) {
            \registrar_log($contexto, $mensaje);
        }
    }
}
