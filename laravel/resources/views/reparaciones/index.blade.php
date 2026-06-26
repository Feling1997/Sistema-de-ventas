@extends('layouts.app')

@section('titulo', 'Reparaciones')

@section('contenido')
<div class="compact-screen reparaciones-screen">
    <div class="module-header">
        <div>
            <h1>Reparaciones</h1>
            <p class="text-muted mb-0">Gestion de trabajos, estados, tickets y equipos.</p>
        </div>
        <div class="module-toolbar">
            <button type="button" data-bs-toggle="offcanvas" data-bs-target="#panelNuevaReparacion">Nueva Reparacion</button>
            <button type="button" id="boton-buscar-reparaciones">Buscar</button>
            <a class="btn btn-light-action btn-sm" href="{{ url('/reparaciones/resumen') }}">Resumen</a>
            <a class="btn btn-light-action btn-sm" href="{{ url('/reparaciones/configuracion') }}">Configuracion</a>
        </div>
    </div>

    <section class="resumen" id="resumen">
        <div class="dato">Total <strong data-k="total">0</strong></div>
        <div class="dato">Pendientes <strong data-k="pendientes">0</strong></div>
        <div class="dato">En reparacion <strong data-k="en_reparacion">0</strong></div>
        <div class="dato">Terminadas <strong data-k="reparadas">0</strong></div>
        <div class="dato">Presupuesto total <strong data-k="total_presupuestado">0</strong></div>
    </section>

    <section class="filter-strip">
        <label>Buscar
            <input id="q" type="search" placeholder="Codigo, cliente, telefono o problema">
        </label>
        <label>Estado
            <select id="estado"><option value="">Todos</option></select>
        </label>
        <label>Activo
            <select id="activo">
                <option value="">Todos</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
        </label>
        <label>Desde
            <input id="fecha_desde" type="date">
        </label>
        <label>Hasta
            <input id="fecha_hasta" type="date">
        </label>
        <button type="button" id="limpiar">Limpiar</button>
    </section>

    <div class="fill-panel">
    <table>
        <thead>
        <tr>
            <th>Codigo</th>
            <th>Cliente</th>
            <th>Equipo</th>
            <th>Estado</th>
            <th>Ingreso</th>
            <th>Precio</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody id="reparaciones"></tbody>
    </table>
    </div>
    <div class="paginacion">
        <button type="button" id="anterior">Anterior</button>
        <span id="pagina">Pagina 1</span>
        <button type="button" id="siguiente">Siguiente</button>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="panelNuevaReparacion" aria-labelledby="panelNuevaReparacionLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="panelNuevaReparacionLabel">Nueva Reparacion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <div class="compact-screen">
            <form id="form-reparacion">
            <input id="reparacion-id" type="hidden">
            <input id="reparacion-contacto-id" name="contacto_id" type="hidden">
            <input id="reparacion-equipo-id" name="equipo_id" type="hidden">
            <label>Cliente <input id="reparacion-contacto" type="search" placeholder="Buscar contacto"></label>
            <ul id="reparacion-contactos"></ul>
            <label>Equipo <input id="reparacion-equipo" type="search" placeholder="Marca, modelo o serie"></label>
            <ul id="reparacion-equipos"></ul>
            <label>Problema <textarea id="reparacion-problema" name="problema" rows="3" required></textarea></label>
            <label>Diagnostico <textarea id="reparacion-diagnostico" name="diagnostico" rows="2"></textarea></label>
            <label>Estado <select id="reparacion-estado" name="estado_id"></select></label>
            <label>Garantia <input id="reparacion-garantia" name="garantia" type="text"></label>
            <label>Precio <input id="reparacion-precio" name="precio" type="number" step="0.01" value="0"></label>
            <label>Fecha ingreso <input id="reparacion-fecha-ingreso" name="fecha_ingreso" type="datetime-local"></label>
            <label>Fecha entrega <input id="reparacion-fecha-entrega" name="fecha_entrega" type="datetime-local"></label>
            <label>Observaciones <textarea id="reparacion-observaciones" name="observaciones" rows="3"></textarea></label>
            <div class="acciones">
                <button type="submit">Guardar</button>
                <button type="button" class="btn-light-action" id="limpiar-reparacion-form">Nueva</button>
                <button type="button" class="btn-light-action" data-bs-dismiss="offcanvas">Cancelar</button>
                <a class="btn-light-action" id="ticket-reparacion-form" href="#" target="_blank">Imprimir Ticket</a>
                <span id="estado-reparacion-form"></span>
            </div>
            </form>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="panelAdjuntos" aria-labelledby="panelAdjuntosLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="panelAdjuntosLabel">Adjuntos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <form id="form-adjunto">
            <input id="adjunto-reparacion-id" type="hidden">
            <label>Archivo <input id="adjunto-archivo" name="archivo" type="file" accept="image/*,.pdf"></label>
            <button type="submit">Agregar adjunto</button>
            <span id="estado-adjunto"></span>
        </form>
        <ul id="lista-adjuntos"></ul>
    </div>
