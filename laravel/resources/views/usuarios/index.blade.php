@extends('layouts.app')

@section('titulo', 'Usuarios')

@php
    $rolesPorId = collect($roles)->keyBy('id');
    $permisosPorModulo = collect($permisos)->groupBy('modulo');
    $rolesPermisos = collect($roles)->mapWithKeys(fn ($rol) => [(string) $rol['id'] => $rol['permisos'] ?? []]);
@endphp

@section('contenido')
<div class="compact-screen usuarios-screen">
    <div class="module-header">
        <div>
            <h1>Usuarios</h1>
            <p class="text-muted mb-0">Usuarios, roles y permisos reales persistidos en sistema_core.</p>
        </div>
        <div class="module-toolbar">
            <button type="button" class="erp-tab active" data-tab="usuarios">Usuarios</button>
            <button type="button" class="erp-tab" data-tab="roles">Roles</button>
            <button type="button" class="erp-tab" data-tab="permisos">Permisos</button>
        </div>
    </div>

    <section class="resumen">
        <div class="dato">Usuarios <strong>{{ count($usuarios) }}</strong></div>
        <div class="dato">Activos <strong>{{ collect($usuarios)->where('activo', true)->count() }}</strong></div>
        <div class="dato">Roles <strong>{{ count($roles) }}</strong></div>
        <div class="dato">Permisos <strong>{{ count($permisos) }}</strong></div>
    </section>

    <section class="tab-panel fill-panel erp-card" data-panel="usuarios">
        <div class="module-header mb-2">
            <div>
                <h2>Usuarios</h2>
                <p class="text-muted mb-0">Alta, edicion, desactivacion y asignacion de rol.</p>
            </div>
            <button type="button" data-bs-toggle="offcanvas" data-bs-target="#panelUsuario">Nuevo</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Ultimo acceso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    @php
                        $rolAsignado = $usuario['roles'][0] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $usuario['usuario'] }}</td>
                        <td>{{ $usuario['nombre'] ?: '-' }}</td>
                        <td>{{ $rolAsignado['nombre'] ?? '-' }}</td>
                        <td>{{ $usuario['activo'] ? 'Activo' : 'Inactivo' }}</td>
                        <td>{{ $usuario['ultimo_acceso'] ?? 'Sin registro' }}</td>
                        <td class="acciones">
                            <button
                                type="button"
                                data-editar-usuario="{{ $usuario['id'] }}"
                                data-nombre="{{ $usuario['nombre'] }}"
                                data-usuario="{{ $usuario['usuario'] }}"
                                data-email="{{ $usuario['email'] }}"
                                data-rol-id="{{ $rolAsignado['id'] ?? '' }}"
                                data-activo="{{ $usuario['activo'] ? '1' : '0' }}"
                            >Editar</button>
                            <button type="button" class="btn-light-action" data-desactivar-usuario="{{ $usuario['id'] }}" data-activo="{{ $usuario['activo'] ? '1' : '0' }}">{{ $usuario['activo'] ? 'Desactivar' : 'Reactivar' }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Sin usuarios.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="tab-panel fill-panel erp-card" data-panel="roles" hidden>
        <div class="module-header mb-2">
            <div>
                <h2>Roles</h2>
                <p class="text-muted mb-0">Roles operativos reales con estado activo/inactivo.</p>
            </div>
            <button type="button" data-bs-toggle="offcanvas" data-bs-target="#panelRol">Nuevo rol</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Rol</th>
                    <th>Descripcion</th>
                    <th>Estado</th>
                    <th>Usuarios</th>
                    <th>Permisos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $rol)
                    <tr>
                        <td>{{ $rol['nombre'] }}</td>
                        <td>{{ $rol['descripcion'] ?: '-' }}</td>
                        <td>{{ $rol['activo'] ? 'Activo' : 'Inactivo' }}</td>
                        <td>{{ collect($usuarios)->filter(fn ($usuario) => collect($usuario['roles'] ?? [])->contains('id', $rol['id']))->count() }}</td>
                        <td>{{ count($rol['permisos'] ?? []) }}</td>
                        <td class="acciones">
                            <button
                                type="button"
                                data-editar-rol="{{ $rol['id'] }}"
                                data-nombre="{{ $rol['nombre'] }}"
                                data-descripcion="{{ $rol['descripcion'] }}"
                                data-activo="{{ $rol['activo'] ? '1' : '0' }}"
                            >Editar</button>
                            <button type="button" class="btn-light-action" data-desactivar-rol="{{ $rol['id'] }}" data-activo="{{ $rol['activo'] ? '1' : '0' }}">{{ $rol['activo'] ? 'Desactivar' : 'Reactivar' }}</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="tab-panel fill-panel erp-card" data-panel="permisos" hidden>
        <div class="module-header mb-2">
            <div>
                <h2>Permisos</h2>
                <p class="text-muted mb-0">Cada switch realiza un request real y persiste en MariaDB.</p>
            </div>
            <label class="mb-0">Permisos del rol
                <select id="rol-permisos">
                    @foreach ($roles as $rol)
                        <option value="{{ $rol['id'] }}">{{ $rol['nombre'] }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="row g-2">
            @foreach ($permisosPorModulo as $modulo => $permisosModulo)
                <div class="col-lg-6">
                    <div class="erp-card h-100">
                        <h3>{{ ucfirst((string) $modulo) }}</h3>
                        @foreach ($permisosModulo as $permiso)
                            <div class="switch-row">
                                <span>{{ $permiso['descripcion'] ?: $permiso['codigo'] }}</span>
                                <label class="erp-switch">
                                    <input
                                        type="checkbox"
                                        data-permiso-id="{{ $permiso['id'] }}"
                                        data-codigo="{{ $permiso['codigo'] }}"
                                    >
                                    <span class="erp-slider"></span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="panelUsuario" aria-labelledby="panelUsuarioLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="panelUsuarioLabel">Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <form id="form-usuario-ui">
            @csrf
            <input id="usuario-id-ui" type="hidden">
            <label>Nombre <input id="usuario-nombre-ui" name="nombre" type="text"></label>
            <label>Apellido <input id="usuario-apellido-ui" name="apellido" type="text"></label>
            <label>Usuario <input id="usuario-usuario-ui" name="usuario" type="text" required></label>
            <label>Email <input id="usuario-email-ui" name="email" type="email"></label>
            <label>Contrasena <input id="usuario-clave-ui" name="clave" type="password"></label>
            <label>Confirmar contrasena <input id="usuario-clave-confirmation-ui" name="clave_confirmation" type="password"></label>
            <label>Rol
                <select id="usuario-rol-ui" name="rol_id" required>
                    <option value="">Seleccionar</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol['id'] }}">{{ $rol['nombre'] }}</option>
                    @endforeach
                </select>
            </label>
            <label>Activo
                <select id="usuario-activo-ui" name="activo">
                    <option value="1">Si</option>
                    <option value="0">No</option>
                </select>
            </label>
            <div class="acciones">
                <button type="submit">Guardar</button>
                <span id="estado-usuario-ui" class="text-muted"></span>
            </div>
        </form>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="panelRol" aria-labelledby="panelRolLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="panelRolLabel">Rol</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <form id="form-rol-ui">
            @csrf
            <input id="rol-id-ui" type="hidden">
            <label>Nombre <input id="rol-nombre-ui" name="nombre" type="text" required></label>
            <label>Descripcion <textarea id="rol-descripcion-ui" name="descripcion" rows="3"></textarea></label>
            <label>Activo
                <select id="rol-activo-ui" name="activo">
                    <option value="1">Si</option>
                    <option value="0">No</option>
                </select>
            </label>
            <div class="acciones">
                <button type="submit">Guardar</button>
                <span id="estado-rol-ui" class="text-muted"></span>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const tokenUsuarios = document.querySelector('meta[name="csrf-token"]')?.content || '';
