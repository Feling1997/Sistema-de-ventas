<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Ventas\Comprobantes;

use Dompdf\Dompdf;
use Throwable;
use Ventas\Dominio\Ventas\NuevaVenta\Repositorios\ConfiguracionVentaRepository;
use Ventas\Dominio\Ventas\Repositorios\ComprobanteVentaRepository;

final class HtmlPdfComprobanteVentaRepository implements ComprobanteVentaRepository
{
    public function __construct(private readonly ConfiguracionVentaRepository $configuracionVentaRepository)
    {
    }

    public function renderizarTicket(array $venta, array $items, bool $paraPdf, bool $autoPrint): string
    {
        $id = (int) $venta['id'];
        $fecha = htmlspecialchars((string) $venta['fecha']);
        $cliente = htmlspecialchars((string) $venta['cliente_nombre']);
        $clienteDoc = htmlspecialchars(trim((string) ($venta['tipo_documento'] ?? '') . ' ' . (string) ($venta['cliente_documento'] ?? '')));
        $usuario = htmlspecialchars((string) $venta['usuario_nombre']);
        $total = htmlspecialchars(moneda_para_mostrar($venta['total'] ?? 0));
        $tipoComprobante = (int) ($venta['tipo_comprobante'] ?? 98);
        $tipoInfo = $this->tipoComprobante($tipoComprobante);
        $letra = htmlspecialchars((string) $tipoInfo['letra']);
        $esFacturaX = $tipoComprobante === 98;
        $titulo = htmlspecialchars($esFacturaX ? 'Ticket' : (string) $tipoInfo['texto']);
        $esFiscal = (($tipoInfo['fiscal'] ?? true) === true);
        $config = $this->configuracionVentaRepository->configuracionFiscal();
        $empresa = is_array($config['empresa'] ?? null) ? $config['empresa'] : [];
        $nombreComercio = htmlspecialchars((string) ($empresa['nombre_comercio'] ?? 'Comercio'));
        $razon = htmlspecialchars((string) ($empresa['razon_social'] ?? 'Comercio'));
        $cuit = htmlspecialchars((string) ($empresa['cuit'] ?? ''));
        $domicilio = htmlspecialchars((string) ($empresa['domicilio'] ?? ''));
        $telefonos = htmlspecialchars((string) ($empresa['telefonos'] ?? ''));
        $email = htmlspecialchars((string) ($empresa['email'] ?? ''));
        $pieTicket = nl2br(htmlspecialchars((string) ($empresa['texto_pie_ticket'] ?? '')));
        $logoHtml = $this->ticketLogoHtml($empresa);
        $ticketImagenCompleta = (string) ($empresa['ticket_imagen_completa'] ?? '0') === '1';
        $ticketFuenteRaw = in_array((string) ($empresa['ticket_fuente'] ?? 'Arial'), ['Arial', 'Verdana', 'Courier New', 'Tahoma'], true) ? (string) $empresa['ticket_fuente'] : 'Arial';
        $ticketFuente = $ticketFuenteRaw === 'Courier New' ? "'Courier New'" : htmlspecialchars($ticketFuenteRaw);
        $ticketTamano = max(10, min(18, (int) ($empresa['ticket_tamano_fuente'] ?? 12)));
        $pv = (int) ($venta['punto_venta'] ?? ($empresa['punto_venta'] ?? 1));
        $numero = (int) ($venta['numero_comprobante'] ?? 0);
        $cae = htmlspecialchars((string) ($venta['cae'] ?? ''));
        $numeroTxt = '';

        if (!$esFacturaX && !$esFiscal) {
            $numeroTxt = 'INTERNO-' . str_pad((string) $id, 8, '0', STR_PAD_LEFT);
        }

        if (!$esFacturaX && $esFiscal) {
            $numeroTxt = $numero > 0 ? str_pad((string) $pv, 5, '0', STR_PAD_LEFT) . '-' . str_pad((string) $numero, 8, '0', STR_PAD_LEFT) : 'PENDIENTE';
        }

        $filasHtml = '';
        foreach ($items as $item) {
            $producto = htmlspecialchars((string) $item['producto_nombre']);
            $cantidad = htmlspecialchars((string) $item['cantidad']);
            $precioUnitario = htmlspecialchars(numero_precio_para_exportar($item['precio_unit'] ?? 0));
            $descuentoRaw = (float) ($item['descuento'] ?? 0);
            $descuentoTexto = (abs($descuentoRaw - round($descuentoRaw)) < 0.00001)
                ? (string) ((int) round($descuentoRaw))
                : rtrim(rtrim(number_format($descuentoRaw, 2, '.', ''), '0'), '.');
            $descuento = htmlspecialchars($descuentoTexto);
            $subtotal = htmlspecialchars(numero_para_mostrar($item['subtotal'] ?? 0));
            $filasHtml .= "<div class='item-row'><div class='item-desc'><strong>$producto</strong><br><span class='item-detail'>$cantidad x $precioUnitario</span></div><div class='item-price'>$subtotal</div></div>";

            if ($descuentoRaw > 0) {
                $filasHtml .= "<div class='item-desc small'>Desc: $descuento%</div>";
            }
        }

        $medidas = $this->medidasImpresionTicket();
        $accionesHtml = $paraPdf ? '' : "<div class='actions'><button type='button' onclick='window.print()'>Imprimir</button><button type='button' onclick='window.close()'>Cerrar</button></div>";
        $autoPrintHtml = (!$paraPdf && $autoPrint) ? "<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 250); });</script>" : '';
        $empresaExtraHtml = '';

        if (!$ticketImagenCompleta) {
            $empresaExtraHtml .= $razon !== '' && $razon !== $nombreComercio ? "<div class='center small'>$razon</div>" : '';
            $empresaExtraHtml .= $cuit !== '' && !$esFacturaX ? "<div class='center small'>CUIT: $cuit</div>" : '';
            $empresaExtraHtml .= $domicilio !== '' ? "<div class='center small'>$domicilio</div>" : '';
            $empresaExtraHtml .= $telefonos !== '' ? "<div class='center small'>Tel: $telefonos</div>" : '';
            $empresaExtraHtml .= $email !== '' ? "<div class='center small'>$email</div>" : '';
        }

        $marcaHtml = $ticketImagenCompleta ? $logoHtml : $logoHtml . "<div class='center brand'>" . strtoupper($nombreComercio) . '</div>' . $empresaExtraHtml;
        $ticketStatus = $esFiscal && $cae === '' ? "<div class='center warning'>CAE PENDIENTE</div>" : '';
        $ticketStatus = !$esFiscal ? "<div class='center warning'>DOCUMENTO INTERNO</div>" : $ticketStatus;
        $ticketStatus = $esFacturaX ? '' : $ticketStatus;
        $numeroHtml = $esFacturaX || $ticketImagenCompleta ? '' : "<div class='row'><span>Nro.</span><strong>$numeroTxt</strong></div>";
        $clienteDocHtml = ($esFacturaX || $clienteDoc === '' || $ticketImagenCompleta) ? '' : "<div class='row'><span>Doc.</span><strong>$clienteDoc</strong></div>";
        $cabeceraDocumentoHtml = $ticketImagenCompleta ? '' : "
    <div class='line'></div>
    <div class='center title'>$titulo</div>
    <div class='line'></div>
    $numeroHtml
    <div class='row'><span>Fecha</span><strong>$fecha</strong></div>
    <div class='row'><span>Cliente</span><strong>$cliente</strong></div>
    $clienteDocHtml
    <div class='row'><span>Vendedor</span><strong>$usuario</strong></div>";
        $html = "<!doctype html>
<html lang='es'>
<head>
  <meta charset='utf-8'>
  <title>$titulo" . ($numeroTxt !== '' ? " - $numeroTxt" : '') . "</title>
  <style>
    @page { size: " . $medidas['page_size'] . '; margin: ' . $medidas['page_margin'] . "; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #fff; font-family: $ticketFuente, Arial, sans-serif; font-size: {$ticketTamano}px; color: #000; }
    .ticket { width: " . $medidas['ticket_width'] . '; max-width: 100%; padding: ' . $medidas['ticket_padding'] . "; margin: 0 auto; }
    .center { text-align: center; }
    .logo-wrap { margin: 0 0 4px; padding: 0; background: #fff; line-height: 0; }
    .logo { display: block; width: auto; max-width: 100%; max-height: 90px; object-fit: contain; margin: 0 auto; background: #fff; }
    .brand { font-weight: 800; font-size: 13px; line-height: 1.05; margin-bottom: 2px; }
    .title { font-weight: 800; font-size: 12px; margin: 5px 0; }
    .small { font-size: 9px; line-height: 1.2; }
    .line { border-top: 1px dashed #000; margin: 4px 0; }
    .row { display: flex; justify-content: space-between; gap: 4px; font-size: 10px; line-height: 1.3; }
    .row span { flex: 0 0 auto; font-weight: 600; }
    .row strong { flex: 1 1 auto; text-align: right; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .item-row { display: flex; justify-content: space-between; gap: 4px; font-size: 10px; line-height: 1.25; }
    .item-desc { flex: 1 1 auto; }
    .item-detail { font-size: 9px; color: #666; display: block; }
    .item-price { flex: 0 0 auto; text-align: right; font-weight: 600; white-space: nowrap; }
    .item-desc.small { font-size: 9px; color: #666; }
    .block-title { font-weight: 800; font-size: 10px; margin-top: 4px; }
    .total-row { display: flex; justify-content: space-between; gap: 4px; font-size: 11px; font-weight: 800; margin: 4px 0; }
    .total-row strong { text-align: right; }
    .warning { border: 1px solid #000; padding: 2px; font-weight: 600; font-size: 9px; }
    .actions { width: " . $medidas['actions_width'] . "; max-width: 100%; padding: 6px; display: flex; gap: 6px; margin: 0 auto 6px; }
    button, .actions a { border: 0; border-radius: 6px; padding: 6px 8px; background: #0e7490; color: white; font-weight: 600; cursor: pointer; font-size: 9px; text-decoration: none; display: inline-flex; align-items: center; }
    @media print { .actions { display: none; } body { width: " . $medidas['body_width'] . '; } .ticket { margin: 0 auto; } }
  </style>
</head>
<body>
  ' . $accionesHtml . "
  <div class='ticket'>
    $marcaHtml
    $cabeceraDocumentoHtml
    <div class='line'></div>
    " . ($ticketImagenCompleta ? '' : "<div class='block-title'>Detalle</div>") . "
    $filasHtml
    <div class='line'></div>
    <div class='total-row'><span>Total</span><strong>$total</strong></div>
    $ticketStatus
    " . ($pieTicket !== '' ? "<div class='line'></div><div class='center small'>$pieTicket</div>" : '') . "
  </div>
  $autoPrintHtml
</body>
</html>";

        return $html;
    }

    public function generarPdf(array $venta, array $items, int $tipoComprobante): bool
    {
        $ok = false;

        try {
            $html = $this->renderizarTicket($venta, $items, true, false);
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html, 'UTF-8');
            $formatoImpresion = $this->formatoImpresionTicket();

            if ($formatoImpresion === 'a4') {
                $dompdf->setPaper('A4', 'portrait');
            }

            if ($formatoImpresion === '58') {
                $dompdf->setPaper([0, 0, 164.41, 900], 'portrait');
            }

            if ($formatoImpresion !== 'a4' && $formatoImpresion !== '58') {
                $dompdf->setPaper([0, 0, 226.77, 900], 'portrait');
            }

            $dompdf->render();
            $carpeta = $this->raizProyecto() . DIRECTORY_SEPARATOR . 'almacenamiento' . DIRECTORY_SEPARATOR . 'pdf';

            if (!is_dir($carpeta)) {
                @mkdir($carpeta, 0777, true);
            }

            $archivo = $carpeta . DIRECTORY_SEPARATOR . 'venta_' . (int) ($venta['id'] ?? 0) . '.pdf';
            $ok = (bool) @file_put_contents($archivo, $dompdf->output());
        } catch (Throwable) {
            $ok = false;
        }

        return $ok;
    }

    public function obtenerArchivoPdf(int $idVenta): array
    {
        $resultado = [
            'ok' => false,
            'nombre' => 'venta_' . $idVenta . '.pdf',
            'contenido' => '',
            'tamano' => 0,
            'archivo' => '',
        ];
        $archivo = $this->raizProyecto() . DIRECTORY_SEPARATOR . 'almacenamiento' . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . 'venta_' . $idVenta . '.pdf';

        if ($idVenta > 0 && is_file($archivo)) {
            $contenido = @file_get_contents($archivo);

            if (is_string($contenido)) {
                $resultado = [
                    'ok' => true,
                    'nombre' => 'venta_' . $idVenta . '.pdf',
                    'contenido' => $contenido,
                    'tamano' => strlen($contenido),
                    'archivo' => $archivo,
                ];
            }
        }

        return $resultado;
    }

    private function tipoComprobante(int $codigo): array
    {
        $tipos = [
            98 => ['letra' => 'X', 'texto' => 'Factura X', 'fiscal' => false],
            1 => ['letra' => 'A', 'texto' => 'Factura A', 'fiscal' => true],
            6 => ['letra' => 'B', 'texto' => 'Factura B', 'fiscal' => true],
            11 => ['letra' => 'C', 'texto' => 'Factura C', 'fiscal' => true],
            19 => ['letra' => 'E', 'texto' => 'Factura E', 'fiscal' => true],
            3 => ['letra' => 'A', 'texto' => 'Nota de credito A', 'fiscal' => true],
            8 => ['letra' => 'B', 'texto' => 'Nota de credito B', 'fiscal' => true],
            13 => ['letra' => 'C', 'texto' => 'Nota de credito C', 'fiscal' => true],
            21 => ['letra' => 'E', 'texto' => 'Nota de credito E', 'fiscal' => true],
            2 => ['letra' => 'A', 'texto' => 'Nota de debito A', 'fiscal' => true],
            7 => ['letra' => 'B', 'texto' => 'Nota de debito B', 'fiscal' => true],
            12 => ['letra' => 'C', 'texto' => 'Nota de debito C', 'fiscal' => true],
            20 => ['letra' => 'E', 'texto' => 'Nota de debito E', 'fiscal' => true],
            99 => ['letra' => 'X', 'texto' => 'Presupuesto', 'fiscal' => false],
        ];
        $tipo = $tipos[$codigo] ?? $tipos[98];
        $tipo['codigo'] = array_key_exists($codigo, $tipos) ? $codigo : 98;

        return $tipo;
    }

    private function formatoImpresionTicket(): string
    {
        $config = $this->configuracionVentaRepository->configuracionFiscal();
        $empresa = is_array($config['empresa'] ?? null) ? $config['empresa'] : [];
        $formato = (string) ($empresa['formato_impresion_ticket'] ?? '80');
        $formato = in_array($formato, ['a4', '80', '58'], true) ? $formato : '80';

        return $formato;
    }

    private function medidasImpresionTicket(): array
    {
        $formato = $this->formatoImpresionTicket();
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
        $raiz = dirname(__DIR__, 4);

        return $raiz;
    }
}
