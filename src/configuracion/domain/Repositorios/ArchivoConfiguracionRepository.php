<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Domain\Repositorios;

interface ArchivoConfiguracionRepository
{
    public function guardarArchivo(string $campo, string $actual, string $nombreBase): string;

    public function procesarLogoTermico(string $rutaImagen, int $ancho, bool $modoTermico = true, string $destino = ''): string;

    public function procesarLogoTicketTermico(string $rutaOriginal, string $formatoTicket, bool $modoTermico = true): string;

    public function procesarLogoTicketTermicoHD(string $rutaOriginal, int $anchoTicket, bool $modoTermico = true): string;
}
