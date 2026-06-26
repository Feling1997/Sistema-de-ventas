<?php

declare(strict_types=1);

namespace Ventas\Core\Contactos\Domain\Repositorios;

use Ventas\Core\Contactos\Domain\Entidades\Contacto;

interface ContactoRepository
{
    public function buscarPorId(int $id): ?Contacto;

    public function buscarPorDocumento(string $documento): ?Contacto;

    public function buscarPorTelefono(string $telefono): ?Contacto;

    /**
     * @return array<int, Contacto>
     */
    public function buscarPorNombre(string $nombre): array;

    /**
     * @return array<int, Contacto>
     */
    public function autocompletar(string $texto): array;

    /**
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): Contacto;

    /**
     * @param array<string, mixed> $datos
     */
    public function actualizar(int $id, array $datos): ?Contacto;

    public function desactivar(int $id): bool;
}
