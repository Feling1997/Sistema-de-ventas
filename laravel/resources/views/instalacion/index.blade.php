@extends('layouts.app')

@section('titulo', 'Instalacion del sistema')

@section('contenido')
<h1>Instalacion guiada</h1>

    <section>
        <h2>Estado inicial</h2>
        <pre>{{ json_encode($estado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        <button type="button" id="preparar">Verificar, crear bases y migrar</button>
    </section>

    <section>
        <h2>Modo Reparaciones</h2>
        <select id="modo">
            <option value="laravel" selected>laravel</option>
        </select>
        <button type="button" id="guardar-modo">Guardar modo</button>
    </section>

    <section>
        <h2>Resultado</h2>
        <pre id="resultado">Listo.</pre>
    </section>
@endsection

@push('scripts')
<script>
const token = document.querySelector('meta[name="csrf-token"]').content;
const resultado = document.getElementById('resultado');

function enviar(url, datos) {
    const form = new FormData();
    Object.keys(datos).forEach(key => form.append(key, datos[key]));
    fetch(url, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'},
        body: form,
    })
        .then(response => response.json())
        .then(data => resultado.textContent = JSON.stringify(data, null, 2));
}

document.getElementById('preparar').addEventListener('click', () => enviar('{{ url('/instalacion/preparar') }}', {}));
document.getElementById('guardar-modo').addEventListener('click', () => enviar('{{ url('/instalacion/modo') }}', {modo: document.getElementById('modo').value}));
</script>
@endpush

