@extends('errors.layout')

@section('code', '404')
@section('title', 'Página no encontrada')
@section('message', 'No pudimos encontrar lo que estabas buscando. Es posible que el enlace haya cambiado o que el recurso ya no exista.')
@section('actions')
    <a class="btn primary" href="{{ url('/dashboard') }}">Ir al inicio</a>
    <button class="btn secondary" type="button" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '{{ url('/dashboard') }}'; }">Volver a la página anterior</button>
@endsection
