$ErrorActionPreference = "Stop"

$baseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$launcher = Join-Path $baseDir "CONTROL REPARACIONES.exe"
$iconPath = Join-Path $baseDir "reparaciones.ico"

if (-not (Test-Path -LiteralPath $launcher)) {
    throw "No se encontro el archivo CONTROL REPARACIONES.exe"
}

function Test-PythonDisponible {
    $runtime = Join-Path $baseDir "python_runtime\python.exe"
    if (Test-Path -LiteralPath $runtime) {
        return $true
    }

    $py = Get-Command py -ErrorAction SilentlyContinue
    if ($py) {
        return $true
    }

    $python = Get-Command python -ErrorAction SilentlyContinue
    if ($python) {
        return $true
    }

    return $false
}

function New-ReparacionesIcon {
    param(
        [Parameter(Mandatory = $true)][string]$Path
    )

    Add-Type -AssemblyName System.Drawing

    function New-RoundedRectanglePath {
        param(
            [Parameter(Mandatory = $true)][float]$X,
            [Parameter(Mandatory = $true)][float]$Y,
            [Parameter(Mandatory = $true)][float]$Width,
            [Parameter(Mandatory = $true)][float]$Height,
            [Parameter(Mandatory = $true)][float]$Radius
        )

        $diameter = $Radius * 2
        $path = New-Object System.Drawing.Drawing2D.GraphicsPath
        $path.AddArc($X, $Y, $diameter, $diameter, 180, 90)
        $path.AddArc($X + $Width - $diameter, $Y, $diameter, $diameter, 270, 90)
        $path.AddArc($X + $Width - $diameter, $Y + $Height - $diameter, $diameter, $diameter, 0, 90)
        $path.AddArc($X, $Y + $Height - $diameter, $diameter, $diameter, 90, 90)
        $path.CloseFigure()
        return $path
    }

    $bitmap = New-Object System.Drawing.Bitmap 256, 256
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    $graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias

    $whiteBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::White)
    $graphics.FillRectangle($whiteBrush, 0, 0, 256, 256)

    $blue = [System.Drawing.Color]::FromArgb(24, 55, 185)
    $dark = [System.Drawing.Color]::FromArgb(0, 32, 55)
    $black = [System.Drawing.Color]::Black

    $blueArc = New-Object System.Drawing.Pen $blue, 13
    $blueArc.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $blueArc.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $graphics.DrawArc($blueArc, 30, 25, 190, 190, 132, 255)

    $darkArc = New-Object System.Drawing.Pen $dark, 13
    $darkArc.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $darkArc.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $graphics.DrawArc($darkArc, 48, 20, 176, 200, 292, 245)

    $thinBlue = New-Object System.Drawing.Pen $blue, 4
    $thinBlue.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $thinBlue.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $graphics.DrawArc($thinBlue, 55, 17, 153, 165, 214, 206)

    $phonePath = New-RoundedRectanglePath -X 89 -Y 49 -Width 75 -Height 137 -Radius 13
    $phoneBrush = New-Object System.Drawing.SolidBrush $blue
    $graphics.FillPath($phoneBrush, $phonePath)

    $screenBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::White)
    $graphics.FillRectangle($screenBrush, 101, 70, 51, 82)

    $speakerPen = New-Object System.Drawing.Pen ([System.Drawing.Color]::White), 5
    $speakerPen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $speakerPen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $graphics.DrawLine($speakerPen, 119, 61, 136, 61)
    $graphics.FillEllipse($screenBrush, 117, 164, 19, 19)

    $smilePen = New-Object System.Drawing.Pen $black, 7
    $smilePen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $smilePen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $graphics.DrawArc($smilePen, 119, 91, 82, 76, 52, 192)

    $toolPen = New-Object System.Drawing.Pen $black, 9
    $toolPen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $toolPen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $graphics.DrawLine($toolPen, 132, 137, 183, 86)
    $graphics.DrawLine($toolPen, 143, 87, 185, 129)

    $graphics.FillEllipse((New-Object System.Drawing.SolidBrush $black), 124, 129, 20, 20)
    $graphics.FillEllipse((New-Object System.Drawing.SolidBrush $black), 177, 79, 18, 18)
    $graphics.FillRectangle((New-Object System.Drawing.SolidBrush $black), 175, 118, 16, 25)

    $wrenchHead = New-Object System.Drawing.Drawing2D.GraphicsPath
    $wrenchHead.AddArc(176, 78, 31, 31, 45, 245)
    $wrenchHead.AddLine(195, 95, 207, 84)
    $wrenchHead.AddLine(201, 78, 189, 90)
    $wrenchHead.CloseFigure()
    $graphics.FillPath((New-Object System.Drawing.SolidBrush $black), $wrenchHead)

    $icon = [System.Drawing.Icon]::FromHandle($bitmap.GetHicon())
    $stream = [System.IO.File]::Open($Path, [System.IO.FileMode]::Create)
    try {
        $icon.Save($stream)
    } finally {
        $stream.Close()
        $icon.Dispose()
        $phonePath.Dispose()
        $wrenchHead.Dispose()
        $graphics.Dispose()
        $bitmap.Dispose()
    }
}

