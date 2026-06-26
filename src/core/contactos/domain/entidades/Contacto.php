<?php

declare(strict_types=1);

namespace Ventas\Core\Contactos\Domain\Entidades;

final class Contacto
{
    public function __construct(
        private readonly ?int $id,
        private readonly string $nombre,
        private readonly ?string $apellido,
        private readonly ?string $telefono,
        private readonly ?string $correo,
        private readonly ?string $documento,
        private readonly ?string $direccion,
        private readonly ?string $observaciones,
        private readonly bool $activo,
        private readonly ?string $creadoEn,
        private readonly ?string $actualizadoEn
    ) {
    }

    /**
     * @param array<string, mixed> $datos
     */
    public static function desdeArray(array $datos): self
    {
        $contacto = new self(
            isset($datos['id']) ? (int) $datos['id'] : null,
            (string) ($datos['nombre'] ?? ''),
            isset($datos['apellido']) ? (string) $datos['apellido'] : null,
            isset($datos['telefono']) ? (string) $datos['telefono'] : null,
            isset($datos['correo']) ? (string) $datos['correo'] : null,
            isset($datos['documento']) ? (string) $datos['documento'] : null,
            isset($datos['direccion']) ? (string) $datos['direccion'] : null,
            isset($datos['observaciones']) ? (string) $datos['observaciones'] : null,
            (bool) ($datos['activo'] ?? true),
            isset($datos['created_at']) ? (string) $datos['created_at'] : null,
            isset($datos['updated_at']) ? (string) $datos['updated_at'] : null
        );

        return $contacto;
    }

    public function id(): ?int
    {
        $id = $this->id;

        return $id;
    }

    public function nombre(): string
    {
        $nombre = $this->nombre;

        return $nombre;
    }

    public function apellido(): ?string
    {
        $apellido = $this->apellido;

        return $apellido;
    }

    public function telefono(): ?string
    {
        $telefono = $this->telefono;

        return $telefono;
    }

    public function correo(): ?string
    {
        $correo = $this->correo;

        return $correo;
    }

    public function documento(): ?string
    {
        $documento = $this->documento;

        return $documento;
    }

    public function direccion(): ?string
    {
        $direccion = $this->direccion;

        return $direccion;
    }

    public function observaciones(): ?string
    {
        $observaciones = $this->observaciones;

        return $observaciones;
    }

    public function activo(): bool
    {
        $activo = $this->activo;

        return $activo;
    }

    public function creadoEn(): ?string
    {
        $creadoEn = $this->creadoEn;

        return $creadoEn;
    }

    public function actualizadoEn(): ?string
    {
        $actualizadoEn = $this->actualizadoEn;

        return $actualizadoEn;
    }

    /**
     * @return array<string, mixed>
     */
    public function comoArray(): array
    {
        $datos = [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'documento' => $this->documento,
            'direccion' => $this->direccion,
            'observaciones' => $this->observaciones,
            'activo' => $this->activo,
            'creado_en' => $this->creadoEn,
            'actualizado_en' => $this->actualizadoEn,
        ];

        return $datos;
    }
}
