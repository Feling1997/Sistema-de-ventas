from datetime import datetime
from random import randint

from database import conectar
from modelos import convertir_precio, estado_valido, fecha_hoy, limpiar_texto


class ReparacionRepositorio:
    def listar(self):
        filas = []
        conexion = conectar()
        try:
            cursor = conexion.execute(
                """
                SELECT id, codigo, cliente_nombre, cliente_telefono, marca, modelo,
                       imei, falla, diagnostico, garantia, estado, precio,
                       fecha_ingreso, fecha_entrega, observaciones, activo
                FROM reparaciones
                WHERE activo = 1
                ORDER BY id DESC
                """
            )
            filas = [dict(fila) for fila in cursor.fetchall()]
        except Exception:
            filas = []
        finally:
            conexion.close()
        return filas

    def buscar_por_id(self, reparacion_id):
        reparacion = None
        conexion = conectar()
        try:
            cursor = conexion.execute(
                """
                SELECT id, codigo, cliente_nombre, cliente_telefono, marca, modelo,
                       imei, falla, diagnostico, garantia, estado, precio,
                       fecha_ingreso, fecha_entrega, observaciones, activo
                FROM reparaciones
                WHERE id = ?
                LIMIT 1
                """,
                (reparacion_id,),
            )
            fila = cursor.fetchone()
            if fila:
                reparacion = dict(fila)
        except Exception:
            reparacion = None
        finally:
            conexion.close()
        return reparacion

    def crear(self, datos):
        nuevo_id = 0
        conexion = conectar()
        try:
            cursor = conexion.execute(
                """
                INSERT INTO reparaciones (
                    codigo, cliente_nombre, cliente_telefono, marca, modelo, imei,
                    falla, diagnostico, garantia, estado, precio, fecha_ingreso,
                    fecha_entrega, observaciones, activo
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                """,
                self._parametros_crear(datos),
            )
            conexion.commit()
            nuevo_id = cursor.lastrowid
        except Exception:
            conexion.rollback()
            nuevo_id = 0
        finally:
            conexion.close()
        return nuevo_id

    def actualizar(self, reparacion_id, datos):
        actualizado = False
        conexion = conectar()
        try:
            parametros = self._parametros_actualizar(datos, reparacion_id)
            cursor = conexion.execute(
                """
                UPDATE reparaciones
                   SET cliente_nombre = ?,
                       cliente_telefono = ?,
                       marca = ?,
                       modelo = ?,
                       imei = ?,
                       falla = ?,
                       diagnostico = ?,
                       garantia = ?,
                       estado = ?,
                       precio = ?,
                       fecha_ingreso = ?,
                       fecha_entrega = ?,
                       observaciones = ?,
                       activo = ?
                 WHERE id = ?
                """,
                parametros,
            )
            conexion.commit()
            actualizado = cursor.rowcount > 0
        except Exception:
            conexion.rollback()
            actualizado = False
        finally:
            conexion.close()
        return actualizado

    def eliminar(self, reparacion_id):
        eliminado = False
        conexion = conectar()
        try:
            cursor = conexion.execute(
                "UPDATE reparaciones SET activo = 0 WHERE id = ?",
                (reparacion_id,),
            )
            conexion.commit()
            eliminado = cursor.rowcount > 0
        except Exception:
            conexion.rollback()
            eliminado = False
        finally:
            conexion.close()
        return eliminado

    def _parametros_crear(self, datos):
        parametros = (
            self._generar_codigo(),
            limpiar_texto(datos.get("cliente_nombre")),
            limpiar_texto(datos.get("cliente_telefono")),
            limpiar_texto(datos.get("marca")),
            limpiar_texto(datos.get("modelo")),
            limpiar_texto(datos.get("imei")),
            limpiar_texto(datos.get("falla")),
            limpiar_texto(datos.get("diagnostico")),
            limpiar_texto(datos.get("garantia")),
            estado_valido(datos.get("estado")),
            convertir_precio(datos.get("precio")),
            limpiar_texto(datos.get("fecha_ingreso")) or fecha_hoy(),
            limpiar_texto(datos.get("fecha_entrega")),
            limpiar_texto(datos.get("observaciones")),
        )
        return parametros

    def _parametros_actualizar(self, datos, reparacion_id):
        parametros = (
            limpiar_texto(datos.get("cliente_nombre")),
            limpiar_texto(datos.get("cliente_telefono")),
            limpiar_texto(datos.get("marca")),
            limpiar_texto(datos.get("modelo")),
            limpiar_texto(datos.get("imei")),
            limpiar_texto(datos.get("falla")),
            limpiar_texto(datos.get("diagnostico")),
            limpiar_texto(datos.get("garantia")),
            estado_valido(datos.get("estado")),
            convertir_precio(datos.get("precio")),
            limpiar_texto(datos.get("fecha_ingreso")) or fecha_hoy(),
            limpiar_texto(datos.get("fecha_entrega")),
            limpiar_texto(datos.get("observaciones")),
            1 if datos.get("activo") else 0,
            reparacion_id,
        )
        return parametros

    def _generar_codigo(self):
        codigo = f"REP-{datetime.now().strftime('%Y%m%d')}-{randint(1, 9999):04d}"
        return codigo
