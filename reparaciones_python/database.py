import sqlite3
from pathlib import Path


BASE_DIR = Path(__file__).resolve().parent
DB_PATH = BASE_DIR / "reparaciones.db"


def conectar():
    conexion = sqlite3.connect(DB_PATH)
    conexion.row_factory = sqlite3.Row
    return conexion


def inicializar_base():
    conexion = conectar()
    try:
        conexion.execute(
            """
            CREATE TABLE IF NOT EXISTS reparaciones (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo TEXT NOT NULL UNIQUE,
                cliente_nombre TEXT NOT NULL,
                cliente_telefono TEXT DEFAULT '',
                marca TEXT DEFAULT '',
                modelo TEXT DEFAULT '',
                imei TEXT DEFAULT '',
                falla TEXT DEFAULT '',
                diagnostico TEXT DEFAULT '',
                garantia TEXT DEFAULT '',
                estado TEXT DEFAULT 'PENDIENTE',
                precio REAL DEFAULT 0,
                fecha_ingreso TEXT NOT NULL,
                fecha_entrega TEXT DEFAULT '',
                observaciones TEXT DEFAULT '',
                activo INTEGER DEFAULT 1,
                creado_en TEXT DEFAULT CURRENT_TIMESTAMP
            )
            """
        )
        conexion.execute(
            """
            UPDATE reparaciones
               SET estado = CASE
                   WHEN estado = 'ENTREGADO' THEN 'ENTREGADO'
                   ELSE 'PENDIENTE'
               END
             WHERE estado NOT IN ('PENDIENTE', 'ENTREGADO')
            """
        )
        conexion.commit()
    finally:
        conexion.close()
