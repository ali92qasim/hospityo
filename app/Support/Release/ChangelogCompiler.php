<?php

namespace App\Support\Release;

final class ChangelogCompiler
{
    /**
     * @param  list<CommitMessage>  $commits
     * @return list<array{category: string, description: string}>
     */
    public static function entries(array $commits): array
    {
        $entries = [];

        foreach ($commits as $commit) {
            if (preg_match('/^(feat|fix)(\([^)]+\))?!?: /', $commit->subject, $matches) !== 1) {
                continue;
            }

            $remainder = preg_replace('/^(feat|fix)(\([^)]+\))?!?: /', '', $commit->subject);
            $remainder = trim((string) $remainder);

            if ($remainder === '') {
                continue;
            }

            $description = strtoupper($remainder[0]).substr($remainder, 1);

            if (SemverBumper::isBreaking($commit)) {
                $description = 'Breaking: '.$description;
            }

            $entries[] = [
                'category' => $matches[1] === 'feat' ? 'added' : 'fixed',
                'description' => $description,
            ];
        }

        return $entries;
    }
}
