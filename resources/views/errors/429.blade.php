@extends('errors.layout')

@section('code', '429')
@section('title', 'Demasiadas solicitudes')
@section('message', 'Detectamos muchas peticiones en poco tiempo. Espera unos instantes antes de volver a intentarlo.')
@section('actions')
    <a class="btn primary" href="{{ url('/') }}">Ir a inicio</a>
    <a class="btn secondary" href="#" onclick="location.reload(); return false;">Reintentar en esta página</a>
@endsection
