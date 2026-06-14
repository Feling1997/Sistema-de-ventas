<?php

declare(strict_types=1);

namespace Ventas\Dominio\Stock\Entidades;

use InvalidArgumentException;

final class Stock
{
    public const TIPO_GENERAL = 'general';
    public const TIPO_PROPIO = 'propio';
    public const MONEDA_ARS = 'ARS';

    private readonly string $tipoStock;

    public function __construct(
        private readonly ?int $id,
        private readonly string $nombre,
        private readonly string $unidad,
        string $tipoStock,
        private readonly float $cantidad,
        private readonly float $stockMinimo,
        private readonly float $stockMaximo,
        private readonly float $precioCosto,
        private readonly string $monedaCosto,
        private readonly float $costoOrigen,
        private readonly bool $activo,
        private readonly int $unidadDecimales = 3,
        private readonly ?string $creadoEn = null
    ) {
        $this->tipoStock = self::normalizarTipoStock($tipoStock);
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

    public function unidad(): string
    {
        return $this->unidad;
    }

    public function tipoStock(): string
    {
        return $this->tipoStock;
    }

    public function cantidad(): float
    {
        return $this->cantidad;
    }

    public function stockMinimo(): float
    {
        return $this->stockMinimo;
    }

    public function stockMaximo(): float
    {
        return $this->stockMaximo;
    }

    public function precioCosto(): float
    {
        return $this->precioCosto;
    }

    public function monedaCosto(): string
    {
        return $this->monedaCosto;
    }

    public function costoOrigen(): float
    {
        return $this->costoOrigen;
    }

    public function activo(): bool
    {
        return $this->activo;
    }

    public function unidadDecimales(): int
    {
        return $this->unidadDecimales;
    }

    public function creadoEn(): ?string
    {
        return $this->creadoEn;
    }

    public function esGeneral(): bool
    {
        return $this->tipoStock === self::TIPO_GENERAL;
    }

    public function esPropio(): bool
    {
        return $this->tipoStock === self::TIPO_PROPIO;
    }

    public function estaActivo(): bool
    {
        return $this->activo;
    }

    public function estaBajoMinimo(): bool
    {
        return $this->cantidad <= $this->stockMinimo;
    }

    private static function normalizarTipoStock(string $tipoStock): string
    {
        $tipoNormalizado = strtolower(trim($tipoStock));
        $tipoFinal = self::TIPO_GENERAL;

        if ($tipoNormalizado === self::TIPO_PROPIO) {
            $tipoFinal = self::TIPO_PROPIO;
        }

        return $tipoFinal;
    }

    private function validar(): void
    {
        if (trim($this->nombre) === '') {
            throw new InvalidArgumentException('El nombre del stock es obligatorio.');
        }

        if (trim($this->unidad) === '') {
            throw new InvalidArgumentException('La unidad del stock es obligatoria.');
        }

        if ($this->id !== null && $this->id < 1) {
            throw new InvalidArgumentException('El ID del stock debe ser positivo.');
        }

        if ($this->cantidad < 0) {
            throw new InvalidArgumentException('La cantidad de stock no puede ser negativa.');
        }

        if ($this->stockMinimo < 0) {
            throw new InvalidArgumentException('El stock minimo no puede ser negativo.');
        }

        if ($this->stockMaximo < 0) {
            throw new InvalidArgumentException('El stock maximo no puede ser negativo.');
        }

        if ($this->precioCosto < 0) {
            throw new InvalidArgumentException('El precio de costo no puede ser negativo.');
        }

        if ($this->costoOrigen < 0) {
            throw new InvalidArgumentException('El costo de origen no puede ser negativo.');
        }

        if (trim($this->monedaCosto) === '') {
            throw new InvalidArgumentException('La moneda de costo es obligatoria.');
        }

        if ($this->unidadDecimales < 0) {
            throw new InvalidArgumentException('Los decimales de la unidad no pueden ser negativos.');
        }
    }
}
