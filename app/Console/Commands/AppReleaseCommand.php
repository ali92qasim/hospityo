<?php

namespace App\Console\Commands;

use App\Models\ChangelogEntry;
use App\Models\Release;
use App\Support\AppVersion;
use App\Support\Release\ChangelogCompiler;
use App\Support\Release\ReleaseGit;
use App\Support\Release\SemverBumper;
use Illuminate\Console\Command;

class AppReleaseCommand extends Command
{
    public const INITIAL_BLURB = 'Initial versioned release of UseClinicSync.';

    protected $signature = 'app:release {--dry-run} {--initial}';

    protected $description = 'Bump the app version, write changelog rows, and create a git tag';

    public function handle(ReleaseGit $git): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $initial = (bool) $this->option('initial');

        try {
            if (! $dryRun && ! $git->isWorkingTreeClean()) {
                $this->error('Working tree must be clean.');

                return self::FAILURE;
            }

            $tag = $git->latestSemverTag();

            if ($initial && $tag !== null) {
                $this->error('Already has a release tag; omit --initial.');

                return self::FAILURE;
            }

            if (! $initial && $tag === null) {
                $this->error('No release tags exist. Re-run with --initial.');

                return self::FAILURE;
            }

            if ($initial) {
                return $this->releaseInitial($git, $dryRun);
            }

            return $this->releaseSubsequent($git, $tag, $dryRun);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function releaseInitial(ReleaseGit $git, bool $dryRun): int
    {
        $version = '1.0.0';

        $this->info("Release {$version}");
        $this->line(self::INITIAL_BLURB);

        if ($dryRun) {
            return self::SUCCESS;
        }

        AppVersion::write($version);
        Release::create([
            'version' => $version,
            'summary' => self::INITIAL_BLURB,
            'released_at' => now(),
        ]);
        $git->commitVersionAndTag($version);

        return self::SUCCESS;
    }

    private function releaseSubsequent(ReleaseGit $git, string $tag, bool $dryRun): int
    {
        $commits = $git->commitsSince($tag);

        if ($commits === []) {
            $this->error('Nothing to release.');

            return self::FAILURE;
        }

        $current = ltrim($tag, 'v');
        $version = SemverBumper::bump($current, $commits);
        $entries = ChangelogCompiler::entries($commits);

        $this->info("Release {$version}");
        foreach ($entries as $entry) {
            $this->line("[{$entry['category']}] {$entry['description']}");
        }

        if ($dryRun) {
            return self::SUCCESS;
        }

        AppVersion::write($version);
        $release = Release::create([
            'version' => $version,
            'summary' => null,
            'released_at' => now(),
        ]);

        foreach ($entries as $index => $entry) {
            ChangelogEntry::create([
                'release_id' => $release->id,
                'category' => $entry['category'],
                'description' => $entry['description'],
                'sort_order' => $index,
            ]);
        }

        $git->commitVersionAndTag($version);

        return self::SUCCESS;
    }
}
