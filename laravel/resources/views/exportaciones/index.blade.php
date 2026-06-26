@extends('layouts.app')

@section('titulo', 'Exportaciones e Importaciones')

@section('contenido')
<h1>Exportaciones e Importaciones</h1>

    <section>
        <h2>Exportar</h2>
        <div class="acciones">
            <button type="button" data-job="{{ url('/exportaciones/productos') }}">Productos</button>
            <button type="button" data-job="{{ url('/exportaciones/stock') }}">Stock</button>
            <button type="button" data-job="{{ url('/exportaciones/clientes') }}">Clientes</button>
            <button type="button" data-job="{{ url('/exportaciones/ventas') }}">Ventas</button>
        </div>
    </section>

    <section>
        <h2>Importar</h2>
        <div class="acciones">
            <button type="button" data-job="{{ url('/importaciones/productos') }}">Productos</button>
            <button type="button" data-job="{{ url('/importaciones/stock') }}">Stock</button>
            <button type="button" data-job="{{ url('/importaciones/clientes') }}">Clientes</button>
        </div>
    </section>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Estado</th>
            <th>Progreso</th>
            <th>Mensaje</th>
            <th>Archivo</th>
        </tr>
        </thead>
        <tbody id="jobs"></tbody>
    </table>
@endsection

@push('scripts')
<script>
const token = document.querySelector('meta[name="csrf-token"]').content;
const cuerpo = document.getElementById('jobs');
const timers = new Map();

function renderJob(job) {
    let row = document.getElementById(`job-${job.id}`);
    if (!row) {
        row = document.createElement('tr');
        row.id = `job-${job.id}`;
        cuerpo.prepend(row);
    }

    row.innerHTML = `
        <td>${job.id}</td>
        <td class="estado">${job.estado}</td>
        <td>${job.porcentaje}%</td>
        <td>${job.mensaje}</td>
        <td>${job.archivo ?? ''}</td>
    `;
}

function consultarJob(id) {
    fetch(`{{ url('/jobs') }}/${id}`)
        .then(response => response.json())
        .then(job => {
            renderJob(job);

            if (job.estado === 'completado' || job.estado === 'error') {
                clearInterval(timers.get(id));
                timers.delete(id);
            }
        });
}

function iniciarJob(url) {
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
    })
        .then(response => response.json())
        .then(job => {
            renderJob(job);
            consultarJob(job.id);
            timers.set(job.id, setInterval(() => consultarJob(job.id), 1000));
        });
}

document.querySelectorAll('[data-job]').forEach(button => {
    button.addEventListener('click', () => iniciarJob(button.dataset.job));
});
</script>
@endpush

