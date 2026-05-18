<?php
require_once __DIR__ . "/../modelos/ListaPrecio.php";
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

    public function index(): void {
        if ($this->permiso()) {
            $listas = ListaPrecio::listar(false);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/listas_precios/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function guardar(): void {
        if ($this->permiso()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=listas_precios&a=index");
            }
            $id = (int)obtener_post("id", 0);
            $nombre = trim((string)obtener_post("nombre", ""));
            $activo = (int)obtener_post("activo", 1);
            if (texto_invalido($nombre)) {
                flash_error("Nombre invalido.");
                redirigir("index.php?c=listas_precios&a=index");
            }
            if ($id > 0 && ListaPrecio::es_lista_base_id($id)) {
                $nombre_base = strtolower(trim($nombre));
                if (!in_array($nombre_base, ["costo", "publico", "público"], true)) {
                    flash_error("No se puede cambiar el nombre base de Costo o Publico.");
                    redirigir("index.php?c=listas_precios&a=index");
                }
                $activo = 1;
            }
            $ok = $id > 0 ? ListaPrecio::actualizar($id, $nombre, $activo) : ListaPrecio::crear($nombre, $activo);
            $ok ? flash_ok("Lista guardada.") : flash_error("No se pudo guardar la lista.");
            redirigir("index.php?c=listas_precios&a=index");
        }
    }

    public function eliminar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            if (ListaPrecio::es_lista_base_id($id)) {
                flash_error("No se pueden eliminar las listas Costo y Publico.");
                redirigir("index.php?c=listas_precios&a=index");
            }
            $ok = ListaPrecio::eliminar($id);
            $ok ? flash_ok("Lista eliminada.") : flash_error("No se pudo eliminar la lista.");
            redirigir("index.php?c=listas_precios&a=index");
        }
    }

    public function exportar(): void {
        if ($this->permiso()) {
            $id_lista = (int)obtener_get("id", 0);
            $formato = strtolower((string)obtener_get("formato", "html"));
            $listas = ListaPrecio::listar(true);
            $nombre_lista = "Precio publico";
            foreach ($listas as $lista) {
                if ((int)$lista["id"] === $id_lista)
                    $nombre_lista = (string)$lista["nombre"];
            }
            if ($id_lista <= 0)
                $nombre_lista = "Lista general";
            $productos = ListaPrecio::productos_para_exportar($id_lista);
            if ($formato === "csv") {
                header("Content-Type: text/csv; charset=utf-8");
                header("Content-Disposition: attachment; filename=lista_precios.csv");
                $out = fopen("php://output", "w");
                fputcsv($out, ["Producto", "Codigo", "Unidad", "Precio"]);
                foreach ($productos as $p)
                    fputcsv($out, [$p["nombre"], $p["cod_barras"], $p["unidad"], numero_precio_para_exportar($p["precio_lista"] ?? 0, 2)]);
                fclose($out);
                return;
            }
            $html = $this->html_lista_precios($productos, $nombre_lista);
            if ($formato === "xls" || $formato === "excel") {
                header("Content-Type: application/vnd.ms-excel; charset=utf-8");
                header("Content-Disposition: attachment; filename=lista_precios.xls");
                echo $html;
                return;
            }
            if ($formato === "pdf") {
                $autoload = __DIR__ . "/../../vendor/autoload.php";
                if (file_exists($autoload)) {
                    require_once $autoload;
                    $dompdf = new \Dompdf\Dompdf();
                    $dompdf->loadHtml($html, "UTF-8");
                    $dompdf->setPaper("A4", "portrait");
                    $dompdf->render();
                    header("Content-Type: application/pdf");
                    header("Content-Disposition: attachment; filename=lista_precios.pdf");
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
}
