<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Application;

use Ventas\Configuracion\Domain\Repositorios\ArchivoConfiguracionRepository;

final class ProcesarLogoTicketTermicoHDConfiguracion
{
    public function __construct(private readonly ArchivoConfiguracionRepository $archivoConfiguracionRepository)
    {
    }

    public function ejecutar(string $rutaOriginal, int $anchoTicket, bool $modoTermico = true): string
    {
        $resultado = $this->archivoConfiguracionRepository->procesarLogoTicketTermicoHD($rutaOriginal, $anchoTicket, $modoTermico);

        return $resultado;
    }
}
