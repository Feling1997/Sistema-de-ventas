$ErrorActionPreference = "Stop"

$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$xamppSource = "C:\xampp82"
$ventasSource = Join-Path $xamppSource "htdocs\VENTAS"
$buildDir = Join-Path $projectDir "build_instalador_ventas_reparaciones"
$stagingDir = Join-Path $buildDir "payload"
$installRootName = "Ventas y Reparaciones"
$installRootPath = "C:\Ventas y Reparaciones"
$payloadZip = Join-Path $buildDir "ventas_reparaciones_payload.zip"
$installerSource = Join-Path $buildDir "InstaladorVentasReparaciones.cs"
$launcherSource = Join-Path $buildDir "ControlVentasReparaciones.cs"
$schemaSource = Join-Path $buildDir "instalacion_schema.sql"
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
    $schema = & $mysqldump --user=root --databases sistema_ventas --no-data --skip-comments --routines --events 2>$null
    if ($LASTEXITCODE -ne 0 -or -not $schema) {
        throw "No se pudo generar el esquema limpio de sistema_ventas. Inicie MySQL local y reintente."
    }
    $schema | Set-Content -LiteralPath $OutputPath -Encoding UTF8
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
Remove-InstallerBusinessDatabases -MysqlDataPath (Join-Path $payloadRoot "mysql\data")
Invoke-RobocopyChecked -Source (Join-Path $xamppSource "tmp") -Destination (Join-Path $payloadRoot "tmp")

Write-Host "Copiando aplicacion VENTAS..."
Invoke-RobocopyChecked -Source $ventasSource -Destination (Join-Path $payloadRoot "htdocs\VENTAS") -ExtraArgs @(
    "/XD",
    ".git",
    "build_instalador_reparaciones",
    "build_instalador_ventas_reparaciones",
    "/XF",
    "Instalador_Reparaciones.exe",
    "Instalador_Ventas_Reparaciones.exe",
    "reparaciones_python_instalador.zip"
)
Write-CleanInstallerConfig -VentasPath (Join-Path $payloadRoot "htdocs\VENTAS")
Write-CleanArcaConfig -VentasPath (Join-Path $payloadRoot "htdocs\VENTAS")
Copy-Item -LiteralPath $schemaSource -Destination (Join-Path $payloadRoot "htdocs\VENTAS\instalacion_schema.sql") -Force

