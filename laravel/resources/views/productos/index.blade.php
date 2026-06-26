@extends('layouts.app')

@section('titulo', 'Productos')

@section('contenido')
<div class="compact-screen productos-screen">
<div class="module-header">
    <div>
        <h1>Productos</h1>
        <p class="text-muted mb-0">Catalogo comercial, precios y stock asociado.</p>
    </div>
    <div class="module-toolbar">
        <button type="button" data-bs-toggle="offcanvas" data-bs-target="#panelProducto">Nuevo Producto</button>
        <button type="submit" form="form-productos">Buscar</button>
        <a class="btn btn-light-action btn-sm" href="{{ url('/exportaciones') }}">Importar</a>
        <a class="btn btn-light-action btn-sm" href="{{ url('/exportaciones') }}">Exportar</a>
    </div>
</div>

        <section class="resumen">
            <div class="dato">Productos <strong>{{ method_exists($productos, 'total') ? $productos->total() : $productos->count() }}</strong></div>
            <div class="dato">Sin stock <strong id="productos-sin-stock">0</strong></div>
            <div class="dato">Con alertas <strong id="productos-con-alertas">0</strong></div>
            <div class="dato">Inactivos <strong id="productos-inactivos">0</strong></div>
        </section>

        <form class="filter-strip erp-card" id="form-productos" method="get" action="{{ url('/productos') }}">
            <label for="buscar-producto">Buscar
                <input id="buscar-producto" name="q" type="search" value="{{ $q }}" autocomplete="off">
            </label>
            <label for="codigo-producto">Codigo
                <input id="codigo-producto" type="search" autocomplete="off">
            </label>
            <label for="stock-producto">Stock
                <select id="stock-producto">
                    <option value="">Todos</option>
                    <option value="sin_stock">Sin stock</option>
                    <option value="con_stock">Con stock</option>
                </select>
            </label>
            <label for="activo-producto">Activo
                <select id="activo-producto">
                    <option value="">Todos</option>
                    <option value="1">Activos</option>
                    <option value="0">Inactivos</option>
                </select>
            </label>
            <button type="button" id="limpiar-productos">Limpiar</button>
        </form>

        <ul id="sugerencias-productos"></ul>

        <div class="fill-panel">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Codigo</th>
                    <th>Nombre</th>
                    <th>Costo</th>
                    <th>Moneda</th>
                    <th>Costo ARS</th>
                    <th>Precio Final</th>
                    <th>Stock</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($productos as $producto)
                    <tr>
                        <td>{{ $producto['id'] ?? '' }}</td>
                        <td>{{ $producto['cod_barras'] ?? '' }}</td>
                        <td>{{ $producto['nombre'] ?? '' }}</td>
                        <td>{{ $producto['stock_costo_origen'] ?? ($producto['stock_precio_costo'] ?? 0) }}</td>
                        <td>{{ $producto['stock_moneda_costo'] ?? 'ARS' }}</td>
                        <td>{{ $producto['stock_precio_costo'] ?? 0 }}</td>
                        <td>{{ $producto['precio'] ?? ($producto['precio_final'] ?? 0) }}</td>
                        <td>{{ $producto['stock_cantidad'] ?? 0 }}</td>
                        <td>{{ ($producto['activo'] ?? true) ? 'Si' : 'No' }}</td>
                        <td><button type="button" data-editar-producto="{{ $producto['id'] ?? 0 }}">Editar</button></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">Busca por codigo o nombre.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        {{ $productos->links() }}
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="panelProducto" aria-labelledby="panelProductoLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="panelProductoLabel">Nuevo Producto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <form id="form-producto">
            <input id="producto-id" type="hidden">
            <label>Codigo <input id="producto-codigo" name="codigo_barras" type="text"></label>
            <label>Nombre <input id="producto-nombre" name="nombre" type="text" required></label>
            <label>Costo <input id="producto-costo" name="precio_costo" type="number" min="0" step="0.01" required></label>
            <label>Moneda
                <select id="producto-moneda" name="moneda_costo">
                    <option value="ARS">ARS</option>
                    <option value="USD">USD</option>
                </select>
            </label>
            <label>Valor del dolar <input id="producto-dolar-venta" name="dolar_venta" type="number" min="0.01" step="0.01" value="{{ $configuracionMonedas['dolar_venta'] ?? 1220 }}" required></label>
            <label>Costo ARS <input id="producto-costo-ars" name="precio_costo_ars" type="number" min="0" step="0.01" readonly></label>
            <label>Precio Final <input id="producto-precio" name="precio" type="number" min="0" step="0.01" readonly required></label>
            <label>ID Stock <input id="producto-stock" name="id_stock" type="number" min="0" step="1"></label>
            <label>Factor conversion <input id="producto-factor" name="factor_conversion" type="number" min="0" step="0.0001" value="1"></label>
            <label>Ganancia % <input id="producto-ganancia" name="ganancia" type="number" step="0.01" value="0"></label>
            <label>Activo
                <select id="producto-activo-form" name="activo">
                    <option value="1">Si</option>
                    <option value="0">No</option>
                </select>
            </label>
            <div class="acciones">
                <button type="submit">Guardar</button>
                <button type="button" class="btn-light-action" id="limpiar-form-producto">Nuevo</button>
                <span id="estado-producto"></span>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const campo = document.getElementById('buscar-producto');
        const configuracionMonedas = @json($configuracionMonedas);
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const sugerencias = document.getElementById('sugerencias-productos');
        const tablaProductos = document.querySelector('.productos-screen tbody');
        const formProducto = document.getElementById('form-producto');
        const estadoProducto = document.getElementById('estado-producto');
        const sinStock = document.getElementById('productos-sin-stock');
        const conAlertas = document.getElementById('productos-con-alertas');
        const inactivos = document.getElementById('productos-inactivos');
        let espera = null;
        let ultimosProductos = [];
        const numero = (valor, defecto = 0) => {
            const n = Number.parseFloat(String(valor ?? '').replace(',', '.'));
            return Number.isFinite(n) ? n : defecto;
        };
        const moneda = document.getElementById('producto-moneda');
        const costo = document.getElementById('producto-costo');
        const dolarVenta = document.getElementById('producto-dolar-venta');
        const costoArs = document.getElementById('producto-costo-ars');
        const precio = document.getElementById('producto-precio');
        const factor = document.getElementById('producto-factor');
        const ganancia = document.getElementById('producto-ganancia');

        const recalcularPrecio = () => {
            const valorCosto = Math.max(0, numero(costo.value));
            const valorDolar = Math.max(0, numero(dolarVenta.value, numero(configuracionMonedas.dolar_venta, 1220)));
            const valorFactor = Math.max(0.0001, numero(factor.value, 1));
            const valorGanancia = Math.max(0, numero(ganancia.value));
            const costoEnPesos = moneda.value === 'USD' ? valorCosto * valorDolar : valorCosto;
            const precioFinal = (costoEnPesos * valorFactor) * (1 + (valorGanancia / 100));
            costoArs.value = costoEnPesos.toFixed(2);
            precio.value = precioFinal.toFixed(2);
        };

        const renderizar = (items) => {
            sugerencias.innerHTML = '';
            ultimosProductos = items;
            sinStock.textContent = items.filter((item) => Number(item.stock || 0) <= 0).length;
            conAlertas.textContent = items.filter((item) => Number(item.stock || 0) <= 0).length;
            inactivos.textContent = items.filter((item) => item.activo === false).length;
            renderizarTablaProductos(items);

            items.forEach((item) => {
                const li = document.createElement('li');
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.textContent = `${item.codigo_barras || ''} - ${item.nombre || ''} - ${item.precio || 0} - stock ${item.stock || 0}`;
                boton.addEventListener('click', () => abrirProducto(item));
                li.appendChild(boton);
                sugerencias.appendChild(li);
            });
        };

        const renderizarTablaProductos = (items) => {
            tablaProductos.innerHTML = '';

            if (items.length === 0) {
                tablaProductos.innerHTML = '<tr><td colspan="10">Sin productos para mostrar.</td></tr>';
                return;
            }

            items.forEach((item) => {
                const fila = document.createElement('tr');
                fila.innerHTML = `
                    <td>${item.id || ''}</td>
                    <td>${item.codigo_barras || item.cod_barras || ''}</td>
                    <td>${item.nombre || ''}</td>
                    <td>${item.precio_costo || item.stock_costo_origen || item.stock_precio_costo || 0}</td>
                    <td>${item.moneda_costo || item.stock_moneda_costo || 'ARS'}</td>
                    <td>${item.precio_costo_ars || item.stock_precio_costo || 0}</td>
                    <td>${item.precio || item.precio_final || 0}</td>
                    <td>${item.stock || item.stock_cantidad || 0}</td>
                    <td>${item.activo === false ? 'No' : 'Si'}</td>
                    <td><button type="button" data-editar-producto="${item.id || 0}">Editar</button></td>
                `;
                tablaProductos.appendChild(fila);
            });
        };

        const refrescarProductos = async () => {
            const q = campo.value.trim();
            const respuesta = await fetch(`{{ url('/productos/buscar') }}?q=${encodeURIComponent(q)}`);
            const payload = await respuesta.json();
            renderizar(payload.data || []);
        };

        const limpiarProducto = () => {
            formProducto.reset();
            document.getElementById('producto-id').value = '';
            document.getElementById('producto-factor').value = '1';
            document.getElementById('producto-ganancia').value = '0';
            document.getElementById('producto-moneda').value = 'ARS';
            document.getElementById('producto-dolar-venta').value = configuracionMonedas.dolar_venta || 1220;
            estadoProducto.textContent = '';
            recalcularPrecio();
        };

        const abrirProducto = async (item) => {
            const id = Number(item.id || 0);
            let producto = item;

            if (id > 0) {
                const respuesta = await fetch(`{{ url('/productos') }}/${id}`);
                const payload = await respuesta.json();
                producto = payload.producto || item;
            }

            document.getElementById('producto-id').value = producto.id || '';
            document.getElementById('producto-codigo').value = producto.codigo_barras || producto.cod_barras || '';
            document.getElementById('producto-nombre').value = producto.nombre || '';
            document.getElementById('producto-costo').value = producto.precio_costo || producto.stock_costo_origen || producto.stock_precio_costo || 0;
            document.getElementById('producto-moneda').value = producto.moneda_costo || producto.stock_moneda_costo || 'ARS';
            document.getElementById('producto-dolar-venta').value = configuracionMonedas.dolar_venta || producto.dolar_venta || 1220;
            document.getElementById('producto-costo-ars').value = producto.precio_costo_ars || producto.stock_precio_costo || 0;
            document.getElementById('producto-precio').value = producto.precio || producto.precio_final || 0;
            document.getElementById('producto-stock').value = producto.id_stock || '';
            document.getElementById('producto-factor').value = producto.factor_conversion || 1;
            document.getElementById('producto-ganancia').value = producto.ganancia || 0;
            document.getElementById('producto-activo-form').value = producto.activo === false ? '0' : '1';
            recalcularPrecio();
            bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelProducto')).show();
        };

        campo.addEventListener('input', () => {
            window.clearTimeout(espera);
            espera = window.setTimeout(async () => {
                const q = campo.value.trim();

                if (q === '') {
                    renderizar([]);
                    return;
                }

                const respuesta = await fetch(`{{ url('/productos/autocompletar') }}?q=${encodeURIComponent(q)}`);
                const datos = await respuesta.json();
                renderizar(datos);
            }, 300);
        });
        document.getElementById('limpiar-productos').addEventListener('click', () => {
            campo.value = '';
            document.getElementById('codigo-producto').value = '';
            renderizar([]);
        });
        document.getElementById('limpiar-form-producto').addEventListener('click', limpiarProducto);
        [costo, moneda, dolarVenta, factor, ganancia].forEach((elemento) => {
            elemento.addEventListener('input', recalcularPrecio);
            elemento.addEventListener('change', recalcularPrecio);
        });
        document.querySelector('[data-bs-target="#panelProducto"]').addEventListener('click', limpiarProducto);
        const abrirNuevoProductoDesdeHash = () => {
            if (window.location.hash === '#nuevo') {
                limpiarProducto();
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelProducto')).show();
            }
        };
        abrirNuevoProductoDesdeHash();
        window.addEventListener('hashchange', abrirNuevoProductoDesdeHash);
        tablaProductos.addEventListener('click', async (event) => {
            const boton = event.target.closest('[data-editar-producto]');

            if (boton) {
                await abrirProducto({ id: boton.dataset.editarProducto });
            }
        });
        formProducto.addEventListener('submit', async (event) => {
            event.preventDefault();
            const id = document.getElementById('producto-id').value;
            recalcularPrecio();
            const datos = new FormData(formProducto);
            const url = id === '' ? '{{ url('/productos') }}' : `{{ url('/productos') }}/${id}`;

            if (id !== '') {
                datos.append('_method', 'PUT');
            }
            const respuesta = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: datos,
            });
            const payload = await respuesta.json();
            estadoProducto.textContent = payload.mensaje || (respuesta.ok ? 'Producto guardado.' : 'No se pudo guardar.');

            if (respuesta.ok) {
                await refrescarProductos();
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelProducto')).hide();
            }
        });
</script>
@endpush

