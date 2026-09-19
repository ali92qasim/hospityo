<?php

use App\Models\ChangelogEntry;
use App\Models\Release;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);
    $this->app['db']->purge('landlord');
    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);
});

it('creates landlord releases and changelog_entries tables', function () {
    expect(Schema::connection('landlord')->hasTable('releases'))->toBeTrue()
        ->and(Schema::connection('landlord')->hasColumns('releases', [
            'id', 'version', 'summary', 'released_at', 'created_at', 'updated_at',
        ]))->toBeTrue()
        ->and(Schema::connection('landlord')->hasTable('changelog_entries'))->toBeTrue()
        ->and(Schema::connection('landlord')->hasColumns('changelog_entries', [
            'id', 'release_id', 'category', 'description', 'sort_order', 'created_at', 'updated_at',
        ]))->toBeTrue();
});

it('stores a release with summary and no entries', function () {
    $release = Release::create([
        'version' => '1.0.0',
        'summary' => 'Initial versioned release of UseClinicSync.',
        'released_at' => now(),
    ]);

    expect($release->changelogEntries()->count())->toBe(0)
        ->and($release->summary)->toBe('Initial versioned release of UseClinicSync.');
});

it('stores added and fixed changelog entries for a release', function () {
    $release = Release::create([
        'version' => '1.1.0',
        'summary' => null,
        'released_at' => now(),
    ]);
    ChangelogEntry::create([
        'release_id' => $release->id,
        'category' => 'added',
        'description' => 'Add rates matrix HTTP with empty-versus-zero cells',
        'sort_order' => 0,
    ]);
    ChangelogEntry::create([
        'release_id' => $release->id,
        'category' => 'fixed',
        'description' => 'Scope rate sync deletes to submitted doctors',
        'sort_order' => 1,
    ]);

    expect($release->changelogEntries()->orderBy('sort_order')->pluck('category')->all())
        ->toBe(['added', 'fixed']);
});
