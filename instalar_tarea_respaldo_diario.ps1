$ErrorActionPreference = "Stop"

$base = Split-Path -Parent $MyInvocation.MyCommand.Path
$script = Join-Path $base "generar_respaldo_diario.ps1"
$nombreTarea = "Ventas Reparaciones - Respaldo Diario"
$hora = "20:30"

if (-not (Test-Path $script)) {
    throw "No se encontro $script"
}

$accion = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$script`""
$trigger = New-ScheduledTaskTrigger -Daily -At $hora
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries

Register-ScheduledTask -TaskName $nombreTarea -Action $accion -Trigger $trigger -Settings $settings -Description "Genera respaldo diario de Ventas y Reparaciones." -Force | Out-Null

Write-Host "Tarea instalada: $nombreTarea"
Write-Host "Horario diario: $hora"
