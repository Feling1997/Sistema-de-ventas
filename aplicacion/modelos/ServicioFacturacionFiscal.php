<?php
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/ConfiguracionSistema.php";

class ServicioFacturacionFiscal {
    private array $config;

    public function __construct(?array $config = null) {
        $this->config = $config ?? $this->cargar_configuracion();
    }

    public function esta_habilitado(): bool {
        return (bool)($this->config["habilitado"] ?? false);
    }

    public function emitir(array $payload): array {
        if (!$this->esta_habilitado()) {
            return [
                "ok" => false,
                "transitorio" => true,
                "error" => "Integracion fiscal no habilitada. Configurar configuraciones/arca.php.",
            ];
        }

        $proveedor = (string)($this->config["proveedor"] ?? "api_rest");
        if ($proveedor === "api_rest")
            return $this->emitir_api_rest($payload);

        return [
            "ok" => false,
            "transitorio" => false,
            "error" => "Proveedor fiscal no soportado: " . $proveedor,
        ];
    }

    private function emitir_api_rest(array $payload): array {
        $endpoint = trim((string)($this->config["api_rest"]["endpoint"] ?? ""));
        $token = trim((string)($this->config["api_rest"]["token"] ?? ""));
        if ($endpoint === "") {
            return [
                "ok" => false,
                "transitorio" => true,
                "error" => "Endpoint fiscal no configurado.",
            ];
        }
        if (!function_exists("curl_init")) {
            return [
                "ok" => false,
                "transitorio" => true,
                "error" => "Extension cURL no disponible en PHP.",
            ];
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($body)) {
            return [
                "ok" => false,
                "transitorio" => false,
                "error" => "No se pudo serializar el comprobante fiscal.",
            ];
        }

        $headers = ["Content-Type: application/json", "Accept: application/json"];
        if ($token !== "")
            $headers[] = "Authorization: Bearer " . $token;

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)($this->config["timeout_segundos"] ?? 20));
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return ["ok" => false, "transitorio" => true, "error" => $error !== "" ? $error : "Error de conexion fiscal."];
        }

        $respuesta = [];
        if (is_string($raw) && trim($raw) !== "") {
            $decodificada = json_decode($raw, true);
            if (is_array($decodificada))
                $respuesta = $decodificada;
        }

        if ($status >= 200 && $status < 300) {
            return [
                "ok" => true,
                "respuesta" => $respuesta,
                "cae" => (string)($respuesta["cae"] ?? ""),
                "cae_vencimiento" => (string)($respuesta["cae_vencimiento"] ?? ""),
                "numero_comprobante" => (int)($respuesta["numero_comprobante"] ?? 0),
            ];
        }

        $transitorio = ($status === 0 || $status === 408 || $status === 429 || $status >= 500);
        return [
            "ok" => false,
            "transitorio" => $transitorio,
            "error" => "API fiscal respondio HTTP " . $status,
            "respuesta" => $respuesta,
        ];
    }

    private function cargar_configuracion(): array {
        return ConfiguracionSistema::obtener_configuracion_fiscal();
    }
}
