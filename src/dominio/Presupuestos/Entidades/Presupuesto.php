<?php

declare(strict_types=1);

namespace Ventas\Dominio\Presupuestos\Entidades;

use InvalidArgumentException;

final class Presupuesto
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $fecha,
        private readonly float $total,
        private readonly string $clienteNombre,
        private readonly string $usuarioNombre
    ) {
        $this->validar();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function fecha(): string
    {
        return $this->fecha;
    }

    public function total(): float
    {
        return $this->total;
    }

    public function clienteNombre(): string
    {
        return $this->clienteNombre;
    }

    public function usuarioNombre(): string
    {
        return $this->usuarioNombre;
    }

    private function validar(): void
    {
        if ($this->id !== null && $this->id <= 0) {
            throw new InvalidArgumentException('El ID del presupuesto debe ser positivo.');
        }

        if (trim($this->fecha) === '') {
            throw new InvalidArgumentException('La fecha del presupuesto es obligatoria.');
        }

        if ($this->total < 0) {
            throw new InvalidArgumentException('El total del presupuesto no puede ser negativo.');
        }
    }
}
