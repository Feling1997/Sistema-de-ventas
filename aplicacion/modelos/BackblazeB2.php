<?php

require_once __DIR__ . "/ConfiguracionSistema.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class BackblazeB2 {
    public static function configurado(array $config = null): bool {
        $config = $config ?? ConfiguracionSistema::obtener();
        return (string)($config["backup_b2_habilitado"] ?? "0") === "1"
            && trim((string)($config["backup_b2_key_id"] ?? "")) !== ""
            && trim((string)($config["backup_b2_application_key"] ?? "")) !== ""
            && trim((string)($config["backup_b2_bucket_id"] ?? "")) !== "";
    }

    public static function probar(array $config = null): array {
        $config = $config ?? ConfiguracionSistema::obtener();
        if (!self::configurado($config))
            return ["ok" => false, "mensaje" => "Backblaze B2 no esta configurado completo."];

        $auth = self::autorizar($config);
        if (empty($auth["ok"]))
            return $auth;

        $upload = self::obtenerUrlSubida($auth, (string)$config["backup_b2_bucket_id"]);
        if (empty($upload["ok"]))
            return $upload;

        return ["ok" => true, "mensaje" => "Conexion con Backblaze B2 correcta."];
    }

    public static function subir(string $ruta, array $config = null): array {
        $config = $config ?? ConfiguracionSistema::obtener();
        if (!is_file($ruta))
            return ["ok" => false, "mensaje" => "No se encontro el archivo para subir."];
        if (!self::configurado($config))
            return ["ok" => false, "mensaje" => "Backblaze B2 no esta configurado."];

        $auth = self::autorizar($config);
        if (empty($auth["ok"]))
            return $auth;

        $upload = self::obtenerUrlSubida($auth, (string)$config["backup_b2_bucket_id"]);
        if (empty($upload["ok"]))
            return $upload;

        $carpeta = trim((string)($config["backup_b2_carpeta"] ?? "ventas-reparaciones"), "/\\ ");
        if ($carpeta === "")
            $carpeta = "ventas-reparaciones";
        $nombreRemoto = $carpeta . "/" . basename($ruta);
        $contenido = @file_get_contents($ruta);
        if (!is_string($contenido))
            return ["ok" => false, "mensaje" => "No se pudo leer el archivo de respaldo."];

        $headers = [
            "Authorization: " . $upload["authorizationToken"],
            "X-Bz-File-Name: " . self::codificarNombre($nombreRemoto),
            "Content-Type: application/gzip",
            "X-Bz-Content-Sha1: " . sha1($contenido),
            "X-Bz-Info-sistema: ventas-reparaciones",
        ];

        $resp = self::request("POST", (string)$upload["uploadUrl"], $headers, $contenido);
        if (!$resp["ok"])
            return ["ok" => false, "mensaje" => "Error subiendo a Backblaze: " . $resp["error"]];
        if ($resp["status"] < 200 || $resp["status"] >= 300)
            return ["ok" => false, "mensaje" => "Backblaze rechazo la subida (" . $resp["status"] . "): " . $resp["body"]];

        $json = json_decode($resp["body"], true);
        if (!is_array($json))
            return ["ok" => false, "mensaje" => "Backblaze respondio un formato inesperado."];

        return [
            "ok" => true,
            "mensaje" => "Respaldo subido a Backblaze B2.",
            "fileId" => (string)($json["fileId"] ?? ""),
            "fileName" => (string)($json["fileName"] ?? $nombreRemoto),
        ];
    }

    private static function autorizar(array $config): array {
        $keyId = trim((string)($config["backup_b2_key_id"] ?? ""));
        $appKey = trim((string)($config["backup_b2_application_key"] ?? ""));
        $headers = ["Authorization: Basic " . base64_encode($keyId . ":" . $appKey)];
        $resp = self::request("GET", "https://api.backblazeb2.com/b2api/v3/b2_authorize_account", $headers);
        if (!$resp["ok"])
            return ["ok" => false, "mensaje" => "No se pudo conectar con Backblaze: " . $resp["error"]];
        if ($resp["status"] < 200 || $resp["status"] >= 300)
            return ["ok" => false, "mensaje" => "Backblaze no autorizo la cuenta (" . $resp["status"] . "): " . $resp["body"]];

        $json = json_decode($resp["body"], true);
        if (!is_array($json))
            return ["ok" => false, "mensaje" => "Respuesta invalida de Backblaze."];

        $apiUrl = (string)($json["apiUrl"] ?? ($json["apiInfo"]["storageApi"]["apiUrl"] ?? ""));
        $token = (string)($json["authorizationToken"] ?? "");
        if ($apiUrl === "" || $token === "")
            return ["ok" => false, "mensaje" => "La autorizacion de Backblaze no devolvio apiUrl/token."];

        return ["ok" => true, "apiUrl" => $apiUrl, "authorizationToken" => $token];
    }

    private static function obtenerUrlSubida(array $auth, string $bucketId): array {
        $url = rtrim((string)$auth["apiUrl"], "/") . "/b2api/v3/b2_get_upload_url";
        $headers = [
            "Authorization: " . (string)$auth["authorizationToken"],
            "Content-Type: application/json",
        ];
        $resp = self::request("POST", $url, $headers, json_encode(["bucketId" => $bucketId], JSON_UNESCAPED_SLASHES));
        if (!$resp["ok"])
            return ["ok" => false, "mensaje" => "No se pudo pedir URL de subida: " . $resp["error"]];
        if ($resp["status"] < 200 || $resp["status"] >= 300)
            return ["ok" => false, "mensaje" => "Backblaze no habilito la subida (" . $resp["status"] . "): " . $resp["body"]];

        $json = json_decode($resp["body"], true);
        if (!is_array($json) || empty($json["uploadUrl"]) || empty($json["authorizationToken"]))
            return ["ok" => false, "mensaje" => "Backblaze devolvio una URL de subida invalida."];

        return [
            "ok" => true,
            "uploadUrl" => (string)$json["uploadUrl"],
            "authorizationToken" => (string)$json["authorizationToken"],
        ];
    }

    private static function request(string $metodo, string $url, array $headers = [], string $body = ""): array {
        if (!function_exists("curl_init"))
            return ["ok" => false, "status" => 0, "body" => "", "error" => "PHP no tiene curl habilitado."];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        if ($body !== "")
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $bodyResp = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return [
            "ok" => $bodyResp !== false,
            "status" => $status,
            "body" => is_string($bodyResp) ? $bodyResp : "",
            "error" => $error,
        ];
    }

    private static function codificarNombre(string $nombre): string {
        return str_replace("%2F", "/", rawurlencode(str_replace("\\", "/", $nombre)));
    }
}
