<?php

namespace Tests\Support;

use App\Support\Release\CommitMessage;
use App\Support\Release\ReleaseGit;

final class FakeReleaseGit implements ReleaseGit
{
    public bool $clean = true;

    public ?string $latestTag = null;

    /** @var list<CommitMessage> */
    public array $commits = [];

    /** @var list<string> */
    public array $tagged = [];

    public function isWorkingTreeClean(): bool
    {
        return $this->clean;
    }

    public function latestSemverTag(): ?string
    {
        return $this->latestTag;
    }

    public function commitsSince(?string $tag): array
    {
        return $this->commits;
    }

    public function commitVersionAndTag(string $version): void
    {
        $this->tagged[] = $version;
    }
}
