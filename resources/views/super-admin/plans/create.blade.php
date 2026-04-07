@extends('super-admin.layout')
@section('title', 'Create Plan')
@section('page-title', 'Create Plan')
@section('page-description', 'Add a new subscription plan')

@section('content')
<div class="max-w-4xl">
    <div class="mb-4">
        <a href="{{ route('super-admin.plans.index') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fas fa-arrow-left mr-1"></i> Back to Plans</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6">
        <form method="POST" action="{{ route('super-admin.plans.store') }}">
            @csrf
            @include('super-admin.plans._form', ['plan' => new \App\Models\Plan()])

            <div class="flex flex-col sm:flex-row sm:justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('super-admin.plans.index') }}" class="px-6 py-2.5 text-sm text-center text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-6 py-2.5 text-sm bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i> Create Plan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
