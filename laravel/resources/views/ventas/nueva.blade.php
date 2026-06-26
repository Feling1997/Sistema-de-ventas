@extends('layouts.app')

@section('titulo', 'Nueva Venta')

@section('contenido')
<div class="compact-screen venta-screen">
    <div class="module-header">
        <div>
            <h1>Nueva Venta</h1>
            <p class="text-muted mb-0">POS horizontal para operar venta, carrito y cobro en una pantalla.</p>
        </div>
        <div class="module-toolbar">
            <button id="confirmar-venta-superior" type="button">Confirmar Venta</button>
            <button id="vaciar-carrito-superior" class="btn-light-action" type="button">Vaciar</button>
        </div>
    </div>

    <div class="venta-pos-grid">
        <div class="venta-pos-left">
            <section class="erp-card">
                <h2>Cliente</h2>
                <label for="buscar-cliente">Buscar cliente</label>
                <input id="buscar-cliente" type="search" autocomplete="off" placeholder="Nombre, telefono o documento">
                <input id="id-cliente" type="hidden" value="1">
                <ul id="sugerencias-clientes"></ul>
            </section>

            <section class="erp-card">
                <h2>Producto</h2>
                <label for="buscar-producto-venta">Buscar producto</label>
                <input id="buscar-producto-venta" type="search" autocomplete="off" placeholder="Codigo o nombre">
                <div class="compact-row">
                    <label for="cantidad-producto">Cantidad
                        <input id="cantidad-producto" type="number" min="0.001" step="0.001" value="1">
                    </label>
                    <label for="descuento-producto">Descuento %
                        <input id="descuento-producto" type="number" min="0" max="100" step="0.01" value="0">
                    </label>
                </div>
                <ul id="sugerencias-productos-venta"></ul>
            </section>

            <section class="erp-card">
                <h2>Pago</h2>
                <label for="forma-pago">Forma de pago
                    <select id="forma-pago">
                        <option value="CONTADO">Contado</option>
                        <option value="CUENTA_CORRIENTE">Cuenta corriente</option>
                    </select>
                </label>
                <div id="panel-cuenta-corriente" hidden>
                    <label for="anticipo-venta">Anticipo</label>
                    <input id="anticipo-venta" type="number" min="0" step="0.01" value="0">
                    <p class="mb-0">Saldo a financiar: <span id="saldo-financiar">0</span></p>
                    <p id="estado-cuenta-corriente" class="mb-0"></p>
                </div>
            </section>
        </div>

        <div class="venta-pos-center">
            <section class="fill-panel venta-carrito erp-card">
                <h2>Carrito</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Descuento</th>
                            <th>Subtotal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="items-carrito"></tbody>
                </table>
            </section>
            <p id="mensaje-venta" class="mb-0"></p>
        </div>

        <aside class="venta-pos-right">
            <div class="dato">Subtotal <strong id="subtotal-carrito">{{ $carrito['subtotal'] ?? 0 }}</strong></div>
            <div class="dato">Descuento <strong id="descuento-carrito">{{ $carrito['descuento'] ?? 0 }}</strong></div>
            <div class="dato">Items <strong id="cantidad-items-carrito">{{ $carrito['cantidad_items'] ?? 0 }}</strong></div>
            <div class="dato">Total <strong id="total-carrito">{{ $carrito['total'] ?? 0 }}</strong></div>
            <div></div>
            <div class="d-grid gap-2">
                <button id="confirmar-venta" type="button">Confirmar</button>
                <button id="vaciar-carrito" class="btn-light-action" type="button">Vaciar</button>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const buscarCliente = document.getElementById('buscar-cliente');
        const idCliente = document.getElementById('id-cliente');
        const sugerenciasClientes = document.getElementById('sugerencias-clientes');
        const buscarProducto = document.getElementById('buscar-producto-venta');
        const cantidadProducto = document.getElementById('cantidad-producto');
        const descuentoProducto = document.getElementById('descuento-producto');
        const sugerenciasProductos = document.getElementById('sugerencias-productos-venta');
        const itemsCarrito = document.getElementById('items-carrito');
        const subtotalCarrito = document.getElementById('subtotal-carrito');
        const descuentoCarrito = document.getElementById('descuento-carrito');
        const totalCarrito = document.getElementById('total-carrito');
        const cantidadItemsCarrito = document.getElementById('cantidad-items-carrito');
        const vaciarCarrito = document.getElementById('vaciar-carrito');
        const confirmarVenta = document.getElementById('confirmar-venta');
        const formaPago = document.getElementById('forma-pago');
        const panelCuentaCorriente = document.getElementById('panel-cuenta-corriente');
        const anticipoVenta = document.getElementById('anticipo-venta');
        const saldoFinanciar = document.getElementById('saldo-financiar');
        const estadoCuentaCorriente = document.getElementById('estado-cuenta-corriente');
        const mensajeVenta = document.getElementById('mensaje-venta');
        let esperaCliente = null;
        let esperaProducto = null;

        const jsonFetch = async (url, opciones = {}) => {
            const respuesta = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                ...opciones,
            });
            const datos = await respuesta.json();
            datos.http_ok = respuesta.ok;

            return datos;
        };

        const renderizarClientes = (clientes) => {
            sugerenciasClientes.innerHTML = '';

            clientes.forEach((cliente) => {
                const li = document.createElement('li');
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.textContent = `${cliente.nombre || ''} ${cliente.documento || ''} ${cliente.telefono || ''}`;
                boton.addEventListener('click', () => {
                    idCliente.value = cliente.id || 1;
                    buscarCliente.value = cliente.nombre || '';
                    renderizarClientes([]);
                });
                li.appendChild(boton);
                sugerenciasClientes.appendChild(li);
            });
        };

        const renderizarProductos = (productos) => {
            sugerenciasProductos.innerHTML = '';

            productos.forEach((producto) => {
                const li = document.createElement('li');
                const boton = document.createElement('button');
                const alerta = producto.stock_bajo ? ' bajo minimo' : '';
                boton.type = 'button';
                boton.textContent = `${producto.codigo_barras || ''} ${producto.nombre || ''} - ${producto.precio || 0} - stock ${producto.stock || 0}${alerta}`;
                boton.addEventListener('click', async () => {
                    await agregarProducto(producto.id || 0);
                    buscarProducto.value = '';
                    renderizarProductos([]);
                });
                li.appendChild(boton);
                sugerenciasProductos.appendChild(li);
            });
        };

        const renderizarCarrito = (payload) => {
            itemsCarrito.innerHTML = '';
            subtotalCarrito.textContent = payload.subtotal || 0;
            descuentoCarrito.textContent = payload.descuento || 0;
            totalCarrito.textContent = payload.total || 0;
            cantidadItemsCarrito.textContent = payload.cantidad_items || 0;
            actualizarCuentaCorriente(payload.total || 0);

            (payload.items || []).forEach((item) => {
                const tr = document.createElement('tr');
                const tdNombre = document.createElement('td');
                const tdCantidad = document.createElement('td');
                const tdPrecio = document.createElement('td');
                const tdDescuento = document.createElement('td');
                const tdSubtotal = document.createElement('td');
                const tdAcciones = document.createElement('td');
                const inputCantidad = document.createElement('input');
                const botonEliminar = document.createElement('button');
                tdNombre.textContent = item.nombre || '';
                inputCantidad.type = 'number';
                inputCantidad.min = '0.001';
                inputCantidad.step = '0.001';
                inputCantidad.value = item.cantidad || 1;
                inputCantidad.addEventListener('change', async () => {
                    await actualizarProducto(item.idx || 0, inputCantidad.value, item.precio_unit || 0, item.descuento || 0);
                });
                tdCantidad.appendChild(inputCantidad);
                tdPrecio.textContent = item.precio_unit || 0;
                tdDescuento.textContent = item.descuento || 0;
                tdSubtotal.textContent = item.subtotal || 0;
                botonEliminar.type = 'button';
                botonEliminar.textContent = 'Quitar';
                botonEliminar.addEventListener('click', async () => {
                    await quitarProducto(item.idx || 0);
                });
                tdAcciones.appendChild(botonEliminar);
                tr.appendChild(tdNombre);
                tr.appendChild(tdCantidad);
                tr.appendChild(tdPrecio);
                tr.appendChild(tdDescuento);
                tr.appendChild(tdSubtotal);
                tr.appendChild(tdAcciones);
                itemsCarrito.appendChild(tr);
            });
        };

        const mostrarResultado = (payload) => {
            mensajeVenta.textContent = payload.error || payload.mensaje || '';

            if (payload.items) {
                renderizarCarrito(payload);
            }
        };

        const actualizarCuentaCorriente = (total) => {
            const usarCuenta = formaPago.value === 'CUENTA_CORRIENTE';
            const anticipo = Number.parseFloat(anticipoVenta.value || '0');
            panelCuentaCorriente.hidden = !usarCuenta;
            saldoFinanciar.textContent = Math.max(0, Number.parseFloat(total || 0) - Math.max(0, anticipo));
            estadoCuentaCorriente.textContent = usarCuenta ? 'La venta se confirmara como cuenta corriente.' : '';
        };

        const agregarProducto = async (idProducto) => {
            const payload = await jsonFetch('{{ url('/ventas/carrito') }}', {
                method: 'POST',
                body: JSON.stringify({
                    id_producto: idProducto,
                    cantidad: cantidadProducto.value || 1,
                    descuento: descuentoProducto.value || 0,
                    buscar_producto: buscarProducto.value || '',
                }),
            });
            mostrarResultado(payload);
        };

        const actualizarProducto = async (idx, cantidad, precioUnit, descuento) => {
            const payload = await jsonFetch('{{ url('/ventas/carrito') }}', {
                method: 'PUT',
                body: JSON.stringify({
                    idx,
                    cantidad,
                    precio_unit: precioUnit,
                    descuento,
                }),
            });
            mostrarResultado(payload);
        };

        const quitarProducto = async (idx) => {
            const payload = await jsonFetch(`{{ url('/ventas/carrito') }}/${idx}`, {
                method: 'DELETE',
            });
            renderizarCarrito(payload);
        };

        buscarCliente.addEventListener('input', () => {
            window.clearTimeout(esperaCliente);
            esperaCliente = window.setTimeout(async () => {
                const q = buscarCliente.value.trim();
                const clientes = q === '' ? [] : await jsonFetch(`{{ url('/ventas/clientes') }}?q=${encodeURIComponent(q)}`);
                renderizarClientes(clientes);
            }, 300);
        });

        buscarProducto.addEventListener('input', () => {
            window.clearTimeout(esperaProducto);
            esperaProducto = window.setTimeout(async () => {
                const q = buscarProducto.value.trim();
                const productos = q === '' ? [] : await jsonFetch(`{{ url('/ventas/productos') }}?q=${encodeURIComponent(q)}`);
                renderizarProductos(productos);
            }, 300);
        });

        vaciarCarrito.addEventListener('click', async () => {
            const payload = await jsonFetch('{{ url('/ventas/carrito') }}', {
                method: 'DELETE',
            });
            renderizarCarrito(payload);
        });

        confirmarVenta.addEventListener('click', async () => {
            const payload = await jsonFetch('{{ url('/ventas/confirmar') }}', {
                method: 'POST',
                body: JSON.stringify({
                    id_cliente: idCliente.value || 1,
                    tipo_comprobante: 98,
                    forma_pago: formaPago.value || 'contado',
                    anticipo: anticipoVenta.value || 0,
                }),
            });
            mostrarResultado(payload);
        });

        document.getElementById('confirmar-venta-superior').addEventListener('click', () => {
            confirmarVenta.click();
        });

        document.getElementById('vaciar-carrito-superior').addEventListener('click', () => {
            vaciarCarrito.click();
        });

        formaPago.addEventListener('change', () => {
            actualizarCuentaCorriente(totalCarrito.textContent || 0);
        });

        anticipoVenta.addEventListener('input', () => {
            actualizarCuentaCorriente(totalCarrito.textContent || 0);
        });

        jsonFetch('{{ url('/ventas/carrito') }}').then((payload) => renderizarCarrito(payload));
</script>
@endpush

