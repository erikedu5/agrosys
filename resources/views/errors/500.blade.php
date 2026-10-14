@extends('errors.layout')

@section('code', '500')
@section('title', 'Ocurrió un problema')
@section('message', 'Hubo un error inesperado. Nuestros registros ya lo anotaron y el equipo lo revisará. No se ha expuesto información sensible.')
@section('actions')
    <a class="btn primary" href="{{ url('/dashboard') }}">Volver al inicio</a>
    <button class="btn secondary" type="button" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '{{ url('/dashboard') }}'; }">Volver</button>
@endsection
