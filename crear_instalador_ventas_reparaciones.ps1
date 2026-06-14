$ErrorActionPreference = "Stop"

$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$xamppSource = "C:\xampp82"
$ventasSource = Join-Path $xamppSource "htdocs\VENTAS"
$buildDir = Join-Path $projectDir "build_instalador_ventas_reparaciones"
$stagingDir = Join-Path $buildDir "payload"
$installRootName = "VentasReparacionesApp"
$installRootPath = "C:\Users\Public\VentasReparacionesApp"
$payloadZip = Join-Path $buildDir "ventas_reparaciones_payload.zip"
$installerSource = Join-Path $buildDir "InstaladorVentasReparaciones.cs"
$launcherSource = Join-Path $buildDir "ControlVentasReparaciones.cs"
$schemaSource = Join-Path $buildDir "instalacion_schema.sql"
$schemaFallback = Join-Path $projectDir "instalacion_schema_base.sql"
$installerExe = Join-Path $projectDir "Instalador_Ventas_Reparaciones.exe"
$launcherExe = Join-Path $stagingDir "$installRootName\Ventas y Reparaciones.exe"
$csc = "C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe"

if (-not (Test-Path -LiteralPath $csc)) {
    $csc = "C:\Windows\Microsoft.NET\Framework\v4.0.30319\csc.exe"
}

if (-not (Test-Path -LiteralPath $csc)) {
    throw "No se encontro csc.exe para compilar el instalador."
}

if (-not (Test-Path -LiteralPath $xamppSource)) {
    throw "No se encontro C:\xampp82."
}

if (-not (Test-Path -LiteralPath $ventasSource)) {
    throw "No se encontro C:\xampp82\htdocs\VENTAS."
}

function Set-Utf8NoBom {
    param(
        [string]$LiteralPath,
        [string]$Value
    )
    $encoding = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($LiteralPath, $Value, $encoding)
}

function Stop-LocalServer {
    param([int[]]$Ports)

    foreach ($port in $Ports) {
        $lineas = netstat -ano | Select-String ":$port\s"
        foreach ($linea in $lineas) {
            $texto = $linea.ToString()
            if ($texto -notmatch "LISTENING") {
                continue
            }
            $partes = $texto -split "\s+"
            $pidTexto = $partes[-1]
            $procId = 0
            if ([int]::TryParse($pidTexto, [ref]$procId) -and $procId -gt 0) {
                try {
                    Stop-Process -Id $procId -Force -ErrorAction SilentlyContinue
                } catch {
                }
            }
        }
    }
}

function Invoke-RobocopyChecked {
    param(
        [Parameter(Mandatory = $true)][string]$Source,
        [Parameter(Mandatory = $true)][string]$Destination,
        [string[]]$ExtraArgs = @()
    )

    New-Item -ItemType Directory -Force -Path $Destination | Out-Null
    $args = @($Source, $Destination, "/E", "/R:1", "/W:1", "/NFL", "/NDL", "/NP") + $ExtraArgs
    & robocopy @args | Out-Null
    if ($LASTEXITCODE -gt 7) {
        throw "Robocopy fallo copiando $Source a $Destination. Codigo: $LASTEXITCODE"
    }
}

function Update-PortableRootPaths {
    param(
        [Parameter(Mandatory = $true)][string]$RootPath,
        [Parameter(Mandatory = $true)][string]$TargetRoot
    )

    $targetSlash = $TargetRoot.Replace("\", "/")
    $targetBackslash = $TargetRoot
    $patterns = @("*.conf", "*.ini", "*.cnf", "*.bat", "*.cmd")
    foreach ($pattern in $patterns) {
        Get-ChildItem -LiteralPath $RootPath -Recurse -File -Filter $pattern -ErrorAction SilentlyContinue | ForEach-Object {
            $contenido = Get-Content -LiteralPath $_.FullName -Raw -Encoding Default
            $nuevo = $contenido.Replace("C:/xampp82", $targetSlash).Replace("C:\xampp82", $targetBackslash)
            if ($nuevo -ne $contenido) {
                Set-Content -LiteralPath $_.FullName -Value $nuevo -Encoding Default
            }
        }
    }
}

function New-ShortcutScript {
    param(
        [Parameter(Mandatory = $true)][string]$Path
    )

    @'
param(
    [Parameter(Mandatory = $true)][string]$Launcher
)

$shell = New-Object -ComObject WScript.Shell
$iconPath = Join-Path (Split-Path -Parent $Launcher) "ventas.ico"
$desktop = [Environment]::GetFolderPath("Desktop")
$publicDesktop = [Environment]::GetFolderPath("CommonDesktopDirectory")
$oneDriveDesktop = ""
if ($env:OneDrive) {
    $possible = Join-Path $env:OneDrive "Desktop"
    if (Test-Path -LiteralPath $possible) {
        $oneDriveDesktop = $possible
    }
}
$startMenu = Join-Path ([Environment]::GetFolderPath("Programs")) "Ventas y Reparaciones"

New-Item -ItemType Directory -Force -Path $startMenu | Out-Null

function New-Shortcut {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Target
    )
    $shortcut = $shell.CreateShortcut($Path)
    $shortcut.TargetPath = $Target
    $shortcut.WorkingDirectory = Split-Path -Parent $Target
    $shortcut.WindowStyle = 1
    $shortcut.Description = "Sistema local Ventas y Reparaciones"
    if (Test-Path -LiteralPath $iconPath) {
        $shortcut.IconLocation = $iconPath
    }
    $shortcut.Save()
}

function Remove-OldShortcut {
    param([string]$Folder)
    if (-not $Folder) { return }
    foreach ($name in @("CONTROL VENTAS.lnk", "Ventas y Reparaciones - Chrome.lnk", "Ventas y Reparaciones.lnk")) {
        $path = Join-Path $Folder $name
        if (Test-Path -LiteralPath $path) {
            Remove-Item -LiteralPath $path -Force -ErrorAction SilentlyContinue
        }
    }
}

Remove-OldShortcut -Folder $desktop
Remove-OldShortcut -Folder $oneDriveDesktop
Remove-OldShortcut -Folder $publicDesktop

if ($desktop) {
    New-Shortcut -Path (Join-Path $desktop "Ventas y Reparaciones.lnk") -Target $Launcher
}
if ($oneDriveDesktop -and $oneDriveDesktop -ne $desktop) {
    New-Shortcut -Path (Join-Path $oneDriveDesktop "Ventas y Reparaciones.lnk") -Target $Launcher
}

if ($publicDesktop) {
    try {
        New-Shortcut -Path (Join-Path $publicDesktop "Ventas y Reparaciones.lnk") -Target $Launcher
    } catch {
    }
}

New-Shortcut -Path (Join-Path $startMenu "Ventas y Reparaciones.lnk") -Target $Launcher
'@ | Set-Content -LiteralPath $Path -Encoding ASCII
}

function Write-CleanInstallerConfig {
    param(
        [Parameter(Mandatory = $true)][string]$VentasPath
    )

    $almacenamiento = Join-Path $VentasPath "almacenamiento"
    $reparaciones = Join-Path $VentasPath "reparaciones_python"
    New-Item -ItemType Directory -Force -Path $almacenamiento | Out-Null
    New-Item -ItemType Directory -Force -Path $reparaciones | Out-Null

    @'
{
    "nombre_comercio": "",
    "razon_social": "",
    "cuit": "",
    "condicion_iva": "",
    "domicilio": "",
    "localidad": "",
    "provincia": "",
    "telefonos": "",
    "whatsapp": "",
    "email": "",
    "sitio_web": "",
    "ingresos_brutos": "",
    "inicio_actividades": "",
    "punto_venta": 1,
    "formato_impresion_ticket": "80",
    "texto_pie_ticket": "",
    "logo_ticket": "",
    "url_reparaciones": "index.php?c=reparaciones&a=index",
    "mostrar_reparaciones": "1",
    "atajo_reparaciones": "F9",
    "configuracion_separada": "1",
    "color_acento": "#1f6f8b",
    "color_fondo": "#f4f6f8",
    "color_fondo_secundario": "#f9fbfc",
    "color_tarjetas": "#ffffff",
    "color_texto": "#203040",
    "color_texto_suave": "#657789",
    "color_borde": "#dbe3ea",
    "color_panel_inicio": "#155e75",
    "color_panel_inicio_2": "#48aaa5",
    "imagen_panel": "",
    "navbar_marca_texto": "",
    "navbar_mostrar_marca": "1",
    "navbar_mostrar_config": "1",
    "navbar_mostrar_usuario": "1",
    "navbar_mostrar_rol": "1",
    "navbar_mostrar_cambio_modulo": "1",
    "navbar_mostrar_salir": "1",
    "navbar_fondo_modo": "imagen",
    "navbar_color_1": "#000000",
    "navbar_color_2": "#1f2937",
    "navbar_texto_color": "#ffffff",
    "navbar_boton_fondo": "#ffffff",
    "navbar_boton_borde": "#ffffff",
    "navbar_boton_opacidad": "10",
    "navbar_imagen": "",
    "navbar_modulos_orden": "ventas,nueva_venta,clientes,stock,productos,listas_precios,exportaciones,cuentas_corrientes,reparaciones",
    "navbar_modulos_visibles": "ventas,nueva_venta,clientes,stock,productos,listas_precios,exportaciones,cuentas_corrientes",
    "tema_paneles": "claro",
    "backup_b2_habilitado": "0",
    "backup_b2_key_id": "",
    "backup_b2_application_key": "",
    "backup_b2_bucket_id": "",
    "backup_b2_bucket_name": "",
    "backup_b2_carpeta": "ventas-reparaciones",
    "auth_modo": "sin_login"
}
'@ | ForEach-Object { Set-Utf8NoBom -LiteralPath (Join-Path $almacenamiento "configuracion_sistema.json") -Value $_ }

    @'
{
  "nombre": "",
  "telefono": "",
  "direccion": "",
  "documento": "",
  "email": "",
  "observaciones": ""
}
'@ | ForEach-Object { Set-Utf8NoBom -LiteralPath (Join-Path $reparaciones "comercio_config.json") -Value $_ }
}

