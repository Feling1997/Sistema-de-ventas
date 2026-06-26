<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Application;

use Ventas\Configuracion\Domain\Repositorios\ArchivoConfiguracionRepository;

final class ProcesarLogoTermicoConfiguracion
{
    public function __construct(private readonly ArchivoConfiguracionRepository $archivoConfiguracionRepository)
    {
    }

    public function ejecutar(string $rutaImagen, int $ancho, bool $modoTermico = true, string $destino = ''): string
    {
        $resultado = $this->archivoConfiguracionRepository->procesarLogoTermico($rutaImagen, $ancho, $modoTermico, $destino);

        return $resultado;
    }
}
