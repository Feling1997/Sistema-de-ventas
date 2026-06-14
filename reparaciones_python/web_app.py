import json
import subprocess
import sys
import webbrowser
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import parse_qs, urlparse

from database import inicializar_base
from modelos import ESTADOS, validar_datos
from repositorio import ReparacionRepositorio
from tickets import armar_ticket_html


HOST = "127.0.0.1"
PORT = 8765
BASE_DIR = Path(__file__).resolve().parent
CONFIG_PATH = BASE_DIR / "comercio_config.json"
MAIN_CONFIG_PATH = BASE_DIR.parent / "almacenamiento" / "configuracion_sistema.json"

CREATE_NO_WINDOW = getattr(subprocess, "CREATE_NO_WINDOW", 0)


def config_vacia():
    datos = {
        "nombre": "",
        "telefono": "",
        "direccion": "",
        "documento": "",
        "email": "",
        "observaciones": "",
        "imagen_panel": "",
        "color_fondo": "#f4f6f8",
        "color_tarjetas": "#ffffff",
        "color_texto": "#203040",
        "color_texto_suave": "#657789",
        "color_borde": "#dbe3ea",
        "color_panel_inicio": "#155e75",
        "color_panel_inicio_2": "#48aaa5",
    }
    return datos


def cargar_config():
    datos = config_vacia()
    if MAIN_CONFIG_PATH.exists():
        try:
            contenido = json.loads(MAIN_CONFIG_PATH.read_text(encoding="utf-8"))
            if isinstance(contenido, dict):
                mapa = {
                    "nombre": "nombre_comercio",
                    "telefono": "telefonos",
                    "direccion": "domicilio",
                    "documento": "cuit",
                    "email": "email",
                    "observaciones": "sitio_web",
                    "imagen_panel": "imagen_panel",
                    "color_fondo": "color_fondo",
                    "color_tarjetas": "color_tarjetas",
                    "color_texto": "color_texto",
                    "color_texto_suave": "color_texto_suave",
                    "color_borde": "color_borde",
                    "color_panel_inicio": "color_panel_inicio",
                    "color_panel_inicio_2": "color_panel_inicio_2",
                }
                for clave, origen in mapa.items():
                    if origen in contenido:
                        datos[clave] = str(contenido.get(origen, "")).strip()
        except (OSError, json.JSONDecodeError):
            datos = config_vacia()
    if CONFIG_PATH.exists():
        try:
            contenido = json.loads(CONFIG_PATH.read_text(encoding="utf-8"))
            if isinstance(contenido, dict):
                datos.update({clave: str(contenido.get(clave, datos.get(clave, ""))).strip() for clave in datos if contenido.get(clave, "") != ""})
        except (OSError, json.JSONDecodeError):
            pass
    return datos


def css_color(valor, defecto):
    valor = str(valor or "").strip()
    if len(valor) == 7 and valor[0] == "#" and all(c in "0123456789abcdefABCDEF" for c in valor[1:]):
        return valor
    return defecto


def guardar_config(datos):
    guardado = False
    limpio = config_vacia()
    for clave in limpio:
        limpio[clave] = str(datos.get(clave, "")).strip()
    try:
        CONFIG_PATH.write_text(json.dumps(limpio, ensure_ascii=False, indent=2), encoding="utf-8")
        guardado = True
    except OSError:
        guardado = False
    return guardado


def listar_impresoras():
    impresoras = []
    try:
        res = subprocess.run(
            ["powershell", "-NoProfile", "-Command", "Get-Printer | Select-Object -ExpandProperty Name"],
            capture_output=True,
            text=True,
            timeout=4,
            creationflags=CREATE_NO_WINDOW,
        )
        if res.returncode == 0:
            impresoras = [linea.strip() for linea in res.stdout.splitlines() if linea.strip()]
    except Exception:
        impresoras = []
    return list(dict.fromkeys(impresoras))


def json_respuesta(datos):
    contenido = json.dumps(datos, ensure_ascii=False).encode("utf-8")
    return contenido


def leer_cuerpo(handler):
    largo = int(handler.headers.get("Content-Length", "0"))
    datos = {}
    if largo > 0:
        cuerpo = handler.rfile.read(largo).decode("utf-8")
        datos = json.loads(cuerpo)
    return datos


def normalizar_id(ruta):
    valor = 0
    partes = ruta.strip("/").split("/")
    if len(partes) >= 3 and partes[2].isdigit():
        valor = int(partes[2])
    return valor


