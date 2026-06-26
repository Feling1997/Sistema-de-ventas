@extends('layouts.app')

@section('titulo', 'Configuracion')

@section('contenido')
<div class="compact-screen">
    <div class="module-header">
        <div>
            <h1>Configuracion</h1>
            <p class="text-muted mb-0">Moneda principal y valor del dolar para precios comerciales.</p>
        </div>
    </div>

    <section class="content-panel erp-card">
        <form id="form-configuracion-monedas" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Moneda principal</label>
                <select class="form-select" name="moneda_principal" id="moneda-principal">
                    <option value="ARS" @selected(($configuracion['moneda_principal'] ?? 'ARS') === 'ARS')>ARS</option>
                    <option value="USD" @selected(($configuracion['moneda_principal'] ?? 'ARS') === 'USD')>USD</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Valor del dolar</label>
                <input class="form-control" name="dolar_venta" id="dolar-venta" type="number" step="0.01" min="0.01" value="{{ $configuracion['dolar_venta'] ?? ($configuracion['productos_cotizacion_dolar'] ?? '1220') }}" required>
                <input name="dolar_compra" id="dolar-compra" type="hidden" value="{{ $configuracion['dolar_venta'] ?? ($configuracion['productos_cotizacion_dolar'] ?? '1220') }}">
            </div>
            <input name="dolar_fecha_actualizacion" id="dolar-fecha" type="hidden" value="{{ $configuracion['dolar_fecha_actualizacion'] ?? date('Y-m-d') }}">
            <div class="col-md-4 d-flex gap-2 align-items-end">
                <button class="btn btn-primary" type="submit">Guardar</button>
                <span id="estado-configuracion-monedas" class="text-muted"></span>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
const formConfiguracionMonedas = document.getElementById('form-configuracion-monedas');
const estadoConfiguracionMonedas = document.getElementById('estado-configuracion-monedas');
const tokenConfiguracion = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

formConfiguracionMonedas.addEventListener('submit', async (event) => {
    event.preventDefault();
    document.getElementById('dolar-compra').value = document.getElementById('dolar-venta').value;
    const datos = new FormData(formConfiguracionMonedas);
    const respuesta = await fetch('{{ url('/configuracion') }}', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': tokenConfiguracion,
        },
        body: datos,
    });
    const payload = await respuesta.json();
    estadoConfiguracionMonedas.textContent = payload.mensaje || (respuesta.ok ? 'Guardado.' : 'No se pudo guardar.');
});
</script>
@endpush