const tabsUsuarios = document.querySelectorAll('[data-tab]');
const panelesUsuarios = document.querySelectorAll('[data-panel]');
const estadoUsuarioUi = document.getElementById('estado-usuario-ui');
const estadoRolUi = document.getElementById('estado-rol-ui');
const rolPermisos = document.getElementById('rol-permisos');
const permisosInputs = document.querySelectorAll('[data-permiso-id]');
const permisosPorRol = @json($rolesPermisos);

function activarTabUsuarios(tab) {
    tabsUsuarios.forEach(item => item.classList.toggle('active', item.dataset.tab === tab));
    panelesUsuarios.forEach(item => item.hidden = item.dataset.panel !== tab);
    sessionStorage.setItem('sistema.usuarios.tab', tab);
}

function requestUsuarios(url, metodo, datos = null) {
    const opciones = {
        method: metodo,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': tokenUsuarios,
        },
    };

    if (datos instanceof FormData) {
        opciones.body = datos;
    }

    if (datos !== null && !(datos instanceof FormData)) {
        opciones.headers['Content-Type'] = 'application/json';
        opciones.body = JSON.stringify(datos);
    }

    return fetch(url, opciones).then(async respuesta => {
        const payload = await respuesta.json().catch(() => ({ ok: respuesta.ok }));

        if (!respuesta.ok) {
            throw new Error(payload.message || 'No se pudo completar la operacion.');
        }

        return payload;
    });
}

function limpiarUsuarioUi() {
    document.getElementById('form-usuario-ui').reset();
    document.getElementById('usuario-id-ui').value = '';
    estadoUsuarioUi.textContent = '';
}

function limpiarRolUi() {
    document.getElementById('form-rol-ui').reset();
    document.getElementById('rol-id-ui').value = '';
    estadoRolUi.textContent = '';
}

function cargarPermisos() {
    const rolId = rolPermisos.value;
    const activos = permisosPorRol[rolId] || [];
    permisosInputs.forEach(input => {
        input.checked = activos.includes(input.dataset.codigo);
    });
}

tabsUsuarios.forEach(tab => {
    tab.addEventListener('click', () => activarTabUsuarios(tab.dataset.tab));
});

