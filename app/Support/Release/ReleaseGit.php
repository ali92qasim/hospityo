<?php

namespace App\Support\Release;

interface ReleaseGit
{
    public function isWorkingTreeClean(): bool;

    /** Highest existing tag like v1.2.0, or null if none. */
    public function latestSemverTag(): ?string;

    /** @return list<CommitMessage> */
    public function commitsSince(?string $tag): array;

    /** Commit VERSION, annotated tag v{$version}, push HEAD and that tag. */
    public function commitVersionAndTag(string $version): void;
}
