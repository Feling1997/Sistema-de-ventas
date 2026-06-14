import html
import json
import base64
from pathlib import Path

from modelos import ESTADOS


BASE_DIR = Path(__file__).resolve().parent
TICKETS_DIR = BASE_DIR / "tickets"
CONFIG_PATH = BASE_DIR / "comercio_config.json"
MAIN_CONFIG_PATH = BASE_DIR.parent / "almacenamiento" / "configuracion_sistema.json"
ANCHO_TICKET = 32


def crear_ticket(reparacion):
    TICKETS_DIR.mkdir(exist_ok=True)
    ruta = TICKETS_DIR / f"{reparacion['codigo']}.txt"
    contenido = armar_contenido_ticket(reparacion)
    ruta.write_text(contenido, encoding="utf-8")
    return ruta


def cargar_comercio():
    datos = {
        "nombre": "",
        "telefono": "",
        "direccion": "",
        "documento": "",
        "email": "",
        "observaciones": "",
        "logo_ticket": "",
        "ticket_imagen_completa": "0",
        "texto_pie_ticket": "Gracias por su visita",
        "ticket_fuente": "Courier New",
        "ticket_tamano_fuente": "12",
    }
    if MAIN_CONFIG_PATH.exists():
        try:
            cargado = json.loads(MAIN_CONFIG_PATH.read_text(encoding="utf-8"))
            if isinstance(cargado, dict):
                mapa = {
                    "nombre": "nombre_comercio",
                    "telefono": "telefonos",
                    "direccion": "domicilio",
                    "documento": "cuit",
                    "email": "email",
                    "observaciones": "sitio_web",
                    "logo_ticket": "logo_ticket",
                    "ticket_imagen_completa": "ticket_imagen_completa",
                    "texto_pie_ticket": "texto_pie_ticket",
                    "ticket_fuente": "ticket_fuente",
                    "ticket_tamano_fuente": "ticket_tamano_fuente",
                }
                for clave, origen in mapa.items():
                    if origen in cargado:
                        datos[clave] = str(cargado.get(origen, "")).strip()
        except (OSError, json.JSONDecodeError):
            pass
    if CONFIG_PATH.exists():
        try:
            cargado = json.loads(CONFIG_PATH.read_text(encoding="utf-8"))
            if isinstance(cargado, dict):
                datos.update({clave: str(cargado.get(clave, datos.get(clave, ""))).strip() for clave in datos if cargado.get(clave, "") != ""})
        except (OSError, json.JSONDecodeError):
            datos = datos
    return datos


def imagen_a_data_uri(ruta_relativa):
    ruta = texto(ruta_relativa)
    if not ruta:
        return ""
    if ruta.startswith("data:") or ruta.startswith("http://") or ruta.startswith("https://"):
        return ruta
    archivo = (BASE_DIR.parent / ruta.lstrip("/")).resolve()
    raiz = BASE_DIR.parent.resolve()
    try:
        archivo.relative_to(raiz)
    except ValueError:
        return ""
    if not archivo.is_file():
        return ""
    ext = archivo.suffix.lower().lstrip(".")
    mime = {
        "jpg": "image/jpeg",
        "jpeg": "image/jpeg",
        "png": "image/png",
        "gif": "image/gif",
        "webp": "image/webp",
    }.get(ext, "image/png")
    try:
        return f"data:{mime};base64," + base64.b64encode(archivo.read_bytes()).decode("ascii")
    except OSError:
        return ""


def texto(valor):
    salida = ""
    if valor is not None:
        salida = str(valor).strip()
    return salida


def una_linea(valor, ancho=ANCHO_TICKET):
    limpio = " ".join(texto(valor).split())
    salida = limpio
    if len(limpio) > ancho:
        salida = limpio[: ancho - 1] + "."
    return salida


def linea_clave_valor(clave, valor):
    etiqueta = una_linea(clave, 11)
    dato = una_linea(valor, ANCHO_TICKET - len(etiqueta) - 2)
    salida = f"{etiqueta}: {dato}"
    return salida


def centrar(valor):
    salida = una_linea(valor).center(ANCHO_TICKET)
    return salida


