from dataclasses import dataclass
from datetime import date


ESTADOS = {
    "PENDIENTE": "Pendiente",
    "ENTREGADO": "Entregado",
}


@dataclass
class Reparacion:
    id: int
    codigo: str
    cliente_nombre: str
    cliente_telefono: str
    marca: str
    modelo: str
    imei: str
    falla: str
    diagnostico: str
    garantia: str
    estado: str
    precio: float
    fecha_ingreso: str
    fecha_entrega: str
    observaciones: str
    activo: int


def fecha_hoy():
    valor = date.today().isoformat()
    return valor


def limpiar_texto(valor):
    texto = ""
    if valor is not None:
        texto = str(valor).strip()
    return texto


def convertir_precio(valor):
    precio = 0.0
    try:
        texto = str(valor).replace(",", ".").strip()
        if texto:
            precio = float(texto)
    except ValueError:
        precio = 0.0
    return precio


def estado_valido(valor):
    estado = "PENDIENTE"
    texto = limpiar_texto(valor)
    if texto in ESTADOS:
        estado = texto
    return estado


def validar_datos(datos):
    errores = []
    if not limpiar_texto(datos.get("cliente_nombre")):
        errores.append("El nombre del cliente es obligatorio.")
    if convertir_precio(datos.get("precio")) < 0:
        errores.append("El precio no puede ser negativo.")
    return errores
