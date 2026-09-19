<?php

use App\Support\Release\CommitMessage;
use App\Support\Release\SemverBumper;

function c(string $subject, string $body = ''): CommitMessage
{
    return new CommitMessage($subject, $body);
}

it('bumps minor for feat and patch for every other non-breaking type', function () {
    expect(SemverBumper::bump('1.0.0', [c('feat: add matrix')]))->toBe('1.1.0')
        ->and(SemverBumper::bump('1.0.0', [c('feat(nav): hide rules link')]))->toBe('1.1.0')
        ->and(SemverBumper::bump('1.2.3', [c('fix: scope deletes')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('chore: ship vite build')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('test: assert hr child gates')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('docs: add catalog plan')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('ci: drop the GitHub Actions workflow')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('refactor: CheckModule uses planAllows')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('perf: cache sidebar')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('style: format pint')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('build: bump vite')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('revert: undo experiment')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('Fix: restore LabOrderItem::hasParameters')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('Ship pending auth work.')]))->toBe('1.2.4');
});

it('lets feat win over patch-only types in the same window', function () {
    expect(SemverBumper::bump('1.0.0', [
        c('chore: ignore build output'),
        c('feat: add VERSION file'),
        c('test: cover footer'),
    ]))->toBe('1.1.0');
});

it('bumps major for BREAKING CHANGE footer or bang type', function () {
    expect(SemverBumper::bump('1.4.2', [
        c('feat: drop old slug', "BREAKING CHANGE: finance slug is gone"),
    ]))->toBe('2.0.0')
        ->and(SemverBumper::bump('1.4.2', [c('feat!: remove rules CRUD')]))->toBe('2.0.0')
        ->and(SemverBumper::bump('1.4.2', [c('fix(nav)!: hide unentitled cells')]))->toBe('2.0.0');
});
