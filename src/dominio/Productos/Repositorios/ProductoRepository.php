<?php

declare(strict_types=1);

namespace Ventas\Dominio\Productos\Repositorios;

use Ventas\Dominio\Productos\Entidades\Producto;

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

    public function eliminarNoVendidos(): int;
}
