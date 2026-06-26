@extends('layouts.app')

@section('titulo', 'DiagnÃ³stico del Sistema')

@section('contenido')
<h1>{{ $diagnostico['nombre'] }}</h1>
    <p>VersiÃ³n {{ $diagnostico['version'] }} Â· Modo {{ $diagnostico['modo'] }}</p>
    <p class="estado {{ $diagnostico['estado_general'] }}">Estado general: {{ $diagnostico['estado_general'] }}</p>

    <section>
        <h2>Bases</h2>
        <table>
            <thead><tr><th>Base</th><th>Estado</th></tr></thead>
            <tbody>
            @foreach ($diagnostico['bases'] as $base => $datos)
                <tr><td>{{ $base }}</td><td class="{{ $datos['estado'] ?? '' }}">{{ $datos['estado'] ?? 'N/D' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <section>
        <h2>Migraciones</h2>
        <table>
            <thead><tr><th>ConexiÃ³n</th><th>Estado</th><th>Mensaje</th><th>Pendientes</th></tr></thead>
            <tbody>
            @foreach ($diagnostico['migraciones'] as $conexion => $datos)
                <tr>
                    <td>{{ $conexion }}</td>
                    <td class="{{ $datos['estado'] ?? '' }}">{{ $datos['estado'] ?? 'N/D' }}</td>
                    <td>{{ $datos['mensaje'] ?? '' }}</td>
                    <td>{{ $datos['pendientes'] ?? 0 }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <section>
        <h2>Salud</h2>
        <pre>{{ json_encode($diagnostico['salud'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </section>

    <section>
        <h2>Backups</h2>
        <pre>{{ json_encode($diagnostico['backups'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </section>

    <section>
        <h2>DiagnÃ³stico Completo</h2>
        <pre>{{ json_encode($diagnostico, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </section>
@endsection

