@extends('errors.layout')

@section('code', '503')
@section('title', 'Servicio en mantenimiento')
@section('message', 'Estamos realizando tareas de mantenimiento para mejorar la plataforma. Vuelve a intentarlo en unos momentos.')
@section('actions')
    <a class="btn primary" href="{{ url('/') }}">Ir a inicio</a>
    <span class="hint">Si el servicio tarda en volver, contacta al administrador.</span>
@endsection
