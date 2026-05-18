$ErrorActionPreference = "Stop"

$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$sourcePath = Join-Path $projectDir "LauncherReparaciones.cs"
$outputExe = Join-Path $projectDir "CONTROL REPARACIONES.exe"
$csc = "C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe"

if (-not (Test-Path -LiteralPath $csc)) {
    $csc = "C:\Windows\Microsoft.NET\Framework\v4.0.30319\csc.exe"
}

if (-not (Test-Path -LiteralPath $csc)) {
    throw "No se encontro csc.exe para compilar el launcher."
}

@'
using System;
using System.Diagnostics;
using System.IO;
using System.Linq;

class LauncherReparaciones
{
    [STAThread]
    static void Main()
    {
        string appDir = DetectarDirectorio();
        string webApp = Path.Combine(appDir, "web_app.py");
        if (!File.Exists(webApp))
        {
            MostrarError("No se encontro web_app.py en la instalacion.");
            return;
        }

        string pythonExe = BuscarPython(appDir);
        if (string.IsNullOrWhiteSpace(pythonExe))
        {
            MostrarError("No se encontro Python en la instalacion.");
            return;
        }

        try
        {
            CerrarProcesoPuerto(8765);
            IniciarServidorOculto(pythonExe, appDir, webApp);
        }
        catch (Exception ex)
        {
            try
            {
                File.WriteAllText(Path.Combine(appDir, "reparaciones_error.log"), ex.ToString());
            }
            catch
            {
            }
            MostrarError("No se pudo iniciar Reparaciones. Revise reparaciones_error.log.");
        }
    }

    static string DetectarDirectorio()
    {
        string baseDir = AppDomain.CurrentDomain.BaseDirectory;
        if (File.Exists(Path.Combine(baseDir, "web_app.py")))
        {
            return baseDir;
        }

        string[] candidatos = {
            @"C:\Reparaciones\reparaciones_python",
            @"C:\REPARACIONES\reparaciones_python"
        };

        foreach (string candidato in candidatos)
        {
            if (File.Exists(Path.Combine(candidato, "web_app.py")))
            {
                return candidato;
            }
        }

        return baseDir;
    }

    static string BuscarPython(string appDir)
    {
        string[] candidatos = {
            Path.Combine(appDir, "python_runtime", "pythonw.exe"),
            Path.Combine(appDir, "python_runtime", "python.exe")
        };

        foreach (string candidato in candidatos)
        {
            if (File.Exists(candidato))
            {
                return candidato;
            }
        }

        return null;
    }

    static void CerrarProcesoPuerto(int port)
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

    static void IniciarServidorOculto(string pythonExe, string appDir, string webApp)
    {
        ProcessStartInfo info = new ProcessStartInfo();
        info.FileName = pythonExe;
        info.Arguments = "\"" + webApp + "\"";
        info.WorkingDirectory = appDir;
        info.UseShellExecute = false;
        info.CreateNoWindow = true;
        info.WindowStyle = ProcessWindowStyle.Hidden;
        Process.Start(info);
    }

    static void MostrarError(string mensaje)
    {
        System.Windows.Forms.MessageBox.Show(mensaje, "Reparaciones", System.Windows.Forms.MessageBoxButtons.OK, System.Windows.Forms.MessageBoxIcon.Error);
    }
}
'@ | Set-Content -LiteralPath $sourcePath -Encoding ASCII

& $csc /nologo /target:winexe /out:$outputExe /reference:System.Windows.Forms.dll $sourcePath

if (-not (Test-Path -LiteralPath $outputExe)) {
    throw "No se pudo crear CONTROL REPARACIONES.exe"
}
