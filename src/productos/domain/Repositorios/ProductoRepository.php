<?php

declare(strict_types=1);

namespace Ventas\Productos\Domain\Repositorios;

use Ventas\Productos\Domain\Entidades\Producto;

interface ProductoRepository
{
    /**
     * @return Producto[]
     */
    public function listar(): array;

    public function buscarPorId(int $id): ?Producto;

    public function listarParaVista(string $ordenCampo, string $ordenDireccion, int $idListaPrecio): array;

    public function buscarFormularioPorId(int $id): ?array;

    public function preciosProducto(int $idProducto): array;

    /**
     * @return Producto[]
     */
    public function listarPorStock(int $idStock): array;

    public function buscarParaVenta(
        string $texto,
        string $modo,
        int $idListaPrecio,
        int $limite
    ): array;

    public function obtenerPrecioPorLista(
        int $idProducto,
        int $idListaPrecio
    ): ?array;

    public function obtenerProductoParaVenta(int $idProducto): ?array;

    public function buscarPorCodigoOPluVenta(string $codigo): ?array;

    public function buscarPorCodigoBarras(string $codigo): ?array;

    public function stockExiste(int $idStock): bool;

    public function obtenerPrecioCostoStock(int $idStock): ?float;

    public function calcularPrecioFinal(float $precioCosto, float $factorConversion, float $ganancia): float;

    public function crear(
        string $nombre,
        string $codBarras,
        ?int $idStock,
        float $factorConversion,
        float $ganancia,
        float $precioFinal,
        int $activo
    ): bool;

    public function crearRetornandoId(
        string $nombre,
        string $codBarras,
        ?int $idStock,
        float $factorConversion,
        float $ganancia,
        float $precioFinal,
        int $activo
    ): int;

    public function actualizar(
        int $id,
        string $nombre,
        string $codBarras,
        ?int $idStock,
        float $factorConversion,
        float $ganancia,
        float $precioFinal,
        int $activo
    ): bool;

    public function eliminarNoVendidos(): int;

    public function eliminarNoVendido(): int;
}
