@extends('errors.layout')

@section('code', '500')
@section('title', 'Ocurrió un problema')
@section('message', 'Hubo un error inesperado. Nuestros registros ya lo anotaron y el equipo lo revisará. No se ha expuesto información sensible.')
@section('actions')
    <a class="btn primary" href="{{ url('/') }}">Volver al inicio</a>
    <a class="btn secondary" href="#" onclick="location.reload(); return false;">Reintentar</a>
@endsection
