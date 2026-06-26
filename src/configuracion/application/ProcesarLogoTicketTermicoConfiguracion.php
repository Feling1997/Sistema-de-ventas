<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Application;

use Ventas\Configuracion\Domain\Repositorios\ArchivoConfiguracionRepository;

final class ProcesarLogoTicketTermicoConfiguracion
{
    public function __construct(private readonly ArchivoConfiguracionRepository $archivoConfiguracionRepository)
    {
    }

    public function ejecutar(string $rutaOriginal, string $formatoTicket, bool $modoTermico = true): string
    {
        $resultado = $this->archivoConfiguracionRepository->procesarLogoTicketTermico($rutaOriginal, $formatoTicket, $modoTermico);

        return $resultado;
    }
}
