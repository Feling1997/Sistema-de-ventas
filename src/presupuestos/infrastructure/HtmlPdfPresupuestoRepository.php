<?php

declare(strict_types=1);

namespace Ventas\Presupuestos\Infrastructure;

use Dompdf\Dompdf;
use Throwable;
use Ventas\Configuracion\Domain\Repositorios\ConfiguracionRepository;
use Ventas\Presupuestos\Domain\Repositorios\ComprobantePresupuestoRepository;

final class HtmlPdfPresupuestoRepository implements ComprobantePresupuestoRepository
{
    public function __construct(private readonly ConfiguracionRepository $configuracionRepository)
    {
    }

    public function renderizarTicket(array $presupuesto, array $items, bool $paraPdf, bool $autoPrint): string
    {
        $id = (int) $presupuesto['id'];
        $fecha = htmlspecialchars((string) $presupuesto['fecha']);
        $cliente = htmlspecialchars((string) $presupuesto['cliente_nombre']);
        $usuario = htmlspecialchars((string) $presupuesto['usuario_nombre']);
        $total = htmlspecialchars(moneda_para_mostrar($presupuesto['total'] ?? 0));
        $config = $this->configuracionRepository->obtenerFiscal();
        $empresa = is_array($config['empresa'] ?? null) ? $config['empresa'] : [];
        $nombreComercio = htmlspecialchars((string) ($empresa['nombre_comercio'] ?? ''));
        $razon = htmlspecialchars((string) ($empresa['razon_social'] ?? ''));
        $cuit = htmlspecialchars((string) ($empresa['cuit'] ?? ''));
        $domicilio = htmlspecialchars((string) ($empresa['domicilio'] ?? ''));
        $telefonos = htmlspecialchars((string) ($empresa['telefonos'] ?? ''));
        $pieTicket = nl2br(htmlspecialchars((string) ($empresa['texto_pie_ticket'] ?? '')));
        $logoHtml = $this->ticketLogoHtml($empresa);
        $ticketImagenCompleta = (string) ($empresa['ticket_imagen_completa'] ?? '0') === '1';
        $ticketFuenteRaw = in_array((string) ($empresa['ticket_fuente'] ?? 'Arial'), ['Arial', 'Verdana', 'Courier New', 'Tahoma'], true) ? (string) $empresa['ticket_fuente'] : 'Arial';
        $ticketFuente = $ticketFuenteRaw === 'Courier New' ? "'Courier New'" : htmlspecialchars($ticketFuenteRaw);
        $ticketTamano = max(10, min(18, (int) ($empresa['ticket_tamano_fuente'] ?? 12)));
        $marcaPresupuesto = $ticketImagenCompleta
            ? $logoHtml
            : $logoHtml . "<div class='center brand'>$nombreComercio</div>" . ($razon !== '' && $razon !== $nombreComercio ? "<div class='center'>$razon</div>" : '') . "<div class='center'>CUIT $cuit</div>"
                . ($domicilio !== '' ? "<div class='center'>$domicilio</div>" : '')
                . ($telefonos !== '' ? "<div class='center'>Tel: $telefonos</div>" : '');
        $filas = '';

        foreach ($items as $item) {
            $producto = htmlspecialchars((string) $item['producto_nombre']);
            $cantidad = htmlspecialchars((string) $item['cantidad']);
            $precioUnitario = htmlspecialchars(numero_precio_para_exportar($item['precio_unit'] ?? 0));
            $descuento = htmlspecialchars((string) $item['descuento']);
            $subtotal = htmlspecialchars(numero_para_mostrar($item['subtotal'] ?? 0));
            $filas .= "<tr><td>$producto<br><span>$cantidad x $precioUnitario Desc $descuento%</span></td><td class='num'>$subtotal</td></tr>";
        }

        $medidas = $this->medidasImpresionTicket();
        $accionesHtml = $paraPdf ? '' : "<div class='actions'><button type='button' onclick='window.print()'>Imprimir</button><button type='button' onclick='window.close()'>Cerrar</button></div>";
        $autoPrintHtml = (!$paraPdf && $autoPrint) ? "<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 250); });</script>" : '';
        $html = "<!doctype html>
            <html lang='es'><head><meta charset='utf-8'><style>
            @page { size: " . $medidas['page_size'] . '; margin: ' . $medidas['page_margin'] . "; }
            body { font-family: $ticketFuente, DejaVu Sans, sans-serif; font-size: {$ticketTamano}px; color: #111; }
            .actions { width: " . $medidas['actions_width'] . "; max-width: 100%; padding: 6px; display: flex; gap: 6px; margin: 0 auto 6px; }
            .ticket { width: " . $medidas['ticket_width'] . '; max-width: 100%; padding: ' . $medidas['ticket_padding'] . "; margin: 0 auto; }
            button { border: 0; border-radius: 6px; padding: 6px 8px; background: #0e7490; color: white; font-weight: 600; cursor: pointer; font-size: 9px; }
            .center { text-align: center; }
            .logo-wrap { margin: 0 0 4px; padding: 0; background: #fff; line-height: 0; }
            .logo { display: block; width: auto; max-width: 100%; max-height: 90px; object-fit: contain; margin: 0 auto; background: #fff; }
            .brand { font-size: 12px; font-weight: bold; }
            .marca { width: 34px; height: 34px; border: 2px solid #111; margin: 4px auto; text-align: center; font-size: 25px; font-weight: bold; line-height: 34px; }
            .sep { border-top: 1px dashed #111; margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 3px 0; border-bottom: 1px dotted #999; vertical-align: top; }
            td span { font-size: 8px; color: #333; }
            .num { text-align: right; white-space: nowrap; }
            .total { display: flex; justify-content: space-between; font-size: 12px; font-weight: bold; }
            .legal { border: 1px solid #111; padding: 4px; text-align: center; font-weight: bold; }
            @media print { .actions { display: none; } body { width: " . $medidas['body_width'] . '; } .ticket { margin: 0 auto; } }
            </style></head><body>
            ' . $accionesHtml . "
            <div class='ticket'>
            $marcaPresupuesto
            <div class='marca'>X</div>
            <div class='legal'>DOCUMENTO NO VALIDO COMO FACTURA</div>
            <div class='center'><b>PRESUPUESTO #$id</b></div>
            <div class='sep'></div>
            <div><b>Fecha:</b> $fecha</div>
            <div><b>Cliente:</b> $cliente</div>
            <div><b>Vendedor:</b> $usuario</div>
            <div class='sep'></div>
            <table><tbody>$filas</tbody></table>
            <div class='sep'></div>
            <div class='total'><span>TOTAL</span><b>$total</b></div>
            " . ($pieTicket !== '' ? "<div class='sep'></div><div class='center'>$pieTicket</div>" : '') . "
            </div>
            $autoPrintHtml
            </body></html>";

        return $html;
    }

    public function generarPdf(array $presupuesto, array $items): bool
    {
        $ok = false;

        try {
            $html = $this->renderizarTicket($presupuesto, $items, true, false);
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper([0, 0, 226.77, 900], 'portrait');
            $dompdf->render();
            $carpeta = $this->raizProyecto() . DIRECTORY_SEPARATOR . 'almacenamiento' . DIRECTORY_SEPARATOR . 'pdf';

            if (!is_dir($carpeta)) {
                @mkdir($carpeta, 0777, true);
            }

            $archivo = $carpeta . DIRECTORY_SEPARATOR . 'presupuesto_' . (int) ($presupuesto['id'] ?? 0) . '.pdf';
            $ok = (bool) @file_put_contents($archivo, $dompdf->output());
        } catch (Throwable) {
            $ok = false;
        }

        return $ok;
    }

    public function obtenerArchivoPdf(int $idPresupuesto): array
    {
        $resultado = [
            'ok' => false,
            'nombre' => 'presupuesto_' . $idPresupuesto . '.pdf',
            'contenido' => '',
            'tamano' => 0,
            'archivo' => '',
        ];
        $archivo = $this->raizProyecto() . DIRECTORY_SEPARATOR . 'almacenamiento' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'presupuesto_' . $idPresupuesto . '.pdf';

        if ($idPresupuesto > 0 && is_file($archivo)) {
            $contenido = @file_get_contents($archivo);

            if (is_string($contenido)) {
                $resultado = [
                    'ok' => true,
                    'nombre' => 'presupuesto_' . $idPresupuesto . '.pdf',
                    'contenido' => $contenido,
                    'tamano' => strlen($contenido),
                    'archivo' => $archivo,
                ];
            }
        }

        return $resultado;
    }

    private function medidasImpresionTicket(): array
    {
        $config = $this->configuracionRepository->obtenerFiscal();
        $empresa = is_array($config['empresa'] ?? null) ? $config['empresa'] : [];
        $formato = (string) ($empresa['formato_impresion_ticket'] ?? '80');
        $formato = in_array($formato, ['a4', '80', '58'], true) ? $formato : '80';
        $medidas = [
            'page_size' => '80mm auto',
            'page_margin' => '0',
            'ticket_width' => '80mm',
            'ticket_padding' => '3mm 3mm 4mm',
            'body_width' => 'auto',
            'actions_width' => '80mm',
        ];

        if ($formato === 'a4') {
            $medidas = [
                'page_size' => 'A4 portrait',
                'page_margin' => '8mm',
                'ticket_width' => '190mm',
                'ticket_padding' => '0',
                'body_width' => 'auto',
                'actions_width' => '190mm',
            ];
        }

        if ($formato === '58') {
            $medidas = [
                'page_size' => '58mm auto',
                'page_margin' => '0',
                'ticket_width' => '58mm',
                'ticket_padding' => '3mm 3mm 4mm',
                'body_width' => 'auto',
                'actions_width' => '58mm',
            ];
        }

        return $medidas;
    }

    private function ticketLogoHtml(array $empresa): string
    {
        $html = '';
        $logoRelativo = trim((string) ($empresa['logo_ticket'] ?? ''));
        $base = $this->raizProyecto();
        $logoPath = realpath($base . DIRECTORY_SEPARATOR . $logoRelativo);
        $basePath = realpath($base);

        if ($logoRelativo !== '' && is_string($logoPath) && is_string($basePath) && str_starts_with($logoPath, $basePath) && is_file($logoPath)) {
            $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
            $mime = $mimes[$extension] ?? 'image/png';
            $bytesLogo = @file_get_contents($logoPath);

            if (is_string($bytesLogo) && $bytesLogo !== '') {
                $html = "<div class='center logo-wrap'><img class='logo' src='data:$mime;base64," . base64_encode($bytesLogo) . "'></div>";
            }
        }

        return $html;
    }

    private function raizProyecto(): string
    {
        $raiz = dirname(__DIR__, 3);

        return $raiz;
    }
}
