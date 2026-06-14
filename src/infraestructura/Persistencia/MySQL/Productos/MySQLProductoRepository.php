<?php

declare(strict_types=1);

namespace Ventas\Infraestructura\Persistencia\MySQL\Productos;

use PDO;
use Ventas\Dominio\Productos\Entidades\Producto;
use Ventas\Dominio\Productos\Repositorios\ProductoRepository;

final class MySQLProductoRepository implements ProductoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function listar(): array
    {
        $productos = [];
        $statement = $this->pdo->prepare(
            "SELECT p.id, p.nombre, p.cod_barras, p.id_stock, p.factor_conversion, p.ganancia, p.precio_final, p.activo, p.creado_en,
                    COALESCE(s.nombre, 'Sin stock asociado') AS stock_nombre, s.unidad AS stock_unidad, s.tipo_stock AS stock_tipo_stock,
                    s.cantidad AS stock_cantidad, s.stock_minimo, s.stock_maximo, s.precio_costo AS stock_precio_costo,
                    s.moneda_costo AS stock_moneda_costo, s.costo_origen AS stock_costo_origen
             FROM productos p
             LEFT JOIN stock s ON s.id = p.id_stock
             ORDER BY p.nombre ASC, p.id ASC"
        );

        $statement->execute();
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $productos[] = new Producto(
                (int) $fila['id'],
                (string) $fila['nombre'],
                isset($fila['cod_barras']) ? (string) $fila['cod_barras'] : null,
                isset($fila['id_stock']) ? (int) $fila['id_stock'] : null,
                (float) $fila['factor_conversion'],
                (float) $fila['ganancia'],
                (float) $fila['precio_final'],
                (int) $fila['activo'] === 1,
                isset($fila['creado_en']) ? (string) $fila['creado_en'] : null
            );
        }

        return $productos;
    }

    public function buscarPorId(int $id): ?Producto
    {
        $producto = null;
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.nombre, p.cod_barras, p.id_stock, p.factor_conversion, p.ganancia, p.precio_final, p.activo, p.creado_en,
                    s.nombre AS stock_nombre, s.unidad AS stock_unidad, s.tipo_stock AS stock_tipo_stock,
                    s.cantidad AS stock_cantidad, s.stock_minimo, s.stock_maximo, s.precio_costo AS stock_precio_costo,
                    s.moneda_costo AS stock_moneda_costo, s.costo_origen AS stock_costo_origen
             FROM productos p
             LEFT JOIN stock s ON s.id = p.id_stock
             WHERE p.id = :id
             LIMIT 1'
        );

        $statement->execute(['id' => $id]);
        $fila = $statement->fetch();

        if (is_array($fila)) {
            $producto = new Producto(
                (int) $fila['id'],
                (string) $fila['nombre'],
                isset($fila['cod_barras']) ? (string) $fila['cod_barras'] : null,
                isset($fila['id_stock']) ? (int) $fila['id_stock'] : null,
                (float) $fila['factor_conversion'],
                (float) $fila['ganancia'],
                (float) $fila['precio_final'],
                (int) $fila['activo'] === 1,
                isset($fila['creado_en']) ? (string) $fila['creado_en'] : null
            );
        }

        return $producto;
    }

    public function listarParaVista(string $ordenCampo, string $ordenDireccion, int $idListaPrecio): array
    {
        $productos = [];
        $ordenColumna = $this->columnaOrdenVista($ordenCampo);
        $direccion = strtoupper($ordenDireccion) === 'DESC' ? 'DESC' : 'ASC';
        $statement = $this->pdo->prepare(
            "SELECT p.id, p.nombre, p.cod_barras, p.id_stock, p.factor_conversion, p.ganancia, p.precio_final, p.activo, p.creado_en,
                    COALESCE(s.nombre, 'Sin stock asociado') AS stock_nombre, s.unidad AS stock_unidad, s.tipo_stock AS stock_tipo_stock,
                    s.cantidad AS stock_cantidad, s.stock_minimo, s.stock_maximo, s.precio_costo AS stock_precio_costo,
                    s.moneda_costo AS stock_moneda_costo, s.costo_origen AS stock_costo_origen
             FROM productos p
             LEFT JOIN stock s ON s.id = p.id_stock
             ORDER BY {$ordenColumna} {$direccion}, p.id ASC"
        );

        $statement->execute();
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $productos[] = $this->mapearProductoVista($fila, $idListaPrecio);
        }

        return $productos;
    }

    public function buscarFormularioPorId(int $id): ?array
    {
        $producto = null;
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.nombre, p.cod_barras, p.id_stock, p.factor_conversion, p.ganancia, p.precio_final, p.activo, p.creado_en,
                    s.nombre AS stock_nombre, s.unidad AS stock_unidad, s.tipo_stock AS stock_tipo_stock,
                    s.cantidad AS stock_cantidad, s.stock_minimo, s.stock_maximo, s.precio_costo AS stock_precio_costo,
                    s.moneda_costo AS stock_moneda_costo, s.costo_origen AS stock_costo_origen,
                    (SELECT COUNT(*) FROM productos px WHERE px.id_stock = p.id_stock) AS productos_asociados_stock
             FROM productos p
             LEFT JOIN stock s ON s.id = p.id_stock
             WHERE p.id = ?
             LIMIT 1'
        );

        $statement->execute([$id]);
        $fila = $statement->fetch();

        if (is_array($fila)) {
            $producto = $this->mapearProductoFormulario($fila);
        }

        return $producto;
    }

    public function preciosProducto(int $idProducto): array
    {
        $precios = [];

        if ($idProducto > 0) {
            $statement = $this->pdo->prepare(
                'SELECT pp.id_lista, pp.porcentaje, pp.precio, l.nombre AS lista_nombre
                 FROM producto_precios pp
                 INNER JOIN listas_precios l ON l.id = pp.id_lista
                 WHERE pp.id_producto = ?'
            );

            $statement->execute([$idProducto]);
            $filas = $statement->fetchAll();

            foreach ($filas as $fila) {
                $precios[(int) $fila['id_lista']] = $fila;
            }

            $precios = $this->normalizarPreciosProducto($idProducto, $precios);
        }

        return $precios;
    }

    public function listarPorStock(int $idStock): array
    {
        $productos = [];
        $statement = $this->pdo->prepare(
            'SELECT p.id,
                    p.nombre,
                    p.cod_barras,
                    p.id_stock,
                    p.factor_conversion,
                    p.ganancia,
                    p.precio_final,
                    p.activo,
                    p.creado_en
             FROM productos p
             WHERE p.id_stock = :id_stock
             ORDER BY p.nombre ASC, p.id ASC'
        );

        $statement->execute(['id_stock' => $idStock]);
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $productos[] = new Producto(
                (int) $fila['id'],
                (string) $fila['nombre'],
                isset($fila['cod_barras']) ? (string) $fila['cod_barras'] : null,
                isset($fila['id_stock']) ? (int) $fila['id_stock'] : null,
                (float) $fila['factor_conversion'],
                (float) $fila['ganancia'],
                (float) $fila['precio_final'],
                (int) $fila['activo'] === 1,
                isset($fila['creado_en']) ? (string) $fila['creado_en'] : null
            );
        }

        return $productos;
    }

    public function buscarParaVenta(
        string $texto,
        string $modo,
        int $idListaPrecio,
        int $limite
    ): array {
        $productos = [];
        $textoNormalizado = trim($texto);
        $limiteNormalizado = max(1, min(50, $limite));
        $soloCodigo = $modo === 'codigo';
        $statement = $this->crearConsultaBuscarParaVenta($textoNormalizado, $soloCodigo, $limiteNormalizado);
        $parametros = $this->parametrosBuscarParaVenta($textoNormalizado, $soloCodigo);

        $statement->execute($parametros);
        $filas = $statement->fetchAll();

        foreach ($filas as $fila) {
            $precioInfo = $this->precioProductoCargado((int) $fila['id'], $idListaPrecio);
            $precio = $precioInfo !== null ? (float) $precioInfo['precio'] : 0.0;
            $productos[] = [
                'id' => (int) $fila['id'],
                'nombre' => (string) $fila['nombre'],
                'cod_barras' => (string) ($fila['cod_barras'] ?? ''),
                'precio' => $precio,
                'precio_texto' => $precio > 0 ? $this->precioParaMostrar($precio) : 'SIN PRECIO',
                'precios_lista' => (string) ($fila['precios_lista'] ?? ''),
                'stock_unidad' => (string) ($fila['stock_unidad'] ?? 'u'),
                'unidad_decimales' => (int) ($fila['unidad_decimales'] ?? 3),
            ];
        }

        return $productos;
    }

    public function obtenerPrecioPorLista(
        int $idProducto,
        int $idListaPrecio
    ): ?array {
        return $this->precioProductoCargado($idProducto, $idListaPrecio);
    }

    public function obtenerProductoParaVenta(int $idProducto): ?array
    {
        $producto = null;

        if ($idProducto > 0) {
            $statement = $this->pdo->prepare(
                'SELECT id, nombre, precio_final, id_stock, id_asociado, factor_conversion, activo
                 FROM productos
                 WHERE id = ?
                 LIMIT 1'
            );

            $statement->execute([$idProducto]);
            $fila = $statement->fetch();

            if (is_array($fila)) {
                $producto = $fila;
            }
        }

        return $producto;
    }

    public function buscarPorCodigoOPluVenta(string $codigo): ?array
    {
        $producto = null;
        $codigoNormalizado = preg_replace('/\D+/', '', $codigo) ?? '';
        $codigoNormalizado = trim($codigoNormalizado);

        if ($codigoNormalizado !== '') {
            $statement = $this->pdo->prepare(
                "SELECT id, nombre, cod_barras, precio_final, factor_conversion, id_stock, id_asociado, activo
                 FROM productos
                 WHERE activo = 1 AND (cod_barras = ? OR TRIM(LEADING '0' FROM cod_barras) = TRIM(LEADING '0' FROM ?))
                 ORDER BY CHAR_LENGTH(cod_barras) ASC
                 LIMIT 1"
            );

            $statement->execute([$codigoNormalizado, $codigoNormalizado]);
            $fila = $statement->fetch();

            if (is_array($fila)) {
                $producto = $fila;
            }
        }

        return $producto;
    }

    public function eliminarNoVendidos(): int
    {
        $eliminados = 0;

        try {
            $this->pdo->beginTransaction();
            $statement = $this->pdo->prepare(
                'SELECT p.id, p.id_stock
                 FROM productos p
                 WHERE NOT EXISTS (SELECT 1 FROM detalle_venta d WHERE d.id_producto = p.id)'
            );
            $statement->execute();
            $productos = $statement->fetchAll();
            $deletePrecios = $this->pdo->prepare('DELETE FROM producto_precios WHERE id_producto = ?');
            $deleteProducto = $this->pdo->prepare('DELETE FROM productos WHERE id = ?');
            $conteoStock = $this->pdo->prepare('SELECT COUNT(*) AS cantidad FROM productos WHERE id_stock = ?');
            $deleteStock = $this->pdo->prepare('DELETE FROM stock WHERE id = ?');
            $idsStock = [];

            foreach ($productos as $producto) {
                $idProducto = (int) $producto['id'];
                $idStock = (int) ($producto['id_stock'] ?? 0);
                $deletePrecios->execute([$idProducto]);
                $deleteProducto->execute([$idProducto]);
                $eliminados++;

                if ($idStock > 0) {
                    $idsStock[$idStock] = $idStock;
                }
            }

            foreach ($idsStock as $idStock) {
                $conteoStock->execute([$idStock]);
                $fila = $conteoStock->fetch();
                $cantidad = is_array($fila) ? (int) ($fila['cantidad'] ?? 0) : 0;

                if ($cantidad === 0) {
                    $deleteStock->execute([$idStock]);
                }
            }

            $this->pdo->commit();
        } catch (\Throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            $eliminados = 0;
        }

        return $eliminados;
    }

    private function crearConsultaBuscarParaVenta(
        string $texto,
        bool $soloCodigo,
        int $limite
    ): \PDOStatement {
        if ($texto === '') {
            $statement = $this->pdo->prepare(
                'SELECT p.id, p.nombre, p.cod_barras, p.precio_final, p.factor_conversion, p.id_stock, p.id_asociado,
                        s.unidad AS stock_unidad, COALESCE(um.decimales, 3) AS unidad_decimales,
                        GROUP_CONCAT(CONCAT(pp.id_lista, ' . "':'" . ', pp.precio) SEPARATOR ' . "'|'" . ') AS precios_lista
                 FROM productos p
                 LEFT JOIN stock s ON s.id = p.id_stock
                 LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
                 LEFT JOIN producto_precios pp ON pp.id_producto = p.id
                 WHERE p.activo = 1
                 GROUP BY p.id, p.nombre, p.cod_barras, p.precio_final, p.factor_conversion, p.id_stock, p.id_asociado, s.unidad, um.decimales
                 ORDER BY p.nombre ASC
                 LIMIT ' . $limite
            );
        } elseif ($soloCodigo) {
            $statement = $this->pdo->prepare(
                'SELECT p.id, p.nombre, p.cod_barras, p.precio_final, p.factor_conversion, p.id_stock, p.id_asociado,
                        s.unidad AS stock_unidad, COALESCE(um.decimales, 3) AS unidad_decimales,
                        GROUP_CONCAT(CONCAT(pp.id_lista, ' . "':'" . ', pp.precio) SEPARATOR ' . "'|'" . ') AS precios_lista
                 FROM productos p
                 LEFT JOIN stock s ON s.id = p.id_stock
                 LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
                 LEFT JOIN producto_precios pp ON pp.id_producto = p.id
                 WHERE p.activo = 1 AND p.cod_barras LIKE ?
                 GROUP BY p.id, p.nombre, p.cod_barras, p.precio_final, p.factor_conversion, p.id_stock, p.id_asociado, s.unidad, um.decimales
                 ORDER BY CHAR_LENGTH(p.cod_barras) ASC, p.nombre ASC
                 LIMIT ' . $limite
            );
        } else {
            $statement = $this->pdo->prepare(
                'SELECT p.id, p.nombre, p.cod_barras, p.precio_final, p.factor_conversion, p.id_stock, p.id_asociado,
                        s.unidad AS stock_unidad, COALESCE(um.decimales, 3) AS unidad_decimales,
                        GROUP_CONCAT(CONCAT(pp.id_lista, ' . "':'" . ', pp.precio) SEPARATOR ' . "'|'" . ') AS precios_lista
                 FROM productos p
                 LEFT JOIN stock s ON s.id = p.id_stock
                 LEFT JOIN unidades_medida um ON um.abreviatura COLLATE utf8mb4_unicode_ci = s.unidad COLLATE utf8mb4_unicode_ci
                 LEFT JOIN producto_precios pp ON pp.id_producto = p.id
                 WHERE p.activo = 1 AND (p.nombre LIKE ? OR p.cod_barras LIKE ?)
                 GROUP BY p.id, p.nombre, p.cod_barras, p.precio_final, p.factor_conversion, p.id_stock, p.id_asociado, s.unidad, um.decimales
                 ORDER BY p.nombre ASC
                 LIMIT ' . $limite
            );
        }

        return $statement;
    }

    private function parametrosBuscarParaVenta(string $texto, bool $soloCodigo): array
    {
        $parametros = [];

        if ($texto !== '' && $soloCodigo) {
            $parametros[] = $texto . '%';
        }

        if ($texto !== '' && !$soloCodigo) {
            $like = '%' . $texto . '%';
            $parametros[] = $like;
            $parametros[] = $like;
        }

        return $parametros;
    }

    private function precioProductoCargado(int $idProducto, int $idLista): ?array
    {
        $precio = null;

        if ($idProducto > 0 && $idLista > 0) {
            $statement = $this->pdo->prepare(
                "SELECT l.nombre AS lista_nombre, COALESCE(pp.porcentaje, 0) AS porcentaje,
                        COALESCE(pp.precio, 0) AS precio,
                        COALESCE(s.precio_costo, 0) AS costo_stock,
                        COALESCE(pc.precio, 0) AS costo_lista
                 FROM productos p
                 INNER JOIN listas_precios l ON l.id = ?
                 LEFT JOIN stock s ON s.id = p.id_stock
                 LEFT JOIN producto_precios pp ON pp.id_producto = p.id AND pp.id_lista = l.id
                 LEFT JOIN listas_precios lc ON LOWER(lc.nombre) = 'costo' AND lc.activo = 1
                 LEFT JOIN producto_precios pc ON pc.id_producto = p.id AND pc.id_lista = lc.id
                 WHERE p.id = ?
                 LIMIT 1"
            );
            $statement->execute([$idLista, $idProducto]);
            $fila = $statement->fetch();

            if (is_array($fila)) {
                $precio = $this->mapearPrecioProductoCargado($fila);
            }
        }

        return $precio;
    }

    private function mapearPrecioProductoCargado(array $fila): ?array
    {
        $precio = null;
        $nombreLista = strtolower(trim((string) ($fila['lista_nombre'] ?? '')));

        if ($nombreLista === 'costo') {
            $costo = (float) ($fila['precio'] ?? 0);

            if ($costo <= 0) {
                $costo = (float) ($fila['costo_stock'] ?? 0);
            }

            $precio = [
                'porcentaje' => 0.0,
                'precio' => $costo,
            ];
        } elseif ((float) ($fila['precio'] ?? 0) > 0) {
            $porcentaje = (float) $fila['porcentaje'];
            $costoBase = (float) ($fila['costo_lista'] ?? 0);

            if ($costoBase <= 0) {
                $costoBase = (float) ($fila['costo_stock'] ?? 0);
            }

            if ($porcentaje <= 0 && $costoBase > 0) {
                $porcentaje = (((float) $fila['precio'] / $costoBase) - 1) * 100;
            }

            $precio = [
                'porcentaje' => $porcentaje,
                'precio' => (float) $fila['precio'],
            ];
        }

        return $precio;
    }

    private function precioParaMostrar(float $precio): string
    {
        return '$' . number_format($precio, 2, ',', '.');
    }

    private function columnaOrdenVista(string $ordenCampo): string
    {
        $columna = 'p.nombre';

        if ($ordenCampo === 'codigo' || $ordenCampo === 'codigo_barras') {
            $columna = 'p.cod_barras';
        } elseif ($ordenCampo === 'precio') {
            $columna = 'p.precio_final';
        } elseif ($ordenCampo === 'stock') {
            $columna = 's.cantidad';
        } elseif ($ordenCampo === 'estado') {
            $columna = 'p.activo';
        } elseif ($ordenCampo === 'fecha') {
            $columna = 'p.creado_en';
        }

        return $columna;
    }

    private function mapearProductoVista(array $fila, int $idListaPrecio): array
    {
        $precioLista = $this->precioProductoCargado((int) $fila['id'], $idListaPrecio);
        $precio = $precioLista !== null ? (float) $precioLista['precio'] : 0.0;
        $porcentaje = $precioLista !== null ? (float) $precioLista['porcentaje'] : 0.0;

        return [
            'id' => (int) $fila['id'],
            'nombre' => (string) $fila['nombre'],
            'cod_barras' => (string) ($fila['cod_barras'] ?? ''),
            'id_stock' => isset($fila['id_stock']) ? (int) $fila['id_stock'] : null,
            'factor_conversion' => (float) $fila['factor_conversion'],
            'ganancia' => (float) $fila['ganancia'],
            'precio_final' => (float) $fila['precio_final'],
            'activo' => (int) $fila['activo'],
            'creado_en' => isset($fila['creado_en']) ? (string) $fila['creado_en'] : null,
            'stock_nombre' => (string) ($fila['stock_nombre'] ?? ''),
            'stock_unidad' => (string) ($fila['stock_unidad'] ?? ''),
            'stock_tipo_stock' => (string) ($fila['stock_tipo_stock'] ?? ''),
            'stock_cantidad' => (float) ($fila['stock_cantidad'] ?? 0),
            'stock_minimo' => (float) ($fila['stock_minimo'] ?? 0),
            'stock_maximo' => (float) ($fila['stock_maximo'] ?? 0),
            'stock_precio_costo' => (float) ($fila['stock_precio_costo'] ?? 0),
            'stock_moneda_costo' => (string) ($fila['stock_moneda_costo'] ?? 'ARS'),
            'stock_costo_origen' => (float) ($fila['stock_costo_origen'] ?? 0),
            'porcentaje_lista' => $porcentaje,
            'precio_lista' => $precio,
        ];
    }

    private function mapearProductoFormulario(array $fila): array
    {
        $producto = $this->mapearProductoVista($fila, 0);
        $producto['id_asociado'] = null;
        $producto['usa_stock_general'] = $this->usaStockGeneral($fila) ? 1 : 0;

        return $producto;
    }

    private function usaStockGeneral(array $fila): bool
    {
        $usaStockGeneral = false;
        $idStock = (int) ($fila['id_stock'] ?? 0);
        $tipoStock = (string) ($fila['stock_tipo_stock'] ?? '');
        $nombreProducto = strtolower(trim((string) ($fila['nombre'] ?? '')));
        $nombreStock = strtolower(trim((string) ($fila['stock_nombre'] ?? '')));
        $nombreProducto = preg_replace('/\s+/', ' ', $nombreProducto) ?? $nombreProducto;
        $nombreStock = preg_replace('/\s+/', ' ', $nombreStock) ?? $nombreStock;

        if ($idStock > 0 && $tipoStock === 'general') {
            $usaStockGeneral = true;
        } elseif ($idStock > 0 && $tipoStock !== 'propio') {
            $usaStockGeneral = (int) ($fila['productos_asociados_stock'] ?? 0) > 1 || ($nombreStock !== '' && $nombreProducto !== '' && $nombreStock !== $nombreProducto);
        }

        return $usaStockGeneral;
    }

    private function normalizarPreciosProducto(int $idProducto, array $precios): array
    {
        $costo = $this->costoProducto($idProducto, $precios);

        foreach ($precios as $idLista => $precio) {
            $nombreLista = strtolower(trim((string) ($precio['lista_nombre'] ?? '')));

            if ($nombreLista === 'costo') {
                $precios[$idLista]['porcentaje'] = 0;

                if ((float) ($precio['precio'] ?? 0) <= 0) {
                    $precios[$idLista]['precio'] = $costo;
                }
            } elseif ((float) ($precio['porcentaje'] ?? 0) <= 0 && $costo > 0 && (float) ($precio['precio'] ?? 0) > 0) {
                $precios[$idLista]['porcentaje'] = (((float) $precio['precio'] / $costo) - 1) * 100;
            }
        }

        return $precios;
    }

    private function costoProducto(int $idProducto, array $precios): float
    {
        $costo = 0.0;

        foreach ($precios as $precio) {
            $nombreLista = strtolower(trim((string) ($precio['lista_nombre'] ?? '')));

            if ($nombreLista === 'costo' && $costo <= 0) {
                $costo = (float) ($precio['precio'] ?? 0);
            }
        }

        if ($costo <= 0) {
            $statement = $this->pdo->prepare(
                'SELECT COALESCE(s.precio_costo, 0) AS costo_stock
                 FROM productos p
                 LEFT JOIN stock s ON s.id = p.id_stock
                 WHERE p.id = ?
                 LIMIT 1'
            );
            $statement->execute([$idProducto]);
            $fila = $statement->fetch();
            $costo = is_array($fila) ? (float) ($fila['costo_stock'] ?? 0) : 0.0;
        }

        return $costo;
    }
}
