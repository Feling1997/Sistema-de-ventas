<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Domain\Repositorios;

use Ventas\ListasPrecios\Domain\Entidades\ListaPrecio;

interface ListaPrecioRepository
{
    /**
     * @return array<int, ListaPrecio>
     */
    public function listar(bool $soloActivas = true, string $ordenSql = 'nombre ASC'): array;

    public function buscarPorId(int $id): ?ListaPrecio;

    public function idPredeterminada(): int;

    public function esListaBase(int $id): bool;

    public function crear(string $nombre, int $activo): bool;

    public function actualizar(int $id, string $nombre, int $activo): bool;

    public function eliminar(int $id): bool;

    public function precioProducto(int $idProducto, int $idLista): ?float;

    /**
     * @return array{porcentaje: float, precio: float}|null
     */
    public function precioProductoCargado(int $idProducto, int $idLista): ?array;

    /**
     * @return array{porcentaje: float, precio: float}|null
     */
    public function precioProductoCompleto(int $idProducto, int $idLista): ?array;

    public function guardarPrecioProducto(int $idProducto, int $idLista, float $porcentaje, float $precio): bool;

    public function guardarPrecioProductoOrigen(int $idProducto, int $idLista, float $porcentaje, float $precio, string $origen = 'manual'): bool;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function productosParaExportar(int $idLista = 0): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function historialPrecios(string $desde = '', string $hasta = '', int $idLista = 0): array;

    public function inicializarEsquema(): void;
}