</div>
@endsection

@push('scripts')
<script>
const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const cuerpo = document.getElementById('reparaciones');
const estado = document.getElementById('estado');
const estadoForm = document.getElementById('reparacion-estado');
const filtros = ['q', 'estado', 'activo', 'fecha_desde', 'fecha_hasta'];
let timer = null;
let pagina = 1;
let ultimaPagina = 1;
let estadosCache = [];

function debounce() {
    clearTimeout(timer);
    timer = setTimeout(() => {
        pagina = 1;
        cargar();
    }, 300);
}

function parametros() {
    const params = new URLSearchParams();
    params.set('page', String(pagina));
    filtros.forEach(id => {
        const valor = document.getElementById(id).value.trim();
        if (valor !== '') {
            params.set(id, valor);
        }
    });
    return params.toString();
}

function cargarEstados() {
    fetch('{{ url('/reparaciones/estados') }}')
        .then(response => response.json())
        .then(items => {
            estadosCache = items;
            estadoForm.innerHTML = '';
            items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.nombre;
                option.textContent = item.nombre;
                estado.appendChild(option);
                const optionForm = document.createElement('option');
                optionForm.value = item.id;
                optionForm.textContent = item.nombre;
                estadoForm.appendChild(optionForm);
            });
        });
}

function cargarResumen() {
    fetch('{{ url('/reparaciones/resumen') }}')
        .then(response => response.json())
        .then(data => {
            document.querySelectorAll('[data-k]').forEach(item => {
                const key = item.dataset.k;
                item.textContent = data[key] ?? 0;
            });
        });
}

function cargar() {
    fetch(`{{ url('/reparaciones/buscar') }}?${parametros()}`)
        .then(response => response.json())
        .then(data => {
            ultimaPagina = data.pagination?.last_page ?? 1;
            document.getElementById('pagina').textContent = `Pagina ${data.pagination?.current_page ?? 1} de ${ultimaPagina}`;
            cuerpo.innerHTML = '';
            (data.data ?? []).forEach(reparacion => cuerpo.appendChild(fila(reparacion)));
        });
    cargarResumen();
}

function fila(reparacion) {
    const row = document.createElement('tr');
    const estadoNombre = reparacion.estado ?? '';
    row.innerHTML = `
        <td>${reparacion.codigo ?? reparacion.id ?? ''}</td>
        <td>${reparacion.cliente ?? ''}<br><small>${reparacion.telefono ?? ''}</small></td>
        <td>${reparacion.equipo ?? ''}</td>
        <td><span class="badge ${estadoNombre}">${estadoNombre}</span></td>
        <td>${reparacion.fecha_ingreso ?? ''}</td>
        <td>${reparacion.precio ?? '0.00'}</td>
        <td class="acciones">
            <button type="button" data-editar-reparacion="${reparacion.id}">Editar</button>
            <a href="{{ url('/reparaciones') }}/${reparacion.id}/ticket" target="_blank">Ticket</a>
            <button type="button" data-adjuntos="${reparacion.id}">Adjuntos</button>
            <button type="button" data-desactivar-reparacion="${reparacion.id}">Desactivar</button>
        </td>
    `;
    return row;
}

function limpiar() {
    filtros.forEach(id => document.getElementById(id).value = '');
    pagina = 1;
    cargar();
}

filtros.forEach(id => document.getElementById(id).addEventListener('input', debounce));
estado.addEventListener('change', debounce);
document.getElementById('limpiar').addEventListener('click', limpiar);
document.getElementById('boton-buscar-reparaciones').addEventListener('click', () => {
    pagina = 1;
    cargar();
});
document.getElementById('anterior').addEventListener('click', () => {
    pagina = Math.max(1, pagina - 1);
    cargar();
});
document.getElementById('siguiente').addEventListener('click', () => {
    pagina = Math.min(ultimaPagina, pagina + 1);
    cargar();
});