function Write-CleanArcaConfig {
    param(
        [Parameter(Mandatory = $true)][string]$VentasPath
    )

    @'
<?php

return array (
  'habilitado' => false,
  'modo' => 'homologacion',
  'proveedor' => 'api_rest',
  'timeout_segundos' => 20,
  'api_rest' =>
  array (
    'endpoint' => '',
    'token' => '',
  ),
  'empresa' =>
  array (
    'cuit' => '',
    'punto_venta' => 1,
    'condicion_iva' => '',
    'razon_social' => '',
    'domicilio' => '',
    'ingresos_brutos' => '',
    'inicio_actividades' => '',
  ),
  'comprobante_defecto' =>
  array (
    'tipo' => 6,
    'concepto' => 1,
    'moneda' => 'PES',
    'cotizacion' => 1,
    'iva_porcentaje' => 21,
    'copia' => 'ORIGINAL',
    'remito' => '',
  ),
);
'@ | ForEach-Object { Set-Utf8NoBom -LiteralPath (Join-Path $VentasPath "configuraciones\arca.php") -Value $_ }
}

function Export-CleanDatabaseSchema {
    param(
        [Parameter(Mandatory = $true)][string]$OutputPath
    )

    $mysqldump = Join-Path $xamppSource "mysql\bin\mysqldump.exe"
    if (-not (Test-Path -LiteralPath $mysqldump)) {
        throw "No se encontro mysqldump.exe para generar el esquema limpio."
    }
    $previousErrorActionPreference = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    $schema = & $mysqldump --user=root --databases sistema_ventas --no-data --skip-comments --routines --events 2>$null
    $dumpExitCode = $LASTEXITCODE
    $ErrorActionPreference = $previousErrorActionPreference
    if ($dumpExitCode -ne 0 -or -not $schema) {
        if (-not (Test-Path -LiteralPath $schemaFallback)) {
            throw "No se pudo generar el esquema limpio de sistema_ventas y no existe instalacion_schema_base.sql."
        }
        Write-Host "MySQL local no disponible. Se usa instalacion_schema_base.sql validado."
        Copy-Item -LiteralPath $schemaFallback -Destination $OutputPath -Force
        return
    }
    Set-Utf8NoBom -LiteralPath $OutputPath -Value ($schema -join [Environment]::NewLine)
}

function Remove-InstallerBusinessDatabases {
    param(
        [Parameter(Mandatory = $true)][string]$MysqlDataPath
    )

    foreach ($db in @("sistema_ventas", "contabilidad_app", "test")) {
        $path = Join-Path $MysqlDataPath $db
        if (Test-Path -LiteralPath $path) {
            Remove-Item -LiteralPath $path -Recurse -Force
        }
    }
}

function Test-PortOpen {
    param(
        [string]$HostName,
        [int]$Port
    )
    try {
        $client = New-Object System.Net.Sockets.TcpClient
        $iar = $client.BeginConnect($HostName, $Port, $null, $null)
        $ok = $iar.AsyncWaitHandle.WaitOne(500)
        if ($ok) {
            $client.EndConnect($iar)
        }
        $client.Close()
        return $ok
    } catch {
        return $false
    }
}

function Wait-PortOpen {
    param(
        [string]$HostName,
        [int]$Port,
        [int]$TimeoutMs = 30000
    )
    $elapsed = 0
    while ($elapsed -lt $TimeoutMs) {
        if (Test-PortOpen -HostName $HostName -Port $Port) {
            return
        }
        Start-Sleep -Milliseconds 500
        $elapsed += 500
    }
    throw "No inicio MySQL temporal en el puerto $Port."
}

function Import-SchemaIntoStagedMysql {
    param(
        [Parameter(Mandatory = $true)][string]$PayloadRootPath,
        [Parameter(Mandatory = $true)][string]$SchemaPath
    )

    $mysqlRoot = Join-Path $PayloadRootPath "mysql"
    $dataPath = Join-Path $mysqlRoot "data"
    $cleanDataPath = Join-Path $mysqlRoot "backup"
    if (-not (Test-Path -LiteralPath $cleanDataPath)) {
        throw "No se encontro mysql\\backup para preparar una base limpia."
    }
    if (Test-Path -LiteralPath $dataPath) {
        Remove-Item -LiteralPath $dataPath -Recurse -Force
    }
    Copy-Item -LiteralPath $cleanDataPath -Destination $dataPath -Recurse -Force
    Remove-InstallerBusinessDatabases -MysqlDataPath $dataPath
    Get-ChildItem -LiteralPath $dataPath -Include "mysql.pid","*.err","ibtmp1" -File -ErrorAction SilentlyContinue | Remove-Item -Force -ErrorAction SilentlyContinue
}

