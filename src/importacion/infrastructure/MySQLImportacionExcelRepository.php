<?php

declare(strict_types=1);

namespace Ventas\Importacion\Infrastructure;

use PDO;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;
use Ventas\Importacion\Domain\Repositorios\ImportacionExcelRepository;

final class MySQLImportacionExcelRepository implements ImportacionExcelRepository
{
    private const MAX_FILAS_ANALISIS = 2000;
    private const MAX_PREVIEW = 80;

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function hojas(string $ruta): array
    {
        $hojas = [];

        if (is_file($ruta) && class_exists(IOFactory::class)) {
            try {
                $reader = IOFactory::createReaderForFile($ruta);
                $hojas = $reader->listWorksheetNames($ruta);
            } catch (Throwable $exception) {
                $this->registrarLog("ImportacionExcelRepository::hojas", $exception->getMessage());
            }
        }

        $resultado = is_array($hojas) ? $hojas : [];

        return $resultado;
    }

    public function analizar(string $ruta, int $indiceHoja): array
    {
        $resultado = $this->resultadoBase();

        if (!class_exists(IOFactory::class)) {
            $resultado["errores"][] = ["fila" => 0, "mensaje" => "PhpSpreadsheet no esta instalado."];
        } elseif (!is_file($ruta)) {
            $resultado["errores"][] = ["fila" => 0, "mensaje" => "Archivo no encontrado."];
        } else {
            try {
                $reader = IOFactory::createReaderForFile($ruta);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($ruta);
                $cantidadHojas = $spreadsheet->getSheetCount();

                if ($indiceHoja < 0 || $indiceHoja >= $cantidadHojas) {
                    $indiceHoja = 0;
                }

                $hoja = $spreadsheet->getSheet($indiceHoja);
                $resultado["hoja"] = $indiceHoja;
                $resultado["hoja_nombre"] = $hoja->getTitle();
                $resultado["hojas"] = $spreadsheet->getSheetNames();
                $resultado = $this->analizarHoja($hoja, $resultado);
                $spreadsheet->disconnectWorksheets();
            } catch (Throwable $exception) {
                $this->registrarLog("ImportacionExcelRepository::analizar", $exception->getMessage());
                $resultado["errores"][] = ["fila" => 0, "mensaje" => $this->mensajeErrorExcel($exception)];
            }
        }

        $resultado["resumen"]["advertencias"] = count($resultado["advertencias"]);
        $resultado["resumen"]["errores"] = count($resultado["errores"]);

        return $resultado;
    }

    private function resultadoBase(): array
    {
        $resultado = [
            "ok" => true,
            "hoja" => 0,
            "hoja_nombre" => "",
            "hojas" => [],
            "columnas" => ["codigo" => "", "nombre" => "", "listas" => []],
            "filas" => [],
            "preview" => [],
            "advertencias" => [],
            "errores" => [],
            "resumen" => [
                "nuevos" => 0,
                "actualizados" => 0,
                "omitidos" => 0,
                "advertencias" => 0,
                "errores" => 0,
            ],
        ];

        return $resultado;
    }

    private function analizarHoja(Worksheet $hoja, array $resultado): array
    {
        $highestRow = min((int) $hoja->getHighestDataRow(), self::MAX_FILAS_ANALISIS);
        $highestColumn = $hoja->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $encabezados = $this->detectarEncabezados($hoja, $highestRow, $highestColumnIndex);

        if (empty($encabezados["fila"])) {
            $resultado["errores"][] = ["fila" => 0, "mensaje" => "No se encontro una fila de encabezados reconocible."];
        } else {
            $resultado["columnas"] = [
                "codigo" => (string) ($encabezados["nombres"][$encabezados["codigo"]] ?? ""),
                "nombre" => (string) ($encabezados["nombres"][$encabezados["nombre"]] ?? ""),
                "listas" => $encabezados["listas"],
            ];

            if (empty($encabezados["codigo"])) {
                $resultado["errores"][] = ["fila" => (int) $encabezados["fila"], "mensaje" => "No se detecto columna de codigo."];
            }

            if (empty($encabezados["nombre"])) {
                $resultado["errores"][] = ["fila" => (int) $encabezados["fila"], "mensaje" => "No se detecto columna de nombre o descripcion."];
            }

            if (count($resultado["errores"]) === 0) {
                $resultado = $this->analizarFilas($hoja, $highestRow, $highestColumnIndex, $encabezados, $resultado);
            }
        }

        return $resultado;
    }

