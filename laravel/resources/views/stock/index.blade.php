@extends('layouts.app')

@section('titulo', 'Stock')

@section('contenido')
<div class="compact-screen stock-screen">
<div class="module-header">
    <div>
        <h1>Stock</h1>
        <p class="text-muted mb-0">Existencias, alertas y faltantes.</p>
    </div>
    <div class="module-toolbar">
        <button type="button" data-bs-toggle="offcanvas" data-bs-target="#panelStock">Nuevo Stock</button>
        <button type="submit" form="form-stock">Buscar</button>
        <a class="btn btn-light-action btn-sm" href="{{ url('/exportaciones') }}">Exportar</a>
    </div>
</div>

        <section class="stock-alertas">
            <div class="resumen">
                <div class="dato">Items <strong>{{ method_exists($stocks, 'total') ? $stocks->total() : $stocks->count() }}</strong></div>
                <div class="dato">Alertas <strong id="alertas-total">{{ $resumenAlertas['total'] ?? 0 }}</strong></div>
                <div class="dato">Pendientes <strong id="alertas-pendientes">{{ $resumenAlertas['pendientes'] ?? 0 }}</strong></div>
                <div class="dato">Bajo minimo <strong id="alertas-bajo-minimo">{{ $resumenAlertas['pendientes'] ?? 0 }}</strong></div>
            </div>
            <ul id="lista-alertas-stock"></ul>
        </section>

        <form class="filter-strip erp-card" id="form-stock" method="get" action="{{ url('/stock') }}">
            <label for="buscar-stock">Buscar
                <input id="buscar-stock" name="q" type="search" value="{{ $q }}" autocomplete="off">
            </label>
            <label for="activo-stock">Activo
                <select id="activo-stock" name="activo">
                    <option value="">Todos</option>
                    <option value="1" @selected(($activo ?? '') === '1')>Activos</option>
                    <option value="0" @selected(($activo ?? '') === '0')>Inactivos</option>
                </select>
            </label>
            <button type="button" id="limpiar-stock">Limpiar</button>
        </form>

        <ul id="sugerencias-stock"></ul>

        <div class="fill-panel">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Unidad</th>
                    <th>Stock actual</th>
                    <th>Stock minimo</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $stock)
                    <tr>
                        <td>{{ $stock->id() }}</td>
                        <td>{{ $stock->nombre() }}</td>
                        <td>{{ $stock->unidad() }}</td>
                        <td>{{ $stock->cantidad() }}</td>
                        <td>{{ $stock->stockMinimo() }}</td>
                        <td>{{ $stock->activo() ? 'Si' : 'No' }}</td>
                        <td class="acciones">
                            <button type="button" data-editar-stock="{{ $stock->id() }}">Editar</button>
                            <button type="button" data-mov-stock="{{ $stock->id() }}">Movimiento</button>
                            <button type="button" data-eliminar-stock="{{ $stock->id() }}">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Busca por nombre o ID de stock.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        {{ $stocks->links() }}

        <section>
            <h2>Faltantes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Actual</th>
                        <th>Minimo</th>
                        <th>Sugerido</th>
                    </tr>
                </thead>
                <tbody id="tabla-faltantes-stock"></tbody>
            </table>
        </section>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="panelStock" aria-labelledby="panelStockLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="panelStockLabel">Stock</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <form id="form-stock-edicion">
            <input id="stock-id" type="hidden">
            <label>Nombre <input id="stock-nombre" name="nombre" type="text" required></label>
            <label>Unidad <input id="stock-unidad" name="unidad" type="text" value="unidad"></label>
            <label>Cantidad <input id="stock-cantidad" name="cantidad" type="number" step="0.001" value="0"></label>
            <label>Precio costo <input id="stock-precio-costo" name="precio_costo" type="number" step="0.01" value="0"></label>
            <label>Minimo <input id="stock-minimo" name="stock_minimo" type="number" step="0.001" value="0"></label>
            <label>Maximo <input id="stock-maximo" name="stock_maximo" type="number" step="0.001" value="0"></label>
            <label>Tipo <input id="stock-tipo" name="tipo_stock" type="text" value="general"></label>
            <label>Moneda <input id="stock-moneda" name="moneda_costo" type="text" value="ARS"></label>
            <label>Activo
                <select id="stock-activo-form" name="activo">
                    <option value="1">Si</option>
                    <option value="0">No</option>
                </select>
            </label>
            <label>Sumar cantidad
                <input id="stock-movimiento" type="number" step="0.001" value="0">
            </label>
            <div class="acciones">
                <button type="submit">Guardar</button>
                <button type="button" class="btn-light-action" id="aplicar-movimiento-stock">Aplicar movimiento</button>
                <button type="button" class="btn-light-action" id="limpiar-form-stock">Nuevo</button>
                <span id="estado-stock"></span>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const campoStock = document.getElementById('buscar-stock');
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const sugerenciasStock = document.getElementById('sugerencias-stock');
        const alertasTotal = document.getElementById('alertas-total');
        const alertasPendientes = document.getElementById('alertas-pendientes');
        const alertasLeidas = document.getElementById('alertas-leidas');
        const alertasBajoMinimo = document.getElementById('alertas-bajo-minimo');
        const listaAlertas = document.getElementById('lista-alertas-stock');
        const tablaFaltantes = document.getElementById('tabla-faltantes-stock');
        const tablaStock = document.querySelector('.stock-screen tbody');
        const formStock = document.getElementById('form-stock-edicion');
        const estadoStock = document.getElementById('estado-stock');
        let esperaStock = null;

        const renderizarSugerencias = (items) => {
            sugerenciasStock.innerHTML = '';
            renderizarTablaStock(items);

            items.forEach((item) => {
                const li = document.createElement('li');
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.textContent = `${item.nombre || ''} - stock ${item.cantidad || 0} - minimo ${item.minimo || 0}`;
                boton.addEventListener('click', () => abrirStock(item));
                li.appendChild(boton);
                sugerenciasStock.appendChild(li);
            });
        };

        const renderizarTablaStock = (items) => {
            tablaStock.innerHTML = '';

            if (items.length === 0) {
                tablaStock.innerHTML = '<tr><td colspan="7">Sin stock para mostrar.</td></tr>';
                return;
            }

            items.forEach((item) => {
                const fila = document.createElement('tr');
                fila.innerHTML = `
                    <td>${item.id || ''}</td>
                    <td>${item.nombre || ''}</td>
                    <td>${item.unidad || ''}</td>
                    <td>${item.cantidad || 0}</td>
                    <td>${item.minimo || 0}</td>
                    <td>${item.activo === false ? 'No' : 'Si'}</td>
                    <td class="acciones">
                        <button type="button" data-editar-stock="${item.id || 0}">Editar</button>
                        <button type="button" data-mov-stock="${item.id || 0}">Movimiento</button>
                        <button type="button" data-eliminar-stock="${item.id || 0}">Eliminar</button>
                    </td>
                `;
                tablaStock.appendChild(fila);
            });
        };

        const refrescarStock = async () => {
            const q = campoStock.value.trim();
            const respuesta = await fetch(`{{ url('/stock/buscar') }}?q=${encodeURIComponent(q)}`);
            const payload = await respuesta.json();
            renderizarSugerencias(payload.data || []);
        };

        const limpiarStockForm = () => {
            formStock.reset();
            document.getElementById('stock-id').value = '';
            document.getElementById('stock-unidad').value = 'unidad';
            document.getElementById('stock-tipo').value = 'general';
            document.getElementById('stock-moneda').value = 'ARS';
            estadoStock.textContent = '';
        };

        const cargarStockForm = (stock) => {
            document.getElementById('stock-id').value = stock.id || '';
            document.getElementById('stock-nombre').value = stock.nombre || '';
            document.getElementById('stock-unidad').value = stock.unidad || 'unidad';
            document.getElementById('stock-cantidad').value = stock.cantidad || 0;
            document.getElementById('stock-precio-costo').value = stock.precio_costo || 0;
            document.getElementById('stock-minimo').value = stock.minimo || 0;
            document.getElementById('stock-maximo').value = stock.maximo || 0;
            document.getElementById('stock-tipo').value = stock.tipo_stock || 'general';
            document.getElementById('stock-moneda').value = stock.moneda_costo || 'ARS';
            document.getElementById('stock-activo-form').value = stock.activo === false ? '0' : '1';
        };

        const abrirStock = async (stock) => {
            let datos = stock;

            if (stock.id) {
                const respuesta = await fetch(`{{ url('/stock') }}/${stock.id}`);
                const payload = await respuesta.json();
                datos = payload.stock || stock;
            }

            cargarStockForm(datos);
            bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelStock')).show();
        };

        const renderizarAlertas = (payload) => {
            const resumen = payload.resumen || {};
            alertasTotal.textContent = resumen.total || 0;
            alertasPendientes.textContent = resumen.pendientes || 0;
            alertasBajoMinimo.textContent = resumen.pendientes || 0;
            listaAlertas.innerHTML = '';

            (payload.data || []).forEach((item) => {
                const li = document.createElement('li');
                li.textContent = `${item.producto || item.nombre || ''} - actual ${item.cantidad || 0} - minimo ${item.minimo || 0}`;
                listaAlertas.appendChild(li);
            });
        };

        const renderizarFaltantes = (payload) => {
            tablaFaltantes.innerHTML = '';

            (payload.data || []).forEach((item) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${item.nombre || ''}</td><td>${item.cantidad || 0}</td><td>${item.minimo || 0}</td><td>${item.cantidad_sugerida || 0}</td>`;
                tablaFaltantes.appendChild(tr);
            });
        };

        campoStock.addEventListener('input', () => {
            window.clearTimeout(esperaStock);
            esperaStock = window.setTimeout(async () => {
                const q = campoStock.value.trim();

                if (q === '') {
                    renderizarSugerencias([]);
                    return;
                }

                const respuesta = await fetch(`{{ url('/stock/autocompletar') }}?q=${encodeURIComponent(q)}`);
                const datos = await respuesta.json();
                renderizarSugerencias(datos);
            }, 300);
        });
        document.getElementById('limpiar-stock').addEventListener('click', () => {
            campoStock.value = '';
            renderizarSugerencias([]);
        });
        document.getElementById('limpiar-form-stock').addEventListener('click', limpiarStockForm);
        document.querySelector('[data-bs-target="#panelStock"]').addEventListener('click', limpiarStockForm);
        const abrirNuevoStockDesdeHash = () => {
            if (window.location.hash === '#nuevo') {
                limpiarStockForm();
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelStock')).show();
            }
        };
        abrirNuevoStockDesdeHash();
        window.addEventListener('hashchange', abrirNuevoStockDesdeHash);
        tablaStock.addEventListener('click', async (event) => {
            const editar = event.target.closest('[data-editar-stock]');
            const movimiento = event.target.closest('[data-mov-stock]');
            const eliminar = event.target.closest('[data-eliminar-stock]');

            if (editar || movimiento) {
                await abrirStock({ id: (editar || movimiento).dataset.editarStock || (editar || movimiento).dataset.movStock });
            }

            if (eliminar) {
                const confirmado = window.confirm('Eliminar este item de stock?');

                if (confirmado) {
                    const respuesta = await fetch(`{{ url('/stock') }}/${eliminar.dataset.eliminarStock}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                    });
                    const payload = await respuesta.json();
                    estadoStock.textContent = payload.mensaje || '';
                    await refrescarStock();
                }
            }
        });
        formStock.addEventListener('submit', async (event) => {
            event.preventDefault();
            const id = document.getElementById('stock-id').value;
            const datos = new FormData(formStock);
            const url = id === '' ? '{{ url('/stock') }}' : `{{ url('/stock') }}/${id}`;

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
            estadoStock.textContent = payload.mensaje || '';

            if (respuesta.ok) {
                await refrescarStock();
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelStock')).hide();
            }
        });
        document.getElementById('aplicar-movimiento-stock').addEventListener('click', async () => {
            const id = document.getElementById('stock-id').value;
            const datos = new FormData();
            datos.append('cantidad', document.getElementById('stock-movimiento').value || '0');
            datos.append('_method', 'PATCH');

            if (id !== '') {
                const respuesta = await fetch(`{{ url('/stock') }}/${id}/sumar`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-HTTP-Method-Override': 'PATCH',
                    },
                    body: datos,
                });
                const payload = await respuesta.json();
                estadoStock.textContent = payload.mensaje || '';

                if (respuesta.ok) {
                    await refrescarStock();
                }
            }
        });

        fetch('{{ url('/stock/alertas') }}')
            .then((respuesta) => respuesta.json())
            .then((payload) => renderizarAlertas(payload));

        fetch('{{ url('/stock/faltantes') }}')
            .then((respuesta) => respuesta.json())
            .then((payload) => renderizarFaltantes(payload));
</script>
@endpush