if (Test-Path -LiteralPath $buildDir) {
    Remove-Item -LiteralPath $buildDir -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $buildDir | Out-Null
New-Item -ItemType Directory -Force -Path $stagingDir | Out-Null

Write-Host "Generando esquema limpio de base de datos..."
Export-CleanDatabaseSchema -OutputPath $schemaSource

Write-Host "Deteniendo Apache/MySQL locales para copiar datos de forma consistente..."
Stop-LocalServer -Ports @(80, 3306)
Start-Sleep -Seconds 2

Write-Host "Copiando runtime portable de XAMPP..."
$payloadRoot = Join-Path $stagingDir $installRootName
New-Item -ItemType Directory -Force -Path $payloadRoot | Out-Null
Invoke-RobocopyChecked -Source (Join-Path $xamppSource "apache") -Destination (Join-Path $payloadRoot "apache")
Invoke-RobocopyChecked -Source (Join-Path $xamppSource "php") -Destination (Join-Path $payloadRoot "php")
Invoke-RobocopyChecked -Source (Join-Path $xamppSource "mysql") -Destination (Join-Path $payloadRoot "mysql") -ExtraArgs @("/XF", "mysql.pid", "*.err")
Import-SchemaIntoStagedMysql -PayloadRootPath $payloadRoot -SchemaPath $schemaSource
Invoke-RobocopyChecked -Source (Join-Path $xamppSource "tmp") -Destination (Join-Path $payloadRoot "tmp")

Write-Host "Copiando aplicacion VENTAS..."
Invoke-RobocopyChecked -Source $ventasSource -Destination (Join-Path $payloadRoot "htdocs\VENTAS") -ExtraArgs @(
    "/XD",
    ".git",
    "build_*",
    "build_instalador_reparaciones",
    "build_instalador_ventas_reparaciones",
    "build_actualizacion_*",
    "__pycache__",
    "/XF",
    "Instalador_Reparaciones.exe",
    "Instalador_Ventas_Reparaciones.exe",
    "Migrar_Base_A_MariaDB_Servicio.exe",
    "Resguardar_Datos_Ventas.exe",
    "reparaciones_python_instalador.zip",
    "Actualizacion_*.exe",
    "Actualizacion_*.zip",
    "ActualizadorCodigo.cs",
    "backup_*.php",
    "ventas_reparaciones_actualizacion_*.log"
)
Write-CleanInstallerConfig -VentasPath (Join-Path $payloadRoot "htdocs\VENTAS")
Write-CleanArcaConfig -VentasPath (Join-Path $payloadRoot "htdocs\VENTAS")
Copy-Item -LiteralPath $schemaSource -Destination (Join-Path $payloadRoot "htdocs\VENTAS\instalacion_schema.sql") -Force

$rootFiles = @(
    "apache_start.bat",
    "apache_stop.bat",
    "mysql_start.bat",
    "mysql_stop.bat",
    "ventas.ico",
    "setup_xampp.bat"
)

foreach ($file in $rootFiles) {
    $sourceFile = Join-Path $xamppSource $file
    if (Test-Path -LiteralPath $sourceFile) {
        Copy-Item -LiteralPath $sourceFile -Destination (Join-Path $payloadRoot $file) -Force
    }
}

Update-PortableRootPaths -RootPath $payloadRoot -TargetRoot $installRootPath
New-ShortcutScript -Path (Join-Path $payloadRoot "crear_accesos.ps1")

Write-Host "Compilando launcher local..."
@'
using System;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Net;
using System.Net.Sockets;
using System.Threading;

class ControlVentasReparaciones
{
    static string launcherLog = "";

    static void Log(string mensaje)
    {
        string linea = "[" + DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + "] " + mensaje;
        try
        {
            Directory.CreateDirectory(Path.GetDirectoryName(launcherLog));
            File.AppendAllText(launcherLog, linea + Environment.NewLine);
        }
        catch
        {
        }
    }

    static void LogTail(string titulo, string path)
    {
        try
        {
            if (!File.Exists(path))
            {
                Log(titulo + ": archivo inexistente: " + path);
                return;
            }
            string[] lineas = File.ReadAllLines(path);
            Log(titulo + ": ultimas lineas de " + path);
            foreach (string linea in lineas.Skip(Math.Max(0, lineas.Length - 80)))
            {
                Log(titulo + "> " + linea);
            }
        }
        catch (Exception ex)
        {
            Log(titulo + ": no se pudo leer el log: " + ex.Message);
        }
    }

    static int Main()
    {
        string root = DetectRoot();
        string url = "http://127.0.0.1/VENTAS/publico/index.php?c=ventas&a=inicio";
        Process mysql = null;
        Process apache = null;
        bool externalDatabase = UseExternalDatabase(root);
        launcherLog = Path.Combine(root, "logs", "launcher.log");
        try
        {
            Log("==== Inicio launcher ====");
            Log("Root: " + root);
            Log("Base de datos externa: " + (externalDatabase ? "SI" : "NO"));
            Log("Deteniendo procesos locales anteriores.");
            StopPort(80);
            if (!externalDatabase)
            {
                StopPort(3306);
            }
            StopLocalProcessesFromRoot(root);
            StopPort(8765);
            WaitForPortClosed("127.0.0.1", 80, 10000);
            if (!externalDatabase)
            {
                WaitForPortClosed("127.0.0.1", 3306, 10000);
            }
            WaitForPortClosed("127.0.0.1", 8765, 10000);
            if (externalDatabase)
            {
                Log("Usando MariaDB servicio.");
                EnsureExternalDatabaseAvailable();
            }
            else
            {
                Log("Iniciando MySQL.");
                mysql = StartMySql(root);
            }
            Log("Iniciando Apache.");
            apache = StartApache(root);
            Log("Esperando Apache en 127.0.0.1:80.");
            WaitForPort("127.0.0.1", 80, 15000);
            EnsureApplicationReady(root, url);
            Log("Abriendo navegador: " + url);
            OpenBrowserAppAndWait(root, url);
            Log("Navegador cerrado.");
            return 0;
        }
        catch (Exception ex)
        {
            try
            {
                File.WriteAllText(Path.Combine(root, "control_ventas_error.log"), ex.ToString());
            }
            catch
            {
            }
            Log("ERROR FATAL: " + ex.ToString());
            LogTail("MYSQL", Path.Combine(root, "mysql", "data", "mysql_error.log"));
            LogTail("APACHE", Path.Combine(root, "apache", "logs", "error.log"));
            System.Windows.Forms.MessageBox.Show("No se pudo iniciar Ventas y Reparaciones. Revise control_ventas_error.log.", "Ventas y Reparaciones", System.Windows.Forms.MessageBoxButtons.OK, System.Windows.Forms.MessageBoxIcon.Error);
            return 1;
        }
        finally
        {
            Log("Deteniendo servicios locales.");
            StopProcess(apache);
            if (!externalDatabase)
            {
                StopMySqlGracefully(root, mysql);
            }
            StopPort(8765);
            StopLocalProcessesFromRoot(root);
            Log("==== Fin launcher ====");
        }
    }

    static string DetectRoot()
    {
        string dir = AppDomain.CurrentDomain.BaseDirectory.TrimEnd('\\');
        if (File.Exists(Path.Combine(dir, "apache", "bin", "httpd.exe")))
        {
            return dir;
        }
        return @"C:\Users\Public\VentasReparacionesApp";
    }

    static bool UseExternalDatabase(string root)
    {
        return File.Exists(Path.Combine(root, "usar_mariadb_servicio.txt"));
    }

    static void EnsureExternalDatabaseAvailable()
    {
        if (IsPortOpen("127.0.0.1", 3306))
        {
            return;
        }

        string[] services = { "MariaDB", "mariadb", "MySQL", "mysql", "MySQL80", "MySQL57" };
        foreach (string service in services)
        {
            RunHiddenNoThrow("sc.exe", "start " + service, Environment.CurrentDirectory, 15000);
            if (WaitForPortNoThrow("127.0.0.1", 3306, 8000))
            {
                Log("MariaDB servicio iniciado: " + service);
                return;
            }
        }
        throw new Exception("No esta iniciado MariaDB servicio en 127.0.0.1:3306.");
    }

    static bool IsPortOpen(string host, int port)
    {
        try
        {
            using (TcpClient client = new TcpClient())
            {
                IAsyncResult result = client.BeginConnect(host, port, null, null);
                bool ok = result.AsyncWaitHandle.WaitOne(500);
                if (!ok)
                {
                    return false;
                }
                client.EndConnect(result);
                return true;
            }
        }
        catch
        {
            return false;
        }
    }

    static void WaitForPort(string host, int port, int timeoutMs)
    {
        if (WaitForPortNoThrow(host, port, timeoutMs))
        {
            return;
        }
        throw new Exception("No inicio el servidor local en el puerto " + port.ToString() + ".");
    }

    static bool WaitForPortNoThrow(string host, int port, int timeoutMs)
    {
        int waited = 0;
        while (waited < timeoutMs)
        {
            if (IsPortOpen(host, port))
            {
                return true;
            }
            Thread.Sleep(500);
            waited += 500;
        }
        return false;
    }

    static void WaitForPortClosed(string host, int port, int timeoutMs)
    {
        int waited = 0;
        while (waited < timeoutMs)
        {
            if (!IsPortOpen(host, port))
            {
                return;
            }
            Thread.Sleep(250);
            waited += 250;
        }
        throw new Exception("No se pudo liberar el puerto local " + port.ToString() + ".");
    }

    static Process StartMySql(string root)
    {
        if (IsPortOpen("127.0.0.1", 3306))
        {
            return null;
        }

        RepairMySqlRuntime(root, false);
        try
        {
            return StartMySqlOnce(root);
        }
        catch (Exception ex)
        {
            Log("MySQL fallo al iniciar. Se intenta reparacion profunda: " + ex.Message);
            StopPort(3306);
            Thread.Sleep(1500);
            RepairMySqlRuntime(root, true);
            return StartMySqlOnce(root);
        }
    }

    static Process StartMySqlOnce(string root)
    {
        string exe = Path.Combine(root, "mysql", "bin", "mysqld.exe");
        string ini = Path.Combine(root, "mysql", "bin", "my.ini");
        if (!File.Exists(exe))
        {
            throw new Exception("No se encontro mysqld.exe.");
        }

        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = exe;
        info.Arguments = "--defaults-file=\"" + ini + "\" --standalone";
        info.WorkingDirectory = root;
        info.UseShellExecute = false;
        info.CreateNoWindow = true;
        info.WindowStyle = ProcessWindowStyle.Hidden;
        Process proceso = Process.Start(info);
        WaitForPort("127.0.0.1", 3306, 20000);
        Thread.Sleep(1500);
        if (proceso == null || proceso.HasExited || !IsPortOpen("127.0.0.1", 3306))
        {
            throw new Exception("MySQL inicio pero no se mantuvo activo.");
        }
        return proceso;
    }

    static void RepairMySqlRuntime(string root, bool restoreSystemTables)
    {
        string data = Path.Combine(root, "mysql", "data");
        if (!Directory.Exists(data))
        {
            return;
        }
        DeleteFileNoThrow(Path.Combine(data, "mysql.pid"));
        DeleteFileNoThrow(Path.Combine(data, "ibtmp1"));
        foreach (string file in Directory.GetFiles(data, "aria_log*", SearchOption.TopDirectoryOnly))
        {
            DeleteFileNoThrow(file);
        }

        string aria = Path.Combine(root, "mysql", "bin", "aria_chk.exe");
        if (File.Exists(aria))
        {
            foreach (string table in Directory.GetFiles(data, "*.MAI", SearchOption.AllDirectories))
            {
                RunHiddenNoThrow(aria, "-r \"" + table + "\"", root, 20000);
            }
        }

        if (!restoreSystemTables)
        {
            return;
        }
        string mysqlData = Path.Combine(data, "mysql");
        string mysqlBackup = Path.Combine(root, "mysql", "backup", "mysql");
        if (!Directory.Exists(mysqlBackup))
        {
            return;
        }
        try
        {
            if (Directory.Exists(mysqlData))
            {
                string roto = Path.Combine(data, "mysql_roto_" + DateTime.Now.ToString("yyyyMMdd_HHmmss"));
                Directory.Move(mysqlData, roto);
            }
            CopyDirectory(mysqlBackup, mysqlData);
            Log("MySQL: tablas internas restauradas desde mysql\\backup.");
        }
        catch (Exception ex)
        {
            Log("MySQL: no se pudieron restaurar tablas internas: " + ex.Message);
        }
    }

    static void DeleteFileNoThrow(string path)
    {
        try
        {
            if (File.Exists(path))
            {
                File.SetAttributes(path, FileAttributes.Normal);
                File.Delete(path);
            }
        }
        catch
        {
        }
    }

    static void RunHiddenNoThrow(string exe, string args, string workingDirectory, int timeoutMs)
    {
        try
        {
            ProcessStartInfo info = new ProcessStartInfo();
            info.FileName = exe;
            info.Arguments = args;
            info.WorkingDirectory = workingDirectory;
            info.UseShellExecute = false;
            info.CreateNoWindow = true;
            using (Process proceso = Process.Start(info))
            {
                proceso.WaitForExit(timeoutMs);
            }
        }
        catch
        {
        }
    }

    static void CopyDirectory(string source, string destination)
    {
        Directory.CreateDirectory(destination);
        foreach (string dir in Directory.GetDirectories(source, "*", SearchOption.AllDirectories))
        {
            Directory.CreateDirectory(dir.Replace(source, destination));
        }
        foreach (string file in Directory.GetFiles(source, "*", SearchOption.AllDirectories))
        {
            string target = file.Replace(source, destination);
            Directory.CreateDirectory(Path.GetDirectoryName(target));
            File.Copy(file, target, true);
        }
    }

    static Process StartApache(string root)
    {
        if (IsPortOpen("127.0.0.1", 80))
        {
            return null;
        }

        string exe = Path.Combine(root, "apache", "bin", "httpd.exe");
        if (!File.Exists(exe))
        {
            throw new Exception("No se encontro httpd.exe.");
        }

        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = exe;
        info.Arguments = "-d \"" + Path.Combine(root, "apache").Replace("\\", "/") + "\"";
        info.WorkingDirectory = root;
        info.UseShellExecute = false;
        info.CreateNoWindow = true;
        info.WindowStyle = ProcessWindowStyle.Hidden;
        Process proceso = Process.Start(info);
        WaitForPort("127.0.0.1", 80, 20000);
        return proceso;
    }

    static void StartReparaciones(string root)
    {
        string carpeta = Path.Combine(root, "htdocs", "VENTAS", "reparaciones_python");
        string launcher = Path.Combine(carpeta, "CONTROL REPARACIONES.exe");
        if (!File.Exists(launcher))
        {
            return;
        }

        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = launcher;
        info.WorkingDirectory = carpeta;
        info.UseShellExecute = false;
        info.CreateNoWindow = true;
        info.WindowStyle = ProcessWindowStyle.Hidden;
        Process.Start(info);
    }

    static void OpenBrowserAppAndWait(string root, string url)
    {
        string chrome = FindChrome();
        if (chrome != "")
        {
            OpenChromeAndWait(root, chrome, url);
            return;
        }

        System.Windows.Forms.DialogResult respuesta = System.Windows.Forms.MessageBox.Show("No se encontro Google Chrome instalado. Queres abrir el sistema con otro navegador?", "Ventas y Reparaciones", System.Windows.Forms.MessageBoxButtons.YesNo, System.Windows.Forms.MessageBoxIcon.Question);
        if (respuesta != System.Windows.Forms.DialogResult.Yes)
        {
            return;
        }

        string browser = FindFallbackBrowser();
        ProcessStartInfo info = new ProcessStartInfo();
        if (browser != "")
        {
            info.FileName = browser;
            info.Arguments = "--new-window \"" + url + "\"";
            info.UseShellExecute = false;
        }
        else
        {
            info.FileName = url;
            info.UseShellExecute = true;
        }
        Process.Start(info);
        System.Windows.Forms.MessageBox.Show("Cuando termine de usar el sistema, presione Aceptar para cerrar los servidores locales.", "Ventas y Reparaciones", System.Windows.Forms.MessageBoxButtons.OK, System.Windows.Forms.MessageBoxIcon.Information);
    }

    static void OpenChromeAndWait(string root, string chrome, string url)
    {
        string profile = Path.Combine(root, "chrome_profile");
        Directory.CreateDirectory(profile);
        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = chrome;
        info.Arguments = "--new-window \"" + url + "\" --user-data-dir=\"" + profile + "\" --no-first-run --disable-background-mode";
        info.UseShellExecute = false;
        Process proceso = Process.Start(info);
        if (proceso != null)
        {
            while (!proceso.WaitForExit(2000))
            {
                EnsureLocalServers(root);
            }
        }
    }

    static void EnsureLocalServers(string root)
    {
        if (!IsPortOpen("127.0.0.1", 3306))
        {
            Log("SUPERVISOR: MySQL no responde. Se reinicia.");
            StartMySql(root);
        }
        else if (!IsMySqlHealthy(root))
        {
            Log("SUPERVISOR: MySQL responde por puerto pero falla la consulta. Se repara y reinicia.");
            StopPort(3306);
            Thread.Sleep(1500);
            RepairMySqlRuntime(root, true);
            StartMySql(root);
        }
        if (!IsPortOpen("127.0.0.1", 80))
        {
            Log("SUPERVISOR: Apache no responde. Se reinicia.");
            StartApache(root);
        }
        else if (!IsHttpReady("http://127.0.0.1/VENTAS/publico/index.php?c=ventas&a=inicio", 2500))
        {
            Log("SUPERVISOR: Apache tiene puerto abierto pero la pagina no responde. Se reinicia.");
            StopPort(80);
            Thread.Sleep(1000);
            StartApache(root);
        }
    }

    static void EnsureApplicationReady(string root, string url)
    {
        for (int intento = 1; intento <= 3; intento++)
        {
            if (IsHttpReady(url, 5000))
            {
                Log("HTTP listo: " + url);
                return;
            }
            Log("HTTP no listo. Intento " + intento.ToString() + "/3. Reiniciando Apache.");
            StopPort(80);
            Thread.Sleep(1000);
            StartApache(root);
            Thread.Sleep(1500);
        }
        Log("HTTP no respondio despues de los reintentos. Se deja Apache/MySQL activos y se abre el navegador para mostrar el error real: " + url);
    }

    static bool IsHttpReady(string url, int timeoutMs)
    {
        try
        {
            HttpWebRequest request = (HttpWebRequest)WebRequest.Create(url);
            request.Method = "GET";
            request.Timeout = timeoutMs;
            request.ReadWriteTimeout = timeoutMs;
            request.AllowAutoRedirect = true;
            using (HttpWebResponse response = (HttpWebResponse)request.GetResponse())
            {
                int code = (int)response.StatusCode;
                return code >= 200 && code < 500;
            }
        }
        catch (WebException ex)
        {
            string detalle = ex.Message;
            try
            {
                HttpWebResponse response = ex.Response as HttpWebResponse;
                if (response != null)
                {
                    detalle = "HTTP " + ((int)response.StatusCode).ToString() + " " + response.StatusDescription;
                    response.Close();
                }
            }
            catch
            {
            }
            Log("HTTP no listo: " + detalle);
            return false;
        }
        catch (Exception ex)
        {
            Log("HTTP no listo: " + ex.Message);
            return false;
        }
    }

    static bool IsMySqlHealthy(string root)
    {
        string mysql = Path.Combine(root, "mysql", "bin", "mysql.exe");
        if (!File.Exists(mysql))
        {
            return false;
        }
        try
        {
            ProcessStartInfo info = new ProcessStartInfo();
            info.FileName = mysql;
            info.Arguments = "--user=root --batch --skip-column-names -e \"SELECT 1; CREATE DATABASE IF NOT EXISTS sistema_ventas; USE sistema_ventas; SELECT COUNT(*) FROM productos;\"";
            info.WorkingDirectory = root;
            info.UseShellExecute = false;
            info.CreateNoWindow = true;
            info.RedirectStandardError = true;
            info.RedirectStandardOutput = true;
            using (Process proceso = Process.Start(info))
            {
                if (!proceso.WaitForExit(5000))
                {
                    try { proceso.Kill(); } catch { }
                    return false;
                }
                string error = proceso.StandardError.ReadToEnd();
                if (proceso.ExitCode != 0)
                {
                    Log("SUPERVISOR MYSQL ERROR: " + error);
                    return false;
                }
            }
            return true;
        }
        catch (Exception ex)
        {
            Log("SUPERVISOR MYSQL CHECK ERROR: " + ex.Message);
            return false;
        }
    }

    static string FindChrome()
    {
        string[] candidates = new[]
        {
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles), "Google", "Chrome", "Application", "chrome.exe"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFilesX86), "Google", "Chrome", "Application", "chrome.exe")
        };
        foreach (string path in candidates)
        {
            if (File.Exists(path))
            {
                return path;
            }
        }
        return "";
    }

    static string FindFallbackBrowser()
    {
        string[] candidates = new[]
        {
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles), "Microsoft", "Edge", "Application", "msedge.exe"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFilesX86), "Microsoft", "Edge", "Application", "msedge.exe"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles), "Mozilla Firefox", "firefox.exe"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFilesX86), "Mozilla Firefox", "firefox.exe")
        };
        foreach (string path in candidates)
        {
            if (File.Exists(path))
            {
                return path;
            }
        }
        return "";
    }

    static void StopProcess(Process proceso)
    {
        if (proceso == null)
        {
            return;
        }
        try
        {
            if (!proceso.HasExited)
            {
                proceso.Kill();
                proceso.WaitForExit(5000);
            }
        }
        catch
        {
        }
    }

    static void StopMySqlGracefully(string root, Process mysqlProcess)
    {
        string mysqladmin = Path.Combine(root, "mysql", "bin", "mysqladmin.exe");
        if (File.Exists(mysqladmin) && IsPortOpen("127.0.0.1", 3306))
        {
            try
            {
                ProcessStartInfo info = new ProcessStartInfo();
                info.FileName = mysqladmin;
                info.Arguments = "--user=root shutdown";
                info.WorkingDirectory = root;
                info.UseShellExecute = false;
                info.CreateNoWindow = true;
                using (Process proceso = Process.Start(info))
                {
                    proceso.WaitForExit(10000);
                }
            }
            catch
            {
            }
        }

        if (mysqlProcess != null)
        {
            try
            {
                mysqlProcess.WaitForExit(10000);
            }
            catch
            {
            }
        }

        if (IsPortOpen("127.0.0.1", 3306))
        {
            StopProcess(mysqlProcess);
        }
    }

    static void StopPort(int port)
    {
        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = "netstat.exe";
        info.Arguments = "-ano";
        info.UseShellExecute = false;
        info.CreateNoWindow = true;
        info.RedirectStandardOutput = true;

        using (Process proceso = Process.Start(info))
        {
            string salida = proceso.StandardOutput.ReadToEnd();
            proceso.WaitForExit();
            string marca = ":" + port.ToString();
            foreach (string linea in salida.Split(new[] { "\r\n", "\n" }, StringSplitOptions.RemoveEmptyEntries))
            {
                if (!linea.Contains(marca) || !linea.Contains("LISTENING"))
                {
                    continue;
                }
                string[] partes = linea.Split((char[])null, StringSplitOptions.RemoveEmptyEntries);
                if (partes.Length < 5)
                {
                    continue;
                }
                int pid;
                if (int.TryParse(partes[4], out pid))
                {
                    try
                    {
                        Process.GetProcessById(pid).Kill();
                    }
                    catch
                    {
                    }
                }
            }
        }
    }

    static void StopLocalProcessesFromRoot(string root)
    {
        string reparaciones = Path.Combine(root, "htdocs", "VENTAS", "reparaciones_python");
        string[] names = new[] { "httpd", "mysqld", "python", "pythonw" };
        foreach (Process proceso in Process.GetProcesses())
        {
            try
            {
                if (!names.Any(n => proceso.ProcessName.Equals(n, StringComparison.OrdinalIgnoreCase)))
                {
                    continue;
                }
                string ruta = "";
                try
                {
                    ruta = proceso.MainModule != null ? proceso.MainModule.FileName : "";
                }
                catch
                {
                }
                if (ruta.StartsWith(root, StringComparison.OrdinalIgnoreCase) || ruta.StartsWith(reparaciones, StringComparison.OrdinalIgnoreCase))
                {
                    proceso.Kill();
                    proceso.WaitForExit(5000);
                }
            }
            catch
            {
            }
        }
    }
}
'@ | Set-Content -LiteralPath $launcherSource -Encoding ASCII

