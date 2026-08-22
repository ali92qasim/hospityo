@extends('admin.pharmacy.pos.layout')

@section('title', 'POS')

@section('content')
<div id="pharmacy-pos"
     data-medicine-search-url="{{ route('pharmacy.pos.medicines.search') }}"
     @if(session('success')) data-flash-success="{{ e(session('success')) }}" @endif
     @if(session('error')) data-flash-error="{{ e(session('error')) }}" @endif>
    @include('admin.pharmacy.pos.partials.tabs')
    @include('admin.pharmacy.pos.partials.prescription-tab')
    @include('admin.pharmacy.pos.partials.counter-tab')
</div>
@endsection
