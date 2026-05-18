$ErrorActionPreference = "Stop"

$base = Split-Path -Parent $MyInvocation.MyCommand.Path
$php = "C:\xampp82\php\php.exe"

if (-not (Test-Path $php)) {
    throw "No se encontro PHP en $php"
}

$temporal = Join-Path $env:TEMP ("generar_respaldo_" + [Guid]::NewGuid().ToString("N") + ".php")
$codigo = @"
<?php
require_once "$($base.Replace('\', '/'))/aplicacion/modelos/RespaldoSistema.php";
require_once "$($base.Replace('\', '/'))/aplicacion/modelos/BackblazeB2.php";
`$res = RespaldoSistema::generar();
if (empty(`$res["ok"])) {
    fwrite(STDERR, (string)(`$res["mensaje"] ?? "No se pudo generar el respaldo.") . PHP_EOL);
    exit(1);
}
echo (string)`$res["ruta"] . PHP_EOL;
if (BackblazeB2::configurado()) {
    `$subida = BackblazeB2::subir((string)`$res["ruta"]);
    if (empty(`$subida["ok"])) {
        fwrite(STDERR, "Backblaze B2: " . (string)(`$subida["mensaje"] ?? "No se pudo subir.") . PHP_EOL);
        exit(2);
    }
    echo "Backblaze B2: " . (string)(`$subida["fileName"] ?? "subido") . PHP_EOL;
}
"@

Push-Location $base
try {
    Set-Content -LiteralPath $temporal -Value $codigo -Encoding UTF8
    & $php $temporal
} finally {
    Remove-Item -LiteralPath $temporal -Force -ErrorAction SilentlyContinue
    Pop-Location
}
