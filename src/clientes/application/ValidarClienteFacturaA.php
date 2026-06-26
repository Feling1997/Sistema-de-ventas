<?php

declare(strict_types=1);

namespace Ventas\Clientes\Application;

final class ValidarClienteFacturaA
{
    /**
     * @param array<string, mixed> $cliente
     */
    public function ejecutar(array $cliente): string
    {
        $resultado = '';
        $tipoDocumento = strtoupper(trim((string) ($cliente['tipo_documento'] ?? '')));
        $documento = preg_replace('/\D+/', '', (string) ($cliente['dni'] ?? ''));
        $condicionIva = trim((string) ($cliente['condicion_iva'] ?? ''));

        if ($tipoDocumento !== 'CUIT') {
            $resultado = 'Para Factura A el cliente debe tener tipo de documento CUIT.';
        } elseif (strlen($documento) !== 11) {
            $resultado = 'Para Factura A cargÃ¡ un CUIT vÃ¡lido de 11 dÃ­gitos.';
        } elseif ($condicionIva !== 'Responsable Inscripto') {
            $resultado = 'Para Factura A el cliente debe ser Responsable Inscripto.';
        }

        return $resultado;
    }
}