& $csc /nologo /target:winexe /out:$launcherExe /reference:System.Windows.Forms.dll $launcherSource
if (-not (Test-Path -LiteralPath $launcherExe)) {
    throw "No se pudo crear Ventas y Reparaciones.exe."
}

Write-Host "Comprimiendo payload del instalador..."
if (Test-Path -LiteralPath $payloadZip) {
    Remove-Item -LiteralPath $payloadZip -Force
}
Compress-Archive -Path $payloadRoot -DestinationPath $payloadZip -Force -CompressionLevel Optimal

Write-Host "Compilando instalador unico..."
@'
using System;
using System.Diagnostics;
using System.IO;
using System.IO.Compression;
using System.Linq;
using System.Reflection;

class InstaladorVentasReparaciones
{
    static readonly string PublicInstallerLog = @"C:\Users\Public\ventas_reparaciones_instalador.log";
    static string InstalledInstallerLog = "";

    static void Log(string mensaje)
    {
        string linea = "[" + DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + "] " + mensaje;
        Console.WriteLine(linea);
        try
        {
            File.AppendAllText(PublicInstallerLog, linea + Environment.NewLine);
        }
        catch
        {
        }
        try
        {
            if (!String.IsNullOrEmpty(InstalledInstallerLog))
            {
                Directory.CreateDirectory(Path.GetDirectoryName(InstalledInstallerLog));
                File.AppendAllText(InstalledInstallerLog, linea + Environment.NewLine);
            }
        }
        catch
        {
        }
    }