def lineas_comercio(comercio):
    lineas = []
    nombre = texto(comercio.get("nombre"))
    if nombre:
        lineas.append(centrar(nombre.upper()))
    else:
        lineas.append(centrar("REPARACIONES"))

    documento = texto(comercio.get("documento"))
    telefono = texto(comercio.get("telefono"))
    direccion = texto(comercio.get("direccion"))
    email = texto(comercio.get("email"))
    observaciones = texto(comercio.get("observaciones"))

    if documento:
        lineas.append(centrar(documento))
    if telefono:
        lineas.append(centrar("Tel: " + telefono))
    if direccion:
        lineas.append(centrar(direccion))
    if email:
        lineas.append(centrar(email))
    if observaciones:
        lineas.append(centrar(observaciones))
    return lineas


def armar_contenido_ticket(reparacion):
    estado = ESTADOS.get(reparacion.get("estado"), reparacion.get("estado", ""))
    comercio = cargar_comercio()
    equipo = " ".join([texto(reparacion.get("marca")), texto(reparacion.get("modelo"))]).strip()
    lineas = []
    lineas.extend(lineas_comercio(comercio))
    lineas.append("-" * ANCHO_TICKET)
    lineas.append(centrar("TICKET DE REPARACION"))
    lineas.append("-" * ANCHO_TICKET)
    lineas.append(linea_clave_valor("Codigo", reparacion.get("codigo", "")))
    lineas.append(linea_clave_valor("Cliente", reparacion.get("cliente_nombre", "")))
    lineas.append(linea_clave_valor("Telefono", reparacion.get("cliente_telefono", "")))
    lineas.append(linea_clave_valor("Equipo", equipo))
    lineas.append(linea_clave_valor("Estado", estado))
    lineas.append(linea_clave_valor("Precio", f"{reparacion.get('precio', 0):.2f}"))
    lineas.append(linea_clave_valor("Ingreso", reparacion.get("fecha_ingreso", "")))
    lineas.append(linea_clave_valor("Entrega", reparacion.get("fecha_entrega", "")))
    lineas.append(linea_clave_valor("Garantia", reparacion.get("garantia", "")))
    lineas.append("-" * ANCHO_TICKET)
    lineas.append("Falla:")
    lineas.append(una_linea(reparacion.get("falla", "")))
    lineas.append("Diagnostico:")
    lineas.append(una_linea(reparacion.get("diagnostico", "")))
    lineas.append("Obs:")
    lineas.append(una_linea(reparacion.get("observaciones", "")))
    lineas.append("-" * ANCHO_TICKET)
    lineas.append(centrar("Gracias por su visita"))
    contenido = "\n".join(lineas).strip()
    return contenido


def tamano_nombre(nombre):
    largo = len(texto(nombre))
    tamano = "18px"
    if largo > 18:
        tamano = "15px"
    if largo > 26:
        tamano = "12px"
    return tamano


def fila_html(clave, valor):
    salida = f"<div class='row'><span>{html.escape(texto(clave))}</span><strong>{html.escape(una_linea(valor, 20))}</strong></div>"
    return salida


