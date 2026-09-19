@foreach($releases as $release)
    <section class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900">
            v{{ $release->version }}
            <span class="ml-2 text-sm font-normal text-gray-500">{{ $release->released_at->format('M j, Y') }}</span>
        </h2>

        @if(filled($release->summary))
            <p class="mt-2 text-sm text-gray-700">{{ $release->summary }}</p>
        @endif

        @php
            $added = $release->changelogEntries->where('category', 'added');
            $fixed = $release->changelogEntries->where('category', 'fixed');
        @endphp

        @if($added->isNotEmpty())
            <h3 class="mt-4 text-sm font-semibold text-gray-800">Added</h3>
            <ul class="mt-1 list-disc list-inside text-sm text-gray-700 space-y-1">
                @foreach($added as $entry)
                    <li>{{ $entry->description }}</li>
                @endforeach
            </ul>
        @endif

        @if($fixed->isNotEmpty())
            <h3 class="mt-4 text-sm font-semibold text-gray-800">Fixed</h3>
            <ul class="mt-1 list-disc list-inside text-sm text-gray-700 space-y-1">
                @foreach($fixed as $entry)
                    <li>{{ $entry->description }}</li>
                @endforeach
            </ul>
        @endif
    </section>
@endforeach
