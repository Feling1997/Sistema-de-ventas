@extends('layouts.app')

@section('titulo', 'Presupuestos')

@section('contenido')
<h1>Presupuestos</h1>

        <form method="get" action="{{ url('/presupuestos') }}">
            <label for="buscar-presupuesto">Buscar presupuesto por ID</label>
            <input id="buscar-presupuesto" name="q" type="search" value="{{ $q }}" autocomplete="off">
            <button type="submit">Buscar</button>
        </form>

        <section>
            <h2>Busquedas rapidas</h2>
            <label for="buscar-cliente-presupuesto">Cliente</label>
            <input id="buscar-cliente-presupuesto" type="search" autocomplete="off">
            <ul id="sugerencias-clientes-presupuesto"></ul>
            <label for="buscar-producto-presupuesto">Producto</label>
            <input id="buscar-producto-presupuesto" type="search" autocomplete="off">
            <ul id="sugerencias-productos-presupuesto"></ul>
        </section>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Subtotal</th>
                    <th>Descuento</th>
                    <th>Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($presupuestos as $presupuesto)
                    <tr>
                        <td>{{ $presupuesto['id'] ?? '' }}</td>
                        <td>{{ $presupuesto['cliente'] ?? '' }}</td>
                        <td>{{ $presupuesto['fecha'] ?? '' }}</td>
                        <td>{{ $presupuesto['subtotal'] ?? 0 }}</td>
                        <td>{{ $presupuesto['descuento'] ?? 0 }}</td>
                        <td>{{ $presupuesto['total'] ?? 0 }}</td>
                        <td>
                            <a href="{{ url('/presupuestos/' . ($presupuesto['id'] ?? 0)) }}">Ver</a>
                            <a href="{{ url('/presupuestos/' . ($presupuesto['id'] ?? 0) . '/ticket') }}">Ticket</a>
                            <a href="{{ url('/presupuestos/' . ($presupuesto['id'] ?? 0) . '/pdf') }}">PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Busca un presupuesto por ID.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $presupuestos->links() }}
@endsection

@push('scripts')
<script>
const cliente = document.getElementById('buscar-cliente-presupuesto');
        const producto = document.getElementById('buscar-producto-presupuesto');
        const listaClientes = document.getElementById('sugerencias-clientes-presupuesto');
        const listaProductos = document.getElementById('sugerencias-productos-presupuesto');
        let esperaCliente = null;
        let esperaProducto = null;

        const renderizar = (contenedor, items, etiqueta) => {
            contenedor.innerHTML = '';
            items.forEach((item) => {
                const li = document.createElement('li');
                li.textContent = etiqueta(item);
                contenedor.appendChild(li);
            });
        };

        cliente.addEventListener('input', () => {
            window.clearTimeout(esperaCliente);
            esperaCliente = window.setTimeout(async () => {
                const q = cliente.value.trim();
                const respuesta = q === '' ? null : await fetch(`{{ url('/presupuestos/clientes') }}?q=${encodeURIComponent(q)}`);
                const datos = respuesta === null ? [] : await respuesta.json();
                renderizar(listaClientes, datos, (item) => `${item.nombre || ''} ${item.documento || ''}`);
            }, 300);
        });

        producto.addEventListener('input', () => {
            window.clearTimeout(esperaProducto);
            esperaProducto = window.setTimeout(async () => {
                const q = producto.value.trim();
                const respuesta = q === '' ? null : await fetch(`{{ url('/presupuestos/productos') }}?q=${encodeURIComponent(q)}`);
                const datos = respuesta === null ? [] : await respuesta.json();
                renderizar(listaProductos, datos, (item) => `${item.cod_barras || ''} ${item.nombre || ''} ${item.precio || item.precio_final || 0}`);
            }, 300);
        });
</script>
@endpush

