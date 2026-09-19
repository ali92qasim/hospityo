<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use Illuminate\View\View;

class WhatsNewController extends Controller
{
    public function index(): View
    {
        $releases = Release::query()
            ->orderByDesc('released_at')
            ->with(['changelogEntries' => fn ($q) => $q->orderBy('sort_order')])
            ->get();

        return view('super-admin.whats-new.index', compact('releases'));
    }
}
