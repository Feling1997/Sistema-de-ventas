<?php

declare(strict_types=1);

namespace Ventas\Ventas\Infrastructure;

use Ventas\Ventas\Domain\Repositorios\TipoComprobanteVentaRepository;

final class MemoriaTipoComprobanteVentaRepository implements TipoComprobanteVentaRepository
{
    public function listar(): array
    {
        $tiposComprobante = [
            98 => ["letra" => "X", "texto" => "Factura X", "operacion" => "factura_x", "fiscal" => false, "requisito" => "Comprobante interno: descuenta stock y no se envia a AFIP"],
            1 => ["letra" => "A", "texto" => "Factura A", "operacion" => "factura", "fiscal" => true, "requisito" => "Cliente Responsable Inscripto con CUIT"],
            6 => ["letra" => "B", "texto" => "Factura B", "operacion" => "factura", "fiscal" => true, "requisito" => "Consumidor Final, monotributista o exento"],
            11 => ["letra" => "C", "texto" => "Factura C", "operacion" => "factura", "fiscal" => true, "requisito" => "Emisor monotributista"],
            19 => ["letra" => "E", "texto" => "Factura E", "operacion" => "exportacion", "fiscal" => true, "requisito" => "Exportacion: requiere pais, CUIT pais, moneda, incoterms y datos aduaneros si corresponden"],
            3 => ["letra" => "A", "texto" => "Nota de credito A", "operacion" => "nota_credito", "fiscal" => true, "requisito" => "Debe referenciar una Factura A autorizada"],
            8 => ["letra" => "B", "texto" => "Nota de credito B", "operacion" => "nota_credito", "fiscal" => true, "requisito" => "Debe referenciar una Factura B autorizada"],
            13 => ["letra" => "C", "texto" => "Nota de credito C", "operacion" => "nota_credito", "fiscal" => true, "requisito" => "Debe referenciar una Factura C autorizada"],
            21 => ["letra" => "E", "texto" => "Nota de credito E", "operacion" => "nota_credito_exportacion", "fiscal" => true, "requisito" => "Debe referenciar una Factura E autorizada"],
            2 => ["letra" => "A", "texto" => "Nota de debito A", "operacion" => "nota_debito", "fiscal" => true, "requisito" => "Debe referenciar una Factura A autorizada"],
            7 => ["letra" => "B", "texto" => "Nota de debito B", "operacion" => "nota_debito", "fiscal" => true, "requisito" => "Debe referenciar una Factura B autorizada"],
            12 => ["letra" => "C", "texto" => "Nota de debito C", "operacion" => "nota_debito", "fiscal" => true, "requisito" => "Debe referenciar una Factura C autorizada"],
            20 => ["letra" => "E", "texto" => "Nota de debito E", "operacion" => "nota_debito_exportacion", "fiscal" => true, "requisito" => "Debe referenciar una Factura E autorizada"],
            99 => ["letra" => "X", "texto" => "Presupuesto", "operacion" => "presupuesto", "fiscal" => false, "requisito" => "Documento no valido como factura, no descuenta stock"],
        ];

        return $tiposComprobante;
    }
}
