@extends('layouts.app')

@section('titulo', 'Cuentas Corrientes')

@section('contenido')
<h1>Cuentas Corrientes</h1>

        <section>
            <h2>Resumen</h2>
            <p>Cuentas: <span id="resumen-cuentas">{{ $resumen['cuentas'] ?? 0 }}</span></p>
            <p>Saldo: <span id="resumen-saldo">{{ $resumen['saldo'] ?? 0 }}</span></p>
            <p>Vencidas: <span id="resumen-vencidas">{{ $resumen['vencidas'] ?? 0 }}</span></p>
            <p>Proximas: <span id="resumen-proximas">{{ $resumen['proximas'] ?? 0 }}</span></p>
        </section>

        <form method="get" action="{{ url('/cuentas-corrientes') }}">
            <label for="buscar-cuenta">Buscar cliente o concepto</label>
            <input id="buscar-cuenta" name="q" type="search" value="{{ $q }}" autocomplete="off">
            <button type="submit">Buscar</button>
        </form>

        <section>
            <h2>Cliente</h2>
            <input id="buscar-cliente-cuenta" type="search" autocomplete="off">
            <ul id="sugerencias-clientes-cuenta"></ul>
        </section>

        <section>
            <h2>Registrar pago</h2>
            <form id="form-pago-cuenta">
                <label>ID cuenta
                    <input id="pago-id-cuenta" name="id_cuenta" type="number" min="1" required>
                </label>
                <label>Importe
                    <input id="pago-importe" name="importe" type="number" min="0.01" step="0.01" required>
                </label>
                <label>Forma de pago
                    <select name="forma_pago">
                        <option value="contado">Contado</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="tarjeta">Tarjeta</option>
                    </select>
                </label>
                <label>Observacion
                    <input name="observacion" type="text">
                </label>
                <button type="submit">Registrar pago</button>
                <span id="estado-pago-cuenta"></span>
            </form>
        </section>

        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Concepto</th>
                    <th>Vencimiento</th>
                    <th>Monto</th>
                    <th>Pendiente</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cuotas as $cuota)
                    <tr>
                        <td>{{ $cuota['cliente_nombre'] ?? '' }}</td>
                        <td>{{ $cuota['concepto'] ?? '' }}</td>
                        <td>{{ $cuota['vencimiento'] ?? '' }}</td>
                        <td>{{ $cuota['monto'] ?? 0 }}</td>
                        <td>{{ $cuota['pendiente'] ?? 0 }}</td>
                        <td>{{ ($cuota['vencida'] ?? 0) ? 'Vencida' : 'Pendiente' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No hay cuotas pendientes para mostrar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $cuotas->links() }}
@endsection

@push('scripts')
<script>
const buscarClienteCuenta = document.getElementById('buscar-cliente-cuenta');
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const sugerenciasClientesCuenta = document.getElementById('sugerencias-clientes-cuenta');
        const formPagoCuenta = document.getElementById('form-pago-cuenta');
        const estadoPagoCuenta = document.getElementById('estado-pago-cuenta');
        let esperaClienteCuenta = null;

        const renderizarClientesCuenta = (clientes) => {
            sugerenciasClientesCuenta.innerHTML = '';

            clientes.forEach((cliente) => {
                const li = document.createElement('li');
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.textContent = `${cliente.nombre || ''} ${cliente.documento || ''} ${cliente.telefono || ''}`;
                boton.addEventListener('click', async () => {
                    const respuesta = await fetch(`{{ url('/cuentas-corrientes/saldo') }}?id=${cliente.id || 0}`);
                    const cuenta = await respuesta.json();
                    document.getElementById('pago-id-cuenta').value = cuenta.id || '';
                });
                li.appendChild(boton);
                sugerenciasClientesCuenta.appendChild(li);
            });
        };

        buscarClienteCuenta.addEventListener('input', () => {
            window.clearTimeout(esperaClienteCuenta);
            esperaClienteCuenta = window.setTimeout(async () => {
                const q = buscarClienteCuenta.value.trim();
                const respuesta = q === '' ? null : await fetch(`{{ url('/cuentas-corrientes/cliente') }}?q=${encodeURIComponent(q)}`);
                const datos = respuesta === null ? [] : await respuesta.json();
                renderizarClientesCuenta(datos);
            }, 300);
        });
        formPagoCuenta.addEventListener('submit', async (event) => {
            event.preventDefault();
            const respuesta = await fetch('{{ url('/cuentas-corrientes/pago') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: new FormData(formPagoCuenta),
            });
            const payload = await respuesta.json();
            estadoPagoCuenta.textContent = payload.mensaje || payload.error || (respuesta.ok ? 'Pago registrado.' : 'No se pudo registrar.');
        });
</script>
@endpush