def armar_ticket_html(reparacion):
    comercio = cargar_comercio()
    estado = ESTADOS.get(reparacion.get("estado"), reparacion.get("estado", ""))
    nombre = texto(comercio.get("nombre")) or "Reparaciones"
    equipo = " ".join([texto(reparacion.get("marca")), texto(reparacion.get("modelo"))]).strip()
    comercio_extra = ""
    logo_uri = imagen_a_data_uri(comercio.get("logo_ticket"))
    imagen_completa = texto(comercio.get("ticket_imagen_completa")) == "1"
    pie_ticket = texto(comercio.get("texto_pie_ticket")) or "Gracias por su visita"
    fuente = texto(comercio.get("ticket_fuente")) or "Courier New"
    if fuente not in ["Arial", "Verdana", "Courier New", "Tahoma", "Consolas"]:
        fuente = "Courier New"
    try:
        tamano_fuente = int(float(texto(comercio.get("ticket_tamano_fuente")) or "12"))
    except ValueError:
        tamano_fuente = 12
    tamano_fuente = max(10, min(18, tamano_fuente))

    for clave in ["documento", "telefono", "direccion", "email", "observaciones"]:
        valor = texto(comercio.get(clave))
        if valor:
            comercio_extra += f"<div class='center small nowrap'>{html.escape(una_linea(valor))}</div>"
    logo_html = f'<img class="ticket-logo" src="{html.escape(logo_uri)}" alt="Logo">' if logo_uri else ""
    marca_html = "" if (logo_html and imagen_completa) else f'<div class="center brand">{html.escape(nombre.upper())}</div>{comercio_extra}'
    encabezado_html = logo_html + marca_html

    cuerpo = f"""<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Ticket {html.escape(texto(reparacion.get('codigo', '')))}</title>
  <style>
    @page {{ size: 58mm auto; margin: 0; }}
    * {{ box-sizing: border-box; }}
    body {{ margin: 0; background: #fff; font-family: {html.escape(fuente)}, Consolas, 'Courier New', monospace; color: #000; display: flex; flex-direction: column; align-items: center; }}
    .ticket {{ width: 58mm; padding: 3mm 3mm 4mm; margin: 0 auto; font-size: {tamano_fuente}px; }}
    .ticket-logo {{ display: block; max-width: 100%; max-height: 34mm; object-fit: contain; margin: 0 auto 2mm auto; }}
    .center {{ text-align: center; }}
    .brand {{ font-weight: 800; font-size: {tamano_nombre(nombre)}; line-height: 1.05; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }}
    .title {{ font-weight: 800; font-size: 13px; margin: 5px 0; }}
    .small {{ font-size: 10px; line-height: 1.2; }}
    .nowrap {{ white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }}
    .line {{ border-top: 1px dashed #000; margin: 5px 0; }}
    .row {{ display: flex; justify-content: space-between; gap: 4px; font-size: 11px; line-height: 1.35; }}
    .row span {{ flex: 0 0 auto; }}
    .row strong {{ flex: 1 1 auto; text-align: right; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }}
    .block-title {{ font-weight: 800; font-size: 11px; margin-top: 5px; }}
    .block-text {{ font-size: 10px; white-space: normal; overflow-wrap: anywhere; line-height: 1.25; }}
    .actions {{ width: 58mm; padding: 6px; display: flex; gap: 6px; flex-wrap: wrap; }}
    button {{ border: 0; border-radius: 8px; padding: 8px 10px; background: #0e7490; color: white; font-weight: 700; cursor: pointer; }}
    @media print {{ .actions {{ display: none; }} body {{ width: auto; }} .ticket {{ margin: 0 auto; }} }}
  </style>
</head>
<body>
  <div class="actions"><button type="button" onclick="window.print()">Imprimir</button><button type="button" onclick="window.close()">Cerrar</button></div>
  <div class="ticket">
    {encabezado_html}
    <div class="line"></div>
    <div class="center title">TICKET DE REPARACION</div>
    <div class="line"></div>
    {fila_html("Codigo", reparacion.get("codigo", ""))}
    {fila_html("Cliente", reparacion.get("cliente_nombre", ""))}
    {fila_html("Telefono", reparacion.get("cliente_telefono", ""))}
    {fila_html("Equipo", equipo)}
    {fila_html("Estado", estado)}
    {fila_html("Precio", f"{reparacion.get('precio', 0):.2f}")}
    {fila_html("Ingreso", reparacion.get("fecha_ingreso", ""))}
    {fila_html("Entrega", reparacion.get("fecha_entrega", ""))}
    {fila_html("Garantia", reparacion.get("garantia", ""))}
    <div class="line"></div>
    <div class="block-title">Falla</div>
    <div class="block-text">{html.escape(texto(reparacion.get("falla", "")))}</div>
    <div class="block-title">Diagnostico</div>
    <div class="block-text">{html.escape(texto(reparacion.get("diagnostico", "")))}</div>
    <div class="block-title">Observaciones</div>
    <div class="block-text">{html.escape(texto(reparacion.get("observaciones", "")))}</div>
    <div class="line"></div>
    <div class="center small">{html.escape(pie_ticket)}</div>
  </div>
</body>
</html>"""
    return cuerpo
