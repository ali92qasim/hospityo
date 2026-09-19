<?php

use App\Console\Commands\AppReleaseCommand;
use App\Models\Release;
use App\Support\AppVersion;
use App\Support\Release\CommitMessage;
use App\Support\Release\ReleaseGit;
use Tests\Support\FakeReleaseGit;

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

    $this->versionBackup = is_file(AppVersion::path()) ? file_get_contents(AppVersion::path()) : null;
    $this->git = new FakeReleaseGit;
    $this->app->instance(ReleaseGit::class, $this->git);
});

afterEach(function () {
    if ($this->versionBackup === null) {
        @unlink(AppVersion::path());
    } else {
        file_put_contents(AppVersion::path(), $this->versionBackup);
    }
});

it('creates the initial 1.0.0 release with the fixed blurb and no changelog entries', function () {
    $this->git->clean = true;
    $this->git->latestTag = null;

    $this->artisan('app:release', ['--initial' => true])->assertSuccessful();

    $release = Release::query()->sole();
    expect($release->version)->toBe('1.0.0')
        ->and($release->summary)->toBe(AppReleaseCommand::INITIAL_BLURB)
        ->and($release->changelogEntries()->count())->toBe(0)
        ->and(trim((string) file_get_contents(AppVersion::path())))->toBe('1.0.0')
        ->and($this->git->tagged)->toBe(['1.0.0']);
});

it('aborts when no tags exist and --initial was not passed', function () {
    $this->git->latestTag = null;

    $this->artisan('app:release')->assertFailed();

    expect(Release::count())->toBe(0)
        ->and($this->git->tagged)->toBe([])
        ->and(trim((string) file_get_contents(AppVersion::path())))->toBe(trim($this->versionBackup));
});

it('aborts when the working tree is dirty', function () {
    $this->git->clean = false;
    $this->git->latestTag = null;

    $this->artisan('app:release', ['--initial' => true])->assertFailed();

    expect(Release::count())->toBe(0)->and($this->git->tagged)->toBe([]);
});

it('writes feat and fix entries on a subsequent release and tags the bumped version', function () {
    Release::create([
        'version' => '1.0.0',
        'summary' => AppReleaseCommand::INITIAL_BLURB,
        'released_at' => now()->subDay(),
    ]);
    $this->git->latestTag = 'v1.0.0';
    $this->git->commits = [
        new CommitMessage('feat: add VERSION file and config app.version'),
        new CommitMessage('chore: ignore build output'),
        new CommitMessage('fix: scope rate sync deletes to submitted doctors'),
    ];

    $this->artisan('app:release')->assertSuccessful();

    $release = Release::query()->where('version', '1.1.0')->sole();
    expect($release->summary)->toBeNull()
        ->and($release->changelogEntries()->orderBy('sort_order')->pluck('description')->all())->toBe([
            'Add VERSION file and config app.version',
            'Scope rate sync deletes to submitted doctors',
        ])
        ->and($this->git->tagged)->toBe(['1.1.0']);
});

it('does not write file db or git on dry-run', function () {
    $this->git->latestTag = null;

    $this->artisan('app:release', ['--initial' => true, '--dry-run' => true])->assertSuccessful();

    expect(Release::count())->toBe(0)
        ->and($this->git->tagged)->toBe([])
        ->and(trim((string) file_get_contents(AppVersion::path())))->toBe(trim($this->versionBackup));
});
