@extends('layouts.error')

@section('code', '403')
@section('title', 'Access denied')

@section('message')
    {{ $exception?->getMessage() ?: 'You do not have permission to view this page or record.' }}
@endsection
