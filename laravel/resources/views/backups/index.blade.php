@extends('layouts.app')

@section('titulo', 'Backups')

@section('contenido')
<h1>Backups</h1>

    <section>
        <div class="acciones">
            <button type="button" data-job="{{ url('/backups') }}">Crear backup</button>
            <button type="button" data-job="{{ url('/backups/backblaze') }}">Subir Backblaze</button>
            <button type="button" id="listar-backups">Listar backups</button>
        </div>
    </section>

    <section>
        <h2>Resumen</h2>
        <pre>{{ $resumen['texto'] ?? '' }}</pre>
    </section>

    <section>
        <h2>Estructura</h2>
        <pre>{{ $estructura }}</pre>
    </section>

    <section>
        <h2>Progreso</h2>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Estado</th>
                <th>Progreso</th>
                <th>Mensaje</th>
            </tr>
            </thead>
            <tbody id="jobs"></tbody>
        </table>
    </section>

    <section>
        <h2>Archivos</h2>
        <table>
            <thead>
            <tr>
                <th>Nombre</th>
                <th>TamaÃ±o</th>
                <th>Modificado</th>
            </tr>
            </thead>
            <tbody id="archivos"></tbody>
        </table>
    </section>
@endsection

@push('scripts')
<script>
const token = document.querySelector('meta[name="csrf-token"]').content;
const cuerpoJobs = document.getElementById('jobs');
const cuerpoArchivos = document.getElementById('archivos');
const timers = new Map();

function renderJob(job) {
    let row = document.getElementById(`job-${job.id}`);
    if (!row) {
        row = document.createElement('tr');
        row.id = `job-${job.id}`;
        cuerpoJobs.prepend(row);
    }

    row.innerHTML = `
        <td>${job.id}</td>
        <td class="estado">${job.estado}</td>
        <td>${job.porcentaje}%</td>
        <td>${job.mensaje}</td>
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

function listarBackups() {
    fetch('{{ url('/backups/listar') }}')
        .then(response => response.json())
        .then(data => {
            cuerpoArchivos.innerHTML = '';
            data.backups.forEach(backup => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${backup.nombre}</td>
                    <td>${backup.tamano}</td>
                    <td>${backup.modificado}</td>
                `;
                cuerpoArchivos.appendChild(row);
            });
        });
}

document.querySelectorAll('[data-job]').forEach(button => {
    button.addEventListener('click', () => iniciarJob(button.dataset.job));
});
document.getElementById('listar-backups').addEventListener('click', listarBackups);
listarBackups();
</script>
@endpush

