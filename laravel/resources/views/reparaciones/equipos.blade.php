@extends('layouts.app')

@section('titulo', 'Equipos')

@section('contenido')
<div class="compact-screen equipos-screen">
    <div class="module-header">
        <div>
            <h1>Equipos</h1>
            <p class="text-muted mb-0">Consulta y mantenimiento rapido de equipos vinculados a reparaciones.</p>
        </div>
        <div class="acciones mb-0">
            <button type="button" data-bs-toggle="offcanvas" data-bs-target="#panelEquipo">Nuevo Equipo</button>
            <button type="button" id="buscar-equipos">Buscar</button>
        </div>
    </div>

    <section class="resumen">
        <div class="dato">Equipos <strong id="equipos-total">0</strong></div>
        <div class="dato">Activos <strong id="equipos-activos">0</strong></div>
        <div class="dato">Con reparacion <strong id="equipos-con-reparacion">0</strong></div>
    </section>

    <form class="filter-strip erp-card" id="form-equipos">
        <label for="q-equipo">Buscar
            <input id="q-equipo" type="search" autocomplete="off" placeholder="Marca, modelo, serie o contacto">
        </label>
        <label for="marca-equipo">Marca
            <input id="marca-equipo" type="search" autocomplete="off">
        </label>
        <label for="modelo-equipo">Modelo
            <input id="modelo-equipo" type="search" autocomplete="off">
        </label>
        <button type="button" id="limpiar-equipos">Limpiar</button>
    </form>

    <div class="fill-panel">
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Serie</th>
                <th>Contacto</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody id="tabla-equipos"></tbody>
        </table>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="panelEquipo" aria-labelledby="panelEquipoLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="panelEquipoLabel">Equipo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <form id="form-equipo">
            <input id="equipo-id" type="hidden">
            <label>ID Contacto <input id="equipo-contacto-id" name="contacto_id" type="number" min="1" required></label>
            <label>Tipo <input id="equipo-tipo" name="tipo" type="text" value="Telefono"></label>
            <label>Marca <input id="equipo-marca-form" name="marca" type="text"></label>
            <label>Modelo <input id="equipo-modelo-form" name="modelo" type="text"></label>
            <label>Serie <input id="equipo-serie-form" name="serie" type="text"></label>
            <label>Observaciones <textarea id="equipo-observaciones" name="observaciones" rows="3"></textarea></label>
            <div class="acciones">
                <button type="submit">Guardar</button>
                <button type="button" class="btn-light-action" id="limpiar-form-equipo">Nuevo</button>
                <span id="estado-equipo"></span>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const campoEquipo = document.getElementById('q-equipo');
const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const marcaEquipo = document.getElementById('marca-equipo');
const modeloEquipo = document.getElementById('modelo-equipo');
const tablaEquipos = document.getElementById('tabla-equipos');
const totalEquipos = document.getElementById('equipos-total');
const activosEquipos = document.getElementById('equipos-activos');
const conReparacionEquipos = document.getElementById('equipos-con-reparacion');
const formEquipo = document.getElementById('form-equipo');
const estadoEquipo = document.getElementById('estado-equipo');
let esperaEquipo = null;

function renderizarEquipos(items) {
    tablaEquipos.innerHTML = '';
    totalEquipos.textContent = items.length;
    activosEquipos.textContent = items.length;
    conReparacionEquipos.textContent = items.filter(item => item.reparaciones_count > 0).length;
    (items || []).forEach((item) => {
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${item.id ?? ''}</td>
            <td>${item.marca ?? ''}</td>
            <td>${item.modelo ?? ''}</td>
            <td>${item.serie ?? ''}</td>
            <td>${item.contacto ?? item.cliente ?? item.contacto_id ?? ''}</td>
            <td><button type="button" data-editar-equipo="${item.id ?? ''}">Editar</button></td>
        `;
        tablaEquipos.appendChild(fila);
    });
}

function limpiarEquipoForm() {
    formEquipo.reset();
    document.getElementById('equipo-id').value = '';
    document.getElementById('equipo-tipo').value = 'Telefono';
    estadoEquipo.textContent = '';
}

async function cargarEquipos() {
    const q = [campoEquipo.value.trim(), marcaEquipo.value.trim(), modeloEquipo.value.trim()].filter(Boolean).join(' ');
    const respuesta = await fetch(`{{ url('/reparaciones/equipos') }}?q=${encodeURIComponent(q)}`);
    const datos = await respuesta.json();
    renderizarEquipos(datos.data ?? datos);
}

async function abrirEquipo(id) {
    const respuesta = await fetch(`{{ url('/reparaciones/equipos') }}?id=${id}`);
    const payload = await respuesta.json();
    const equipo = payload.equipo || {};
    document.getElementById('equipo-id').value = equipo.id || '';
    document.getElementById('equipo-contacto-id').value = equipo.contacto_id || '';
    document.getElementById('equipo-tipo').value = equipo.tipo || 'Telefono';
    document.getElementById('equipo-marca-form').value = equipo.marca || '';
    document.getElementById('equipo-modelo-form').value = equipo.modelo || '';
    document.getElementById('equipo-serie-form').value = equipo.serie || '';
    document.getElementById('equipo-observaciones').value = equipo.observaciones || '';
    bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelEquipo')).show();
}

campoEquipo.addEventListener('input', () => {
    window.clearTimeout(esperaEquipo);
    esperaEquipo = window.setTimeout(cargarEquipos, 300);
});
marcaEquipo.addEventListener('input', () => {
    window.clearTimeout(esperaEquipo);
    esperaEquipo = window.setTimeout(cargarEquipos, 300);
});
modeloEquipo.addEventListener('input', () => {
    window.clearTimeout(esperaEquipo);
    esperaEquipo = window.setTimeout(cargarEquipos, 300);
});

document.getElementById('buscar-equipos').addEventListener('click', cargarEquipos);
document.getElementById('limpiar-equipos').addEventListener('click', () => {
    campoEquipo.value = '';
    marcaEquipo.value = '';
    modeloEquipo.value = '';
    cargarEquipos();
});
document.querySelector('[data-bs-target="#panelEquipo"]').addEventListener('click', limpiarEquipoForm);
document.getElementById('limpiar-form-equipo').addEventListener('click', limpiarEquipoForm);
function abrirNuevoEquipoDesdeHash() {
    if (window.location.hash === '#nuevo') {
        limpiarEquipoForm();
        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelEquipo')).show();
    }
}
abrirNuevoEquipoDesdeHash();
window.addEventListener('hashchange', abrirNuevoEquipoDesdeHash);
tablaEquipos.addEventListener('click', async (event) => {
    const boton = event.target.closest('[data-editar-equipo]');

    if (boton) {
        await abrirEquipo(boton.dataset.editarEquipo);
    }
});
formEquipo.addEventListener('submit', async (event) => {
    event.preventDefault();
    const id = document.getElementById('equipo-id').value;
    const datos = new FormData(formEquipo);
    const url = id === '' ? '{{ url('/reparaciones/equipos') }}' : `{{ url('/reparaciones/equipos') }}/${id}`;

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
    estadoEquipo.textContent = payload.mensaje || (respuesta.ok ? 'Equipo guardado.' : 'No se pudo guardar.');

    if (respuesta.ok) {
        await cargarEquipos();
        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelEquipo')).hide();
    }
});
cargarEquipos();
</script>
@endpush