$rootFiles = @(
    "apache_start.bat",
    "apache_stop.bat",
    "mysql_start.bat",
    "mysql_stop.bat",
    "xampp-control.exe",
    "xampp-control.ini",
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
using System.Net.Sockets;
using System.Threading;

class ControlVentasReparaciones
{
    static int Main()
    {
        string root = DetectRoot();
        string url = "http://localhost/VENTAS/publico/index.php?c=ventas&a=inicio";
        Process mysql = null;
        Process apache = null;
        try
        {
            StopLocalProcessesFromRoot(root);
            StopPort(8765);
            mysql = StartMySql(root);
            apache = StartApache(root);
            StartReparaciones(root);
            WaitForPort("127.0.0.1", 80, 15000);
            OpenBrowserAppAndWait(root, url);
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
            System.Windows.Forms.MessageBox.Show("No se pudo iniciar Ventas y Reparaciones. Revise control_ventas_error.log.", "Ventas y Reparaciones", System.Windows.Forms.MessageBoxButtons.OK, System.Windows.Forms.MessageBoxIcon.Error);
            return 1;
        }
        finally
        {
            StopProcess(apache);
            StopProcess(mysql);
            StopPort(8765);
            StopLocalProcessesFromRoot(root);
        }
    }

    static string DetectRoot()
    {
        string dir = AppDomain.CurrentDomain.BaseDirectory.TrimEnd('\\');
        if (File.Exists(Path.Combine(dir, "apache", "bin", "httpd.exe")))
        {
            return dir;
        }
        return @"C:\Ventas y Reparaciones";
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
        int waited = 0;
        while (waited < timeoutMs)
        {
            if (IsPortOpen(host, port))
            {
                return;
            }
            Thread.Sleep(500);
            waited += 500;
        }
        throw new Exception("No inicio el servidor local en el puerto " + port.ToString() + ".");
    }

    static Process StartMySql(string root)
    {
        if (IsPortOpen("127.0.0.1", 3306))
        {
            return null;
        }

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
        return proceso;
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
        string vbs = Path.Combine(root, "htdocs", "VENTAS", "reparaciones_python", "abrir_reparaciones.vbs");
        if (!File.Exists(vbs))
        {
            return;
        }

        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = "wscript.exe";
        info.Arguments = "\"" + vbs + "\"";
        info.WorkingDirectory = Path.GetDirectoryName(vbs);
        info.UseShellExecute = false;
        info.CreateNoWindow = true;
        Process.Start(info);
    }

    static void OpenBrowserAppAndWait(string root, string url)
    {
        string browser = FindBrowser();
        if (browser == "")
        {
            ProcessStartInfo fallback = new ProcessStartInfo();
            fallback.FileName = url;
            fallback.UseShellExecute = true;
            Process.Start(fallback);
            System.Windows.Forms.MessageBox.Show("Cuando termine de usar el sistema, presione Aceptar para cerrar los servidores locales.", "Ventas y Reparaciones", System.Windows.Forms.MessageBoxButtons.OK, System.Windows.Forms.MessageBoxIcon.Information);
            return;
        }

        string profile = Path.Combine(root, "browser_profile");
        Directory.CreateDirectory(profile);
        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = browser;
        info.Arguments = "--app=\"" + url + "\" --user-data-dir=\"" + profile + "\" --no-first-run --disable-background-mode";
        info.UseShellExecute = false;
        Process proceso = Process.Start(info);
        if (proceso != null)
        {
            proceso.WaitForExit();
        }
    }

    static string FindBrowser()
    {
        string[] candidates = new[]
        {
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFilesX86), "Microsoft", "Edge", "Application", "msedge.exe"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles), "Microsoft", "Edge", "Application", "msedge.exe"),
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
    static int Main()
    {
        string destino = @"C:\Ventas y Reparaciones";
        string backup = Path.Combine(Path.GetTempPath(), "ventas_reparaciones_backup");
        string payload = Path.Combine(Path.GetTempPath(), "ventas_reparaciones_payload.zip");

        try
        {
            Console.Title = "Instalador Ventas y Reparaciones";
            Console.WriteLine("Instalando Ventas y Reparaciones...");
            Console.WriteLine("Destino: " + destino);

            StopLocalServers(destino);
            BackupData(destino, backup);
            ExtractPayload(payload);

            if (Directory.Exists(destino))
            {
                DeleteDirectoryWithRetries(destino);
            }

            ZipFile.ExtractToDirectory(payload, @"C:\");
            RestoreData(destino, backup);
            InitializeBlankDatabaseIfNeeded(destino, backup);
            CreateShortcuts(destino);

            Console.WriteLine("");
            Console.WriteLine("Instalacion terminada.");
            Console.WriteLine("Use el acceso directo Ventas y Reparaciones del Escritorio.");
            Console.WriteLine("No hace falta instalar XAMPP, PHP, MySQL ni Python.");
            return 0;
        }
        catch (Exception ex)
        {
            Console.WriteLine("");
            Console.WriteLine("No se pudo instalar.");
            Console.WriteLine(ex.Message);
            Console.WriteLine("");
            Console.WriteLine("Pruebe ejecutar este instalador como administrador.");
            Console.WriteLine("Presione una tecla para cerrar...");
            Console.ReadKey();
            return 1;
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
        CopyDirIfExists(Path.Combine(backup, "mysql_data"), Path.Combine(destino, "mysql", "data"));
        CopyDirIfExists(Path.Combine(backup, "almacenamiento"), Path.Combine(destino, "htdocs", "VENTAS", "almacenamiento"));
        CopyFileIfExists(Path.Combine(backup, "reparaciones.db"), Path.Combine(destino, "htdocs", "VENTAS", "reparaciones_python", "reparaciones.db"));
        CopyFileIfExists(Path.Combine(backup, "comercio_config.json"), Path.Combine(destino, "htdocs", "VENTAS", "reparaciones_python", "comercio_config.json"));
        CopyDirIfExists(Path.Combine(backup, "tickets"), Path.Combine(destino, "htdocs", "VENTAS", "reparaciones_python", "tickets"));
    }

    static void InitializeBlankDatabaseIfNeeded(string destino, string backup)
    {
        if (Directory.Exists(Path.Combine(backup, "mysql_data")))
        {
            return;
        }
        string schema = Path.Combine(destino, "htdocs", "VENTAS", "instalacion_schema.sql");
        if (!File.Exists(schema))
        {
            return;
        }
        StartMySql(destino);
        string mysql = Path.Combine(destino, "mysql", "bin", "mysql.exe");
        RunProcess(mysql, "--user=root < \"" + schema + "\"", destino, true);
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