class ReparacionesHandler(BaseHTTPRequestHandler):
    repositorio = ReparacionRepositorio()

    def do_GET(self):
        ruta = urlparse(self.path).path
        if ruta == "/":
            self._enviar_html(pagina_principal())
        elif ruta == "/api/config":
            self._enviar_json({"ok": True, "datos": cargar_config()})
        elif ruta == "/api/reparaciones":
            query = parse_qs(urlparse(self.path).query)
            orden = (query.get("orden", ["fecha"])[0] or "fecha").lower()
            direccion = (query.get("direccion", ["desc"])[0] or "desc").upper()
            self._enviar_json({"ok": True, "datos": self.repositorio.listar(orden, direccion)})
        elif ruta == "/api/impresoras":
            self._enviar_json({"ok": True, "impresoras": listar_impresoras()})
        elif ruta.startswith("/api/reparaciones/"):
            reparacion_id = normalizar_id(ruta)
            reparacion = self.repositorio.buscar_por_id(reparacion_id)
            self._enviar_json({"ok": reparacion is not None, "datos": reparacion})
        elif ruta.startswith("/ticket/"):
            self._enviar_ticket(ruta)
        else:
            self._enviar_json({"ok": False, "mensaje": "Ruta no encontrada"}, 404)

    def do_POST(self):
        ruta = urlparse(self.path).path
        if ruta == "/api/config":
            datos = leer_cuerpo(self)
            ok = guardar_config(datos)
            mensaje = "Datos del comercio guardados." if ok else "No se pudo guardar la configuracion."
            self._enviar_json({"ok": ok, "mensaje": mensaje})
        elif ruta == "/api/reparaciones":
            datos = leer_cuerpo(self)
            errores = validar_datos(datos)
            if errores:
                self._enviar_json({"ok": False, "mensaje": "\n".join(errores)}, 400)
            else:
                nuevo_id = self.repositorio.crear(datos)
                ok = nuevo_id > 0
                mensaje = "Reparacion creada correctamente." if ok else "No se pudo crear."
                self._enviar_json({"ok": ok, "mensaje": mensaje, "id": nuevo_id})
        else:
            self._enviar_json({"ok": False, "mensaje": "Ruta no encontrada"}, 404)

    def do_PUT(self):
        ruta = urlparse(self.path).path
        reparacion_id = normalizar_id(ruta)
        if reparacion_id > 0:
            datos = leer_cuerpo(self)
            errores = validar_datos(datos)
            if errores:
                self._enviar_json({"ok": False, "mensaje": "\n".join(errores)}, 400)
            else:
                ok = self.repositorio.actualizar(reparacion_id, datos)
                mensaje = "Reparacion actualizada correctamente." if ok else "No se pudo actualizar."
                self._enviar_json({"ok": ok, "mensaje": mensaje})
        else:
            self._enviar_json({"ok": False, "mensaje": "ID invalido"}, 400)

    def do_DELETE(self):
        ruta = urlparse(self.path).path
        reparacion_id = normalizar_id(ruta)
        if reparacion_id > 0:
            ok = self.repositorio.eliminar(reparacion_id)
            mensaje = "Reparacion eliminada correctamente." if ok else "No se pudo eliminar."
            self._enviar_json({"ok": ok, "mensaje": mensaje})
        else:
            self._enviar_json({"ok": False, "mensaje": "ID invalido"}, 400)

    def log_message(self, formato, *args):
        pass

    def _enviar_html(self, contenido):
        datos = contenido.encode("utf-8")
        self.send_response(200)
        self.send_header("Content-Type", "text/html; charset=utf-8")
        self.send_header("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0")
        self.send_header("Pragma", "no-cache")
        self.send_header("Content-Length", str(len(datos)))
        self.end_headers()
        self.wfile.write(datos)

    def _enviar_json(self, datos, codigo=200):
        contenido = json_respuesta(datos)
        self.send_response(codigo)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0")
        self.send_header("Pragma", "no-cache")
        self.send_header("Content-Length", str(len(contenido)))
        self.end_headers()
        self.wfile.write(contenido)

    def _enviar_ticket(self, ruta):
        query = parse_qs(urlparse(self.path).query)
        auto_print = query.get("auto_print", ["0"])[0] == "1"
        partes = ruta.strip("/").split("/")
        reparacion_id = 0
        if len(partes) == 2 and partes[1].isdigit():
            reparacion_id = int(partes[1])

        reparacion = self.repositorio.buscar_por_id(reparacion_id)
        if reparacion:
            contenido_html = armar_ticket_html(reparacion)
            if auto_print:
                contenido_html = contenido_html.replace("</body>", "<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 250); });</script></body>")
            contenido = contenido_html.encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "text/html; charset=utf-8")
            self.send_header("Cache-Control", "no-store, no-cache, must-revalidate, max-age=0")
            self.send_header("Pragma", "no-cache")
            self.send_header("Content-Length", str(len(contenido)))
            self.end_headers()
            self.wfile.write(contenido)
        else:
            self._enviar_json({"ok": False, "mensaje": "Reparacion no encontrada"}, 404)

