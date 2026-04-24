@extends('admin.layout')

@section('title', 'Add Tax')
@section('page-title', 'Add Tax Rule')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Create Tax Rule</h3>
                <a href="{{ route('taxes.index') }}" class="text-gray-500 hover:text-gray-700 text-sm"><i class="fas fa-arrow-left mr-1"></i>Back</a>
            </div>
        </div>
        <form action="{{ route('taxes.store') }}" method="POST" class="p-6">
            @csrf
            @include('admin.taxes._form', ['tax' => null])
        </form>
    </div>
</div>
@endsection
