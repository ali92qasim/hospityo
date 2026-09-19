<?php

namespace App\Support\Release;

final class SemverBumper
{
    public static function isBreaking(CommitMessage $commit): bool
    {
        if (preg_match('/^[a-z]+(\([^)]+\))?!: /', $commit->subject) === 1) {
            return true;
        }

        return str_contains($commit->body, 'BREAKING CHANGE:');
    }

    /**
     * @param  list<CommitMessage>  $commits
     */
    public static function bump(string $current, array $commits): string
    {
        [$major, $minor, $patch] = array_map('intval', explode('.', $current));

        $severity = 'patch';

        foreach ($commits as $commit) {
            if (self::isBreaking($commit)) {
                $severity = 'major';
                continue;
            }

            if (preg_match('/^feat(\([^)]+\))?!?: /', $commit->subject) === 1 && $severity !== 'major') {
                $severity = 'minor';
            }
        }

        return match ($severity) {
            'major' => ($major + 1).'.0.0',
            'minor' => $major.'.'.($minor + 1).'.0',
            default => $major.'.'.$minor.'.'.($patch + 1),
        };
    }
}