def pagina_principal():
    estados = "".join(f"<option value='{clave}'>{nombre}</option>" for clave, nombre in ESTADOS.items())
    comercio_visual = cargar_config()
    imagen_panel = comercio_visual.get("imagen_panel", "")
    imagen_panel_url = ""
    if imagen_panel:
        if imagen_panel.startswith("http://") or imagen_panel.startswith("https://"):
            imagen_panel_url = imagen_panel
        else:
            imagen_panel_url = "http://localhost/VENTAS/" + imagen_panel.lstrip("/")
    panel_image_css = "none" if not imagen_panel_url else "url('" + imagen_panel_url.replace("'", "%27") + "')"
    color_fondo = css_color(comercio_visual.get("color_fondo"), "#f4f6f8")
    color_tarjetas = css_color(comercio_visual.get("color_tarjetas"), "#ffffff")
    color_texto = css_color(comercio_visual.get("color_texto"), "#203040")
    color_texto_suave = css_color(comercio_visual.get("color_texto_suave"), "#657789")
    color_borde = css_color(comercio_visual.get("color_borde"), "#dbe3ea")
    color_panel_inicio = css_color(comercio_visual.get("color_panel_inicio"), "#155e75")
    color_panel_inicio_2 = css_color(comercio_visual.get("color_panel_inicio_2"), "#48aaa5")
    html = f"""<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reparaciones</title>
  <style>
    :root {{
      --azul:#071826; --panel:{color_tarjetas}; --fondo:{color_fondo}; --linea:{color_borde};
      --texto:{color_texto}; --muted:{color_texto_suave}; --cyan:#0f6f8b; --verde:#15803d;
      --amarillo:#b77900; --rojo:#dc2626; --violeta:#6d28d9; --soft:#f8fafc;
      --home-a:{color_panel_inicio}; --home-b:{color_panel_inicio_2};
      --panel-image:{panel_image_css};
    }}
    * {{ box-sizing:border-box; }}
    body {{ margin:0; font-family:Segoe UI, Arial, sans-serif; color:var(--texto); background:var(--fondo); }}
    .embedded .topbar {{ display:none; }}
    .topbar {{ min-height:44px; background:var(--panel); border:1px solid var(--linea); border-radius:14px; display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin:14px 18px 0; padding:7px; box-shadow:0 10px 24px rgba(15,23,42,.08); }}
    .topbar button {{ border:1px solid #cbd5e1; color:#162233; background:#fff; min-height:32px; padding:6px 12px; border-radius:8px; font-weight:700; cursor:pointer; }}
    .topbar button:hover, .topbar button.activo {{ background:#071826; border-color:#071826; color:#fff; }}
    main {{ padding:0 20px 24px; }}
    .hero {{ position:relative; overflow:hidden; border-radius:0 0 18px 18px; min-height:76px; background-color:var(--home-a); background-image:linear-gradient(115deg,rgba(0,0,0,.46),rgba(0,0,0,.14)),var(--panel-image),linear-gradient(115deg,var(--home-a),var(--home-b)); background-size:cover,100% 100%,cover; background-position:center; background-repeat:no-repeat; color:#fff; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 20px; box-shadow:0 16px 36px rgba(15,23,42,.15); }}
    .hero-left {{ display:flex; align-items:flex-start; gap:8px; }}
    .hero-icon {{ width:40px; height:40px; border-radius:12px; background:#f59e0b; color:#111827; display:grid; place-items:center; font-weight:900; flex:0 0 auto; }}
    .hero strong {{ display:block; color:#fff; font-size:15px; line-height:1; }}
    .hero small {{ display:block; margin-top:3px; color:rgba(255,255,255,.78); font-size:11px; letter-spacing:.08em; text-transform:uppercase; }}
    .hero h1 {{ margin:4px 0 2px; font-size:22px; line-height:1.08; font-weight:800; }}
    .hero p {{ margin:0; color:rgba(255,255,255,.88); font-size:12px; }}
    .hero .quick {{ border:1px solid rgba(255,255,255,.35); background:rgba(255,255,255,.18); color:white; min-height:38px; padding:9px 16px; border-radius:12px; font-weight:800; cursor:pointer; white-space:nowrap; }}
    .vista {{ display:none; }}
    .vista.activa {{ display:block; }}
    .cards {{ display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-top:14px; }}
    .card {{ background:var(--panel); border:1px solid var(--linea); border-radius:18px; min-height:136px; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:18px; box-shadow:0 14px 30px rgba(15,23,42,.08); cursor:pointer; transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease; }}
    .card:hover {{ transform:translateY(-4px); border-color:rgba(31,111,139,.25); box-shadow:0 18px 36px rgba(31,48,64,.14); }}
    .icon {{ width:50px; height:50px; border-radius:14px; color:white; display:grid; place-items:center; font-size:19px; font-weight:900; margin-bottom:8px; box-shadow:inset 0 1px 0 rgba(255,255,255,.22); }}
    .card h3 {{ margin:0 0 4px; font-size:14px; font-weight:800; }}
    .card p {{ margin:0; color:var(--muted); font-size:12px; line-height:1.2; }}
    .card-link {{ text-decoration:none; color:inherit; }}
    .section-title {{ display:flex; justify-content:space-between; align-items:end; margin:8px 0 12px; }}
    .section-title h2 {{ margin:0; font-size:22px; }}
    .section-title p {{ margin:4px 0 0; color:var(--muted); }}
    .panel {{ background:white; border:1px solid var(--linea); border-radius:14px; padding:16px; box-shadow:0 10px 24px rgba(15,23,42,.07); }}
    form .grid {{ display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }}
    label {{ font-size:13px; color:#334155; font-weight:600; display:block; margin-bottom:5px; }}
    input, select, textarea {{ width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:9px 10px; font:inherit; background:white; }}
    textarea {{ min-height:86px; resize:vertical; }}
    .full {{ grid-column:span 4; }}
    .acciones {{ margin-top:14px; display:flex; flex-wrap:wrap; gap:8px; }}
    .btn {{ border:0; min-height:38px; padding:9px 14px; border-radius:8px; color:white; font-weight:800; cursor:pointer; background:var(--cyan); }}
    .btn.sec {{ background:#475569; }} .btn.danger {{ background:var(--rojo); }} .btn.ok {{ background:var(--verde); }}
    .metrics {{ display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:12px; }}
    .metric {{ background:white; border:1px solid var(--linea); border-radius:12px; padding:12px; border-left:5px solid var(--cyan); }}
    .metric strong {{ display:block; font-size:24px; }} .metric span {{ color:var(--muted); font-size:13px; }}
    .tools {{ display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:12px; }}
    .tools input {{ max-width:360px; min-width:260px; }}
    .tools select {{ max-width:190px; }}
    .table-wrap {{ overflow:auto; border:1px solid var(--linea); border-radius:12px; }}
    table {{ width:100%; border-collapse:collapse; background:white; }}
    th,td {{ padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:13px; }}
    th {{ background:#e8edf3; font-weight:800; white-space:nowrap; }}
    th[data-orden] {{ cursor:pointer; }}
    th[data-orden] span {{ color:var(--cyan); margin-left:4px; }}
    tr:hover {{ background:#f8fafc; cursor:pointer; }}
    tr.sel {{ outline:2px solid #38bdf8; background:#ecfeff; }}
    .layout-consulta {{ display:grid; grid-template-columns:minmax(0,1fr) 330px; gap:12px; }}
    .detail {{ position:sticky; top:12px; align-self:start; white-space:pre-wrap; color:#334155; line-height:1.45; max-height:calc(100vh - 24px); overflow:auto; }}
    .badge {{ display:inline-block; padding:5px 9px; border-radius:999px; color:white; font-size:12px; font-weight:700; }}
    .PENDIENTE {{ background:var(--amarillo); }} .ENTREGADO {{ background:var(--violeta); }}
    .modal {{ position:fixed; inset:0; background:rgba(15,23,42,.48); display:none; align-items:center; justify-content:center; padding:18px; z-index:50; }}
    .modal.abierto {{ display:flex; }}
    .modal-box {{ width:min(460px,100%); background:white; border-radius:14px; border:1px solid var(--linea); box-shadow:0 20px 60px rgba(15,23,42,.22); padding:16px; }}
    .modal-head {{ display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:12px; }}
    .modal-head h3 {{ margin:0; font-size:18px; }}
    .modal-actions {{ display:flex; justify-content:flex-end; gap:8px; margin-top:14px; }}
    .muted {{ color:var(--muted); font-size:13px; margin:8px 0 0; }}
    @media (max-width:1100px) {{ .cards {{ grid-template-columns:repeat(2,minmax(0,1fr)); }} }}
    @media (max-width:900px) {{ .metrics,form .grid,.layout-consulta {{ grid-template-columns:1fr; }} .detail {{ position:static; max-height:none; }} .full {{ grid-column:span 1; }} .hero {{ display:none; }} .tools input,.tools select {{ max-width:none; min-width:0; }} }}
    @media (max-width:620px) {{ .cards {{ grid-template-columns:1fr; }} .topbar,main {{ margin-left:10px; margin-right:10px; padding-left:10px; padding-right:10px; }} .acciones .btn,.tools .btn {{ width:100%; }} }}
  </style>
  <script>
    try {{
      if (window.self !== window.top)
        document.documentElement.classList.add('embedded');
    }} catch (e) {{
      document.documentElement.classList.add('embedded');
    }}
  </script>
</head>
<body>
  <nav class="topbar">
    <button data-view="inicio" class="activo">Inicio</button>
    <button data-view="nueva">Nueva reparacion</button>
    <button data-view="consultas" data-estado="TODOS">Consultas</button>
    <button data-view="consultas" data-estado="PENDIENTE">Pendientes</button>
    <button data-view="consultas" data-estado="ENTREGADO">Entregados</button>
  </nav>

  <main>
    <section id="inicio" class="vista activa">
      <div class="hero">
        <div class="hero-left">
          <div class="hero-icon">REP</div>
          <div>
          <strong>Reparaciones</strong><small>Sistema de gestion</small>
          <h1>Panel principal</h1>
          <p>Control de ingresos, consultas, pendientes, entregas y tickets del taller.</p>
          </div>
        </div>
        <button class="quick" data-view="nueva">Acceso rapido</button>
      </div>
      <div class="cards">
        <article class="card" data-view="nueva"><div class="icon" style="background:#0f766e">+</div><h3>Nueva reparacion</h3><p>Alta de cliente, equipo, falla, garantia y precio.</p></article>
        <article class="card" data-view="consultas" data-estado="TODOS"><div class="icon" style="background:#2563eb">?</div><h3>Consultas</h3><p>Listado completo con busqueda, detalle y edicion.</p></article>
        <article class="card" data-view="consultas" data-estado="PENDIENTE"><div class="icon" style="background:#b77900">!</div><h3>Pendientes</h3><p>Trabajos activos que todavia no fueron entregados.</p></article>
        <article class="card" data-view="consultas" data-estado="ENTREGADO"><div class="icon" style="background:#6d28d9">OK</div><h3>Entregados</h3><p>Historial de reparaciones finalizadas y tickets.</p></article>
        <a class="card card-link" href="http://localhost/VENTAS/publico/index.php?c=configuraciones&a=backup" target="_top"><div class="icon" style="background:#16a34a">BK</div><h3>Backup</h3><p>Copias completas a pendrive, carpeta, Drive o Backblaze.</p></a>
      </div>
    </section>

    <section id="nueva" class="vista">
      <div class="section-title"><div><h2>Nueva reparacion</h2><p>Carga fija de ingresos al taller.</p></div></div>
      <form id="formReparacion" class="panel">
        <input type="hidden" id="id">
        <div class="grid">
          <div><label>Cliente *</label><input id="cliente_nombre" required></div>
          <div><label>Telefono</label><input id="cliente_telefono"></div>
          <div><label>Marca</label><input id="marca"></div>
          <div><label>Modelo</label><input id="modelo"></div>
          <div><label>Garantia</label><input id="garantia" placeholder="30 dias, 3 meses"></div>
          <div><label>Precio</label><input id="precio" type="number" step="0.01" min="0"></div>
          <div><label>Estado</label><select id="estado">{estados}</select></div>
          <div><label>Ingreso</label><input id="fecha_ingreso" type="date"></div>
          <div><label>Entrega</label><input id="fecha_entrega" type="date"></div>
          <div class="full"><label>Falla reportada</label><textarea id="falla"></textarea></div>
          <div class="full"><label>Diagnostico</label><textarea id="diagnostico"></textarea></div>
          <div class="full"><label>Observaciones</label><textarea id="observaciones"></textarea></div>
        </div>
        <div class="acciones">
          <button class="btn ok" type="submit">Guardar</button>
          <button class="btn" type="button" id="guardarImprimir">Guardar e imprimir</button>
          <button class="btn sec" type="button" id="limpiar">Limpiar</button>
          <button class="btn sec" type="button" data-view="consultas" data-estado="TODOS">Ir a consultas</button>
          <button class="btn danger" type="button" id="eliminar">Eliminar</button>
        </div>
      </form>
    </section>

    <section id="consultas" class="vista">
      <div class="section-title"><div><h2>Consultas</h2><p>Busqueda, filtros, estados, edicion y tickets.</p></div></div>
      <div class="metrics" id="metrics"></div>
      <div class="panel">
        <div class="tools">
          <input id="buscar" placeholder="Buscar por codigo, cliente, telefono, marca o modelo...">
          <select id="filtro"><option value="TODOS">Todos</option>{estados}</select>
          <button class="btn sec" id="limpiarFiltros">Limpiar filtros</button>
          <button class="btn" id="editar">Editar seleccionado</button>
          <button class="btn ok" id="ticket">Ticket</button>
        </div>
        <div class="layout-consulta">
          <div class="table-wrap"><table><thead><tr><th data-orden="codigo">Codigo <span></span></th><th data-orden="cliente">Cliente <span></span></th><th>Telefono</th><th>Equipo</th><th data-orden="estado">Estado <span></span></th><th data-orden="precio">Precio <span></span></th><th data-orden="fecha">Ingreso <span></span></th><th data-orden="entrega">Entrega <span></span></th></tr></thead><tbody id="tabla"></tbody></table></div>
          <aside class="panel detail" id="detalle">Seleccione una reparacion para ver el detalle.</aside>
        </div>
      </div>
    </section>

    <section id="config" class="vista">
      <div class="section-title"><div><h2>Configuracion</h2><p>Datos locales del programa y del comercio.</p></div></div>
      <form id="formConfig" class="panel">
        <div class="grid">
          <div><label>Nombre del comercio</label><input id="cfg_nombre" placeholder="Opcional"></div>
          <div><label>Telefono</label><input id="cfg_telefono" placeholder="Opcional"></div>
          <div><label>RUC / Documento</label><input id="cfg_documento" placeholder="Opcional"></div>
          <div><label>Email</label><input id="cfg_email" type="email" placeholder="Opcional"></div>
          <div class="full"><label>Direccion</label><input id="cfg_direccion" placeholder="Opcional"></div>
          <div class="full"><label>Observaciones del comercio</label><textarea id="cfg_observaciones" placeholder="Opcional"></textarea></div>
        </div>
        <div class="acciones">
          <button class="btn ok" type="submit">Guardar configuracion</button>
          <button class="btn sec" type="button" id="limpiarConfig">Limpiar</button>
        </div>
      </form>
      <div class="panel" style="margin-top:12px">
        <p><strong>Base de datos:</strong> reparaciones.db</p>
        <p><strong>Tickets:</strong> carpeta tickets</p>
        <p><strong>Configuracion:</strong> comercio_config.json</p>
        <p><strong>Servidor local:</strong> Python sin librerias externas</p>
      </div>
    </section>
  </main>

  <div class="modal" id="modalImpresora">
    <div class="modal-box">
      <div class="modal-head">
        <h3>Comprobante</h3>
        <button class="btn sec" type="button" id="cerrarImpresora">Cerrar</button>
      </div>
      <label>Impresora disponible</label>
      <select id="impresoraSelect"><option value="">Cargando impresoras...</option></select>
      <p class="muted">Podes guardar un PDF para enviar por WhatsApp o abrir el dialogo de impresion y elegir impresora.</p>
      <div class="modal-actions">
        <button class="btn sec" type="button" id="cancelarImpresora">Cancelar</button>
        <button class="btn ok" type="button" id="confirmarImpresora">Imprimir</button>
      </div>
    </div>
  </div>

<script>
const estados = {json.dumps(ESTADOS, ensure_ascii=False)};
let reparaciones = [];
let seleccion = null;
let ordenActual = sessionStorage.getItem('orden:reparaciones:orden') || 'fecha';
let direccionActual = sessionStorage.getItem('orden:reparaciones:direccion') || 'desc';

function hoy() {{ return new Date().toISOString().slice(0, 10); }}
function qs(id) {{ return document.getElementById(id); }}
function qsa(selector) {{ return Array.from(document.querySelectorAll(selector)); }}

function vista(nombre, estado='TODOS') {{
  document.querySelectorAll('.vista').forEach(v => v.classList.remove('activa'));
  qs(nombre).classList.add('activa');
  document.querySelectorAll('.topbar button').forEach(b => b.classList.remove('activo'));
  document.querySelectorAll(`[data-view="${{nombre}}"]`).forEach(b => b.classList.add('activo'));
  if (nombre === 'nueva') limpiarFormulario();
  if (nombre === 'consultas') {{ qs('filtro').value = estado; cargar(); }}
  if (nombre === 'config') cargarConfig();
}}

document.addEventListener('click', e => {{
  const item = e.target.closest('[data-view]');
  if (item) vista(item.dataset.view, item.dataset.estado || 'TODOS');
}});

window.addEventListener('message', e => {{
  if (e.origin !== 'http://localhost' && e.origin !== 'http://127.0.0.1')
    return;
  const data = e.data || {{}};
  if (data.tipo === 'reparaciones:vista')
    vista(data.vista || 'inicio', data.estado || 'TODOS');
}});

function datosForm() {{
  const datos = {{}};
  ['cliente_nombre','cliente_telefono','marca','modelo','garantia','precio','estado','fecha_ingreso','fecha_entrega','falla','diagnostico','observaciones'].forEach(id => datos[id] = qs(id).value.trim());
  datos.activo = true;
  return datos;
}}

function datosConfig() {{
  const datos = {{
    nombre: qs('cfg_nombre').value.trim(),
    telefono: qs('cfg_telefono').value.trim(),
    direccion: qs('cfg_direccion').value.trim(),
    documento: qs('cfg_documento').value.trim(),
    email: qs('cfg_email').value.trim(),
    observaciones: qs('cfg_observaciones').value.trim()
  }};
  return datos;
}}

function limpiarConfig() {{
  ['cfg_nombre','cfg_telefono','cfg_direccion','cfg_documento','cfg_email','cfg_observaciones'].forEach(id => qs(id).value = '');
}}

async function cargarConfig() {{
  const resp = await fetch('/api/config');
  const data = await resp.json();
  const cfg = data.datos || {{}};
  qs('cfg_nombre').value = cfg.nombre || '';
  qs('cfg_telefono').value = cfg.telefono || '';
  qs('cfg_direccion').value = cfg.direccion || '';
  qs('cfg_documento').value = cfg.documento || '';
  qs('cfg_email').value = cfg.email || '';
  qs('cfg_observaciones').value = cfg.observaciones || '';
}}

async function guardarConfig(e) {{
  e.preventDefault();
  const resp = await fetch('/api/config', {{method:'POST', headers:{{'Content-Type':'application/json'}}, body:JSON.stringify(datosConfig())}});
  const data = await resp.json();
  alert(data.mensaje);
}}

function limpiarFormulario() {{
  qs('formReparacion').reset();
  qs('id').value = '';
  qs('fecha_ingreso').value = hoy();
  qs('estado').value = 'PENDIENTE';
  qs('cliente_nombre').focus();
}}

async function guardar(e) {{
  e.preventDefault();
  const data = await guardarReparacionBase();
  alert(data.mensaje);
  if (data.ok) {{
    limpiarFormulario();
    cargar();
  }}
}}

async function guardarReparacionBase() {{
  const id = qs('id').value;
  const metodo = id ? 'PUT' : 'POST';
  const url = id ? `/api/reparaciones/${{id}}` : '/api/reparaciones';
  const resp = await fetch(url, {{method: metodo, headers: {{'Content-Type':'application/json'}}, body: JSON.stringify(datosForm())}});
  const data = await resp.json();
  if (data.ok && !data.id && id) data.id = Number(id);
  return data;
}}

function cerrarModalImpresora() {{
  qs('modalImpresora').classList.remove('abierto');
}}

async function abrirModalImpresora() {{
  qs('modalImpresora').classList.add('abierto');
  qs('impresoraSelect').innerHTML = '<option value="">Cargando impresoras...</option>';
  try {{
    const resp = await fetch('/api/impresoras');
    const data = await resp.json();
    const impresoras = data.impresoras || [];
    if (!impresoras.length) {{
      qs('impresoraSelect').innerHTML = '<option value="">No se detectaron impresoras</option>';
      return;
    }}
    qs('impresoraSelect').innerHTML = impresoras.map(nombre => `<option value="${{nombre.replace(/"/g, '&quot;')}}">${{nombre}}</option>`).join('');
  }} catch (err) {{
    qs('impresoraSelect').innerHTML = '<option value="">No se pudieron cargar impresoras</option>';
  }}
}}

async function guardarEImprimir() {{
  const data = await guardarReparacionBase();
  alert(data.mensaje);
  if (data.ok && data.id) {{
    cerrarModalImpresora();
    window.open(`/ticket/${{data.id}}?auto_print=1`, '_blank');
    limpiarFormulario();
    cargar();
  }}
}}

async function eliminar() {{
  const id = qs('id').value || (seleccion && seleccion.id);
  if (id && confirm('Eliminar reparacion seleccionada?')) {{
    const resp = await fetch(`/api/reparaciones/${{id}}`, {{method:'DELETE'}});
    const data = await resp.json();
    alert(data.mensaje);
    if (data.ok) {{ limpiarFormulario(); cargar(); }}
  }}
}}

async function cargar() {{
  const resp = await fetch(`/api/reparaciones?orden=${{encodeURIComponent(ordenActual)}}&direccion=${{encodeURIComponent(direccionActual)}}`);
  const data = await resp.json();
  reparaciones = data.datos || [];
  metricas();
  render();
  actualizarOrdenVisual();
}}

function actualizarOrdenVisual() {{
  qsa('th[data-orden]').forEach(th => {{
    const span = th.querySelector('span');
    if (!span) return;
    span.textContent = th.dataset.orden === ordenActual ? (direccionActual === 'asc' ? '↑' : '↓') : '↕';
  }});
}}

function cambiarOrden(campo) {{
  if (ordenActual !== campo) {{
    ordenActual = campo;
    direccionActual = 'asc';
  }} else if (direccionActual === 'asc') {{
    direccionActual = 'desc';
  }} else {{
    ordenActual = 'fecha';
    direccionActual = 'desc';
  }}
  sessionStorage.setItem('orden:reparaciones:orden', ordenActual);
  sessionStorage.setItem('orden:reparaciones:direccion', direccionActual);
  cargar();
}}

function metricas() {{
  const m = {{total:reparaciones.length, pendiente:0, entregado:0}};
  reparaciones.forEach(r => {{
    if (r.estado === 'PENDIENTE') m.pendiente++;
    if (r.estado === 'ENTREGADO') m.entregado++;
  }});
  qs('metrics').innerHTML = `
    <div class="metric"><strong>${{m.total}}</strong><span>Total activas</span></div>
    <div class="metric" style="border-left-color:#ca8a04"><strong>${{m.pendiente}}</strong><span>Pendientes</span></div>
    <div class="metric" style="border-left-color:#7c3aed"><strong>${{m.entregado}}</strong><span>Entregados</span></div>`;
}}

function filtradas() {{
  const texto = qs('buscar').value.toLowerCase().trim();
  const estado = qs('filtro').value;
  const lista = reparaciones.filter(r => {{
    const coincideEstado = estado === 'TODOS' || r.estado === estado;
    const contenido = [r.codigo,r.cliente_nombre,r.cliente_telefono,r.marca,r.modelo,r.falla,r.garantia].join(' ').toLowerCase();
    return coincideEstado && (!texto || contenido.includes(texto));
  }});
  return lista;
}}

function render() {{
  const lista = filtradas();
  qs('tabla').innerHTML = lista.map(r => `<tr data-id="${{r.id}}" class="${{seleccion && seleccion.id === r.id ? 'sel' : ''}}">
    <td>${{r.codigo}}</td><td>${{r.cliente_nombre}}</td><td>${{r.cliente_telefono || ''}}</td>
    <td>${{[r.marca,r.modelo].filter(Boolean).join(' ')}}</td><td><span class="badge ${{r.estado}}">${{estados[r.estado] || r.estado}}</span></td>
    <td>${{Number(r.precio || 0).toFixed(2)}}</td><td>${{r.fecha_ingreso || ''}}</td><td>${{r.fecha_entrega || ''}}</td>
  </tr>`).join('');
}}

function detalle(r) {{
  qs('detalle').textContent = `Codigo: ${{r.codigo}}
Cliente: ${{r.cliente_nombre}}
Telefono: ${{r.cliente_telefono || ''}}

Equipo: ${{[r.marca,r.modelo].filter(Boolean).join(' ')}}

Estado: ${{estados[r.estado] || r.estado}}
Precio: ${{Number(r.precio || 0).toFixed(2)}}
Garantia: ${{r.garantia || ''}}
Ingreso: ${{r.fecha_ingreso || ''}}
Entrega: ${{r.fecha_entrega || ''}}

Falla:
${{r.falla || ''}}

Diagnostico:
${{r.diagnostico || ''}}

Observaciones:
${{r.observaciones || ''}}`;
}}

function editarSeleccionado() {{
  if (seleccion) {{
    vista('nueva');
    Object.keys(seleccion).forEach(k => {{ if (qs(k)) qs(k).value = seleccion[k] || ''; }});
    qs('id').value = seleccion.id;
  }} else {{
    alert('Seleccione una reparacion.');
  }}
}}

qs('formReparacion').addEventListener('submit', guardar);
qs('formConfig').addEventListener('submit', guardarConfig);
qs('guardarImprimir').addEventListener('click', abrirModalImpresora);
qs('cerrarImpresora').addEventListener('click', cerrarModalImpresora);
qs('cancelarImpresora').addEventListener('click', cerrarModalImpresora);
qs('confirmarImpresora').addEventListener('click', guardarEImprimir);
qs('limpiar').addEventListener('click', limpiarFormulario);
qs('limpiarConfig').addEventListener('click', limpiarConfig);
qs('eliminar').addEventListener('click', eliminar);
qs('buscar').addEventListener('input', render);
qs('filtro').addEventListener('change', render);
qsa('th[data-orden]').forEach(th => th.addEventListener('click', () => cambiarOrden(th.dataset.orden)));
qs('limpiarFiltros').addEventListener('click', () => {{ qs('buscar').value=''; qs('filtro').value='TODOS'; render(); }});
qs('editar').addEventListener('click', editarSeleccionado);
qs('ticket').addEventListener('click', () => {{ if (seleccion) window.open(`/ticket/${{seleccion.id}}`, '_blank'); else alert('Seleccione una reparacion.'); }});
qs('tabla').addEventListener('click', e => {{
  const tr = e.target.closest('tr');
  if (tr) {{
    seleccion = reparaciones.find(r => r.id === Number(tr.dataset.id));
    detalle(seleccion);
    render();
  }}
}});
limpiarFormulario();
</script>
</body>
</html>"""
    return html


def iniciar():
    inicializar_base()
    servidor = ThreadingHTTPServer((HOST, PORT), ReparacionesHandler)
    url = f"http://{HOST}:{PORT}"
    if "--no-browser" not in sys.argv:
        webbrowser.open(url)
    print(f"Sistema de reparaciones abierto en {url}")
    servidor.serve_forever()


if __name__ == "__main__":
    iniciar()
