@extends('settings.shell')

@section('settings-section')
<div class="rounded-lg bg-white shadow">
    <div class="flex flex-col gap-3 border-b border-gray-200 p-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Prescription Print Templates</h2>
            <p class="mt-1 text-sm text-gray-500">Manage physical overlay and digitized prescription layouts.</p>
        </div>
        <a href="{{ route('settings.prescription-print-templates.create') }}"
           class="inline-flex items-center justify-center rounded-lg bg-medical-blue px-4 py-2 text-sm font-medium text-white hover:opacity-90">
            <i class="fas fa-plus mr-2"></i>New template
        </a>
    </div>

    @if($errors->any())
        <div class="mx-6 mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Mode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Scope</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($templates as $template)
                    <tr>
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ $template->name }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                            {{ $template->mode === 'overlay_physical' ? 'Physical overlay' : 'Digitized background' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $template->doctor?->name ?? 'Clinic default' }}</td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($template->is_active)
                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">Active</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">Inactive</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <div class="flex items-center justify-end gap-3">
                                @if($template->mode === 'overlay_physical')
                                    <a href="{{ route('settings.prescription-print-templates.calibration', $template) }}" class="text-gray-600 hover:text-gray-900">Calibration</a>
                                @endif
                                <a href="{{ route('settings.prescription-print-templates.edit', $template) }}" class="text-medical-blue hover:underline">Edit</a>
                                @if($template->is_active)
                                    <form method="POST" action="{{ route('settings.prescription-print-templates.deactivate', $template) }}">
                                        @csrf
                                        <button type="submit" class="text-amber-700 hover:underline">Deactivate</button>
                                    </form>
                                @elseif($completeness[$template->id])
                                    <form method="POST" action="{{ route('settings.prescription-print-templates.activate', $template) }}">
                                        @csrf
                                        <button type="submit" class="text-green-700 hover:underline">Activate</button>
                                    </form>
                                @else
                                    <button type="button" disabled title="Position all required fields first" class="cursor-not-allowed text-gray-400">Activate</button>
                                @endif
                                <form method="POST" action="{{ route('settings.prescription-print-templates.destroy', $template) }}"
                                      onsubmit="return confirm('Delete this prescription print template?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No prescription print templates yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