    static void LogTail(string titulo, string path)
    {
        try
        {
            if (!File.Exists(path))
            {
                Log(titulo + ": archivo inexistente: " + path);
                return;
            }
            string[] lineas = File.ReadAllLines(path);
            Log(titulo + ": ultimas lineas de " + path);
            foreach (string linea in lineas.Skip(Math.Max(0, lineas.Length - 80)))
            {
                Log(titulo + "> " + linea);
            }
        }
        catch (Exception ex)
        {
            Log(titulo + ": no se pudo leer el log: " + ex.Message);
        }
    }

    static int Main()
    {
        string destino = @"C:\Users\Public\VentasReparacionesApp";
        string backup = Path.Combine(Path.GetTempPath(), "ventas_reparaciones_backup");
        string payload = Path.Combine(Path.GetTempPath(), "ventas_reparaciones_payload.zip");

        try
        {
            Console.Title = "Instalador Ventas y Reparaciones";
            Log("==== Inicio instalador version 2026-06-03.1 ====");
            Log("Instalando Ventas y Reparaciones...");
            Log("Destino: " + destino);

            Log("ETAPA: detener servidores anteriores.");
            StopLocalServers(destino);
            Log("ETAPA: limpieza total de instalaciones anteriores.");
            CleanPreviousInstallations(destino, backup, payload);
            Log("ETAPA: extraer payload incorporado.");
            ExtractPayload(payload);

            Directory.CreateDirectory(Path.GetDirectoryName(destino));
            ZipFile.ExtractToDirectory(payload, Path.GetDirectoryName(destino));
            InstalledInstallerLog = Path.Combine(destino, "logs", "instalador.log");
            Log("ETAPA: payload extraido.");
            Log("ETAPA: asignar permisos de escritura.");
            GrantWritePermissions(destino);
            Log("ETAPA: inicializar y validar base de datos limpia.");
            InitializeBlankDatabaseIfNeeded(destino, backup);
            Log("ETAPA: crear accesos directos.");
            CreateShortcuts(destino);

            Log("Instalacion terminada.");
            Log("Use el acceso directo Ventas y Reparaciones del Escritorio.");
            Log("No hace falta instalar XAMPP, PHP, MySQL ni Python.");
            return 0;
        }
        catch (Exception ex)
        {
            Log("ERROR FATAL: " + ex.ToString());
            LogTail("MYSQL", Path.Combine(destino, "mysql", "data", "mysql_error.log"));
            LogTail("APACHE", Path.Combine(destino, "apache", "logs", "error.log"));
            Console.WriteLine("");
            Console.WriteLine("No se pudo instalar.");
            Console.WriteLine("Log persistente: " + PublicInstallerLog);
            Console.WriteLine("Pruebe ejecutar este instalador como administrador.");
            Console.WriteLine("Presione una tecla para cerrar...");
            Console.ReadKey();
            return 1;
        }
    }