function limpiarFormularioReparacion() {
    document.getElementById('form-reparacion').reset();
    document.getElementById('reparacion-id').value = '';
    document.getElementById('reparacion-contacto-id').value = '';
    document.getElementById('reparacion-equipo-id').value = '';
    document.getElementById('reparacion-contactos').innerHTML = '';
    document.getElementById('reparacion-equipos').innerHTML = '';
    document.getElementById('estado-reparacion-form').textContent = '';
    document.getElementById('ticket-reparacion-form').href = '#';
}

function formatearFechaInput(valor) {
    return valor ? String(valor).replace(' ', 'T').slice(0, 16) : '';
}

function cargarFormularioReparacion(reparacion) {
    document.getElementById('reparacion-id').value = reparacion.id ?? '';
    document.getElementById('reparacion-contacto-id').value = reparacion.contacto_id ?? '';
    document.getElementById('reparacion-equipo-id').value = reparacion.equipo_id ?? '';
    document.getElementById('reparacion-contacto').value = reparacion.contacto?.nombre || reparacion.cliente || '';
    document.getElementById('reparacion-equipo').value = reparacion.equipo?.marca ? `${reparacion.equipo.marca || ''} ${reparacion.equipo.modelo || ''}` : (reparacion.equipo || '');
    document.getElementById('reparacion-problema').value = reparacion.problema ?? '';
    document.getElementById('reparacion-diagnostico').value = reparacion.diagnostico ?? '';
    document.getElementById('reparacion-estado').value = reparacion.estado_id ?? '';
    document.getElementById('reparacion-garantia').value = reparacion.garantia ?? '';
    document.getElementById('reparacion-precio').value = reparacion.precio ?? 0;
    document.getElementById('reparacion-fecha-ingreso').value = formatearFechaInput(reparacion.fecha_ingreso);
    document.getElementById('reparacion-fecha-entrega').value = formatearFechaInput(reparacion.fecha_entrega);
    document.getElementById('reparacion-observaciones').value = reparacion.observaciones ?? '';
    document.getElementById('ticket-reparacion-form').href = reparacion.id ? `{{ url('/reparaciones') }}/${reparacion.id}/ticket` : '#';
}

function renderizarOpciones(contenedor, items, callback) {
    contenedor.innerHTML = '';
    items.forEach(item => {
        const li = document.createElement('li');
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.textContent = callback(item);
        boton.addEventListener('click', () => {
            contenedor.dispatchEvent(new CustomEvent('seleccionar', { detail: item }));
            contenedor.innerHTML = '';
        });
        li.appendChild(boton);
        contenedor.appendChild(li);
    });
}

