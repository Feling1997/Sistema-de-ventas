@extends('layouts.app')

@section('titulo', 'Presupuesto')

@section('contenido')
@if ($existe)
            <h1>Presupuesto #{{ $presupuesto['id'] ?? '' }}</h1>
            <p>Cliente: {{ $presupuesto['cliente'] ?? '' }}</p>
            <p>Fecha: {{ $presupuesto['fecha'] ?? '' }}</p>
            <p>Subtotal: {{ $presupuesto['subtotal'] ?? 0 }}</p>
            <p>Descuento: {{ $presupuesto['descuento'] ?? 0 }}</p>
            <p>Total: {{ $presupuesto['total'] ?? 0 }}</p>

            <p>
                <a href="{{ url('/presupuestos/' . ($presupuesto['id'] ?? 0) . '/ticket') }}">Ticket</a>
                <a href="{{ url('/presupuestos/' . ($presupuesto['id'] ?? 0) . '/pdf') }}">PDF</a>
            </p>

            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Descuento</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (($presupuesto['detalles'] ?? []) as $detalle)
                        <tr>
                            <td>{{ $detalle['producto'] ?? '' }}</td>
                            <td>{{ $detalle['cantidad'] ?? 0 }}</td>
                            <td>{{ $detalle['precio_unit'] ?? 0 }}</td>
                            <td>{{ $detalle['descuento'] ?? 0 }}</td>
                            <td>{{ $detalle['subtotal'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <h1>Presupuesto no encontrado</h1>
        @endif
@endsection