    static void CleanPreviousInstallations(string destino, string backup, string payload)
    {
        DeleteDirectoryIfExists(backup);
        DeleteFileIfExists(payload);
        DeleteDirectoryIfExists(destino);
        DeleteDirectoryIfExists(@"C:\VentasReparacionesApp");
        DeleteDirectoryIfExists(@"C:\Ventas y Reparaciones");
        DeleteDirectoryIfExists(@"C:\xampp82");
        DeleteDirectoryIfExists(Path.Combine(Path.GetTempPath(), "ventas_reparaciones_backup"));
        DeleteFileIfExists(Path.Combine(Path.GetTempPath(), "ventas_reparaciones_payload.zip"));
        DeleteDirectoryIfExists(@"C:\Windows\Temp\ventas_reparaciones_backup");
        DeleteFileIfExists(@"C:\Windows\Temp\ventas_reparaciones_payload.zip");
        foreach (string dir in Directory.GetDirectories(Path.GetTempPath(), "ventas_reparaciones_*", SearchOption.TopDirectoryOnly))
        {
            DeleteDirectoryIfExists(dir);
        }
        if (Directory.Exists(@"C:\Windows\Temp"))
        {
            foreach (string dir in Directory.GetDirectories(@"C:\Windows\Temp", "ventas_reparaciones_*", SearchOption.TopDirectoryOnly))
            {
                DeleteDirectoryIfExists(dir);
            }
        }
        CleanPublicGeneratedBackups();
    }

    static void CleanPublicGeneratedBackups()
    {
        string publico = Environment.GetFolderPath(Environment.SpecialFolder.CommonDocuments);
        if (string.IsNullOrWhiteSpace(publico))
        {
            publico = @"C:\Users\Public\Documents";
        }
        string raizPublica = Directory.GetParent(publico) != null ? Directory.GetParent(publico).FullName : @"C:\Users\Public";
        if (!Directory.Exists(raizPublica))
        {
            return;
        }

        string[] carpetas = {
            "backup_*",
            "VentasReparacionesBackupCodigo_*"
        };
        foreach (string patron in carpetas)
        {
            foreach (string dir in Directory.GetDirectories(raizPublica, patron, SearchOption.TopDirectoryOnly))
            {
                DeleteDirectoryIfExists(dir);
            }
        }

        string[] archivos = {
            "ventas_reparaciones_actualizacion_*.log",
            "backup_*.php"
        };
        foreach (string patron in archivos)
        {
            foreach (string archivo in Directory.GetFiles(raizPublica, patron, SearchOption.TopDirectoryOnly))
            {
                DeleteFileIfExists(archivo);
            }
        }
    }

    static void DeleteDirectoryIfExists(string path)
    {
        if (Directory.Exists(path))
        {
            DeleteDirectoryWithRetries(path);
        }
    }

    static void DeleteFileIfExists(string path)
    {
        try
        {
            if (File.Exists(path))
            {
                File.SetAttributes(path, FileAttributes.Normal);
                File.Delete(path);
            }
        }
        catch
        {
        }
    }

    static void BackupData(string destino, string backup)
    {
        if (Directory.Exists(backup))
        {
            Directory.Delete(backup, true);
        }
        Directory.CreateDirectory(backup);

        CopyDirIfExists(Path.Combine(destino, "mysql", "data"), Path.Combine(backup, "mysql_data"));
        CopyDirIfExists(Path.Combine(destino, "htdocs", "VENTAS", "almacenamiento"), Path.Combine(backup, "almacenamiento"));
        CopyFileIfExists(Path.Combine(destino, "htdocs", "VENTAS", "reparaciones_python", "reparaciones.db"), Path.Combine(backup, "reparaciones.db"));
        CopyFileIfExists(Path.Combine(destino, "htdocs", "VENTAS", "reparaciones_python", "comercio_config.json"), Path.Combine(backup, "comercio_config.json"));
        CopyDirIfExists(Path.Combine(destino, "htdocs", "VENTAS", "reparaciones_python", "tickets"), Path.Combine(backup, "tickets"));

        string instalacionAppReciente = @"C:\VentasReparacionesApp";
        if (!Directory.Exists(Path.Combine(backup, "mysql_data")))
        {
            CopyDirIfExists(Path.Combine(instalacionAppReciente, "mysql", "data"), Path.Combine(backup, "mysql_data"));
        }
        if (!Directory.Exists(Path.Combine(backup, "almacenamiento")))
        {
            CopyDirIfExists(Path.Combine(instalacionAppReciente, "htdocs", "VENTAS", "almacenamiento"), Path.Combine(backup, "almacenamiento"));
        }
        if (!File.Exists(Path.Combine(backup, "reparaciones.db")))
        {
            CopyFileIfExists(Path.Combine(instalacionAppReciente, "htdocs", "VENTAS", "reparaciones_python", "reparaciones.db"), Path.Combine(backup, "reparaciones.db"));
        }
        if (!File.Exists(Path.Combine(backup, "comercio_config.json")))
        {
            CopyFileIfExists(Path.Combine(instalacionAppReciente, "htdocs", "VENTAS", "reparaciones_python", "comercio_config.json"), Path.Combine(backup, "comercio_config.json"));
        }
        if (!Directory.Exists(Path.Combine(backup, "tickets")))
        {
            CopyDirIfExists(Path.Combine(instalacionAppReciente, "htdocs", "VENTAS", "reparaciones_python", "tickets"), Path.Combine(backup, "tickets"));
        }

        string instalacionAppAnterior = @"C:\Ventas y Reparaciones";
        if (!Directory.Exists(Path.Combine(backup, "mysql_data")))
        {
            CopyDirIfExists(Path.Combine(instalacionAppAnterior, "mysql", "data"), Path.Combine(backup, "mysql_data"));
        }
        if (!Directory.Exists(Path.Combine(backup, "almacenamiento")))
        {
            CopyDirIfExists(Path.Combine(instalacionAppAnterior, "htdocs", "VENTAS", "almacenamiento"), Path.Combine(backup, "almacenamiento"));
        }
        if (!File.Exists(Path.Combine(backup, "reparaciones.db")))
        {
            CopyFileIfExists(Path.Combine(instalacionAppAnterior, "htdocs", "VENTAS", "reparaciones_python", "reparaciones.db"), Path.Combine(backup, "reparaciones.db"));
        }
        if (!File.Exists(Path.Combine(backup, "comercio_config.json")))
        {
            CopyFileIfExists(Path.Combine(instalacionAppAnterior, "htdocs", "VENTAS", "reparaciones_python", "comercio_config.json"), Path.Combine(backup, "comercio_config.json"));
        }
        if (!Directory.Exists(Path.Combine(backup, "tickets")))
        {
            CopyDirIfExists(Path.Combine(instalacionAppAnterior, "htdocs", "VENTAS", "reparaciones_python", "tickets"), Path.Combine(backup, "tickets"));
        }

        string instalacionXamppAnterior = @"C:\xampp82";
        if (!Directory.Exists(Path.Combine(backup, "mysql_data")))
        {
            CopyDirIfExists(Path.Combine(instalacionXamppAnterior, "mysql", "data"), Path.Combine(backup, "mysql_data"));
        }
        if (!Directory.Exists(Path.Combine(backup, "almacenamiento")))
        {
            CopyDirIfExists(Path.Combine(instalacionXamppAnterior, "htdocs", "VENTAS", "almacenamiento"), Path.Combine(backup, "almacenamiento"));
        }
        if (!File.Exists(Path.Combine(backup, "reparaciones.db")))
        {
            CopyFileIfExists(Path.Combine(instalacionXamppAnterior, "htdocs", "VENTAS", "reparaciones_python", "reparaciones.db"), Path.Combine(backup, "reparaciones.db"));
        }
        if (!File.Exists(Path.Combine(backup, "comercio_config.json")))
        {
            CopyFileIfExists(Path.Combine(instalacionXamppAnterior, "htdocs", "VENTAS", "reparaciones_python", "comercio_config.json"), Path.Combine(backup, "comercio_config.json"));
        }
        if (!Directory.Exists(Path.Combine(backup, "tickets")))
        {
            CopyDirIfExists(Path.Combine(instalacionXamppAnterior, "htdocs", "VENTAS", "reparaciones_python", "tickets"), Path.Combine(backup, "tickets"));
        }

        string reparacionesLegacy = @"C:\Reparaciones\reparaciones_python";
        if (!File.Exists(Path.Combine(backup, "reparaciones.db")))
        {
            CopyFileIfExists(Path.Combine(reparacionesLegacy, "reparaciones.db"), Path.Combine(backup, "reparaciones.db"));
        }
        if (!File.Exists(Path.Combine(backup, "comercio_config.json")))
        {
            CopyFileIfExists(Path.Combine(reparacionesLegacy, "comercio_config.json"), Path.Combine(backup, "comercio_config.json"));
        }
        if (!Directory.Exists(Path.Combine(backup, "tickets")))
        {
            CopyDirIfExists(Path.Combine(reparacionesLegacy, "tickets"), Path.Combine(backup, "tickets"));
        }
    }

