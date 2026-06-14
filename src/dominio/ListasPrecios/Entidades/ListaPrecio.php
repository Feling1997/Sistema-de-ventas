<?php

declare(strict_types=1);

namespace Ventas\Dominio\ListasPrecios\Entidades;

use InvalidArgumentException;

final class ListaPrecio
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $nombre,
        private readonly bool $activo,
        private readonly ?string $creadoEn = null
    ) {
        $this->validar();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function activo(): bool
    {
        return $this->activo;
    }

    public function creadoEn(): ?string
    {
        return $this->creadoEn;
    }

    private function validar(): void
    {
        if ($this->id !== null && $this->id <= 0) {
            throw new InvalidArgumentException('El ID de la lista de precios debe ser positivo.');
        }

        if (trim($this->nombre) === '') {
            throw new InvalidArgumentException('El nombre de la lista de precios es obligatorio.');
        }
    }
}
