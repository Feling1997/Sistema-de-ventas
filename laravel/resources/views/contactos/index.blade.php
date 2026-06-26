@extends('layouts.app')

@section('titulo', request()->is('clientes*') ? 'Clientes' : 'Contactos')

@section('contenido')
<div class="compact-screen contactos-screen">
<div class="module-header">
    <div>
        <h1>{{ request()->is('clientes*') ? 'Clientes' : 'Contactos' }}</h1>
        <p class="text-muted mb-0">Busqueda incremental por nombre, telefono o documento.</p>
    </div>
    <div class="module-toolbar">
        <button type="button" data-bs-toggle="offcanvas" data-bs-target="#panelContacto">{{ request()->is('clientes*') ? 'Nuevo Cliente' : 'Nuevo Contacto' }}</button>
        <button type="button" id="boton-buscar-contacto">Buscar</button>
        @if (request()->is('clientes*'))
            <a class="btn btn-light-action btn-sm" href="{{ url('/exportaciones') }}">Exportar</a>
        @endif
    </div>
</div>

        <section class="resumen">
            <div class="dato">Total clientes <strong id="contactos-total">0</strong></div>
            <div class="dato">Activos <strong id="contactos-activos">0</strong></div>
            <div class="dato">Con deuda <strong id="contactos-deuda">0</strong></div>
        </section>

        <section class="filter-strip erp-card">
            <label for="buscar-contacto">Buscar
                <input id="buscar-contacto" type="search" autocomplete="off" placeholder="Nombre, telefono o documento">
            </label>
            <label for="telefono-contacto">Telefono
                <input id="telefono-contacto" type="search" autocomplete="off">
            </label>
            <label for="documento-contacto">Documento
                <input id="documento-contacto" type="search" autocomplete="off">
            </label>
            <label for="activo-contacto">Activo
                <select id="activo-contacto">
                    <option value="">Todos</option>
                    <option value="1">Activos</option>
                </select>
            </label>
            <button type="button" id="limpiar-contacto">Limpiar</button>
        </section>

        <div class="fill-panel">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Telefono</th>
                    <th>Documento</th>
                    <th>Correo</th>
                    <th>Direccion</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody id="resultados-contactos"></tbody>
            </table>
        </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="panelContacto" aria-labelledby="panelContactoLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="panelContactoLabel">{{ request()->is('clientes*') ? 'Nuevo Cliente' : 'Nuevo Contacto' }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <form id="form-contacto">
            <input type="hidden" id="contacto-id">
            <label>Nombre <input id="contacto-nombre" name="nombre" type="text" required></label>
            <label>Apellido <input id="contacto-apellido" name="apellido" type="text"></label>
            <label>Telefono <input id="contacto-telefono" name="telefono" type="text"></label>
            <label>Documento <input id="contacto-documento" name="documento" type="text"></label>
            <label>Correo <input id="contacto-correo" name="correo" type="email"></label>
            <label>Direccion <input id="contacto-direccion" name="direccion" type="text"></label>
            <label>Observaciones <textarea id="contacto-observaciones" name="observaciones" rows="3"></textarea></label>
            <label>Activo
                <select id="contacto-activo-form" name="activo">
                    <option value="1">Si</option>
                    <option value="0">No</option>
                </select>
            </label>
            <div class="acciones">
                <button type="submit">Guardar</button>
                <button type="button" class="btn-light-action" id="nuevo-contacto-limpiar">Nuevo</button>
                <span id="estado-contacto"></span>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const campo = document.getElementById('buscar-contacto');
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const telefono = document.getElementById('telefono-contacto');
        const documento = document.getElementById('documento-contacto');
        const resultados = document.getElementById('resultados-contactos');
        const total = document.getElementById('contactos-total');
        const activos = document.getElementById('contactos-activos');
        const deuda = document.getElementById('contactos-deuda');
        const formContacto = document.getElementById('form-contacto');
        const estadoContacto = document.getElementById('estado-contacto');
        let espera = null;
        let ultimosItems = [];

        const renderizar = (items) => {
            resultados.innerHTML = '';
            ultimosItems = items;
            total.textContent = items.length;
            activos.textContent = items.filter((item) => item.activo !== false).length;
            deuda.textContent = '0';

            items.forEach((item) => {
                const fila = document.createElement('tr');
                const nombre = [item.nombre, item.apellido].filter(Boolean).join(' ');
                fila.innerHTML = `
                    <td>${item.id ?? ''}</td>
                    <td>${nombre}</td>
                    <td>${item.telefono ?? ''}</td>
                    <td>${item.documento ?? ''}</td>
                    <td>${item.correo ?? ''}</td>
                    <td>${item.direccion ?? ''}</td>
                    <td class="acciones">
                        <button type="button" data-editar-contacto="${item.id ?? 0}">Editar</button>
                        <button type="button" data-desactivar-contacto="${item.id ?? 0}">Desactivar</button>
                    </td>
                `;
                resultados.appendChild(fila);
            });
        };

        const limpiarFormulario = () => {
            formContacto.reset();
            document.getElementById('contacto-id').value = '';
            estadoContacto.textContent = '';
        };

        const cargarFormulario = (item) => {
            document.getElementById('contacto-id').value = item.id ?? '';
            document.getElementById('contacto-nombre').value = item.nombre ?? '';
            document.getElementById('contacto-apellido').value = item.apellido ?? '';
            document.getElementById('contacto-telefono').value = item.telefono ?? '';
            document.getElementById('contacto-documento').value = item.documento ?? '';
            document.getElementById('contacto-correo').value = item.correo ?? '';
            document.getElementById('contacto-direccion').value = item.direccion ?? '';
            document.getElementById('contacto-observaciones').value = item.observaciones ?? '';
            document.getElementById('contacto-activo-form').value = item.activo === false ? '0' : '1';
        };

        const buscar = () => {
            window.clearTimeout(espera);
            espera = window.setTimeout(async () => {
                const q = [campo.value.trim(), telefono.value.trim(), documento.value.trim()].filter(Boolean).join(' ');

                if (q === '') {
                    renderizar([]);
                    return;
                }

                const respuesta = await fetch(`{{ url('/contactos/autocompletar') }}?q=${encodeURIComponent(q)}`);
                const datos = await respuesta.json();
                renderizar(datos);
            }, 300);
        };

        campo.addEventListener('input', buscar);
        telefono.addEventListener('input', buscar);
        documento.addEventListener('input', buscar);

        document.getElementById('boton-buscar-contacto').addEventListener('click', () => {
            buscar();
        });
        document.getElementById('limpiar-contacto').addEventListener('click', () => {
            campo.value = '';
            telefono.value = '';
            documento.value = '';
            renderizar([]);
        });
        document.getElementById('nuevo-contacto-limpiar').addEventListener('click', limpiarFormulario);
        document.querySelector('[data-bs-target="#panelContacto"]').addEventListener('click', limpiarFormulario);
        const abrirNuevoContactoDesdeHash = () => {
            if (window.location.hash === '#nuevo') {
                limpiarFormulario();
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelContacto')).show();
            }
        };
        abrirNuevoContactoDesdeHash();
        window.addEventListener('hashchange', abrirNuevoContactoDesdeHash);
        resultados.addEventListener('click', async (event) => {
            const editar = event.target.closest('[data-editar-contacto]');
            const desactivar = event.target.closest('[data-desactivar-contacto]');

            if (editar) {
                const id = Number.parseInt(editar.dataset.editarContacto || '0', 10);
                const item = ultimosItems.find((contacto) => Number(contacto.id) === id);

                if (item) {
                    cargarFormulario(item);
                    bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelContacto')).show();
                }
            }

            if (desactivar) {
                const id = Number.parseInt(desactivar.dataset.desactivarContacto || '0', 10);
                const confirmado = window.confirm('Desactivar este contacto?');

            if (confirmado) {
                    const respuesta = await fetch(`{{ url('/contactos') }}/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                    });
                    estadoContacto.textContent = respuesta.ok ? 'Contacto desactivado.' : 'No se pudo desactivar.';
                    const item = ultimosItems.find((contacto) => Number(contacto.id) === id);
                    if (item && campo.value.trim() === '') {
                        campo.value = item.nombre || item.telefono || item.documento || '';
                    }
                    buscar();
                }
            }
        });
        formContacto.addEventListener('submit', async (event) => {
            event.preventDefault();
            const id = document.getElementById('contacto-id').value;
            const url = id === '' ? '{{ url('/contactos') }}' : `{{ url('/contactos') }}/${id}`;
            const metodo = id === '' ? 'POST' : 'POST';
            const datos = new FormData(formContacto);

            if (id !== '') {
                datos.append('_method', 'PUT');
            }

            const respuesta = await fetch(url, {
                method: metodo,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: datos,
            });
            const payload = await respuesta.json();
            estadoContacto.textContent = respuesta.ok ? 'Contacto guardado.' : 'No se pudo guardar.';

            if (respuesta.ok) {
                const contacto = payload.contacto ?? payload;
                cargarFormulario(contacto);
                campo.value = contacto.nombre || contacto.telefono || contacto.documento || campo.value;
                buscar();
                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelContacto')).hide();
            }
        });
</script>
@endpush