    static void StopLocalServers(string destino)
    {
        StopPort(80);
        StopPort(3306);
        StopProcessesFromRoots(new[] { destino, @"C:\xampp82" });
        System.Threading.Thread.Sleep(2500);
    }

    static void StopPort(int port)
    {
        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = "netstat.exe";
        info.Arguments = "-ano";
        info.UseShellExecute = false;
        info.CreateNoWindow = true;
        info.RedirectStandardOutput = true;

        using (Process proceso = Process.Start(info))
        {
            string salida = proceso.StandardOutput.ReadToEnd();
            proceso.WaitForExit();
            string marca = ":" + port.ToString();
            foreach (string linea in salida.Split(new[] { "\r\n", "\n" }, StringSplitOptions.RemoveEmptyEntries))
            {
                if (!linea.Contains(marca) || !linea.Contains("LISTENING"))
                {
                    continue;
                }

                string[] partes = linea.Split((char[])null, StringSplitOptions.RemoveEmptyEntries);
                if (partes.Length < 5)
                {
                    continue;
                }

                int pid;
                if (int.TryParse(partes[4], out pid))
                {
                    try
                    {
                        Process.GetProcessById(pid).Kill();
                    }
                    catch
                    {
                    }
                }
            }
        }
    }

    static void StopProcessesFromRoots(string[] roots)
    {
        string[] names = new[] { "httpd", "mysqld", "Ventas y Reparaciones", "CONTROL VENTAS" };
        foreach (Process proceso in Process.GetProcesses())
        {
            try
            {
                if (!names.Any(n => proceso.ProcessName.Equals(n, StringComparison.OrdinalIgnoreCase)))
                {
                    continue;
                }

                string ruta = "";
                try
                {
                    ruta = proceso.MainModule != null ? proceso.MainModule.FileName : "";
                }
                catch
                {
                }

                if (ruta == "" || roots.Any(r => ruta.StartsWith(r, StringComparison.OrdinalIgnoreCase)))
                {
                    proceso.Kill();
                    proceso.WaitForExit(5000);
                }
            }
            catch
            {
            }
        }
    }

    static void DeleteDirectoryWithRetries(string path)
    {
        Exception last = null;
        for (int i = 0; i < 6; i++)
        {
            try
            {
                if (!Directory.Exists(path))
                {
                    return;
                }
                ClearAttributes(path);
                Directory.Delete(path, true);
                return;
            }
            catch (Exception ex)
            {
                last = ex;
                System.Threading.Thread.Sleep(1500);
            }
        }
        throw last;
    }

    static void ClearAttributes(string path)
    {
        if (!Directory.Exists(path))
        {
            return;
        }
        foreach (string file in Directory.GetFiles(path, "*", SearchOption.AllDirectories))
        {
            try
            {
                File.SetAttributes(file, FileAttributes.Normal);
            }
            catch
            {
            }
        }
        foreach (string dir in Directory.GetDirectories(path, "*", SearchOption.AllDirectories))
        {
            try
            {
                File.SetAttributes(dir, FileAttributes.Directory);
            }
            catch
            {
            }
        }
    }

    static void RestoreData(string destino, string backup)
    {
        // No restaurar mysql_data automaticamente: si la base anterior esta corrupta, copiarla rompe la instalacion nueva.
        CopyDirIfExists(Path.Combine(backup, "almacenamiento"), Path.Combine(destino, "htdocs", "VENTAS", "almacenamiento"));
        CopyFileIfExists(Path.Combine(backup, "reparaciones.db"), Path.Combine(destino, "htdocs", "VENTAS", "reparaciones_python", "reparaciones.db"));
        CopyFileIfExists(Path.Combine(backup, "comercio_config.json"), Path.Combine(destino, "htdocs", "VENTAS", "reparaciones_python", "comercio_config.json"));
        CopyDirIfExists(Path.Combine(backup, "tickets"), Path.Combine(destino, "htdocs", "VENTAS", "reparaciones_python", "tickets"));
    }

    static void GrantWritePermissions(string destino)
    {
        RunIcacls(destino, "/grant *S-1-5-32-545:(OI)(CI)M /T /C");
        RunIcacls(destino, "/grant *S-1-1-0:(OI)(CI)M /T /C");
    }

    static void RunIcacls(string destino, string args)
    {
        try
        {
            ProcessStartInfo info = new ProcessStartInfo();
            info.FileName = "icacls.exe";
            info.Arguments = "\"" + destino + "\" " + args;
            info.UseShellExecute = false;
            info.CreateNoWindow = true;
            using (Process proceso = Process.Start(info))
            {
                proceso.WaitForExit(60000);
            }
        }
        catch
        {
        }
    }

    static void ResetDatabaseIfBroken(string destino, string cleanMysqlData)
    {
        if (!Directory.Exists(cleanMysqlData))
        {
            return;
        }
        string mysql = Path.Combine(destino, "mysql", "bin", "mysql.exe");
        string data = Path.Combine(destino, "mysql", "data");
        if (!File.Exists(mysql) || !Directory.Exists(data))
        {
            return;
        }

        bool broken = false;
        try
        {
            StartMySql(destino);
            string error = "";
            int code = RunMysqlInline(mysql,
                "CREATE DATABASE IF NOT EXISTS sistema_ventas; " +
                "USE sistema_ventas; " +
                "SELECT COUNT(*) FROM configuraciones; " +
                "SELECT COUNT(*) FROM listas_precios; " +
                "SELECT COUNT(*) FROM clientes;",
                destino,
                out error);
            broken = code != 0 && IsMysqlEngineBroken(error);
            if (code != 0 && !broken)
            {
                broken = error.IndexOf("doesn't exist", StringComparison.OrdinalIgnoreCase) >= 0
                    || error.IndexOf("does not exist", StringComparison.OrdinalIgnoreCase) >= 0
                    || error.IndexOf("Unknown database", StringComparison.OrdinalIgnoreCase) >= 0;
            }
        }
        catch (Exception ex)
        {
            broken = IsMysqlEngineBroken(ex.Message);
        }

        if (!broken)
        {
            return;
        }

        Console.WriteLine("Base de datos rota detectada. Se reinstala una base limpia.");
        StopPort(3306);
        System.Threading.Thread.Sleep(1500);
        DeleteDirectoryWithRetries(data);
        CopyDirIfExists(cleanMysqlData, data);
        GrantWritePermissions(destino);
        StartMySql(destino);
        string schema = Path.Combine(destino, "htdocs", "VENTAS", "instalacion_schema.sql");
        if (File.Exists(schema))
        {
            RunMysqlScript(mysql, schema, destino);
        }
    }

    static bool IsMysqlEngineBroken(string texto)
    {
        if (texto == null)
        {
            return false;
        }
        return texto.IndexOf("1932", StringComparison.OrdinalIgnoreCase) >= 0
            || texto.IndexOf("doesn't exist in engine", StringComparison.OrdinalIgnoreCase) >= 0
            || texto.IndexOf("does not exist in engine", StringComparison.OrdinalIgnoreCase) >= 0;
    }

    static void InitializeBlankDatabaseIfNeeded(string destino, string backup)
    {
        string schema = Path.Combine(destino, "htdocs", "VENTAS", "instalacion_schema.sql");
        if (!File.Exists(schema))
        {
            throw new Exception("No se encontro instalacion_schema.sql.");
        }
        string mysql = Path.Combine(destino, "mysql", "bin", "mysql.exe");
        try
        {
            StartMySql(destino);
            if (!Directory.Exists(Path.Combine(destino, "mysql", "data", "sistema_ventas")))
            {
                RunMysqlScript(mysql, schema, destino);
            }
            ValidateDatabase(mysql, destino);
        }
        catch (Exception ex)
        {
            Log("La primera inicializacion de MySQL fallo. Se reconstruye desde cero.");
            Log("ERROR PRIMER INTENTO MYSQL: " + ex.ToString());
            LogTail("MYSQL", Path.Combine(destino, "mysql", "data", "mysql_error.log"));
            RebuildDatabaseFromPackagedBackup(destino, schema);
            ValidateDatabase(mysql, destino);
        }
    }