    private function detectarEncabezados(Worksheet $hoja, int $highestRow, int $highestColumnIndex): array
    {
        $detectado = ["fila" => 0, "codigo" => 0, "nombre" => 0, "listas" => [], "nombres" => [], "desconocidas" => []];
        $listas = $this->listasNormalizadas();
        $limite = min($highestRow, 30);

        for ($fila = 1; $fila <= $limite && (int) $detectado["fila"] === 0; $fila++) {
            $nombres = [];
            $codigo = 0;
            $nombre = 0;
            $listasDetectadas = [];
            $desconocidas = [];

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $valor = $this->celdaTexto($hoja, $fila, $col);
                $nombres[$col] = $valor;
                $normal = $this->normalizarClave($valor);

                if ($normal !== "") {
                    if ($codigo === 0 && in_array($normal, ["codigo", "cod", "codigobarras", "ean", "plu", "codbarras"], true)) {
                        $codigo = $col;
                    }

                    if ($nombre === 0 && in_array($normal, ["nombre", "descripcion", "producto"], true)) {
                        $nombre = $col;
                    }

                    $idLista = $this->idListaPorNormalizado($normal, $listas);

                    if ($idLista > 0) {
                        $listasDetectadas[$col] = ["id" => $idLista, "nombre" => $listas[$idLista]["nombre"], "columna" => $valor];
                    } elseif (!in_array($normal, ["codigo", "cod", "codigobarras", "ean", "plu", "codbarras", "nombre", "descripcion", "producto"], true)) {
                        $desconocidas[$col] = $valor;
                    }
                }
            }

            if (($codigo > 0 && $nombre > 0) || ($nombre > 0 && count($listasDetectadas) > 0)) {
                $detectado = ["fila" => $fila, "codigo" => $codigo, "nombre" => $nombre, "listas" => $listasDetectadas, "nombres" => $nombres, "desconocidas" => $desconocidas];
            }
        }