function New-Shortcut {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Target,
        [Parameter(Mandatory = $false)][string]$Arguments = "",
        [Parameter(Mandatory = $true)][string]$WorkingDirectory,
        [Parameter(Mandatory = $false)][string]$IconPath
    )

    $shell = New-Object -ComObject WScript.Shell
    $shortcut = $shell.CreateShortcut($Path)
    $shortcut.TargetPath = $Target
    $shortcut.Arguments = $Arguments
    $shortcut.WorkingDirectory = $WorkingDirectory
    $shortcut.WindowStyle = 1
    $shortcut.Description = "Sistema local de reparaciones"
    if ($IconPath -and (Test-Path -LiteralPath $IconPath)) {
        $shortcut.IconLocation = $IconPath
    }
    $shortcut.Save()
}

$desktop = [Environment]::GetFolderPath("Desktop")
$publicDesktop = [Environment]::GetFolderPath("CommonDesktopDirectory")
$oneDriveDesktop = ""
if ($env:OneDrive) {
    $posibleOneDriveDesktop = Join-Path $env:OneDrive "Desktop"
    if (Test-Path -LiteralPath $posibleOneDriveDesktop) {
        $oneDriveDesktop = $posibleOneDriveDesktop
    }
}
$startMenu = Join-Path ([Environment]::GetFolderPath("Programs")) "Reparaciones"
New-Item -ItemType Directory -Force -Path $startMenu | Out-Null
New-ReparacionesIcon -Path $iconPath

Copy-Item -LiteralPath $launcher -Destination (Join-Path $baseDir "Reparaciones.exe") -Force

New-Shortcut -Path (Join-Path $baseDir "Reparaciones.lnk") -Target $launcher -Arguments "" -WorkingDirectory $baseDir -IconPath $iconPath
New-Shortcut -Path (Join-Path $baseDir "CONTROL REPARACIONES.lnk") -Target $launcher -Arguments "" -WorkingDirectory $baseDir -IconPath $iconPath
if ($desktop) {
    New-Shortcut -Path (Join-Path $desktop "Reparaciones.lnk") -Target $launcher -Arguments "" -WorkingDirectory $baseDir -IconPath $iconPath
    New-Shortcut -Path (Join-Path $desktop "CONTROL REPARACIONES.lnk") -Target $launcher -Arguments "" -WorkingDirectory $baseDir -IconPath $iconPath
}
if ($oneDriveDesktop) {
    New-Shortcut -Path (Join-Path $oneDriveDesktop "Reparaciones.lnk") -Target $launcher -Arguments "" -WorkingDirectory $baseDir -IconPath $iconPath
    New-Shortcut -Path (Join-Path $oneDriveDesktop "CONTROL REPARACIONES.lnk") -Target $launcher -Arguments "" -WorkingDirectory $baseDir -IconPath $iconPath
}
if ($publicDesktop) {
    try {
        New-Shortcut -Path (Join-Path $publicDesktop "Reparaciones.lnk") -Target $launcher -Arguments "" -WorkingDirectory $baseDir -IconPath $iconPath
        New-Shortcut -Path (Join-Path $publicDesktop "CONTROL REPARACIONES.lnk") -Target $launcher -Arguments "" -WorkingDirectory $baseDir -IconPath $iconPath
    } catch {
        Write-Host "No se pudo crear el acceso en el Escritorio publico. Se continua con los demas accesos."
    }
}
New-Shortcut -Path (Join-Path $startMenu "Reparaciones.lnk") -Target $launcher -Arguments "" -WorkingDirectory $baseDir -IconPath $iconPath
New-Shortcut -Path (Join-Path $startMenu "CONTROL REPARACIONES.lnk") -Target $launcher -Arguments "" -WorkingDirectory $baseDir -IconPath $iconPath

Write-Host ""
Write-Host "Instalacion terminada."
Write-Host "Se crearon accesos directos en esta carpeta, en el Escritorio y en el Menu Inicio."
Write-Host ""

if (-not (Test-PythonDisponible)) {
    Write-Host "Atencion: no se encontro Python instalado en esta PC."
    Write-Host "Este paquete tampoco incluye la carpeta python_runtime."
    Write-Host "Use el instalador completo o instale Python 3.10 o superior y marque 'Add python.exe to PATH'."
    Write-Host "Descarga: https://www.python.org/downloads/"
    Write-Host ""
}

Write-Host "Para abrir el sistema use el acceso directo 'Reparaciones'."