document.querySelector('[data-bs-target="#panelNuevaReparacion"]').addEventListener('click', limpiarFormularioReparacion);
document.getElementById('limpiar-reparacion-form').addEventListener('click', limpiarFormularioReparacion);
function abrirNuevaReparacionDesdeHash() {
    if (window.location.hash === '#nuevo') {
        limpiarFormularioReparacion();
        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelNuevaReparacion')).show();
    }
}
abrirNuevaReparacionDesdeHash();
window.addEventListener('hashchange', abrirNuevaReparacionDesdeHash);
document.getElementById('reparacion-contacto').addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        const q = document.getElementById('reparacion-contacto').value.trim();
        const respuesta = q === '' ? null : await fetch(`{{ url('/reparaciones/contactos') }}?q=${encodeURIComponent(q)}`);
        const datos = respuesta === null ? [] : await respuesta.json();
        renderizarOpciones(document.getElementById('reparacion-contactos'), datos, item => `${item.nombre || ''} ${item.apellido || ''} ${item.telefono || ''}`);
    }, 300);
});
document.getElementById('reparacion-contactos').addEventListener('seleccionar', event => {
    document.getElementById('reparacion-contacto-id').value = event.detail.id || '';
    document.getElementById('reparacion-contacto').value = `${event.detail.nombre || ''} ${event.detail.apellido || ''}`.trim();
});
document.getElementById('reparacion-equipo').addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        const q = document.getElementById('reparacion-equipo').value.trim();
        const respuesta = q === '' ? null : await fetch(`{{ url('/reparaciones/equipos') }}?q=${encodeURIComponent(q)}`);
        const datos = respuesta === null ? { data: [] } : await respuesta.json();
        renderizarOpciones(document.getElementById('reparacion-equipos'), datos.data || [], item => `${item.marca || ''} ${item.modelo || ''} ${item.serie || ''}`);
    }, 300);
});
document.getElementById('reparacion-equipos').addEventListener('seleccionar', event => {
    document.getElementById('reparacion-equipo-id').value = event.detail.id || '';
    document.getElementById('reparacion-equipo').value = `${event.detail.marca || ''} ${event.detail.modelo || ''}`.trim();
});
document.getElementById('form-reparacion').addEventListener('submit', async event => {
    event.preventDefault();
    const id = document.getElementById('reparacion-id').value;
    const datos = new FormData(event.target);
    const url = id === '' ? '{{ url('/reparaciones') }}' : `{{ url('/reparaciones') }}/${id}`;

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
    document.getElementById('estado-reparacion-form').textContent = payload.mensaje || (respuesta.ok ? 'Reparacion guardada.' : 'No se pudo guardar.');

    if (respuesta.ok) {
        if (payload.id) {
            document.getElementById('reparacion-id').value = payload.id;
            document.getElementById('ticket-reparacion-form').href = `{{ url('/reparaciones') }}/${payload.id}/ticket`;
        }
        cargar();
        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelNuevaReparacion')).hide();
    }
});
cuerpo.addEventListener('click', async event => {
    const editar = event.target.closest('[data-editar-reparacion]');
    const adjuntos = event.target.closest('[data-adjuntos]');
    const desactivar = event.target.closest('[data-desactivar-reparacion]');

    if (editar) {
        const respuesta = await fetch(`{{ url('/reparaciones') }}/${editar.dataset.editarReparacion}`);
        const payload = await respuesta.json();
        cargarFormularioReparacion(payload.reparacion || {});
        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelNuevaReparacion')).show();
    }

    if (adjuntos) {
        await cargarAdjuntos(adjuntos.dataset.adjuntos);
        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelAdjuntos')).show();
    }

    if (desactivar) {
        const confirmado = window.confirm('Desactivar esta reparacion?');

        if (confirmado) {
            const respuesta = await fetch(`{{ url('/reparaciones') }}/${desactivar.dataset.desactivarReparacion}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
            });
            const payload = await respuesta.json();
            document.getElementById('estado-reparacion-form').textContent = payload.mensaje || (respuesta.ok ? 'Reparacion desactivada.' : 'No se pudo desactivar.');
            cargar();
        }
    }
});

async function cargarAdjuntos(id) {
    document.getElementById('adjunto-reparacion-id').value = id;
    const respuesta = await fetch(`{{ url('/reparaciones') }}/${id}/adjuntos`);
    const payload = await respuesta.json();
    const lista = document.getElementById('lista-adjuntos');
    lista.innerHTML = '';
    (payload.data || []).forEach(adjunto => {
        const li = document.createElement('li');
        li.innerHTML = `${adjunto.nombre_original || adjunto.ruta || adjunto.id} <button type="button" data-eliminar-adjunto="${adjunto.id}">Eliminar</button>`;
        lista.appendChild(li);
    });
}

document.getElementById('form-adjunto').addEventListener('submit', async event => {
    event.preventDefault();
    const id = document.getElementById('adjunto-reparacion-id').value;
    const respuesta = await fetch(`{{ url('/reparaciones') }}/${id}/adjuntos`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
        },
        body: new FormData(event.target),
    });
    const payload = await respuesta.json();
    document.getElementById('estado-adjunto').textContent = payload.mensaje || (respuesta.ok ? 'Adjunto agregado.' : 'No se pudo agregar.');
    await cargarAdjuntos(id);
    if (respuesta.ok) {
        event.target.reset();
    }
});
document.getElementById('lista-adjuntos').addEventListener('click', async event => {
    const boton = event.target.closest('[data-eliminar-adjunto]');

    if (boton) {
        await fetch(`{{ url('/reparaciones/adjuntos') }}/${boton.dataset.eliminarAdjunto}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
        });
        await cargarAdjuntos(document.getElementById('adjunto-reparacion-id').value);
    }
});

cargarEstados();
cargar();
</script>
@endpush

