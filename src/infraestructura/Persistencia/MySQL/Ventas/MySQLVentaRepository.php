<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Persistencia\MySQL\Ventas;

use PDO;
use Ventas\Dominio\Ventas\Entidades\DetalleVenta;
use Ventas\Dominio\Ventas\Entidades\Venta;
use Ventas\Dominio\Ventas\Repositorios\VentaRepository;

final class MySQLVentaRepository implements VentaRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function listar(): array
    {
        $ventas = [];
        $statement = $this->pdo->prepare(
            'SELECT v.id,
                    v.fecha,
                    v.id_cliente,
                    v.id_usuario,
                    v.total
             FROM ventas v
             ORDER BY v.fecha DESC, v.id DESC'
        );

        $statement->execute();
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $ventas[] = new Venta(
                (int) $fila['id'],
                (string) $fila['fecha'],
                (int) $fila['id_cliente'],
                (int) $fila['id_usuario'],
                (float) $fila['total']
            );
        }

        return $ventas;
    }

    public function buscarPorId(int $id): ?Venta
    {
        $venta = null;
        $statement = $this->pdo->prepare(
            'SELECT v.id,
                    v.fecha,
                    v.id_cliente,
                    v.id_usuario,
                    v.total
             FROM ventas v
             WHERE v.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);
        $fila = $statement->fetch();

        if (is_array($fila)) {
            $venta = new Venta(
                (int) $fila['id'],
                (string) $fila['fecha'],
                (int) $fila['id_cliente'],
                (int) $fila['id_usuario'],
                (float) $fila['total']
            );
        }

        return $venta;
    }

    public function obtenerDetalle(int $idVenta): array
    {
        $detalles = [];
        $statement = $this->pdo->prepare(
            'SELECT d.id,
                    d.id_venta,
                    d.id_producto,
                    d.cantidad,
                    d.precio_unit,
                    d.costo_unit,
                    d.descuento,
                    d.subtotal
             FROM detalle_venta d
             WHERE d.id_venta = :id_venta
             ORDER BY d.id ASC'
        );

        $statement->execute(['id_venta' => $idVenta]);
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $detalles[] = new DetalleVenta(
                (int) $fila['id'],
                (int) $fila['id_venta'],
                (int) $fila['id_producto'],
                (float) $fila['cantidad'],
                (float) $fila['precio_unit'],
                (float) $fila['costo_unit'],
                (float) $fila['descuento'],
                (float) $fila['subtotal']
            );
        }

        return $detalles;
    }

    public function obtenerComprobante(int $idVenta): ?array
    {
        $comprobante = null;
        $statement = $this->pdo->prepare(
            'SELECT v.id,
                    v.fecha,
                    v.total,
                    v.id_cliente,
                    c.nombre AS cliente_nombre,
                    c.dni AS cliente_documento,
                    c.tipo_documento,
                    c.condicion_iva,
                    c.direccion AS cliente_direccion,
                    u.usuario AS usuario_nombre,
                    COALESCE(f.tipo_comprobante, ?) AS tipo_comprobante,
                    f.punto_venta,
                    f.numero_comprobante,
                    f.cae,
                    f.cae_vencimiento,
                    f.estado AS fiscal_estado,
                    f.respuesta_json
             FROM ventas v
             INNER JOIN clientes c ON c.id = v.id_cliente
             INNER JOIN usuarios u ON u.id = v.id_usuario
             LEFT JOIN fiscal_comprobantes f ON f.id_venta = v.id
             WHERE v.id = ?
             LIMIT 1'
        );

        $statement->execute([98, $idVenta]);
        $venta = $statement->fetch();

        if (is_array($venta)) {
            $comprobante = [
                'venta' => $venta,
                'items' => $this->obtenerDetalleComprobante($idVenta),
            ];
        }

        return $comprobante;
    }

    public function listarPeriodo(string $fechaDesde, string $fechaHasta, string $ordenCampo, string $ordenDireccion): array
    {
        $ventas = [];
        $condiciones = [];
        $parametros = [];
        $fechaDesde = trim($fechaDesde);
        $fechaHasta = trim($fechaHasta);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde) === 1) {
            $partesDesde = array_map('intval', explode('-', $fechaDesde));
            if (checkdate($partesDesde[1], $partesDesde[2], $partesDesde[0])) {
                $condiciones[] = 'DATE(v.fecha) >= ?';
                $parametros[] = $fechaDesde;
            }
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta) === 1) {
            $partesHasta = array_map('intval', explode('-', $fechaHasta));
            if (checkdate($partesHasta[1], $partesHasta[2], $partesHasta[0])) {
                $condiciones[] = 'DATE(v.fecha) <= ?';
                $parametros[] = $fechaHasta;
            }
        }
        $where = count($condiciones) > 0 ? ' WHERE ' . implode(' AND ', $condiciones) : '';
        $sql = 'SELECT v.id,
                       v.fecha,
                       v.total,
                       c.nombre AS cliente_nombre,
                       u.usuario AS usuario_nombre
                FROM ventas v
                INNER JOIN clientes c ON c.id = v.id_cliente
                INNER JOIN usuarios u ON u.id = v.id_usuario'
            . $where
            . ' ORDER BY ' . $this->ordenVentasSql($ordenCampo, $ordenDireccion) . ', v.id DESC';
        $statement = $this->pdo->prepare($sql);

        $statement->execute($parametros);
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $ventas[] = $fila;
        }

        return $ventas;
    }

    public function obtenerGananciasPorIds(array $ids): array
    {
        $resultado = ['ganancia' => 0.0];
        $idsNormalizados = $this->normalizarIds($ids);

        if (count($idsNormalizados) > 0) {
            $placeholders = $this->placeholders($idsNormalizados);
            $statement = $this->pdo->prepare(
                'SELECT COALESCE(SUM(
                    d.subtotal - (COALESCE(NULLIF(d.costo_unit, 0), COALESCE(s.precio_costo, 0) * p.factor_conversion, 0) * d.cantidad)
                 ), 0) AS ganancia
                 FROM detalle_venta d
                 INNER JOIN productos p ON p.id = d.id_producto
                 LEFT JOIN stock s ON s.id = p.id_stock
                 WHERE d.id_venta IN (' . $placeholders . ')'
            );
            $statement->execute($idsNormalizados);
            $fila = $statement->fetch();
            $resultado['ganancia'] = (float) ($fila['ganancia'] ?? 0);
        }

        return $resultado;
    }

    public function obtenerEstadosFiscales(array $ids): array
    {
        $estados = [];
        $idsNormalizados = $this->normalizarIds($ids);

        if (count($idsNormalizados) > 0) {
            $placeholders = $this->placeholders($idsNormalizados);
            $statement = $this->pdo->prepare(
                'SELECT id_venta, estado, cae, numero_comprobante, ultimo_error
                 FROM fiscal_comprobantes
                 WHERE id_venta IN (' . $placeholders . ')'
            );
            $statement->execute($idsNormalizados);
            $filas = $statement->fetchAll();

            foreach ($filas as $fila) {
                $estados[(int) $fila['id_venta']] = $fila;
            }
        }

        return $estados;
    }

    public function obtenerDetallesVentas(array $ids): array
    {
        $detalles = [];
        $idsNormalizados = $this->normalizarIds($ids);

        if (count($idsNormalizados) > 0) {
            $placeholders = $this->placeholders($idsNormalizados);
            $statement = $this->pdo->prepare(
                'SELECT d.id_venta,
                        d.id,
                        d.id_producto,
                        p.nombre AS producto_nombre,
                        d.cantidad,
                        d.precio_unit,
                        d.costo_unit,
                        d.descuento,
                        d.subtotal
                 FROM detalle_venta d
                 INNER JOIN productos p ON p.id = d.id_producto
                 WHERE d.id_venta IN (' . $placeholders . ')
                 ORDER BY d.id_venta ASC, d.id ASC'
            );
            $statement->execute($idsNormalizados);
            $filas = $statement->fetchAll();

            foreach ($filas as $fila) {
                $idVenta = (int) ($fila['id_venta'] ?? 0);

                if (!isset($detalles[$idVenta])) {
                    $detalles[$idVenta] = [];
                }

                $detalles[$idVenta][] = $fila;
            }
        }

        return $detalles;
    }

    public function confirmarVenta(int $idCliente, int $idUsuario, array $carrito, bool $controlarStock): array
    {
        $resultado = ['ok' => false, 'id_venta' => 0, 'error' => ''];

        try {
            $this->pdo->beginTransaction();

            if (count($carrito) === 0) {
                $resultado['error'] = 'El carrito esta vacio.';
            } else {
                $idCliente = $idCliente > 0 ? $idCliente : 1;
                $idUsuario = $this->asegurarUsuarioSistema($idUsuario);
                $total = $this->calcularTotalCarrito($carrito);
                $statementVenta = $this->pdo->prepare('INSERT INTO ventas (id_cliente, id_usuario, total) VALUES (?, ?, ?)');
                $ventaCreada = $statementVenta->execute([$idCliente, $idUsuario, $total]);

                if (!$ventaCreada) {
                    $resultado['error'] = 'No se pudo crear la venta.';
                } else {
                    $idVenta = (int) $this->pdo->lastInsertId();
                    $resultado = $this->insertarDetalleVenta($idVenta, $carrito, $controlarStock);
                }
            }

            if (($resultado['ok'] ?? false) === true) {
                $this->pdo->commit();
            } elseif ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        } catch (\Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $resultado = [
                'ok' => false,
                'id_venta' => 0,
                'error' => 'Error al confirmar venta: ' . $throwable->getMessage(),
            ];
        }

        return $resultado;
    }

    public function buscarClienteFactura(int $idCliente): ?array
    {
        $cliente = null;

        if ($idCliente > 0) {
            $statement = $this->pdo->prepare(
                'SELECT id, nombre, dni, tipo_documento, condicion_iva, email, direccion
                 FROM clientes
                 WHERE id = ?
                 LIMIT 1'
            );
            $statement->execute([$idCliente]);
            $fila = $statement->fetch();

            if (is_array($fila)) {
                $cliente = $fila;
            }
        }

        return $cliente;
    }

    public function saldoFavorCliente(int $idCliente): float
    {
        $saldo = 0.0;

        if ($idCliente > 0) {
            $statement = $this->pdo->prepare(
                "SELECT COALESCE(SUM(CASE WHEN tipo = 'ANTICIPO' THEN monto WHEN tipo = 'APLICACION' THEN -monto ELSE 0 END), 0) AS saldo
                 FROM cuentas_corrientes_recibos
                 WHERE id_cliente = ?"
            );
            $statement->execute([$idCliente]);
            $fila = $statement->fetch();
            $saldo = max(0.0, round((float) ($fila['saldo'] ?? 0), 2));
        }

        return $saldo;
    }

    public function crearFiscalPendiente(int $idVenta, string $tipoOperacion, int $tipoComprobante, array $configFiscal): bool
    {
        $ok = false;

        if ($idVenta > 0) {
            $payload = $this->construirPayloadFiscal($idVenta, $tipoOperacion, $tipoComprobante, $configFiscal);
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (is_string($json) && $payload !== []) {
                $proveedor = (string) ($configFiscal['proveedor'] ?? 'api_rest');
                $empresa = is_array($configFiscal['empresa'] ?? null) ? $configFiscal['empresa'] : [];
                $puntoVenta = (int) ($empresa['punto_venta'] ?? 1);
                $statement = $this->pdo->prepare(
                    "INSERT INTO fiscal_comprobantes
                        (id_venta, tipo_operacion, estado, proveedor, punto_venta, tipo_comprobante, payload_json)
                     VALUES (?, ?, 'PENDIENTE', ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE payload_json = VALUES(payload_json), actualizado_en = CURRENT_TIMESTAMP"
                );
                $statement->execute([$idVenta, $tipoOperacion, $proveedor, $puntoVenta, $tipoComprobante, $json]);
                $idComprobante = $this->obtenerIdComprobantePorVenta($idVenta);

                if ($idComprobante > 0) {
                    $statementCola = $this->pdo->prepare(
                        "INSERT INTO fiscal_cola (id_comprobante, estado)
                         SELECT ?, 'PENDIENTE'
                         WHERE NOT EXISTS (
                             SELECT 1 FROM fiscal_cola
                             WHERE id_comprobante = ? AND estado IN ('PENDIENTE','EN_PROCESO')
                         )"
                    );
                    $statementCola->execute([$idComprobante, $idComprobante]);
                    $ok = true;
                }
            }
        }

        return $ok;
    }

    public function crearCuentaCorriente(
        int $idCliente,
        string $concepto,
        float $total,
        int $cuotas,
        string $primerVencimiento,
        ?int $idVenta,
        array $vencimientos
    ): bool {
        $ok = false;

        if ($idCliente > 0 && $total > 0 && $cuotas > 0) {
            try {
                $this->pdo->beginTransaction();
                $statementCuenta = $this->pdo->prepare('INSERT INTO cuentas_corrientes (id_cliente, id_venta, concepto, total, saldo) VALUES (?, ?, ?, ?, ?)');
                $statementCuenta->execute([$idCliente, $idVenta, $concepto, $total, $total]);
                $idCuenta = (int) $this->pdo->lastInsertId();
                $monto = round($total / $cuotas, 2);
                $fecha = new \DateTime($primerVencimiento);
                $statementCuota = $this->pdo->prepare('INSERT INTO cuentas_corrientes_cuotas (id_cuenta, numero, vencimiento, monto) VALUES (?, ?, ?, ?)');

                for ($i = 1; $i <= $cuotas; $i++) {
                    $montoCuota = $i === $cuotas ? round($total - ($monto * ($cuotas - 1)), 2) : $monto;
                    $vencimiento = trim((string) ($vencimientos[$i - 1] ?? ''));
                    $statementCuota->execute([$idCuenta, $i, $vencimiento !== '' ? $vencimiento : $fecha->format('Y-m-d'), $montoCuota]);
                    $fecha->modify('+1 month');
                }

                $this->pdo->commit();
                $ok = true;
            } catch (\Throwable) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            }
        }

        return $ok;
    }

    public function aplicarSaldoFavor(int $idCliente, int $idVenta, float $monto): bool
    {
        $ok = false;

        if ($idCliente > 0 && $idVenta > 0 && $monto > 0) {
            try {
                $this->pdo->beginTransaction();
                $saldo = $this->saldoFavorCliente($idCliente);

                if ($saldo + 0.00001 >= $monto) {
                    $observacion = 'Aplicado a venta #' . $idVenta;
                    $statement = $this->pdo->prepare(
                        "INSERT INTO cuentas_corrientes_recibos (id_cuenta, id_cliente, tipo, monto, forma_pago, observacion)
                         VALUES (NULL, ?, 'APLICACION', ?, 'saldo_favor', ?)"
                    );
                    $ok = $statement->execute([$idCliente, $monto, $observacion]);
                    $this->pdo->commit();
                } elseif ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            } catch (\Throwable) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
            }
        }

        return $ok;
    }

    private function obtenerDetalleComprobante(int $idVenta): array
    {
        $items = [];
        $statement = $this->pdo->prepare(
            'SELECT d.id,
                    d.id_producto,
                    p.nombre AS producto_nombre,
                    d.cantidad,
                    d.precio_unit,
                    d.costo_unit,
                    d.descuento,
                    d.subtotal
             FROM detalle_venta d
             INNER JOIN productos p ON p.id = d.id_producto
             WHERE d.id_venta = ?
             ORDER BY d.id ASC'
        );

        $statement->execute([$idVenta]);
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $items[] = $fila;
        }

        return $items;
    }

    private function insertarDetalleVenta(int $idVenta, array $carrito, bool $controlarStock): array
    {
        $resultado = ['ok' => true, 'id_venta' => $idVenta, 'error' => ''];
        $statementProducto = $this->pdo->prepare(
            'SELECT p.id, p.nombre, p.precio_final, p.id_stock, p.id_asociado, p.factor_conversion, p.activo,
                    COALESCE(s.precio_costo, 0) AS precio_costo_stock
             FROM productos p
             LEFT JOIN stock s ON s.id = p.id_stock
             WHERE p.id = ?
             LIMIT 1'
        );
        $statementStock = $this->pdo->prepare('SELECT id, cantidad FROM stock WHERE id = ? LIMIT 1');
        $statementDescuento = $this->pdo->prepare('UPDATE stock SET cantidad = cantidad - ? WHERE id = ?');
        $statementDetalle = $this->pdo->prepare(
            'INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unit, costo_unit, descuento, subtotal)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($carrito as $item) {
            if (($resultado['ok'] ?? false) === true) {
                $idProducto = (int) ($item['id_producto'] ?? 0);
                $cantidad = (float) ($item['cantidad'] ?? 0);
                $precioUnitario = (float) ($item['precio_unit'] ?? 0);
                $descuento = (float) ($item['descuento'] ?? 0);

                if ($idProducto <= 0 || $cantidad <= 0) {
                    $resultado = ['ok' => false, 'id_venta' => 0, 'error' => 'Item invalido en carrito.'];
                } else {
                    $statementProducto->execute([$idProducto]);
                    $producto = $statementProducto->fetch();

                    if (!is_array($producto) || (int) ($producto['activo'] ?? 0) !== 1) {
                        $resultado = ['ok' => false, 'id_venta' => 0, 'error' => 'Producto no disponible.'];
                    } else {
                        $factor = $this->normalizarMinimoCero((float) ($producto['factor_conversion'] ?? 0));
                        $idStockConsumo = $this->obtenerIdStockConsumo($producto);

                        if ($idStockConsumo !== null) {
                            $resultado = $this->descontarStock($resultado, $statementStock, $statementDescuento, $idStockConsumo, $cantidad, $factor, $controlarStock, $idProducto);
                        }

                        if (($resultado['ok'] ?? false) === true) {
                            $subtotal = $this->calcularSubtotal($cantidad, $precioUnitario, $descuento);
                            $costoUnitario = $this->normalizarMinimoCero((float) ($producto['precio_costo_stock'] ?? 0)) * $factor;
                            $okDetalle = $statementDetalle->execute([$idVenta, $idProducto, $cantidad, $precioUnitario, $costoUnitario, $descuento, $subtotal]);

                            if (!$okDetalle) {
                                $resultado = ['ok' => false, 'id_venta' => 0, 'error' => 'No se pudo insertar detalle.'];
                            }
                        }
                    }
                }
            }
        }

        return $resultado;
    }

    private function descontarStock(
        array $resultado,
        \PDOStatement $statementStock,
        \PDOStatement $statementDescuento,
        int $idStockConsumo,
        float $cantidad,
        float $factor,
        bool $controlarStock,
        int $idProducto
    ): array {
        $statementStock->execute([$idStockConsumo]);
        $stock = $statementStock->fetch();

        if (!is_array($stock)) {
            $resultado = ['ok' => false, 'id_venta' => 0, 'error' => 'Stock no encontrado para el producto.'];
        } else {
            $cantidadStockActual = (float) ($stock['cantidad'] ?? 0);
            $consumo = $this->calcularConsumoStock($cantidad, $factor);

            if ($controlarStock && $consumo > $cantidadStockActual + 0.0000001) {
                $resultado = ['ok' => false, 'id_venta' => 0, 'error' => 'Stock insuficiente para el producto ID ' . $idProducto . '.'];
            } else {
                $okDescuento = $statementDescuento->execute([$consumo, $idStockConsumo]);

                if (!$okDescuento) {
                    $resultado = ['ok' => false, 'id_venta' => 0, 'error' => 'No se pudo descontar stock.'];
                }
            }
        }

        return $resultado;
    }

    private function asegurarUsuarioSistema(int $idUsuario): int
    {
        $id = $idUsuario;

        if ($id <= 0) {
            $statement = $this->pdo->prepare("SELECT id FROM usuarios WHERE usuario = 'sistema' LIMIT 1");
            $statement->execute();
            $fila = $statement->fetch();

            if (is_array($fila)) {
                $id = (int) $fila['id'];
            } else {
                $insert = $this->pdo->prepare("INSERT INTO usuarios (usuario, clave, rol, activo, permisos) VALUES ('sistema', '', 'ADMIN', 1, ?)");
                $insert->execute(['[]']);
                $id = (int) $this->pdo->lastInsertId();
            }
        }

        return $id;
    }

    private function calcularTotalCarrito(array $carrito): float
    {
        $total = 0.0;

        foreach ($carrito as $item) {
            $total += $this->calcularSubtotal(
                (float) ($item['cantidad'] ?? 0),
                (float) ($item['precio_unit'] ?? 0),
                (float) ($item['descuento'] ?? 0)
            );
        }

        return $total;
    }

    private function calcularSubtotal(float $cantidad, float $precioUnitario, float $descuento): float
    {
        $cantidad = $this->normalizarMinimoCero($cantidad);
        $precioUnitario = $this->normalizarMinimoCero($precioUnitario);
        $descuento = $this->normalizarDescuento($descuento);
        $bruto = $cantidad * $precioUnitario;
        $subtotal = $this->normalizarMinimoCero($bruto - (($bruto * $descuento) / 100));

        return $subtotal;
    }

    private function calcularConsumoStock(float $cantidadProducto, float $factorConversion): float
    {
        $cantidad = $this->normalizarMinimoCero($cantidadProducto);
        $factor = $this->normalizarMinimoCero($factorConversion);
        $consumo = $cantidad * $factor;

        return $consumo;
    }

    private function obtenerIdStockConsumo(array $producto): ?int
    {
        $id = null;
        $idStock = $producto['id_stock'] ?? null;
        $idAsociado = $producto['id_asociado'] ?? null;

        if ($idStock !== null && (int) $idStock > 0) {
            $id = (int) $idStock;
        }

        if ($id === null && $idAsociado !== null && (int) $idAsociado > 0) {
            $id = (int) $idAsociado;
        }

        return $id;
    }

    private function construirPayloadFiscal(int $idVenta, string $tipoOperacion, int $tipoComprobante, array $configFiscal): array
    {
        $payload = [];
        $statement = $this->pdo->prepare(
            'SELECT v.id, v.fecha, v.total, c.nombre AS cliente_nombre, c.dni AS cliente_documento,
                    c.tipo_documento, c.condicion_iva, c.email
             FROM ventas v
             INNER JOIN clientes c ON c.id = v.id_cliente
             WHERE v.id = ?
             LIMIT 1'
        );
        $statement->execute([$idVenta]);
        $venta = $statement->fetch();

        if (is_array($venta)) {
            $comprobante = is_array($configFiscal['comprobante_defecto'] ?? null) ? $configFiscal['comprobante_defecto'] : [];
            $comprobante['tipo'] = $tipoComprobante;
            $payload = [
                'tipo_operacion' => $tipoOperacion,
                'venta' => [
                    'id' => (int) $venta['id'],
                    'fecha' => (string) $venta['fecha'],
                    'total' => (float) $venta['total'],
                ],
                'emisor' => $configFiscal['empresa'] ?? [],
                'comprobante' => $comprobante,
                'receptor' => [
                    'nombre' => (string) $venta['cliente_nombre'],
                    'documento' => (string) ($venta['cliente_documento'] ?? ''),
                    'tipo_documento' => (string) ($venta['tipo_documento'] ?? 'DNI'),
                    'condicion_iva' => (string) ($venta['condicion_iva'] ?? ''),
                    'email' => (string) ($venta['email'] ?? ''),
                ],
                'items' => $this->itemsPayloadFiscal($idVenta),
            ];
        }

        return $payload;
    }

    private function itemsPayloadFiscal(int $idVenta): array
    {
        $items = [];
        $detalles = $this->obtenerDetalleComprobante($idVenta);

        foreach ($detalles as $detalle) {
            $items[] = [
                'producto' => (string) $detalle['producto_nombre'],
                'cantidad' => (float) $detalle['cantidad'],
                'precio_unitario' => (float) $detalle['precio_unit'],
                'descuento' => (float) $detalle['descuento'],
                'subtotal' => (float) $detalle['subtotal'],
            ];
        }

        return $items;
    }

    private function obtenerIdComprobantePorVenta(int $idVenta): int
    {
        $id = 0;
        $statement = $this->pdo->prepare('SELECT id FROM fiscal_comprobantes WHERE id_venta = ? LIMIT 1');
        $statement->execute([$idVenta]);
        $fila = $statement->fetch();

        if (is_array($fila)) {
            $id = (int) $fila['id'];
        }

        return $id;
    }

    private function ordenVentasSql(string $campo, string $direccion): string
    {
        $direccionNormalizada = strtoupper($direccion) === 'ASC' ? 'ASC' : 'DESC';
        $orden = 'v.fecha ' . $direccionNormalizada;

        if ($campo === 'cliente' || $campo === 'nombre') {
            $orden = 'c.nombre ' . $direccionNormalizada;
        } elseif ($campo === 'precio' || $campo === 'total') {
            $orden = 'v.total ' . $direccionNormalizada;
        }

        return $orden;
    }

    private function normalizarIds(array $ids): array
    {
        $normalizados = [];

        foreach ($ids as $id) {
            $idNormalizado = (int) $id;

            if ($idNormalizado > 0 && !in_array($idNormalizado, $normalizados, true)) {
                $normalizados[] = $idNormalizado;
            }
        }

        return $normalizados;
    }

    private function placeholders(array $valores): string
    {
        $placeholders = implode(', ', array_fill(0, count($valores), '?'));

        return $placeholders;
    }

    private function normalizarDescuento(float $descuento): float
    {
        $normalizado = $this->normalizarMinimoCero($descuento);

        if ($normalizado > 100) {
            $normalizado = 100;
        }

        return $normalizado;
    }

    private function normalizarMinimoCero(float $valor): float
    {
        $normalizado = $valor;

        if ($normalizado < 0) {
            $normalizado = 0;
        }

        return $normalizado;
    }
}
