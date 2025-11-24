@extends('errors.layout')

@section('code', '404')
@section('title', 'Página no encontrada')
@section('message', 'No pudimos encontrar lo que estabas buscando. Es posible que el enlace haya cambiado o que el recurso ya no exista.')
@section('actions')
    <a class="btn primary" href="{{ url('/') }}">Ir al inicio</a>
    <a class="btn secondary" href="#" onclick="history.back(); return false;">Volver a la página anterior</a>
@endsection
