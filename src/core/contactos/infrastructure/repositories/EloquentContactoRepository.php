<?php

declare(strict_types=1);

namespace Ventas\Core\Contactos\Infrastructure\Repositories;

use Ventas\Core\Contactos\Domain\Entidades\Contacto;
use Ventas\Core\Contactos\Domain\Repositorios\ContactoRepository;
use Ventas\Core\Contactos\Infrastructure\Models\ContactoModel;
use Throwable;

final class EloquentContactoRepository implements ContactoRepository
{
    public function buscarPorId(int $id): ?Contacto
    {
        $contacto = null;

        try {
            $modelo = ContactoModel::query()->where('id', $id)->first();
            $contacto = $this->contactoDesdeModelo($modelo);
        } catch (Throwable) {
            $contacto = null;
        }

        return $contacto;
    }

    public function buscarPorDocumento(string $documento): ?Contacto
    {
        $contacto = null;

        try {
            $modelo = ContactoModel::query()
                ->where('documento', trim($documento))
                ->where('activo', true)
                ->first();
            $contacto = $this->contactoDesdeModelo($modelo);
        } catch (Throwable) {
            $contacto = null;
        }

        return $contacto;
    }

    public function buscarPorTelefono(string $telefono): ?Contacto
    {
        $contacto = null;

        try {
            $modelo = ContactoModel::query()
                ->where('telefono', trim($telefono))
                ->where('activo', true)
                ->first();
            $contacto = $this->contactoDesdeModelo($modelo);
        } catch (Throwable) {
            $contacto = null;
        }

        return $contacto;
    }

    public function buscarPorNombre(string $nombre): array
    {
        $contactos = $this->buscarTexto($nombre);

        return $contactos;
    }

    public function autocompletar(string $texto): array
    {
        $contactos = $this->buscarTexto($texto);

        return $contactos;
    }

    public function crear(array $datos): Contacto
    {
        $modelo = ContactoModel::query()->create($this->datosPermitidos($datos));
        $contacto = Contacto::desdeArray($modelo->toArray());

        return $contacto;
    }

    public function actualizar(int $id, array $datos): ?Contacto
    {
        $contacto = null;
        $modelo = ContactoModel::query()->where('id', $id)->first();

        if ($modelo instanceof ContactoModel) {
            $modelo->fill($this->datosPermitidosParciales($datos));
            $modelo->save();
            $contacto = Contacto::desdeArray($modelo->fresh()->toArray());
        }

        return $contacto;
    }

    public function desactivar(int $id): bool
    {
        $desactivado = false;
        $modelo = ContactoModel::query()->where('id', $id)->first();

        if ($modelo instanceof ContactoModel) {
            $modelo->activo = false;
            $desactivado = (bool) $modelo->save();
        }

        return $desactivado;
    }

    /**
     * @return array<int, Contacto>
     */
    private function buscarTexto(string $texto): array
    {
        $contactos = [];
        $busqueda = trim($texto);
        $terminos = array_values(array_filter(explode(' ', $busqueda), static fn (string $termino): bool => trim($termino) !== ''));

        if ($busqueda !== '') {
            try {
                $query = ContactoModel::query()->where('activo', true);

                foreach ($terminos as $termino) {
                    $query->where(function ($consulta) use ($termino): void {
                        $this->aplicarTerminoBusqueda($consulta, $termino);
                    });
                }

                $modelos = $query
                    ->orderBy('nombre')
                    ->orderBy('apellido')
                    ->limit(10)
                    ->get();

                foreach ($modelos as $modelo) {
                    $contactos[] = Contacto::desdeArray($modelo->toArray());
                }
            } catch (Throwable) {
                $contactos = [];
            }
        }

        return $contactos;
    }

    private function aplicarTerminoBusqueda(mixed $query, string $busqueda): void
    {
        $query->where('nombre', 'like', '%' . $busqueda . '%')
            ->orWhere('apellido', 'like', '%' . $busqueda . '%')
            ->orWhere('telefono', 'like', '%' . $busqueda . '%')
            ->orWhere('documento', 'like', '%' . $busqueda . '%');
    }

    private function contactoDesdeModelo(mixed $modelo): ?Contacto
    {
        $contacto = null;

        if ($modelo instanceof ContactoModel) {
            $contacto = Contacto::desdeArray($modelo->toArray());
        }

        return $contacto;
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function datosPermitidos(array $datos): array
    {
        $permitidos = [
            'nombre' => (string) ($datos['nombre'] ?? ''),
            'apellido' => $datos['apellido'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'correo' => $datos['correo'] ?? null,
            'documento' => $datos['documento'] ?? null,
            'direccion' => $datos['direccion'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'activo' => (bool) ($datos['activo'] ?? true),
        ];

        return $permitidos;
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function datosPermitidosParciales(array $datos): array
    {
        $permitidos = [];
        $campos = [
            'nombre',
            'apellido',
            'telefono',
            'correo',
            'documento',
            'direccion',
            'observaciones',
            'activo',
        ];

        foreach ($campos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $permitidos[$campo] = $datos[$campo];
            }
        }

        return $permitidos;
    }
}