        return $detectado;
    }

    private function analizarFilas(Worksheet $hoja, int $highestRow, int $highestColumnIndex, array $encabezados, array $resultado): array
    {
        $productos = $this->productosPorCodigo();

        foreach ($encabezados["desconocidas"] as $col => $titulo) {
            if ($this->columnaPareceLista($hoja, (int) $encabezados["fila"] + 1, $highestRow, (int) $col)) {
                $resultado["advertencias"][] = ["fila" => (int) $encabezados["fila"], "mensaje" => "La lista " . trim((string) $titulo) . " no existe y fue ignorada"];
            }
        }

        $codigosVistos = [];

        for ($fila = (int) $encabezados["fila"] + 1; $fila <= $highestRow; $fila++) {
            $filaVacia = $this->filaVacia($hoja, $fila, $highestColumnIndex);
            $esTituloRepetido = $this->esTituloRepetido($hoja, $fila, $encabezados);

            if (!$filaVacia && !$esTituloRepetido) {
                $codigo = $this->limpiarCodigo($this->celdaTexto($hoja, $fila, (int) $encabezados["codigo"]));
                $nombre = $this->limpiarNombre($this->celdaTexto($hoja, $fila, (int) $encabezados["nombre"]));
                $detalle = [];
                $accion = "Nuevo";
                $idProducto = 0;

                if ($codigo === "") {
                    $accion = "Ignorar";
                    $detalle[] = "Codigo vacio";
                }

                if ($nombre === "") {
                    $accion = "Ignorar";
                    $detalle[] = "Nombre o descripcion vacio";
                }

                if ($accion !== "Ignorar" && isset($codigosVistos[$codigo])) {
                    $accion = "Ignorar";
                    $detalle[] = "Codigo duplicado en el archivo";
                    $resultado["advertencias"][] = ["fila" => $fila, "mensaje" => "Codigo duplicado en el archivo"];
                }

                if ($accion !== "Ignorar") {
                    $codigosVistos[$codigo] = true;
                }

                $accionBase = $accion;

                if ($accion !== "Ignorar" && isset($productos[$codigo])) {
                    $accion = "Actualizar";
                    $accionBase = "Actualizar";
                    $idProducto = (int) $productos[$codigo]["id"];
                }

                $precios = $this->preciosFila($hoja, $fila, $encabezados, $detalle, $resultado);

                if ($accion !== "Ignorar" && count($detalle) > 0) {
                    $accion = "Advertencia";
                }

                $filaResultado = [
                    "fila" => $fila,
                    "codigo" => $codigo,
                    "nombre" => $nombre,
                    "accion" => $accion,
                    "accion_base" => $accionBase,
                    "id_producto" => $idProducto,
                    "precios" => $precios,
                    "detalle" => $detalle,
                ];
                $resultado["filas"][] = $filaResultado;

                if (count($resultado["preview"]) < self::MAX_PREVIEW) {
                    $resultado["preview"][] = $filaResultado;
                }
            }
        }

        foreach ($resultado["filas"] as $fila) {
            $accionResumen = (string) ($fila["accion_base"] ?? $fila["accion"]);

            if ($accionResumen === "Nuevo") {
                $resultado["resumen"]["nuevos"]++;
            } elseif ($accionResumen === "Actualizar") {
                $resultado["resumen"]["actualizados"]++;
            } elseif ($accionResumen === "Ignorar") {
                $resultado["resumen"]["omitidos"]++;
            }
        }

        return $resultado;
    }

    private function preciosFila(Worksheet $hoja, int $fila, array $encabezados, array &$detalle, array &$resultado): array
    {
        $precios = [];

        foreach ($encabezados["listas"] as $col => $lista) {
            $raw = $this->celdaTexto($hoja, $fila, (int) $col);
            $precio = $this->parsearPrecio($raw);

            if ($precio["estado"] === "ok") {
                $precios[] = ["id_lista" => (int) $lista["id"], "lista" => (string) $lista["nombre"], "precio" => (float) $precio["valor"]];
            } elseif ($precio["estado"] === "invalido") {
                $detalle[] = "Precio invalido en lista " . (string) $lista["nombre"];
                $resultado["advertencias"][] = ["fila" => $fila, "mensaje" => "Precio invalido en lista " . (string) $lista["nombre"]];
            }
        }

        return $precios;
    }

    private function listasNormalizadas(): array
    {
        $mapa = [];

        try {
            $statement = $this->pdo->query("SELECT id, nombre FROM listas_precios WHERE activo = 1 ORDER BY nombre ASC, id ASC");
            $rows = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];

            foreach ($rows as $row) {
                $id = (int) $row["id"];
                $nombre = (string) ($row["nombre"] ?? "");
                $mapa[$id] = [
                    "id" => $id,
                    "nombre" => $nombre,
                    "claves" => array_values(array_unique([
                        $this->normalizarClave($nombre),
                        $this->normalizarClave(preg_replace('/^lista\s+/i', '', $nombre) ?? $nombre),
                        $this->normalizarClave("lista " . $nombre),
                    ])),
                ];
            }
        } catch (Throwable $exception) {
            $this->registrarLog("ImportacionExcelRepository::listasNormalizadas", $exception->getMessage());
        }

        return $mapa;
    }

    private function idListaPorNormalizado(string $normal, array $listas): int
    {
        $id = 0;
        $normalSinLista = preg_replace('/^lista/', '', $normal) ?? $normal;

        foreach ($listas as $lista) {
            $claves = $lista["claves"] ?? [];

            if ($id === 0 && (in_array($normal, $claves, true) || in_array($normalSinLista, $claves, true))) {
                $id = (int) $lista["id"];
            }
        }

        return $id;
    }

    private function productosPorCodigo(): array
    {
        $mapa = [];

        try {
            $statement = $this->pdo->query("SELECT id, cod_barras, nombre FROM productos WHERE cod_barras IS NOT NULL AND cod_barras <> ''");
            $rows = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];

            foreach ($rows as $row) {
                $mapa[(string) $row["cod_barras"]] = $row;
            }
        } catch (Throwable $exception) {
            $this->registrarLog("ImportacionExcelRepository::productosPorCodigo", $exception->getMessage());
        }

        return $mapa;
    }

    private function celdaTexto(Worksheet $hoja, int $fila, int $col): string
    {
        $texto = "";

        if ($fila > 0 && $col > 0) {
            try {
                $coordenada = Coordinate::stringFromColumnIndex($col) . $fila;
                $cell = $hoja->getCell($coordenada);
                $valor = $cell->getCalculatedValue();

                if (SpreadsheetDate::isDateTime($cell) && is_numeric($valor)) {
                    $texto = date("Y-m-d", SpreadsheetDate::excelToTimestamp((float) $valor));
                } elseif (is_scalar($valor) || $valor === null) {
                    $texto = trim((string) $valor);
                }
            } catch (Throwable $exception) {
                $this->registrarLog("ImportacionExcelRepository::celdaTexto", $exception->getMessage());
            }
        }

        $resultado = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return $resultado;
    }

    private function filaVacia(Worksheet $hoja, int $fila, int $highestColumnIndex): bool
    {
        $vacia = true;

        for ($col = 1; $col <= $highestColumnIndex && $vacia; $col++) {
            if ($this->celdaTexto($hoja, $fila, $col) !== "") {
                $vacia = false;
            }
        }

        return $vacia;
    }

    private function esTituloRepetido(Worksheet $hoja, int $fila, array $encabezados): bool
    {
        $repetido = false;
        $codigo = $this->normalizarClave($this->celdaTexto($hoja, $fila, (int) $encabezados["codigo"]));
        $nombre = $this->normalizarClave($this->celdaTexto($hoja, $fila, (int) $encabezados["nombre"]));
        $codigoHeader = $this->normalizarClave((string) ($encabezados["nombres"][$encabezados["codigo"]] ?? ""));
        $nombreHeader = $this->normalizarClave((string) ($encabezados["nombres"][$encabezados["nombre"]] ?? ""));

        if ($codigo !== "" && $nombre !== "" && $codigo === $codigoHeader && $nombre === $nombreHeader) {
            $repetido = true;
        }

        return $repetido;
    }

    private function columnaPareceLista(Worksheet $hoja, int $desde, int $hasta, int $col): bool
    {
        $parece = false;
        $limite = min($hasta, $desde + 40);

        for ($fila = $desde; $fila <= $limite && !$parece; $fila++) {
            $precio = $this->parsearPrecio($this->celdaTexto($hoja, $fila, $col));

            if ($precio["estado"] === "ok" || $precio["estado"] === "invalido") {
                $parece = true;
            }
        }

        return $parece;
    }

    private function normalizarClave(string $texto): string
    {
        $texto = trim($texto);
        $texto = strtr($texto, [
            "Ã¡" => "a",
            "Ã©" => "e",
            "Ã­" => "i",
            "Ã³" => "o",
            "Ãº" => "u",
            "Ã±" => "n",
            "Ã¼" => "u",
            "Ã" => "a",
            "Ã‰" => "e",
            "Ã" => "i",
            "Ã“" => "o",
            "Ãš" => "u",
            "Ã‘" => "n",
            "Ãœ" => "u",
            "ÃƒÂ¡" => "a",
            "ÃƒÂ©" => "e",
            "ÃƒÂ­" => "i",
            "ÃƒÂ³" => "o",
            "ÃƒÂº" => "u",
            "ÃƒÂ±" => "n",
        ]);
        $texto = mb_strtolower($texto, "UTF-8");
        $resultado = preg_replace('/[^a-z0-9]+/u', '', $texto) ?? "";

        return $resultado;
    }

    private function limpiarCodigo(string $codigo): string
    {
        $codigo = trim($codigo);
        $codigo = preg_replace('/[^\pL\pN_\-\.]/u', '', $codigo) ?? "";
        $resultado = substr($codigo, 0, 80);

        return $resultado;
    }

    private function limpiarNombre(string $nombre): string
    {
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre) ?? $nombre);
        $nombre = preg_replace('/[\x00-\x1F\x7F]/u', '', $nombre) ?? "";
        $resultado = substr($nombre, 0, 180);

        return $resultado;
    }

    private function parsearPrecio(string $valor): array
    {
        $resultado = ["estado" => "vacio", "valor" => 0.0];
        $texto = trim($valor);
        $normal = $this->normalizarClave($texto);

        if ($texto !== "" && !in_array($normal, ["sinstock", "sinprecio", "n/a", "na"], true)) {
            $limpio = preg_replace('/[^0-9,\.\-]/', '', $texto) ?? "";

            if ($limpio === "" || $limpio === "-" || substr_count($limpio, "-") > 1) {
                $resultado["estado"] = "invalido";
            } else {
                $limpio = $this->normalizarNumero($limpio);

                if (is_numeric($limpio) && (float) $limpio >= 0) {
                    $resultado["estado"] = "ok";
                    $resultado["valor"] = round((float) $limpio, 2);
                } else {
                    $resultado["estado"] = "invalido";
                }
            }
        }

        return $resultado;
    }

    private function normalizarNumero(string $limpio): string
    {
        $resultado = $limpio;
        $posPunto = strrpos($resultado, ".");
        $posComa = strrpos($resultado, ",");

        if ($posPunto !== false && $posComa !== false) {
            if ($posComa > $posPunto) {
                $resultado = str_replace(".", "", $resultado);
                $resultado = str_replace(",", ".", $resultado);
            } else {
                $resultado = str_replace(",", "", $resultado);
            }
        } elseif ($posComa !== false) {
            $resultado = str_replace(".", "", $resultado);
            $resultado = str_replace(",", ".", $resultado);
        } elseif (substr_count($resultado, ".") > 1) {
            $ultimo = strrpos($resultado, ".");
            $resultado = str_replace(".", "", substr($resultado, 0, $ultimo)) . substr($resultado, $ultimo);
        }

        return $resultado;
    }

    private function mensajeErrorExcel(Throwable $exception): string
    {
        $mensaje = "No se pudo leer el Excel.";
        $texto = $exception->getMessage();

        if (stripos($texto, "ZipArchive") !== false || stripos($texto, "zip") !== false) {
            $mensaje = "No se pudo leer .xlsx/.xlsm porque la extension zip de PHP no esta habilitada.";
        }

        return $mensaje;
    }

    private function registrarLog(string $contexto, string $mensaje): void
    {
        if (function_exists("\\registrar_log")) {
            \registrar_log($contexto, $mensaje);
        }
    }
}
