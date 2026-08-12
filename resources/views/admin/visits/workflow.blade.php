@extends('admin.layout')

@section('title', 'Visit Workflow - Hospital Management System')
@section('page-title', 'Visit Workflow')
@section('page-description', 'Manage patient visit workflow')

@section('content')
    @include('admin.visits.workflow.'.$visit->visit_type.'._layout')
@endsection
