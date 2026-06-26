@extends('layouts.app')

@section('titulo', 'Configuracion de reparaciones')

@section('contenido')
<h1>Configuracion de reparaciones</h1>
    <form id="configuracion-form">
        <label>Nombre del comercio
            <input name="nombre_comercio" value="{{ $configuracion['nombre_comercio'] ?? '' }}">
        </label>
        <label>Telefono
            <input name="telefono_comercio" value="{{ $configuracion['telefono_comercio'] ?? '' }}">
        </label>
        <label>Direccion
            <input name="direccion_comercio" value="{{ $configuracion['direccion_comercio'] ?? '' }}">
        </label>
        <label>Impresora predeterminada
            <input name="impresora_predeterminada" value="{{ $configuracion['impresora_predeterminada'] ?? '' }}">
        </label>
        <label>Mostrar logo
            <select name="mostrar_logo">
                <option value="0" @selected(($configuracion['mostrar_logo'] ?? '0') === '0')>No</option>
                <option value="1" @selected(($configuracion['mostrar_logo'] ?? '0') === '1')>Si</option>
            </select>
        </label>
        <label>Texto ticket
            <textarea name="texto_ticket">{{ $configuracion['texto_ticket'] ?? '' }}</textarea>
        </label>
        <label>Observaciones ticket
            <textarea name="observaciones_ticket">{{ $configuracion['observaciones_ticket'] ?? '' }}</textarea>
        </label>
        <div class="acciones">
            <button type="submit">Guardar</button>
            <a href="{{ url('/reparaciones') }}">Volver</a>
            <span id="estado" class="estado"></span>
        </div>
    </form>
@endsection

@push('scripts')
<script>
const token = document.querySelector('meta[name="csrf-token"]').content;
const estado = document.getElementById('estado');

document.getElementById('configuracion-form').addEventListener('submit', (event) => {
    event.preventDefault();
    fetch('{{ url('/reparaciones/configuracion') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
        body: new FormData(event.target),
    })
        .then(response => response.json())
        .then(data => {
            estado.textContent = data.mensaje ?? 'Configuracion guardada.';
        });
});
</script>
@endpush