document.querySelector('[data-bs-target="#panelUsuario"]').addEventListener('click', limpiarUsuarioUi);
document.querySelector('[data-bs-target="#panelRol"]').addEventListener('click', limpiarRolUi);

document.getElementById('form-usuario-ui').addEventListener('submit', event => {
    event.preventDefault();
    const id = document.getElementById('usuario-id-ui').value;
    const datos = new FormData(event.currentTarget);

    if (id !== '') {
        datos.append('_method', 'PUT');
    }

    requestUsuarios(id === '' ? '/usuarios' : `/usuarios/${id}`, 'POST', datos)
        .then(() => window.location.reload())
        .catch(error => estadoUsuarioUi.textContent = error.message);
});

document.querySelectorAll('[data-editar-usuario]').forEach(boton => {
    boton.addEventListener('click', () => {
        document.getElementById('usuario-id-ui').value = boton.dataset.editarUsuario;
        document.getElementById('usuario-nombre-ui').value = boton.dataset.nombre || '';
        document.getElementById('usuario-apellido-ui').value = '';
        document.getElementById('usuario-usuario-ui').value = boton.dataset.usuario || '';
        document.getElementById('usuario-email-ui').value = boton.dataset.email || '';
        document.getElementById('usuario-clave-ui').value = '';
        document.getElementById('usuario-clave-confirmation-ui').value = '';
        document.getElementById('usuario-rol-ui').value = boton.dataset.rolId || '';
        document.getElementById('usuario-activo-ui').value = boton.dataset.activo || '1';
        estadoUsuarioUi.textContent = '';
        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelUsuario')).show();
    });
});

document.querySelectorAll('[data-desactivar-usuario]').forEach(boton => {
    boton.addEventListener('click', () => {
        const reactivar = boton.dataset.activo === '0';
        const datos = reactivar ? {
            nombre: boton.closest('tr').children[1].textContent.trim(),
            usuario: boton.closest('tr').children[0].textContent.trim(),
            activo: true,
            rol_id: boton.closest('tr').querySelector('[data-editar-usuario]')?.dataset.rolId || 0,
        } : null;
        requestUsuarios(`/usuarios/${boton.dataset.desactivarUsuario}`, reactivar ? 'PUT' : 'DELETE', datos)
            .then(() => window.location.reload())
            .catch(error => estadoUsuarioUi.textContent = error.message);
    });
});

document.getElementById('form-rol-ui').addEventListener('submit', event => {
    event.preventDefault();
    const id = document.getElementById('rol-id-ui').value;
    const datos = new FormData(event.currentTarget);

    if (id !== '') {
        datos.append('_method', 'PUT');
    }

    requestUsuarios(id === '' ? '/usuarios/roles' : `/usuarios/roles/${id}`, 'POST', datos)
        .then(() => window.location.reload())
        .catch(error => estadoRolUi.textContent = error.message);
});

document.querySelectorAll('[data-editar-rol]').forEach(boton => {
    boton.addEventListener('click', () => {
        document.getElementById('rol-id-ui').value = boton.dataset.editarRol;
        document.getElementById('rol-nombre-ui').value = boton.dataset.nombre || '';
        document.getElementById('rol-descripcion-ui').value = boton.dataset.descripcion || '';
        document.getElementById('rol-activo-ui').value = boton.dataset.activo || '1';
        estadoRolUi.textContent = '';
        bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelRol')).show();
    });
});

document.querySelectorAll('[data-desactivar-rol]').forEach(boton => {
    boton.addEventListener('click', () => {
        const reactivar = boton.dataset.activo === '0';
        const datos = reactivar ? {
            nombre: boton.closest('tr').children[0].textContent.trim(),
            descripcion: boton.closest('tr').children[1].textContent.trim(),
            activo: true,
        } : null;
        requestUsuarios(`/usuarios/roles/${boton.dataset.desactivarRol}`, reactivar ? 'PUT' : 'DELETE', datos)
            .then(() => window.location.reload())
            .catch(error => estadoRolUi.textContent = error.message);
    });
});

rolPermisos.addEventListener('change', cargarPermisos);
permisosInputs.forEach(input => {
    input.addEventListener('change', () => {
        const rolId = rolPermisos.value;
        const permisoId = input.dataset.permisoId;
        const url = input.checked
            ? `/usuarios/roles/${rolId}/permisos/${permisoId}/activar`
            : `/usuarios/roles/${rolId}/permisos/${permisoId}`;
        const metodo = input.checked ? 'POST' : 'DELETE';

        requestUsuarios(url, metodo)
            .then(() => {
                const lista = permisosPorRol[rolId] || [];

                if (input.checked && !lista.includes(input.dataset.codigo)) {
                    lista.push(input.dataset.codigo);
                }

                if (!input.checked) {
                    permisosPorRol[rolId] = lista.filter(codigo => codigo !== input.dataset.codigo);
                } else {
                    permisosPorRol[rolId] = lista;
                }
            })
            .catch(error => {
                input.checked = !input.checked;
                alert(error.message);
            });
    });
});

activarTabUsuarios(sessionStorage.getItem('sistema.usuarios.tab') || 'usuarios');
cargarPermisos();
</script>
@endpush
