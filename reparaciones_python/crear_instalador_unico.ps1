$ErrorActionPreference = "Stop"

$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$rootDir = Split-Path -Parent $projectDir
$buildDir = Join-Path $rootDir "build_instalador_reparaciones"
$payloadZip = Join-Path $buildDir "reparaciones_payload.zip"
$sourcePath = Join-Path $buildDir "InstaladorReparaciones.cs"
$outputExe = Join-Path $rootDir "Instalador_Reparaciones.exe"
$csc = "C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe"

if (-not (Test-Path -LiteralPath $csc)) {
    $csc = "C:\Windows\Microsoft.NET\Framework\v4.0.30319\csc.exe"
}

if (-not (Test-Path -LiteralPath $csc)) {
    throw "No se encontro csc.exe para compilar el instalador."
}

if (Test-Path -LiteralPath $buildDir) {
    Remove-Item -LiteralPath $buildDir -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $buildDir | Out-Null

& (Join-Path $projectDir "compilar_launcher_reparaciones.ps1")

Compress-Archive -Path $projectDir -DestinationPath $payloadZip -Force -CompressionLevel Fastest

@'
using System;
using System.Diagnostics;
using System.IO;
using System.IO.Compression;
using System.Linq;
using System.Reflection;

class InstaladorReparaciones
{
    static int Main()
    {
        int salida = 1;
        string destino = @"C:\Reparaciones";
        string app = Path.Combine(destino, "reparaciones_python");
        string backup = Path.Combine(Path.GetTempPath(), "reparaciones_backup_instalador");
        string payload = Path.Combine(Path.GetTempPath(), "reparaciones_payload.zip");

        try
        {
            Console.Title = "Instalador Reparaciones";
            Console.WriteLine("Instalando Reparaciones...");

            Directory.CreateDirectory(destino);
            if (Directory.Exists(backup))
            {
                Directory.Delete(backup, true);
            }
            Directory.CreateDirectory(backup);

            GuardarSiExiste(Path.Combine(app, "reparaciones.db"), Path.Combine(backup, "reparaciones.db"));
            GuardarSiExiste(Path.Combine(app, "comercio_config.json"), Path.Combine(backup, "comercio_config.json"));
            CopiarDirectorioSiExiste(Path.Combine(app, "tickets"), Path.Combine(backup, "tickets"));

            ExtraerPayload(payload);

            if (Directory.Exists(app))
            {
                Directory.Delete(app, true);
            }

            ZipFile.ExtractToDirectory(payload, destino);

            GuardarSiExiste(Path.Combine(backup, "reparaciones.db"), Path.Combine(app, "reparaciones.db"));
            GuardarSiExiste(Path.Combine(backup, "comercio_config.json"), Path.Combine(app, "comercio_config.json"));
            CopiarDirectorioSiExiste(Path.Combine(backup, "tickets"), Path.Combine(app, "tickets"));

            EjecutarPowerShell(Path.Combine(app, "instalar_reparaciones.ps1"));

            Console.WriteLine("");
            Console.WriteLine("Instalacion terminada.");
            Console.WriteLine("Se instalo en: " + app);
            Console.WriteLine("Abra Reparaciones desde el acceso directo del Escritorio.");
            salida = 0;
        }
        catch (Exception ex)
        {
            Console.WriteLine("");
            Console.WriteLine("No se pudo instalar Reparaciones.");
            Console.WriteLine(ex.Message);
            Console.WriteLine("");
            Console.WriteLine("Pruebe ejecutar este instalador como administrador.");
        }

        Console.WriteLine("");
        Console.WriteLine("Presione una tecla para cerrar...");
        Console.ReadKey();
        return salida;
    }

    static void ExtraerPayload(string destino)
    {
        Assembly asm = Assembly.GetExecutingAssembly();
        string nombre = asm.GetManifestResourceNames().First(n => n.EndsWith("reparaciones_payload.zip"));
        using (Stream entrada = asm.GetManifestResourceStream(nombre))
        using (FileStream salida = File.Create(destino))
        {
            entrada.CopyTo(salida);
        }
    }

    static void EjecutarPowerShell(string script)
    {
        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = "powershell.exe";
        info.Arguments = "-NoProfile -ExecutionPolicy Bypass -File \"" + script + "\"";
        info.UseShellExecute = false;
        info.CreateNoWindow = true;

        using (Process proceso = Process.Start(info))
        {
            proceso.WaitForExit();
            if (proceso.ExitCode != 0)
            {
                throw new Exception("No se pudieron crear los accesos directos.");
            }
        }
    }

    static void GuardarSiExiste(string origen, string destino)
    {
        if (File.Exists(origen))
        {
            Directory.CreateDirectory(Path.GetDirectoryName(destino));
            File.Copy(origen, destino, true);
        }
    }

    static void CopiarDirectorioSiExiste(string origen, string destino)
    {
        if (!Directory.Exists(origen))
        {
            return;
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
'@ | Set-Content -LiteralPath $sourcePath -Encoding ASCII

& $csc /nologo /target:exe /out:$outputExe /resource:$payloadZip /reference:System.IO.Compression.dll /reference:System.IO.Compression.FileSystem.dll $sourcePath

if (-not (Test-Path -LiteralPath $outputExe)) {
    throw "No se pudo crear el instalador unico."
}

Write-Host "Instalador creado:"
Write-Host $outputExe
