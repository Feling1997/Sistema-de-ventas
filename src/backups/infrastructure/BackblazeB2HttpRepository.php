<?php

declare(strict_types=1);

namespace Ventas\Backups\Infrastructure;

use Ventas\Backups\Domain\Repositorios\BackblazeStorageRepository;

final class BackblazeB2HttpRepository implements BackblazeStorageRepository
{
    public function configurado(array $config): bool
    {
        $resultado = (string)($config["backup_b2_habilitado"] ?? "0") === "1"
            && trim((string)($config["backup_b2_key_id"] ?? "")) !== ""
            && trim((string)($config["backup_b2_application_key"] ?? "")) !== ""
            && trim((string)($config["backup_b2_bucket_id"] ?? "")) !== "";

        return $resultado;
    }

    public function probar(array $config): array
    {
        $resultado = ["ok" => false, "mensaje" => "Backblaze B2 no esta configurado completo."];

        if ($this->configurado($config)) {
            $auth = $this->autorizar($config);
            if (empty($auth["ok"])) {
                $resultado = $auth;
            } else {
                $upload = $this->obtenerUrlSubida($auth, (string)$config["backup_b2_bucket_id"]);
                if (empty($upload["ok"])) {
                    $resultado = $upload;
                } else {
                    $resultado = ["ok" => true, "mensaje" => "Conexion con Backblaze B2 correcta."];
                }
            }
        }

        return $resultado;
    }

    public function subir(string $ruta, array $config): array
    {
        $resultado = ["ok" => false, "mensaje" => "No se encontro el archivo para subir."];

        if (is_file($ruta)) {
            if (!$this->configurado($config)) {
                $resultado = ["ok" => false, "mensaje" => "Backblaze B2 no esta configurado."];
            } else {
                $auth = $this->autorizar($config);
                if (empty($auth["ok"])) {
                    $resultado = $auth;
                } else {
                    $upload = $this->obtenerUrlSubida($auth, (string)$config["backup_b2_bucket_id"]);
                    if (empty($upload["ok"])) {
                        $resultado = $upload;
                    } else {
                        $resultado = $this->subirArchivo($ruta, $config, $upload);
                    }
                }
            }
        }

        return $resultado;
    }

    private function subirArchivo(string $ruta, array $config, array $upload): array
    {
        $resultado = ["ok" => false, "mensaje" => "No se pudo leer el archivo de respaldo."];
        $carpeta = trim((string)($config["backup_b2_carpeta"] ?? "ventas-reparaciones"), "/\\ ");

        if ($carpeta === "") {
            $carpeta = "ventas-reparaciones";
        }

        $nombreRemoto = $carpeta . "/" . basename($ruta);
        $contenido = @file_get_contents($ruta);

        if (is_string($contenido)) {
            $headers = [
                "Authorization: " . $upload["authorizationToken"],
                "X-Bz-File-Name: " . $this->codificarNombre($nombreRemoto),
                "Content-Type: application/gzip",
                "X-Bz-Content-Sha1: " . sha1($contenido),
                "X-Bz-Info-sistema: ventas-reparaciones",
            ];

            $resp = $this->request("POST", (string)$upload["uploadUrl"], $headers, $contenido);
            if (!$resp["ok"]) {
                $resultado = ["ok" => false, "mensaje" => "Error subiendo a Backblaze: " . $resp["error"]];
            } elseif ($resp["status"] < 200 || $resp["status"] >= 300) {
                $resultado = ["ok" => false, "mensaje" => "Backblaze rechazo la subida (" . $resp["status"] . "): " . $resp["body"]];
            } else {
                $json = json_decode($resp["body"], true);
                if (!is_array($json)) {
                    $resultado = ["ok" => false, "mensaje" => "Backblaze respondio un formato inesperado."];
                } else {
                    $resultado = [
                        "ok" => true,
                        "mensaje" => "Respaldo subido a Backblaze B2.",
                        "fileId" => (string)($json["fileId"] ?? ""),
                        "fileName" => (string)($json["fileName"] ?? $nombreRemoto),
                    ];
                }
            }
        }

        return $resultado;
    }

