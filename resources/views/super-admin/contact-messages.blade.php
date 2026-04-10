@extends('super-admin.layout')

@section('title', 'Contact Messages')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">Contact Messages</h3>
        <p class="text-sm text-gray-500 mt-1">Messages submitted through the contact form.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($messages as $msg)
                <tr class="{{ !$msg->is_read ? 'bg-blue-50' : 'hover:bg-gray-50' }}">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900 {{ !$msg->is_read ? 'font-bold' : '' }}">{{ $msg->name }}</div>
                        <div class="text-xs text-gray-500">{{ $msg->email }}</div>
                        @if($msg->phone)<div class="text-xs text-gray-400">{{ $msg->phone }}</div>@endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700">{{ $msg->subject }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{{ Str::limit($msg->message, 80) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $msg->created_at->format('M d, Y h:i A') }}</td>
                    <td class="px-6 py-4 text-sm space-x-2">
                        <button onclick="viewMessage({{ $msg->id }})" class="text-medical-blue hover:text-blue-700" title="View"><i class="fas fa-eye"></i></button>
                        <a href="mailto:{{ $msg->email }}?subject=Re: {{ $msg->subject }}" class="text-green-600 hover:text-green-700" title="Reply"><i class="fas fa-reply"></i></a>
                        <form action="{{ route('super-admin.contact-messages.destroy', $msg) }}" method="POST" class="inline" onsubmit="return confirm('Delete this message?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">No messages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($messages->hasPages())
    <div class="px-6 py-4 border-t">{{ $messages->links() }}</div>
    @endif
</div>

<!-- Message Modal -->
<div id="messageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800" id="msgSubject"></h3>
                <button onclick="document.getElementById('messageModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center text-white font-bold text-sm" id="msgAvatar"></div>
                    <div class="ml-3">
                        <div class="text-sm font-medium text-gray-900" id="msgName"></div>
                        <div class="text-xs text-gray-500" id="msgEmail"></div>
                    </div>
                    <div class="ml-auto text-xs text-gray-400" id="msgDate"></div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700 leading-relaxed whitespace-pre-wrap" id="msgBody"></div>
            </div>
        </div>
    </div>
</div>

<script>
function viewMessage(id) {
    fetch(`/super-admin/contact-messages/${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(msg => {
            document.getElementById('msgSubject').textContent = msg.subject;
            document.getElementById('msgName').textContent = msg.name;
            document.getElementById('msgEmail').textContent = msg.email + (msg.phone ? ' • ' + msg.phone : '');
            document.getElementById('msgAvatar').textContent = msg.name.charAt(0).toUpperCase();
            document.getElementById('msgDate').textContent = new Date(msg.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            document.getElementById('msgBody').textContent = msg.message;
            document.getElementById('messageModal').classList.remove('hidden');
        });
}
document.getElementById('messageModal').addEventListener('click', function(e) {
    if (e.target === this || e.target.classList.contains('flex')) this.classList.add('hidden');
});
</script>
@endsection
