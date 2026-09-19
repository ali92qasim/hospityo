@extends('super-admin.layout')

@section('title', "What's New")
@section('page-title', "What's New")
@section('page-description', 'Product release notes')

@section('content')
    @include('whats-new._list')
@endsection
