<?php

declare(strict_types=1);

namespace Ventas\Backups\Infrastructure;

use Ventas\Backups\Domain\Repositorios\BackupRepository;

final class FilesystemBackupRepository implements BackupRepository
{
    public function generarResumen(): array
    {
        $resultado = [
            'generado' => date('Y-m-d H:i:s'),
            'ventas_mysql' => [],
            'reparaciones_sqlite' => [],
        ];

        return $resultado;
    }

    public function generarTextoResumen(array $resumen): string
    {
        $lineas = [];
        $lineas[] = 'Respaldo de Ventas y Reparaciones';
        $lineas[] = 'Generado: ' . (string)($resumen['generado'] ?? '');
        $lineas[] = '';
        $lineas[] = 'IMPORTANTE:';
        $lineas[] = '- Este archivo .tar.gz no es un programa ejecutable.';
        $lineas[] = '- Se abre con 7-Zip, WinRAR o una herramienta compatible.';
        $lineas[] = '- Para recuperar el sistema hay que instalar XAMPP/PHP/MySQL y restaurar los datos indicados abajo.';
        $lineas[] = '';
        $lineas[] = 'Contenido importante:';
        $lineas[] = '- ventas_mysql.sql: base MySQL de Ventas.';
        $lineas[] = '- reparaciones_python/reparaciones.db: base SQLite de Reparaciones.';
        $lineas[] = '- configuraciones y datos del comercio.';
        $lineas[] = '- tickets de Reparaciones e imagenes usadas por el sistema.';
        $lineas[] = '- archivos del programa necesarios para reconstruir la instalacion.';
        $lineas[] = '';
        $lineas[] = 'Como restaurar en otra PC:';
        $lineas[] = '1. Instalar XAMPP/PHP/MySQL compatible.';
        $lineas[] = '2. Copiar el proyecto del sistema en la carpeta web, por ejemplo C:/xampp82/htdocs/VENTAS.';
        $lineas[] = '3. Crear la base MySQL del sistema y ejecutar el archivo ventas_mysql.sql.';
        $lineas[] = '4. Copiar las carpetas almacenamiento, configuraciones y publico sobre la instalacion nueva.';
        $lineas[] = '5. Copiar reparaciones_python/reparaciones.db y la carpeta reparaciones_python/tickets si se usa Reparaciones.';
        $lineas[] = '6. Revisar configuraciones/base_datos.php para que usuario, clave y nombre de base coincidan con la PC nueva.';
        $lineas[] = '7. Entrar al sistema y verificar clientes, productos, stock, ventas, usuarios y reparaciones.';
        $lineas[] = '';
        $lineas[] = 'Resumen MySQL:';
        foreach (($resumen['ventas_mysql'] ?? []) as $tabla => $cantidad) {
            $lineas[] = '- ' . $tabla . ': ' . $cantidad;
        }
        $lineas[] = '';
        $lineas[] = 'Resumen Reparaciones:';
        foreach (($resumen['reparaciones_sqlite'] ?? []) as $tabla => $cantidad) {
            $lineas[] = '- ' . $tabla . ': ' . $cantidad;
        }
        $resultado = implode("\n", $lineas) . "\n";

        return $resultado;
    }

    public function generarEstructura(): string
    {
        $resultado = "Este respaldo contiene datos y archivos esenciales del sistema.\n"
            . "\n"
            . "No es un ejecutable. Es un paquete comprimido .tar.gz.\n"
            . "\n"
            . "Contenido principal:\n"
            . "- ventas_mysql.sql: dump completo de tablas MySQL del sistema de Ventas.\n"
            . "- almacenamiento/: configuracion local, tickets, PDFs, logos procesados, logs y archivos guardados.\n"
            . "- configuraciones/: configuracion PHP del sistema.\n"
            . "- aplicacion/: controladores, modelos y vistas PHP.\n"
            . "- publico/: assets publicos, CSS, imagenes e index.\n"
            . "- reparaciones_python/reparaciones.db: base SQLite de Reparaciones.\n"
            . "- reparaciones_python/tickets/: tickets generados desde Reparaciones.\n"
            . "\n"
            . "Recuperacion resumida:\n"
            . "1. Instalar XAMPP/PHP/MySQL.\n"
            . "2. Restaurar el proyecto en C:/xampp82/htdocs/VENTAS o ruta equivalente.\n"
            . "3. Importar ventas_mysql.sql en la base MySQL.\n"
            . "4. Copiar almacenamiento/, configuraciones/, publico/ y reparaciones_python/ sobre la instalacion nueva.\n"
            . "5. Ajustar configuraciones/base_datos.php si cambia usuario, clave o nombre de base.\n";

        return $resultado;
    }
}
