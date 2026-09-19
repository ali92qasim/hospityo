<?php

use App\Support\Release\ChangelogCompiler;
use App\Support\Release\CommitMessage;

function line(string $subject, string $body = ''): CommitMessage
{
    return new CommitMessage($subject, $body);
}

it('includes only strict feat and fix subjects after prefix-strip capitalize', function () {
    $entries = ChangelogCompiler::entries([
        line('feat(doctor-share): add rates matrix HTTP with empty-versus-zero cells'),
        line('fix: scope rate sync deletes to submitted doctors'),
        line('chore: ship Vite production build'),
        line('test: assert hr child plan gates on HTTP'),
        line('docs: add unified feature catalog plan'),
        line('ci: drop the GitHub Actions Vite freshness workflow'),
        line('refactor: CheckModule uses ModuleRegistry::planAllows'),
        line('perf: cache sidebar'),
        line('Fix: restore LabOrderItem::hasParameters so batch result entry does not 500.'),
        line('Ship pending auth, upload, accounting, and visit workflow work.'),
    ]);

    expect($entries)->toBe([
        [
            'category' => 'added',
            'description' => 'Add rates matrix HTTP with empty-versus-zero cells',
        ],
        [
            'category' => 'fixed',
            'description' => 'Scope rate sync deletes to submitted doctors',
        ],
    ]);
});

it('does not treat patch-only commits as changelog lines even though they bump patch', function () {
    $commits = [
        line('chore: ignore build output'),
        line('refactor(pharmacy): use MedicinePricing in prescription store'),
    ];

    expect(\App\Support\Release\SemverBumper::bump('1.0.0', $commits))->toBe('1.0.1')
        ->and(ChangelogCompiler::entries($commits))->toBe([]);
});

it('prefixes Breaking: on changelog lines that triggered a major bump', function () {
    $commits = [
        line('feat: drop finance slug', "The details.\n\nBREAKING CHANGE: old URLs 404."),
        line('fix!: reject unentitled cells before validation'),
    ];

    expect(\App\Support\Release\SemverBumper::bump('1.0.0', $commits))->toBe('2.0.0')
        ->and(ChangelogCompiler::entries($commits))->toBe([
            [
                'category' => 'added',
                'description' => 'Breaking: Drop finance slug',
            ],
            [
                'category' => 'fixed',
                'description' => 'Breaking: Reject unentitled cells before validation',
            ],
        ]);
});
