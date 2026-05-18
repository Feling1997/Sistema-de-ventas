<?php
require_once __DIR__ . "/../modelos/CuentaCorriente.php";
require_once __DIR__ . "/../modelos/Cliente.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/../../configuraciones/csrf.php";

class ControladorCuentasCorrientes {
    private function permiso(): bool {
        if (!require_login()) {
            flash_error("Tenes que iniciar sesion.");
            redirigir("index.php?c=auth&a=login");
        }
        if (!require_rol(["ADMIN","VENDEDOR"])) {
            flash_error("No tenes permisos para cuenta corriente.");
            redirigir("index.php?c=ventas&a=inicio");
        }
        return true;
    }

    public function index(): void {
        if ($this->permiso()) {
            $cuentas = CuentaCorriente::listar_resumen(true);
            $alertas = CuentaCorriente::cuotas_alerta(7);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/cuentas_corrientes/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function pagar_cuota(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            CuentaCorriente::marcar_cuota_pagada($id)
                ? flash_ok("Cuota marcada como pagada.")
                : flash_error("No se pudo pagar la cuota.");
            redirigir("index.php?c=cuentas_corrientes&a=index");
        }
    }

    public function cancelar(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            CuentaCorriente::cancelar($id)
                ? flash_ok("Cuenta corriente cancelada.")
                : flash_error("No se pudo cancelar la cuenta.");
            redirigir("index.php?c=cuentas_corrientes&a=index");
        }
    }

    public function recibo(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            $cuenta = CuentaCorriente::buscar_cuenta($id);
            if ($cuenta === null || (float)$cuenta["saldo"] <= 0) {
                flash_error("Cuenta sin deuda pendiente.");
                redirigir("index.php?c=cuentas_corrientes&a=index");
            }
            $cuotas = CuentaCorriente::cuotas_pendientes($id);
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/cuentas_corrientes/recibo.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function generar_recibo(): void {
        if ($this->permiso()) {
            if ($_SERVER["REQUEST_METHOD"] !== "POST" || !csrf_valido(obtener_post("csrf", ""))) {
                flash_error("Acceso invalido.");
                redirigir("index.php?c=cuentas_corrientes&a=index");
            }
            $id_cuenta = (int)obtener_post("id_cuenta", 0);
            $monto = parsear_numero_form(obtener_post("monto", 0), 0);
            $forma_pago = trim((string)obtener_post("forma_pago", "contado"));
            $observacion = trim((string)obtener_post("observacion", ""));
            $cuotas = $_POST["cuotas"] ?? [];
            if (!is_array($cuotas))
                $cuotas = [];
            $id_recibo = CuentaCorriente::registrar_pago($id_cuenta, $monto, array_map("intval", $cuotas), $forma_pago, $observacion);
            if ($id_recibo > 0)
                redirigir("index.php?c=cuentas_corrientes&a=ver_recibo&id=" . $id_recibo);
            flash_error("No se pudo generar el recibo.");
            redirigir("index.php?c=cuentas_corrientes&a=recibo&id=" . $id_cuenta);
        }
    }

    public function ver_recibo(): void {
        if ($this->permiso()) {
            $id = (int)obtener_get("id", 0);
            $recibo = CuentaCorriente::buscar_recibo($id);
            if ($recibo === null) {
                flash_error("Recibo no encontrado.");
                redirigir("index.php?c=cuentas_corrientes&a=index");
            }
            echo $this->html_recibo($recibo);
        }
    }

    private function html_recibo(array $recibo): string {
        $id = (int)$recibo["id"];
        $cliente = htmlspecialchars((string)$recibo["cliente_nombre"]);
        $doc = htmlspecialchars((string)($recibo["cliente_documento"] ?? ""));
        $concepto = htmlspecialchars((string)$recibo["concepto"]);
        $fecha = htmlspecialchars((string)$recibo["fecha"]);
        $monto = htmlspecialchars(moneda_para_mostrar($recibo["monto"] ?? 0));
        $forma = htmlspecialchars((string)$recibo["forma_pago"]);
        $obs = htmlspecialchars((string)$recibo["observacion"]);
        return "<!doctype html><html lang='es'><head><meta charset='utf-8'><title>Recibo #$id</title><style>body{font-family:Arial,sans-serif;margin:24px;color:#111}.box{max-width:720px;border:1px solid #ddd;padding:22px}.row{display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:8px 0}.total{font-size:22px;font-weight:800}.actions{margin-bottom:14px;display:flex;gap:8px}button{border:0;border-radius:6px;padding:8px 12px;background:#0e7490;color:white;font-weight:700;cursor:pointer}@media print{.actions{display:none}}</style></head><body><div class='actions'><button type='button' onclick='window.print()'>Imprimir</button></div><div class='box'><h2>Recibo #$id</h2><div class='row'><span>Fecha</span><strong>$fecha</strong></div><div class='row'><span>Cliente</span><strong>$cliente</strong></div><div class='row'><span>Documento</span><strong>$doc</strong></div><div class='row'><span>Concepto</span><strong>$concepto</strong></div><div class='row'><span>Forma de pago</span><strong>$forma</strong></div><div class='row total'><span>Importe recibido</span><strong>$monto</strong></div>" . ($obs !== "" ? "<p><b>Observacion:</b> $obs</p>" : "") . "</div></body></html>";
    }
}
