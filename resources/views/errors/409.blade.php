@extends('errors.layout')

@section('code', '409')
@section('title', 'La compra ya fue registrada')
@section('message', 'Los datos enviados difieren de la compra registrada. Revisa el listado antes de registrar otra compra.')
@section('actions')
    <a class="btn primary" href="{{ route('compra.index') }}">Revisar compras</a>
@endsection
