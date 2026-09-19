<?php

namespace App\Http\Controllers;

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

        return view('admin.whats-new.index', compact('releases'));
    }
}