    private function autorizar(array $config): array
    {
        $keyId = trim((string)($config["backup_b2_key_id"] ?? ""));
        $appKey = trim((string)($config["backup_b2_application_key"] ?? ""));
        $headers = ["Authorization: Basic " . base64_encode($keyId . ":" . $appKey)];
        $resp = $this->request("GET", "https://api.backblazeb2.com/b2api/v3/b2_authorize_account", $headers);
        $resultado = ["ok" => false, "mensaje" => "No se pudo conectar con Backblaze: " . $resp["error"]];

        if ($resp["ok"]) {
            if ($resp["status"] < 200 || $resp["status"] >= 300) {
                $resultado = ["ok" => false, "mensaje" => "Backblaze no autorizo la cuenta (" . $resp["status"] . "): " . $resp["body"]];
            } else {
                $json = json_decode($resp["body"], true);
                if (!is_array($json)) {
                    $resultado = ["ok" => false, "mensaje" => "Respuesta invalida de Backblaze."];
                } else {
                    $apiUrl = (string)($json["apiUrl"] ?? ($json["apiInfo"]["storageApi"]["apiUrl"] ?? ""));
                    $token = (string)($json["authorizationToken"] ?? "");
                    if ($apiUrl === "" || $token === "") {
                        $resultado = ["ok" => false, "mensaje" => "La autorizacion de Backblaze no devolvio apiUrl/token."];
                    } else {
                        $resultado = ["ok" => true, "apiUrl" => $apiUrl, "authorizationToken" => $token];
                    }
                }
            }
        }

        return $resultado;
    }

    private function obtenerUrlSubida(array $auth, string $bucketId): array
    {
        $url = rtrim((string)$auth["apiUrl"], "/") . "/b2api/v3/b2_get_upload_url";
        $headers = [
            "Authorization: " . (string)$auth["authorizationToken"],
            "Content-Type: application/json",
        ];
        $body = (string)json_encode(["bucketId" => $bucketId], JSON_UNESCAPED_SLASHES);
        $resp = $this->request("POST", $url, $headers, $body);
        $resultado = ["ok" => false, "mensaje" => "No se pudo pedir URL de subida: " . $resp["error"]];

        if ($resp["ok"]) {
            if ($resp["status"] < 200 || $resp["status"] >= 300) {
                $resultado = ["ok" => false, "mensaje" => "Backblaze no habilito la subida (" . $resp["status"] . "): " . $resp["body"]];
            } else {
                $json = json_decode($resp["body"], true);
                if (!is_array($json) || empty($json["uploadUrl"]) || empty($json["authorizationToken"])) {
                    $resultado = ["ok" => false, "mensaje" => "Backblaze devolvio una URL de subida invalida."];
                } else {
                    $resultado = [
                        "ok" => true,
                        "uploadUrl" => (string)$json["uploadUrl"],
                        "authorizationToken" => (string)$json["authorizationToken"],
                    ];
                }
            }
        }

        return $resultado;
    }

    private function request(string $metodo, string $url, array $headers = [], string $body = ""): array
    {
        $resultado = ["ok" => false, "status" => 0, "body" => "", "error" => "PHP no tiene curl habilitado."];

        if (function_exists("curl_init")) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $metodo);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 90);
            if ($body !== "") {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
            $bodyResp = curl_exec($ch);
            $error = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);

            $resultado = [
                "ok" => $bodyResp !== false,
                "status" => $status,
                "body" => is_string($bodyResp) ? $bodyResp : "",
                "error" => $error,
            ];
        }

        return $resultado;
    }

    private function codificarNombre(string $nombre): string
    {
        $resultado = str_replace("%2F", "/", rawurlencode(str_replace("\\", "/", $nombre)));

        return $resultado;
    }
}