    static void RebuildDatabaseFromPackagedBackup(string destino, string schema)
    {
        string mysqlRoot = Path.Combine(destino, "mysql");
        string data = Path.Combine(mysqlRoot, "data");
        string cleanData = Path.Combine(mysqlRoot, "backup");
        if (!Directory.Exists(cleanData))
        {
            throw new Exception("No se encontro la base limpia mysql\\backup.");
        }
        StopPort(3306);
        System.Threading.Thread.Sleep(1500);
        DeleteDirectoryIfExists(data);
        CopyDirIfExists(cleanData, data);
        GrantWritePermissions(destino);
        StartMySql(destino);
        RunMysqlScript(Path.Combine(mysqlRoot, "bin", "mysql.exe"), schema, destino);
    }

    static void ValidateDatabase(string mysqlExe, string workingDirectory)
    {
        string error = "";
        string sql =
            "USE sistema_ventas; " +
            "CHECK TABLE clientes, configuraciones, listas_precios, producto_precios, productos, stock, ventas; " +
            "SELECT COUNT(*) FROM productos; " +
            "SELECT COUNT(*) FROM stock; " +
            "SELECT COUNT(*) FROM ventas; " +
            "SELECT COUNT(*) FROM producto_precios; " +
            "CREATE TABLE IF NOT EXISTS instalacion_autoprueba (id INT AUTO_INCREMENT PRIMARY KEY, nota VARCHAR(80) NOT NULL) ENGINE=InnoDB; " +
            "INSERT INTO instalacion_autoprueba (nota) VALUES ('ok'); " +
            "UPDATE instalacion_autoprueba SET nota='ok_actualizado' WHERE nota='ok'; " +
            "DELETE FROM instalacion_autoprueba; " +
            "DROP TABLE instalacion_autoprueba; " +
            "INSERT INTO configuraciones (clave, valor, tipo, grupo) VALUES ('__instalador_prueba__', 'ok', 'texto', 'sistema') " +
            "ON DUPLICATE KEY UPDATE valor=VALUES(valor); " +
            "DELETE FROM configuraciones WHERE clave='__instalador_prueba__'; " +
            "INSERT INTO listas_precios (nombre, activo) VALUES ('__INSTALADOR_PRUEBA__', 1); " +
            "DELETE FROM listas_precios WHERE nombre='__INSTALADOR_PRUEBA__'; " +
            "INSERT INTO clientes (nombre) VALUES ('__INSTALADOR_PRUEBA__'); " +
            "DELETE FROM clientes WHERE nombre='__INSTALADOR_PRUEBA__';";
        int code = RunMysqlInline(mysqlExe, sql, workingDirectory, out error);
        if (code != 0)
        {
            throw new Exception("La autoprueba de base de datos fallo. " + error);
        }
        Log("Autoprueba de base de datos: OK.");
    }

    static int RunMysqlInline(string mysqlExe, string sql, string workingDirectory, out string error)
    {
        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = mysqlExe;
        info.Arguments = "--user=root --batch --skip-column-names -e \"" + sql.Replace("\"", "\\\"") + "\"";
        info.WorkingDirectory = workingDirectory;
        info.UseShellExecute = false;
        info.CreateNoWindow = true;
        info.RedirectStandardError = true;
        info.RedirectStandardOutput = true;
        using (Process proceso = Process.Start(info))
        {
            proceso.StandardOutput.ReadToEnd();
            error = proceso.StandardError.ReadToEnd();
            proceso.WaitForExit();
            return proceso.ExitCode;
        }
    }


    static bool IsPortOpen(string host, int port)
    {
        try
        {
            using (System.Net.Sockets.TcpClient client = new System.Net.Sockets.TcpClient())
            {
                IAsyncResult result = client.BeginConnect(host, port, null, null);
                bool ok = result.AsyncWaitHandle.WaitOne(500);
                if (!ok)
                {
                    return false;
                }
                client.EndConnect(result);
                return true;
            }
        }
        catch
        {
            return false;
        }
    }

    static void WaitForPort(string host, int port, int timeoutMs)
    {
        int waited = 0;
        while (waited < timeoutMs)
        {
            if (IsPortOpen(host, port))
            {
                return;
            }
            System.Threading.Thread.Sleep(500);
            waited += 500;
        }
        throw new Exception("No inicio MySQL en el puerto " + port.ToString() + ".");
    }

    static void StartMySql(string destino)
    {
        if (IsPortOpen("127.0.0.1", 3306))
        {
            return;
        }
        string exe = Path.Combine(destino, "mysql", "bin", "mysqld.exe");
        string ini = Path.Combine(destino, "mysql", "bin", "my.ini");
        if (!File.Exists(exe))
        {
            throw new Exception("No se encontro mysqld.exe.");
        }
        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = exe;
        info.Arguments = "--defaults-file=\"" + ini + "\" --standalone";
        info.WorkingDirectory = destino;
        info.UseShellExecute = false;
        info.CreateNoWindow = true;
        info.WindowStyle = ProcessWindowStyle.Hidden;
        Process.Start(info);
        WaitForPort("127.0.0.1", 3306, 20000);
    }

    static void RunProcess(string exe, string args, string workingDirectory, bool useShell)
    {
        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = useShell ? "cmd.exe" : exe;
        info.Arguments = useShell ? "/c \"" + exe + "\" " + args : args;
        info.WorkingDirectory = workingDirectory;
        info.UseShellExecute = false;
        info.CreateNoWindow = true;
        using (Process proceso = Process.Start(info))
        {
            proceso.WaitForExit();
            if (proceso.ExitCode != 0)
            {
                throw new Exception("No se pudo inicializar la base de datos limpia.");
            }
        }
    }

    static void RunMysqlScript(string mysqlExe, string schemaPath, string workingDirectory)
    {
        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = mysqlExe;
        info.Arguments = "--user=root";
        info.WorkingDirectory = workingDirectory;
        info.UseShellExecute = false;
        info.CreateNoWindow = true;
        info.RedirectStandardInput = true;
        info.RedirectStandardError = true;
        using (Process proceso = Process.Start(info))
        {
            string sql = File.ReadAllText(schemaPath);
            proceso.StandardInput.Write(sql);
            proceso.StandardInput.Close();
            string error = proceso.StandardError.ReadToEnd();
            proceso.WaitForExit();
            if (proceso.ExitCode != 0)
            {
                throw new Exception("No se pudo inicializar la base de datos limpia. " + error);
            }
        }
    }

    static void ExtractPayload(string destino)
    {
        Assembly asm = Assembly.GetExecutingAssembly();
        string nombre = asm.GetManifestResourceNames().First(n => n.EndsWith("ventas_reparaciones_payload.zip"));
        using (Stream entrada = asm.GetManifestResourceStream(nombre))
        using (FileStream salida = File.Create(destino))
        {
            entrada.CopyTo(salida);
        }
    }

    static void CreateShortcuts(string destino)
    {
        string script = Path.Combine(destino, "crear_accesos.ps1");
        string launcher = Path.Combine(destino, "Ventas y Reparaciones.exe");
        if (!File.Exists(script) || !File.Exists(launcher))
        {
            return;
        }

        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = "powershell.exe";
        info.Arguments = "-NoProfile -ExecutionPolicy Bypass -File \"" + script + "\" -Launcher \"" + launcher + "\"";
        info.UseShellExecute = false;
        info.CreateNoWindow = true;
        using (Process proceso = Process.Start(info))
        {
            proceso.WaitForExit();
        }
    }

    static void CopyFileIfExists(string origen, string destino)
    {
        if (!File.Exists(origen))
        {
            return;
        }
        Directory.CreateDirectory(Path.GetDirectoryName(destino));
        File.Copy(origen, destino, true);
    }

    static void CopyDirIfExists(string origen, string destino)
    {
        if (!Directory.Exists(origen))
        {
            return;
        }
        if (Directory.Exists(destino))
        {
            Directory.Delete(destino, true);
        }
        foreach (string dir in Directory.GetDirectories(origen, "*", SearchOption.AllDirectories))
        {
            Directory.CreateDirectory(dir.Replace(origen, destino));
        }
        Directory.CreateDirectory(destino);
        foreach (string archivo in Directory.GetFiles(origen, "*", SearchOption.AllDirectories))
        {
            string salida = archivo.Replace(origen, destino);
            Directory.CreateDirectory(Path.GetDirectoryName(salida));
            File.Copy(archivo, salida, true);
        }
    }
}
'@ | Set-Content -LiteralPath $installerSource -Encoding ASCII

& $csc /nologo /target:exe /out:$installerExe /resource:$payloadZip /reference:System.IO.Compression.dll /reference:System.IO.Compression.FileSystem.dll $installerSource
if (-not (Test-Path -LiteralPath $installerExe)) {
    throw "No se pudo crear el instalador unico."
}

Write-Host ""
Write-Host "Instalador creado:"
Write-Host $installerExe
