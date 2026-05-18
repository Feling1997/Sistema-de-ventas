Set fso = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

baseDir = fso.GetParentFolderName(WScript.ScriptFullName)
If Not fso.FileExists(fso.BuildPath(baseDir, "web_app.py")) Then
    If fso.FileExists("C:\Reparaciones\reparaciones_python\web_app.py") Then
        baseDir = "C:\Reparaciones\reparaciones_python"
    End If
End If
If Not fso.FileExists(fso.BuildPath(baseDir, "web_app.py")) Then
    If fso.FileExists("C:\REPARACIONES\reparaciones_python\web_app.py") Then
        baseDir = "C:\REPARACIONES\reparaciones_python"
    End If
End If
shell.CurrentDirectory = baseDir
cerrarPuerto = "cmd.exe /c for /f ""tokens=5"" %p in ('netstat -ano ^| findstr "":8765"" ^| findstr ""LISTENING""') do taskkill /PID %p /F >nul 2>nul"
shell.Run cerrarPuerto, 0, True

pythonExe = fso.BuildPath(baseDir, "python_runtime\python.exe")
If fso.FileExists(pythonExe) Then
    comando = "cmd.exe /c """"" & pythonExe & """ -u """ & fso.BuildPath(baseDir, "web_app.py") & """ --no-browser > """ & fso.BuildPath(baseDir, "reparaciones_error.log") & """ 2>&1"""
Else
    comando = """" & fso.BuildPath(baseDir, "iniciar_web_oculto.bat") & """"
End If
shell.Run comando, 0, False
