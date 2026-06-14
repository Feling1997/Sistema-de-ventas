<?php

declare(strict_types=1);

namespace Ventas\Dominio\Clientes\Entidades;

use InvalidArgumentException;

final class Cliente
{
    public const ID_CONSUMIDOR_FINAL = 1;

    public function __construct(
        private readonly ?int $id,
        private readonly string $nombre,
        private readonly ?string $documento = null,
        private readonly string $tipoDocumento = 'DNI',
        private readonly string $condicionIva = 'Consumidor Final',
        private readonly ?string $email = null,
        private readonly ?string $telefono = null,
        private readonly ?string $direccion = null,
        private readonly ?int $idListaPrecio = null,
        private readonly ?string $listaPrecioNombre = null,
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

    public function documento(): ?string
    {
        return $this->documento;
    }

    public function tipoDocumento(): string
    {
        return $this->tipoDocumento;
    }

    public function condicionIva(): string
    {
        return $this->condicionIva;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function telefono(): ?string
    {
        return $this->telefono;
    }

    public function direccion(): ?string
    {
        return $this->direccion;
    }

    public function idListaPrecio(): ?int
    {
        return $this->idListaPrecio;
    }

    public function listaPrecioNombre(): ?string
    {
        return $this->listaPrecioNombre;
    }

    public function creadoEn(): ?string
    {
        return $this->creadoEn;
    }

    public function esConsumidorFinal(): bool
    {
        return $this->id === self::ID_CONSUMIDOR_FINAL;
    }

    public function puedeEditarse(): bool
    {
        return !$this->esConsumidorFinal();
    }

    public function puedeEliminarse(): bool
    {
        return !$this->esConsumidorFinal();
    }

    private function validar(): void
    {
        if (trim($this->nombre) === '') {
            throw new InvalidArgumentException('El nombre del cliente es obligatorio.');
        }

        if ($this->id !== null && $this->id < 1) {
            throw new InvalidArgumentException('El ID del cliente debe ser positivo.');
        }

        if ($this->idListaPrecio !== null && $this->idListaPrecio < 1) {
            throw new InvalidArgumentException('El ID de lista de precio debe ser positivo.');
        }
    }
}
