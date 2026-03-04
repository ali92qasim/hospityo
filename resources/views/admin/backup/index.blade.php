@extends('admin.layout')

@section('title', 'Backup & Restore - Hospityo')
@section('page-title', __('messages.backup_restore'))
@section('page-description', __('messages.backup_restore_description'))

@section('content')
<div class="space-y-6">
    <!-- Create Backup Section -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('messages.create_backup') }}</h3>
        
        <form action="{{ route('backup.create') }}" method="POST" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('messages.backup_type') }}</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="radio" name="type" value="full" checked class="mr-2">
                        <span class="text-sm">{{ __('messages.full_backup') }} ({{ __('messages.database_and_files') }})</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="type" value="database" class="mr-2">
                        <span class="text-sm">{{ __('messages.database_only') }}</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="type" value="files" class="mr-2">
                        <span class="text-sm">{{ __('messages.files_only') }}</span>
                    </label>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="bg-medical-blue text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>{{ __('messages.create_backup') }}
                </button>
            </div>
        </form>
    </div>
    
    <!-- Available Backups Section -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('messages.available_backups') }}</h3>
        
        @if(count($backups) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('messages.backup_name') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('messages.size') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('messages.created_at') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('messages.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($backups as $backup)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <i class="fas fa-file-archive text-gray-400 mr-3"></i>
                                    <span class="text-sm font-medium text-gray-900">{{ $backup['name'] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $backup['size'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $backup['date']->format('M d, Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('backup.download', $backup['name']) }}" 
                                       class="text-blue-600 hover:text-blue-900"
                                       title="{{ __('messages.download') }}">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    
                                    <button onclick="confirmRestore('{{ $backup['name'] }}')" 
                                            class="text-green-600 hover:text-green-900"
                                            title="{{ __('messages.restore') }}">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    
                                    <form action="{{ route('backup.destroy', $backup['name']) }}" 
                                          method="POST" 
                                          class="inline"
                                          onsubmit="return confirm('{{ __('messages.confirm_delete_backup') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-900"
                                                title="{{ __('messages.delete') }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-database text-gray-300 text-5xl mb-4"></i>
                <p class="text-gray-500">{{ __('messages.no_backups_found') }}</p>
                <p class="text-sm text-gray-400 mt-2">{{ __('messages.create_first_backup') }}</p>
            </div>
        @endif
    </div>
    
    <!-- Backup Information -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h4 class="text-sm font-semibold text-blue-900 mb-3">
            <i class="fas fa-info-circle mr-2"></i>{{ __('messages.important_information') }}
        </h4>
        <ul class="text-sm text-blue-800 space-y-2">
            <li><i class="fas fa-check mr-2"></i>{{ __('messages.backup_info_1') }}</li>
            <li><i class="fas fa-check mr-2"></i>{{ __('messages.backup_info_2') }}</li>
            <li><i class="fas fa-check mr-2"></i>{{ __('messages.backup_info_3') }}</li>
            <li><i class="fas fa-exclamation-triangle mr-2"></i>{{ __('messages.backup_warning') }}</li>
        </ul>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div id="restoreModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">{{ __('messages.confirm_restore') }}</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    {{ __('messages.restore_warning_message') }}
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <form id="restoreForm" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                        {{ __('messages.yes_restore') }}
                    </button>
                </form>
                <button onclick="closeRestoreModal()" 
                        class="mt-3 px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    {{ __('messages.cancel') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmRestore(filename) {
    document.getElementById('restoreForm').action = '/backup/restore/' + filename;
    document.getElementById('restoreModal').classList.remove('hidden');
}

function closeRestoreModal() {
    document.getElementById('restoreModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('restoreModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRestoreModal();
    }
});
</script>
@endsection
