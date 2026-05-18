import tkinter as tk
from tkinter import messagebox, ttk

from modelos import ESTADOS, fecha_hoy, validar_datos
from repositorio import ReparacionRepositorio
from tickets import crear_ticket


class ReparacionesApp(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title("Sistema de Reparaciones")
        self.geometry("1220x760")
        self.minsize(1080, 660)
        self.configure(bg="#eef2f7")

        self.repositorio = ReparacionRepositorio()
        self.reparacion_actual_id = None
        self.reparaciones_cache = []
        self.campos = {}
        self.estado_var = tk.StringVar(value="PENDIENTE")
        self.activo_var = tk.BooleanVar(value=True)
        self.busqueda_var = tk.StringVar(value="")
        self.filtro_estado_var = tk.StringVar(value="TODOS")
        self.estado_sistema_var = tk.StringVar(value="Listo")

        self._configurar_estilos()
        self._crear_layout()
        self._mostrar_inicio()

    def _configurar_estilos(self):
        estilo = ttk.Style()
        estilo.theme_use("clam")
        estilo.configure("TFrame", background="#eef2f7")
        estilo.configure("Panel.TFrame", background="#ffffff")
        estilo.configure("TLabel", background="#eef2f7", foreground="#0f172a", font=("Segoe UI", 9))
        estilo.configure("Panel.TLabel", background="#ffffff", foreground="#0f172a", font=("Segoe UI", 9))
        estilo.configure("Titulo.TLabel", background="#eef2f7", foreground="#ffffff", font=("Segoe UI", 20, "bold"))
        estilo.configure("Subtitulo.TLabel", background="#eef2f7", foreground="#dbeafe", font=("Segoe UI", 9))
        estilo.configure("Seccion.TLabel", background="#eef2f7", foreground="#0f172a", font=("Segoe UI", 18, "bold"))
        estilo.configure("Ayuda.TLabel", background="#eef2f7", foreground="#64748b", font=("Segoe UI", 9))
        estilo.configure("CardTitle.TLabel", background="#ffffff", foreground="#0f172a", font=("Segoe UI", 11, "bold"))
        estilo.configure("CardText.TLabel", background="#ffffff", foreground="#475569", font=("Segoe UI", 9))
        estilo.configure("CardValue.TLabel", background="#ffffff", foreground="#0f172a", font=("Segoe UI", 20, "bold"))
        estilo.configure("TLabelframe", background="#ffffff", bordercolor="#d7dee8", relief="solid")
        estilo.configure("TLabelframe.Label", background="#ffffff", foreground="#0f172a", font=("Segoe UI", 10, "bold"))
        estilo.configure("TEntry", fieldbackground="#ffffff", padding=5)
        estilo.configure("TCombobox", fieldbackground="#ffffff", padding=5)
        estilo.configure("TButton", font=("Segoe UI", 9), padding=(10, 6))
        estilo.configure("Primary.TButton", background="#0e7490", foreground="#ffffff")
        estilo.map("Primary.TButton", background=[("active", "#0891b2")])
        estilo.configure("Danger.TButton", background="#dc2626", foreground="#ffffff")
        estilo.map("Danger.TButton", background=[("active", "#b91c1c")])
        estilo.configure("Treeview", background="#ffffff", fieldbackground="#ffffff", rowheight=30, font=("Segoe UI", 9))
        estilo.configure("Treeview.Heading", background="#e5e7eb", foreground="#111827", font=("Segoe UI", 9, "bold"))
        estilo.map("Treeview", background=[("selected", "#bae6fd")], foreground=[("selected", "#0f172a")])

    def _crear_layout(self):
        self.barra = tk.Frame(self, bg="#0f2f45", height=38)
        self.barra.pack(fill=tk.X)
        self.barra.pack_propagate(False)
        self._crear_barra_superior()

        self.contenido = tk.Frame(self, bg="#eef2f7")
        self.contenido.pack(fill=tk.BOTH, expand=True)

        self.estado_barra = ttk.Frame(self)
        self.estado_barra.pack(fill=tk.X)
        ttk.Label(self.estado_barra, textvariable=self.estado_sistema_var, style="Ayuda.TLabel").pack(anchor=tk.W, padx=10, pady=4)

    def _crear_barra_superior(self):
        botones = [
            ("Inicio", self._mostrar_inicio, "#123b56"),
            ("Nueva reparacion", self._mostrar_nueva, "#0f766e"),
            ("Consultas", lambda: self._mostrar_consultas("TODOS"), "#164e63"),
            ("Pendientes", lambda: self._mostrar_consultas("PENDIENTE"), "#854d0e"),
            ("Entregados", lambda: self._mostrar_consultas("ENTREGADO"), "#6d28d9"),
            ("Config", self._mostrar_config, "#334155"),
            ("admin", self._mostrar_inicio, "#475569"),
            ("Salir", self.destroy, "#111827"),
        ]

        for texto, comando, color in botones:
            boton = tk.Button(
                self.barra,
                text=texto,
                command=comando,
                bg=color,
                fg="#ffffff",
                activebackground="#0ea5e9",
                activeforeground="#ffffff",
                bd=0,
                padx=12,
                pady=6,
                font=("Segoe UI", 9),
                cursor="hand2",
            )
            boton.pack(side=tk.LEFT, padx=(2, 0), pady=2)

    def _limpiar_contenido(self):
        for widget in self.contenido.winfo_children():
            widget.destroy()

    def _mostrar_inicio(self):
        self._limpiar_contenido()
        self.reparacion_actual_id = None
        self.campos = {}
        self._crear_banner_inicio()
        self._crear_tarjetas_inicio()
        self._mostrar_estado("Panel principal.")

    def _crear_banner_inicio(self):
        banner = tk.Frame(self.contenido, bg="#1f7a8c", height=138)
        banner.pack(fill=tk.X, padx=0, pady=(10, 18))
        banner.pack_propagate(False)

        icono = tk.Label(banner, text="REP", bg="#f59e0b", fg="#111827", font=("Segoe UI", 11, "bold"), width=5, height=2)
        icono.pack(side=tk.LEFT, padx=18, pady=24)

        textos = tk.Frame(banner, bg="#1f7a8c")
        textos.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, pady=18)
        tk.Label(textos, text="Reparaciones", bg="#1f7a8c", fg="#ffffff", font=("Segoe UI", 12, "bold")).pack(anchor=tk.W)
        tk.Label(textos, text="SISTEMA LOCAL DE GESTION", bg="#1f7a8c", fg="#dbeafe", font=("Segoe UI", 8, "bold")).pack(anchor=tk.W)
        tk.Label(textos, text="Panel principal", bg="#1f7a8c", fg="#ffffff", font=("Segoe UI", 22, "bold")).pack(anchor=tk.W, pady=(14, 4))
        tk.Label(textos, text="Entra al modulo que necesitas desde un panel simple, visual y facil de entender.", bg="#1f7a8c", fg="#ffffff", font=("Segoe UI", 9)).pack(anchor=tk.W)

        rapido = tk.Button(banner, text="Acceso rapido", command=self._mostrar_nueva, bg="#57b8bc", fg="#ffffff", bd=0, padx=18, pady=12, font=("Segoe UI", 10, "bold"))
        rapido.pack(side=tk.RIGHT, padx=20)

    def _crear_tarjetas_inicio(self):
        contenedor = tk.Frame(self.contenido, bg="#eef2f7")
        contenedor.pack(fill=tk.BOTH, expand=True, padx=10)

        tarjetas = [
            ("+", "Nueva reparacion", "Registrar ingreso, cliente, equipo, falla y precio.", self._mostrar_nueva, "#0f766e"),
            ("?", "Consultas", "Buscar, filtrar, editar y generar tickets.", lambda: self._mostrar_consultas("TODOS"), "#2563eb"),
            ("!", "Pendientes", "Ver equipos ingresados aun sin resolver.", lambda: self._mostrar_consultas("PENDIENTE"), "#ca8a04"),
            ("OK", "Entregados", "Historial de trabajos ya entregados.", lambda: self._mostrar_consultas("ENTREGADO"), "#7c3aed"),
        ]

        for indice, (icono, titulo, texto, comando, color) in enumerate(tarjetas):
            fila = indice // 3
            columna = indice % 3
            tarjeta = tk.Frame(contenedor, bg="#ffffff", highlightthickness=1, highlightbackground="#d7dee8", cursor="hand2")
            tarjeta.grid(row=fila, column=columna, sticky=tk.NSEW, padx=10, pady=10)
            tarjeta.bind("<Button-1>", lambda evento, cmd=comando: cmd())
            self._rellenar_tarjeta(tarjeta, icono, titulo, texto, color, comando)

        for columna in range(3):
            contenedor.columnconfigure(columna, weight=1)
        for fila in range(2):
            contenedor.rowconfigure(fila, weight=1)

    def _rellenar_tarjeta(self, tarjeta, icono, titulo, texto, color, comando):
        sombra = tk.Frame(tarjeta, bg="#f1f5f9", width=62, height=54)
        sombra.pack(pady=(18, 8))
        sombra.pack_propagate(False)
        caja_icono = tk.Label(sombra, text=icono, bg=color, fg="#ffffff", font=("Segoe UI", 18, "bold"), width=4, height=2)
        caja_icono.pack(fill=tk.BOTH, expand=True, padx=4, pady=4)
        caja_icono.bind("<Button-1>", lambda evento: comando())
        tk.Label(tarjeta, text=titulo, bg="#ffffff", fg="#0f172a", font=("Segoe UI", 11, "bold")).pack()
        descripcion = tk.Label(tarjeta, text=texto, bg="#ffffff", fg="#475569", font=("Segoe UI", 9), wraplength=280, justify=tk.CENTER)
        descripcion.pack(padx=14, pady=(8, 18))
        descripcion.bind("<Button-1>", lambda evento: comando())

    def _mostrar_nueva(self):
        self._limpiar_contenido()
        self.campos = {}
        self.reparacion_actual_id = None
        self.estado_var.set("PENDIENTE")
        self.activo_var.set(True)
        self._crear_titulo_vista("Nueva reparacion", "Carga fija de ingresos al taller.")
        self._crear_formulario_reparacion()
        self._mostrar_estado("Nueva reparacion lista para cargar.")

    def _crear_titulo_vista(self, titulo, subtitulo):
        encabezado = tk.Frame(self.contenido, bg="#eef2f7")
        encabezado.pack(fill=tk.X, padx=14, pady=(14, 10))
        ttk.Label(encabezado, text=titulo, style="Seccion.TLabel").pack(anchor=tk.W)
        ttk.Label(encabezado, text=subtitulo, style="Ayuda.TLabel").pack(anchor=tk.W)

    def _crear_formulario_reparacion(self):
        formulario = ttk.LabelFrame(self.contenido, text="Datos de la reparacion", padding=14)
        formulario.pack(fill=tk.BOTH, expand=True, padx=14, pady=(0, 14))

        definiciones = [
            ("cliente_nombre", "Cliente *", 0, 0, 30),
            ("cliente_telefono", "Telefono", 0, 2, 22),
            ("marca", "Marca", 1, 0, 22),
            ("modelo", "Modelo", 1, 2, 22),
            ("garantia", "Garantia", 2, 0, 22),
            ("precio", "Precio", 2, 2, 15),
            ("fecha_ingreso", "Ingreso", 3, 0, 15),
            ("fecha_entrega", "Entrega", 3, 2, 15),
        ]

        for nombre, etiqueta, fila, columna, ancho in definiciones:
            ttk.Label(formulario, text=etiqueta, style="Panel.TLabel").grid(row=fila, column=columna, sticky=tk.W, padx=5, pady=6)
            entrada = ttk.Entry(formulario, width=ancho)
            entrada.grid(row=fila, column=columna + 1, sticky=tk.EW, padx=5, pady=6)
            self.campos[nombre] = entrada

        self.campos["fecha_ingreso"].insert(0, fecha_hoy())

        ttk.Label(formulario, text="Estado", style="Panel.TLabel").grid(row=4, column=0, sticky=tk.W, padx=5, pady=6)
        estado = ttk.Combobox(formulario, textvariable=self.estado_var, values=list(ESTADOS.keys()), state="readonly", width=22)
        estado.grid(row=4, column=1, sticky=tk.W, padx=5, pady=6)

        activo = ttk.Checkbutton(formulario, text="Activo", variable=self.activo_var)
        activo.grid(row=5, column=0, sticky=tk.W, padx=5, pady=6)

        areas = [
            ("falla", "Falla reportada", 6),
            ("diagnostico", "Diagnostico", 7),
            ("observaciones", "Observaciones", 8),
        ]
        for nombre, etiqueta, fila in areas:
            ttk.Label(formulario, text=etiqueta, style="Panel.TLabel").grid(row=fila, column=0, sticky=tk.NW, padx=5, pady=6)
            texto = tk.Text(formulario, height=4, wrap=tk.WORD, bg="#ffffff", fg="#111827", relief=tk.SOLID, bd=1)
            texto.grid(row=fila, column=1, columnspan=3, sticky=tk.EW, padx=5, pady=6)
            self.campos[nombre] = texto

        acciones = ttk.Frame(formulario, style="Panel.TFrame")
        acciones.grid(row=9, column=0, columnspan=4, sticky=tk.W, pady=(12, 0))
        ttk.Button(acciones, text="Guardar", style="Primary.TButton", command=self._guardar).pack(side=tk.LEFT, padx=4)
        ttk.Button(acciones, text="Limpiar", command=self._limpiar_formulario).pack(side=tk.LEFT, padx=4)
        ttk.Button(acciones, text="Ir a consultas", command=lambda: self._mostrar_consultas("TODOS")).pack(side=tk.LEFT, padx=4)
        ttk.Button(acciones, text="Eliminar", style="Danger.TButton", command=self._eliminar).pack(side=tk.LEFT, padx=4)

        for columna in range(4):
            formulario.columnconfigure(columna, weight=1)
        self.campos["cliente_nombre"].focus_set()

    def _mostrar_consultas(self, estado):
        self._limpiar_contenido()
        self.reparacion_actual_id = None
        self.campos = {}
        self.filtro_estado_var.set(estado)
        self._crear_titulo_vista("Consultas", "Busqueda, filtros, estados, edicion y tickets.")
        self._crear_panel_resumen()
        self._crear_panel_consultas()
        self._cargar_tabla()

    def _crear_panel_resumen(self):
        self.tarjetas = {}
        fila = tk.Frame(self.contenido, bg="#eef2f7")
        fila.pack(fill=tk.X, padx=14, pady=(0, 10))
        definiciones = [
            ("total", "Total activas", "#0e7490"),
            ("pendiente", "Pendientes", "#ca8a04"),
            ("entregado", "Entregados", "#7c3aed"),
        ]

        for clave, titulo, color in definiciones:
            tarjeta = tk.Frame(fila, bg="#ffffff", highlightthickness=1, highlightbackground="#d7dee8")
            tarjeta.pack(side=tk.LEFT, fill=tk.X, expand=True, padx=(0, 8))
            tk.Frame(tarjeta, bg=color, width=5).pack(side=tk.LEFT, fill=tk.Y)
            cuerpo = tk.Frame(tarjeta, bg="#ffffff", padx=12, pady=9)
            cuerpo.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)
            ttk.Label(cuerpo, text=titulo, style="CardText.TLabel").pack(anchor=tk.W)
            valor = ttk.Label(cuerpo, text="0", style="CardValue.TLabel")
            valor.pack(anchor=tk.W)
            self.tarjetas[clave] = valor

    def _crear_panel_consultas(self):
        panel = ttk.Frame(self.contenido)
        panel.pack(fill=tk.BOTH, expand=True, padx=14, pady=(0, 14))

        barra = ttk.Frame(panel, style="Panel.TFrame", padding=10)
        barra.pack(fill=tk.X, pady=(0, 10))
        ttk.Label(barra, text="Buscar", style="Panel.TLabel").pack(side=tk.LEFT, padx=(0, 6))
        entrada = ttk.Entry(barra, textvariable=self.busqueda_var, width=36)
        entrada.pack(side=tk.LEFT, padx=(0, 10))
        entrada.bind("<KeyRelease>", self._evento_filtrar)

        ttk.Label(barra, text="Estado", style="Panel.TLabel").pack(side=tk.LEFT, padx=(10, 6))
        valores = ["TODOS"] + list(ESTADOS.keys())
        estado = ttk.Combobox(barra, textvariable=self.filtro_estado_var, values=valores, state="readonly", width=18)
        estado.pack(side=tk.LEFT, padx=(0, 10))
        estado.bind("<<ComboboxSelected>>", self._evento_filtrar)

        ttk.Button(barra, text="Limpiar filtros", command=self._limpiar_filtros).pack(side=tk.LEFT, padx=4)
        ttk.Button(barra, text="Editar seleccionado", command=self._editar_seleccionado).pack(side=tk.RIGHT, padx=4)
        ttk.Button(barra, text="Ticket", command=self._generar_ticket).pack(side=tk.RIGHT, padx=4)

        zona = ttk.Frame(panel)
        zona.pack(fill=tk.BOTH, expand=True)
        listado = ttk.LabelFrame(zona, text="Listado de reparaciones", padding=10)
        listado.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, padx=(0, 10))
        self._crear_tabla(listado)

        detalle = ttk.LabelFrame(zona, text="Detalle seleccionado", padding=10)
        detalle.pack(side=tk.RIGHT, fill=tk.Y)
        self.detalle_texto = tk.Text(detalle, width=36, height=20, wrap=tk.WORD, bg="#ffffff", fg="#111827", bd=0)
        self.detalle_texto.pack(fill=tk.BOTH, expand=True)
        self.detalle_texto.configure(state=tk.DISABLED)

    def _crear_tabla(self, marco):
        columnas = ("id", "codigo", "cliente", "telefono", "equipo", "estado", "precio", "ingreso", "entrega")
        self.tabla = ttk.Treeview(marco, columns=columnas, show="headings", height=12)
        anchos = {
            "id": 45,
            "codigo": 120,
            "cliente": 170,
            "telefono": 105,
            "equipo": 165,
            "estado": 138,
            "precio": 85,
            "ingreso": 95,
            "entrega": 95,
        }

        for columna in columnas:
            self.tabla.heading(columna, text=columna.capitalize())
            self.tabla.column(columna, width=anchos[columna], anchor=tk.W)

        self.tabla.tag_configure("PENDIENTE", background="#fef9c3")
        self.tabla.tag_configure("ENTREGADO", background="#ede9fe")

        barra_y = ttk.Scrollbar(marco, orient=tk.VERTICAL, command=self.tabla.yview)
        self.tabla.configure(yscrollcommand=barra_y.set)
        self.tabla.grid(row=0, column=0, sticky=tk.NSEW)
        barra_y.grid(row=0, column=1, sticky=tk.NS)
        marco.columnconfigure(0, weight=1)
        marco.rowconfigure(0, weight=1)
        self.tabla.bind("<<TreeviewSelect>>", self._seleccionar_fila)

    def _mostrar_config(self):
        self._limpiar_contenido()
        self._crear_titulo_vista("Configuracion", "Datos locales del programa.")
        panel = ttk.LabelFrame(self.contenido, text="Informacion", padding=14)
        panel.pack(fill=tk.X, padx=14)
        ttk.Label(panel, text="Base de datos: reparaciones.db", style="Panel.TLabel").pack(anchor=tk.W)
        ttk.Label(panel, text="Tickets: carpeta tickets", style="Panel.TLabel").pack(anchor=tk.W)
        ttk.Label(panel, text="Programa local sin dependencias externas.", style="Panel.TLabel").pack(anchor=tk.W)
        self._mostrar_estado("Configuracion.")

    def _guardar(self):
        datos = self._leer_formulario()
        errores = validar_datos(datos)
        if errores:
            messagebox.showwarning("Validacion", "\n".join(errores))
        else:
            if self.reparacion_actual_id:
                ok = self.repositorio.actualizar(self.reparacion_actual_id, datos)
                mensaje = "Reparacion actualizada correctamente." if ok else "No se pudo actualizar."
            else:
                nuevo_id = self.repositorio.crear(datos)
                ok = nuevo_id > 0
                mensaje = "Reparacion creada correctamente." if ok else "No se pudo crear."

            if ok:
                messagebox.showinfo("Reparaciones", mensaje)
                self._mostrar_nueva()
            else:
                messagebox.showerror("Reparaciones", mensaje)
            self._mostrar_estado(mensaje)

    def _eliminar(self):
        if self.reparacion_actual_id:
            confirmado = messagebox.askyesno("Eliminar", "Eliminar reparacion seleccionada?")
            if confirmado:
                ok = self.repositorio.eliminar(self.reparacion_actual_id)
                if ok:
                    messagebox.showinfo("Reparaciones", "Reparacion eliminada correctamente.")
                    self._mostrar_consultas("TODOS")
                    self._mostrar_estado("Reparacion eliminada correctamente.")
                else:
                    messagebox.showerror("Reparaciones", "No se pudo eliminar.")
                    self._mostrar_estado("No se pudo eliminar.")
        else:
            messagebox.showwarning("Eliminar", "Seleccione una reparacion.")
            self._mostrar_estado("Seleccione una reparacion para eliminar.")

    def _generar_ticket(self):
        if self.reparacion_actual_id:
            reparacion = self.repositorio.buscar_por_id(self.reparacion_actual_id)
            if reparacion:
                try:
                    ruta = crear_ticket(reparacion)
                    messagebox.showinfo("Ticket", f"Ticket generado en:\n{ruta}")
                    self._mostrar_estado("Ticket generado correctamente.")
                except OSError:
                    messagebox.showerror("Ticket", "No se pudo generar el archivo del ticket.")
                    self._mostrar_estado("No se pudo generar el ticket.")
            else:
                messagebox.showerror("Ticket", "No se encontro la reparacion.")
                self._mostrar_estado("No se encontro la reparacion.")
        else:
            messagebox.showwarning("Ticket", "Seleccione una reparacion.")
            self._mostrar_estado("Seleccione una reparacion para generar ticket.")

    def _editar_seleccionado(self):
        reparacion = None
        if self.reparacion_actual_id:
            reparacion = self.repositorio.buscar_por_id(self.reparacion_actual_id)
        if reparacion:
            self._mostrar_nueva()
            self.reparacion_actual_id = reparacion["id"]
            self._cargar_formulario(reparacion)
        else:
            messagebox.showwarning("Editar", "Seleccione una reparacion.")
            self._mostrar_estado("Seleccione una reparacion para editar.")

    def _seleccionar_fila(self, evento):
        seleccion = self.tabla.selection()
        if seleccion:
            valores = self.tabla.item(seleccion[0], "values")
            reparacion = self.repositorio.buscar_por_id(int(valores[0]))
            if reparacion:
                self.reparacion_actual_id = reparacion["id"]
                self._mostrar_detalle(reparacion)
                self._mostrar_estado("Reparacion seleccionada.")

    def _cargar_tabla(self):
        self.reparaciones_cache = self.repositorio.listar()
        self._actualizar_tarjetas()
        self._aplicar_filtros()

    def _aplicar_filtros(self):
        for item in self.tabla.get_children():
            self.tabla.delete(item)

        reparaciones = self._obtener_reparaciones_filtradas()
        for reparacion in reparaciones:
            equipo = f"{reparacion['marca']} {reparacion['modelo']}".strip()
            estado = ESTADOS.get(reparacion["estado"], reparacion["estado"])
            self.tabla.insert(
                "",
                tk.END,
                values=(
                    reparacion["id"],
                    reparacion["codigo"],
                    reparacion["cliente_nombre"],
                    reparacion["cliente_telefono"],
                    equipo,
                    estado,
                    f"{reparacion['precio']:.2f}",
                    reparacion["fecha_ingreso"],
                    reparacion["fecha_entrega"],
                ),
                tags=(reparacion["estado"],),
            )
        self._mostrar_estado(f"Mostrando {len(reparaciones)} reparaciones.")

    def _obtener_reparaciones_filtradas(self):
        texto = self.busqueda_var.get().strip().lower()
        estado = self.filtro_estado_var.get()
        reparaciones = []

        for reparacion in self.reparaciones_cache:
            coincide_estado = estado == "TODOS" or reparacion["estado"] == estado
            coincide_texto = self._coincide_busqueda(reparacion, texto)
            if coincide_estado and coincide_texto:
                reparaciones.append(reparacion)

        return reparaciones

    def _coincide_busqueda(self, reparacion, texto):
        coincide = True
        if texto:
            campos = [
                reparacion.get("codigo", ""),
                reparacion.get("cliente_nombre", ""),
                reparacion.get("cliente_telefono", ""),
                reparacion.get("marca", ""),
                reparacion.get("modelo", ""),
                reparacion.get("falla", ""),
                reparacion.get("garantia", ""),
            ]
            contenido = " ".join(str(campo).lower() for campo in campos)
            coincide = texto in contenido
        return coincide

    def _actualizar_tarjetas(self):
        conteos = {
            "total": len(self.reparaciones_cache),
            "pendiente": 0,
            "entregado": 0,
        }

        for reparacion in self.reparaciones_cache:
            if reparacion["estado"] == "PENDIENTE":
                conteos["pendiente"] += 1
            if reparacion["estado"] == "ENTREGADO":
                conteos["entregado"] += 1

        for clave, valor in conteos.items():
            self.tarjetas[clave].configure(text=str(valor))

    def _mostrar_detalle(self, reparacion):
        equipo = f"{reparacion.get('marca', '')} {reparacion.get('modelo', '')}".strip()
        estado = ESTADOS.get(reparacion.get("estado"), reparacion.get("estado", ""))
        texto = (
            f"Codigo: {reparacion.get('codigo', '')}\n"
            f"Cliente: {reparacion.get('cliente_nombre', '')}\n"
            f"Telefono: {reparacion.get('cliente_telefono', '')}\n\n"
            f"Equipo: {equipo}\n"
            f"Estado: {estado}\n"
            f"Precio: {reparacion.get('precio', 0):.2f}\n"
            f"Garantia: {reparacion.get('garantia', '')}\n"
            f"Ingreso: {reparacion.get('fecha_ingreso', '')}\n"
            f"Entrega: {reparacion.get('fecha_entrega', '')}\n\n"
            f"Falla:\n{reparacion.get('falla', '')}\n\n"
            f"Diagnostico:\n{reparacion.get('diagnostico', '')}\n\n"
            f"Observaciones:\n{reparacion.get('observaciones', '')}"
        )
        self.detalle_texto.configure(state=tk.NORMAL)
        self.detalle_texto.delete("1.0", tk.END)
        self.detalle_texto.insert("1.0", texto)
        self.detalle_texto.configure(state=tk.DISABLED)

    def _evento_filtrar(self, evento):
        self._aplicar_filtros()

    def _limpiar_filtros(self):
        self.busqueda_var.set("")
        self.filtro_estado_var.set("TODOS")
        self._aplicar_filtros()

    def _leer_formulario(self):
        datos = {
            "cliente_nombre": self._valor_campo("cliente_nombre"),
            "cliente_telefono": self._valor_campo("cliente_telefono"),
            "marca": self._valor_campo("marca"),
            "modelo": self._valor_campo("modelo"),
            "falla": self._valor_campo("falla"),
            "diagnostico": self._valor_campo("diagnostico"),
            "garantia": self._valor_campo("garantia"),
            "estado": self.estado_var.get(),
            "precio": self._valor_campo("precio"),
            "fecha_ingreso": self._valor_campo("fecha_ingreso"),
            "fecha_entrega": self._valor_campo("fecha_entrega"),
            "observaciones": self._valor_campo("observaciones"),
            "activo": self.activo_var.get(),
        }
        return datos

    def _valor_campo(self, nombre):
        widget = self.campos[nombre]
        valor = ""
        if isinstance(widget, tk.Text):
            valor = widget.get("1.0", tk.END).strip()
        else:
            valor = widget.get().strip()
        return valor

    def _cargar_formulario(self, reparacion):
        for nombre, widget in self.campos.items():
            valor = str(reparacion.get(nombre, ""))
            if isinstance(widget, tk.Text):
                widget.delete("1.0", tk.END)
                widget.insert("1.0", valor)
            else:
                widget.delete(0, tk.END)
                widget.insert(0, valor)
        self.estado_var.set(reparacion.get("estado", "PENDIENTE"))
        self.activo_var.set(bool(reparacion.get("activo", 1)))
        self._mostrar_estado("Reparacion cargada para editar.")

    def _limpiar_formulario(self):
        self.reparacion_actual_id = None
        for widget in self.campos.values():
            if isinstance(widget, tk.Text):
                widget.delete("1.0", tk.END)
            else:
                widget.delete(0, tk.END)
        self.campos["fecha_ingreso"].insert(0, fecha_hoy())
        self.estado_var.set("PENDIENTE")
        self.activo_var.set(True)
        self.campos["cliente_nombre"].focus_set()
        self._mostrar_estado("Formulario limpio.")

    def _mostrar_estado(self, mensaje):
        self.estado_sistema_var.set(mensaje)
