@php
    $filterCatalog = $catalog ?? null;
    $showLab = ($filterCatalog === null || $filterCatalog === 'lab')
        && ($workflowData['show_lab_investigations'] ?? true);
    $showImaging = ($filterCatalog === null || $filterCatalog === 'imaging')
        && ($workflowData['show_imaging_investigations'] ?? true);
    $labInvestigations = $labTests ?? collect();
    $imagingInvestigations = $imagingStudies ?? collect();
    $categoryLabels = [
        'hematology' => 'Hematology',
        'biochemistry' => 'Biochemistry',
        'microbiology' => 'Microbiology',
        'immunology' => 'Immunology',
        'pathology' => 'Pathology',
        'histopathology' => 'Histopathology',
        'molecular' => 'Molecular Biology',
        'x-ray' => 'X-Ray',
        'ultrasound' => 'Ultrasound',
        'ct-scan' => 'CT Scan',
        'mri' => 'MRI',
        'cardiology' => 'Cardiology',
        'cardiac-diagnostics' => 'Cardiac Diagnostics',
        'radiology' => 'Radiology',
    ];
    $headingPrefix = $filterCatalog ?? 'all';
    $orderedTitle = match ($filterCatalog) {
        'lab' => 'Ordered lab tests',
        'imaging' => 'Ordered imaging',
        default => 'Ordered Investigations',
    };
    $emptyMessage = match ($filterCatalog) {
        'lab' => 'No lab tests ordered yet',
        'imaging' => 'No imaging ordered yet',
        default => 'No investigations ordered yet',
    };
    $countNoun = match ($filterCatalog) {
        'lab' => 'lab test',
        'imaging' => 'imaging study',
        default => 'investigation',
    };
@endphp

<div class="max-w-7xl mx-auto">
    @if($workflowData['can_order_labs'])
        <div class="grid grid-cols-1 {{ $filterCatalog ? '' : 'xl:grid-cols-2' }} gap-6 mb-8">
            @if($showLab)
                @include('admin.visits.workflow.opd._investigation-order-section', [
                    'catalog' => 'lab',
                    'sectionTitle' => 'Lab tests',
                    'sectionIcon' => 'fa-flask',
                    'submitLabel' => 'Order lab tests',
                    'kindInvestigations' => $labInvestigations,
                    'categoryLabels' => $categoryLabels,
                    'visit' => $visit,
                    'workflowData' => $workflowData,
                    'formAction' => route('visits.order-multiple-lab-tests', $visit),
                    'itemField' => 'lab_test_id',
                ])
            @endif

            @if($showImaging)
                @include('admin.visits.workflow.opd._investigation-order-section', [
                    'catalog' => 'imaging',
                    'sectionTitle' => 'Imaging',
                    'sectionIcon' => 'fa-x-ray',
                    'submitLabel' => 'Order imaging',
                    'kindInvestigations' => $imagingInvestigations,
                    'categoryLabels' => $categoryLabels,
                    'visit' => $visit,
                    'workflowData' => $workflowData,
                    'formAction' => route('visits.order-multiple-imaging-studies', $visit),
                    'itemField' => 'imaging_study_id',
                ])
            @endif
        </div>
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-8">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                @if($workflowData['care_team_labs_message'] ?? false)
                    <p class="text-yellow-800">{{ $workflowData['care_team_labs_message'] }}</p>
                @else
                    <p class="text-yellow-800">Doctor must be assigned to order investigations.</p>
                @endif
            </div>
        </div>
    @endif

    <div class="mt-8">
        @php
            $labItems = $showLab
                ? $visit->labOrders->flatMap(fn ($order) => $order->items->map(fn ($item) => $item->setRelation('order', $order)))
                : collect();
            $imagingItems = $showImaging
                ? ($visit->imagingOrders ?? collect())->flatMap(fn ($order) => $order->items->map(fn ($item) => $item->setRelation('order', $order)))
                : collect();
            $allOrderItems = $labItems->concat($imagingItems);
            $pendingOrders = $allOrderItems->whereIn('status', ['ordered', 'collected', 'testing']);
            $completedOrders = $allOrderItems->whereIn('status', ['verified', 'reported']);
        @endphp

        <div class="flex justify-between items-center mb-4">
            <h4 class="text-lg font-medium text-gray-800">{{ $orderedTitle }}</h4>
            <span class="text-sm text-gray-500">{{ $allOrderItems->count() }} {{ Str::plural($countNoun, $allOrderItems->count()) }}</span>
        </div>

        <div class="space-y-6">
            @if($pendingOrders->count() > 0)
                <section aria-labelledby="pending-{{ $headingPrefix }}-tests-heading">
                    <div class="flex flex-col sm:flex-row sm:items-center mb-4 gap-2">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-yellow-500 rounded-full mr-3 animate-pulse"></div>
                            <h5 id="pending-{{ $headingPrefix }}-tests-heading" class="text-base font-semibold text-gray-900">Pending Results</h5>
                        </div>
                        <span class="px-2.5 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full font-medium">{{ $pendingOrders->count() }}</span>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($pendingOrders as $orderItem)
                            @include('admin.visits.workflow.opd._investigation-order-card', [
                                'orderItem' => $orderItem,
                                'tone' => 'pending',
                            ])
                        @endforeach
                    </div>
                </section>
            @endif

            @if($completedOrders->count() > 0)
                <section aria-labelledby="completed-{{ $headingPrefix }}-tests-heading" class="mt-6">
                    <div class="flex flex-col sm:flex-row sm:items-center mb-4 gap-2">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                            <h5 id="completed-{{ $headingPrefix }}-tests-heading" class="text-base font-semibold text-gray-900">Completed Results</h5>
                        </div>
                        <span class="px-2.5 py-1 text-xs bg-green-100 text-green-800 rounded-full font-medium">{{ $completedOrders->count() }}</span>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($completedOrders as $orderItem)
                            @include('admin.visits.workflow.opd._investigation-order-card', [
                                'orderItem' => $orderItem,
                                'tone' => 'completed',
                            ])
                        @endforeach
                    </div>
                </section>
            @endif

            @if($allOrderItems->count() === 0)
                <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-lg p-8 text-center">
                    <i class="fas fa-clipboard-list text-gray-400 text-3xl mb-3"></i>
                    <p class="text-gray-500">{{ $emptyMessage }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
