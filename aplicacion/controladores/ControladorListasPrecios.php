<?php
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorListasPrecios {
    private function permiso(): bool {
        if (!require_login()) {
            flash_error("Tenes que iniciar sesion.");
            redirigir("index.php?c=auth&a=login");
        }
        if (!require_rol(["ADMIN","VENDEDOR"])) {
            flash_error("No tenes permisos para listas de precios.");
            redirigir("index.php?c=ventas&a=lista");
        }
        return true;
    }

    private function listaPrecioDominioAArray(\Ventas\ListasPrecios\Domain\Entidades\ListaPrecio $lista): array {
        $resultado = [
            "id" => $lista->id(),
            "nombre" => $lista->nombre(),
            "activo" => $lista->activo() ? 1 : 0,
            "creado_en" => $lista->creadoEn(),
        ];

        return $resultado;
    }

    private function listar_listas_precios(bool $solo_activas = true, string $orden_sql = "nombre ASC"): array {
        global $container;

        $listarListasPrecios = $container->get(\Ventas\ListasPrecios\Application\ListarListasPrecios::class);
        $esListaCosto = $container->get(\Ventas\ListasPrecios\Application\EsListaCosto::class);
        $resultado = [];

        foreach ($listarListasPrecios->ejecutar($solo_activas, $orden_sql) as $lista_precio_dominio) {
            $lista = $this->listaPrecioDominioAArray($lista_precio_dominio);
            $lista["es_lista_costo"] = $esListaCosto->ejecutar($lista);
            $resultado[] = $lista;
        }

        return $resultado;
    }

    private function es_lista_base(int $id): bool {
        global $container;

        $esListaBase = $container->get(\Ventas\ListasPrecios\Application\EsListaBase::class);
        $resultado = $esListaBase->ejecutar($id);

        return $resultado;
    }

    public function index(): void {
        if ($this->permiso()) {
            $orden_listas = orden_parametros([
                "nombre" => "nombre",
                "descripcion" => "nombre",
                "estado" => "activo",
                "fecha" => "creado_en"
            ], "nombre", "ASC");
            $listas = $this->listar_listas_precios(false, $orden_listas["sql"]);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/listas_precios/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function guardar(): void {
        if ($this->permiso()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                registrar_operacion("listas_precios.guardar.rechazado", [
                    "error" => "Acceso invalido o token invalido",
                    "post" => $_POST,
                ]);
                flash_error("Acceso invalido.");
                redirigir("index.php?c=listas_precios&a=index");
            }
            $id = (int)obtener_post("id", 0);
            $nombre = trim((string)obtener_post("nombre", ""));
            $activo = (int)obtener_post("activo", 1);
            if (texto_invalido($nombre)) {
                registrar_operacion("listas_precios.guardar.rechazado", [
                    "error" => "Nombre invalido",
                    "id" => $id,
                    "nombre" => $nombre,
                    "activo" => $activo,
                ]);
                flash_error("Nombre invalido.");
                redirigir("index.php?c=listas_precios&a=index");
            }
            if ($id > 0 && $this->es_lista_base($id)) {
                $nombre_base = strtolower(trim($nombre));
                if ($nombre_base !== "costo") {
                    registrar_operacion("listas_precios.guardar.rechazado", [
                        "error" => "Intento cambiar lista base Costo",
                        "id" => $id,
                        "nombre" => $nombre,
                    ]);
                    flash_error("No se puede cambiar el nombre base de Costo.");
                    redirigir("index.php?c=listas_precios&a=index");
                    return;
                }
                if (!in_array($nombre_base, ["costo", "publico", "público"], true)) {
                    flash_error("No se puede cambiar el nombre base de Costo.");
                    redirigir("index.php?c=listas_precios&a=index");
                }
                $activo = 1;
            }
            registrar_operacion("listas_precios.guardar.intento", [
                "accion" => $id > 0 ? "actualizar" : "crear",
                "id" => $id,
                "nombre" => $nombre,
                "activo" => $activo,
            ]);
            global $container;
            $ok = $id > 0
                ? $container->get(\Ventas\ListasPrecios\Application\ActualizarListaPrecio::class)->ejecutar($id, $nombre, $activo)
                : $container->get(\Ventas\ListasPrecios\Application\CrearListaPrecio::class)->ejecutar($nombre, $activo);
            $listas = $this->listar_listas_precios(false);
            registrar_operacion("listas_precios.guardar.resultado", [
                "ok" => $ok ? "SI" : "NO",
                "id" => $id,
                "nombre" => $nombre,
                "activo" => $activo,
                "total_listas" => count($listas),
                "listas" => array_map(fn($lista) => [
                    "id" => (string)($lista["id"] ?? ""),
                    "nombre" => (string)($lista["nombre"] ?? ""),
                    "activo" => (string)($lista["activo"] ?? ""),
                ], $listas),
            ]);
            $ok ? flash_ok("Lista guardada.") : flash_error("No se pudo guardar la lista.");
            redirigir("index.php?c=listas_precios&a=index");
        }
    }

    public function eliminar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            if ($this->es_lista_base($id)) {
                flash_error("No se puede eliminar la lista Costo.");
                redirigir("index.php?c=listas_precios&a=index");
                return;
            }
            global $container;
            $ok = $container->get(\Ventas\ListasPrecios\Application\EliminarListaPrecio::class)->ejecutar($id);
            $ok ? flash_ok("Lista eliminada.") : flash_error("No se pudo eliminar la lista.");
            redirigir("index.php?c=listas_precios&a=index");
        }
    }

    public function exportar(): void {
        if ($this->permiso()) {
            $id_lista = (int)obtener_get("id", 0);
            $formato = strtolower((string)obtener_get("formato", "html"));
            $listas = $this->listar_listas_precios(true);
            $nombre_lista = "";
            foreach ($listas as $lista) {
                if ((int)$lista["id"] === $id_lista)
                    $nombre_lista = (string)$lista["nombre"];
            }
            if ($id_lista <= 0 || $nombre_lista === "") {
                flash_error("Selecciona una lista de precios cargada.");
                redirigir("index.php?c=exportaciones&a=index");
                return;
            }
            global $container;
            $productos = $container->get(\Ventas\ListasPrecios\Application\ListarProductosParaExportar::class)->ejecutar($id_lista);
            $base_archivo = "lista_precios_" . $nombre_lista;
            if ($formato === "csv" || $formato === "xls" || $formato === "excel") {
                header("Content-Type: text/csv; charset=utf-8");
                header("Content-Disposition: attachment; filename=\"" . $this->nombre_archivo($base_archivo, "csv") . "\"");
                $out = fopen("php://output", "w");
                fprintf($out, "\xEF\xBB\xBF");
                fputcsv($out, ["Producto", "Codigo", "Unidad", "Precio"], ";");
                foreach ($productos as $p)
                    fputcsv($out, [$p["nombre"], $p["cod_barras"], $p["unidad"], numero_precio_para_exportar($p["precio_lista"] ?? 0, 2)], ";");
                fclose($out);
                return;
            }
            $html = $this->html_lista_precios($productos, $nombre_lista);
            if ($formato === "pdf") {
                $autoload = __DIR__ . "/../../vendor/autoload.php";
                if (file_exists($autoload)) {
                    require_once $autoload;
                    $dompdf = new \Dompdf\Dompdf();
                    $dompdf->loadHtml($html, "UTF-8");
                    $dompdf->setPaper("A4", "portrait");
                    $dompdf->render();
                    header("Content-Type: application/pdf");
                    header("Content-Disposition: attachment; filename=\"" . $this->nombre_archivo($base_archivo, "pdf") . "\"");
                    echo $dompdf->output();
                    return;
                }
            }
            echo $html;
        }
    }

    private function html_lista_precios(array $productos, string $nombre_lista): string {
        $filas = "";
        foreach ($productos as $p) {
            $filas .= "<tr><td>" . htmlspecialchars((string)$p["nombre"]) . "</td><td>" . htmlspecialchars((string)$p["cod_barras"]) . "</td><td>" . htmlspecialchars((string)($p["unidad"] ?? "")) . "</td><td class='num'>" . htmlspecialchars(precio_para_mostrar($p["precio_lista"] ?? 0)) . "</td></tr>";
        }
        return reporte_html_tabla($nombre_lista, "Lista de precios vigente", ["Producto", "Codigo", "Unidad", "Precio"], $filas, 4);
    }

    private function nombre_archivo(string $base, string $extension): string {
        return $this->slug_archivo($base) . "_" . date("Ymd_His") . "." . ltrim($extension, ".");
    }

    private function slug_archivo(string $texto): string {
        $texto = trim($texto);
        $texto = strtr($texto, [
            "á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u", "ñ" => "n",
            "Á" => "a", "É" => "e", "Í" => "i", "Ó" => "o", "Ú" => "u", "Ñ" => "n",
            "ü" => "u", "Ü" => "u"
        ]);
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', '_', $texto) ?? "";
        $texto = trim($texto, "_");
        return $texto !== "" ? $texto : "exportacion";
    }
}
