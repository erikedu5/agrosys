@extends('errors.layout')

@section('code', '419')
@section('title', 'Sesión expirada')
@section('message', 'Por seguridad tu sesión caducó. Inicia sesión nuevamente para continuar trabajando.')
@section('actions')
    <a class="btn primary" href="{{ route('login') }}">Ir a iniciar sesión</a>
    <a class="btn secondary" href="#" onclick="history.back(); return false;">Regresar</a>
@endsection
