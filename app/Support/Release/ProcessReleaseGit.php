<?php

namespace App\Support\Release;

use Symfony\Component\Process\Process;

final class ProcessReleaseGit implements ReleaseGit
{
    public function isWorkingTreeClean(): bool
    {
        $process = $this->run(['git', 'status', '--porcelain']);

        return trim($process->getOutput()) === '';
    }

    public function latestSemverTag(): ?string
    {
        $process = $this->run(['git', 'tag', '-l', 'v[0-9]*']);
        $tags = array_values(array_filter(array_map('trim', explode("\n", $process->getOutput()))));

        if ($tags === []) {
            return null;
        }

        usort($tags, function (string $a, string $b): int {
            return version_compare(ltrim($a, 'v'), ltrim($b, 'v'));
        });

        return $tags[array_key_last($tags)];
    }

    public function commitsSince(?string $tag): array
    {
        $range = $tag === null ? 'HEAD' : $tag.'..HEAD';
        $process = $this->run([
            'git', 'log', $range, '--format=%s%x00%b%x1e',
        ]);

        $chunks = array_filter(explode("\x1e", $process->getOutput()));
        $commits = [];

        foreach ($chunks as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }

            [$subject, $body] = array_pad(explode("\x00", $chunk, 2), 2, '');
            $commits[] = new CommitMessage(trim($subject), trim($body));
        }

        return $commits;
    }

    public function commitVersionAndTag(string $version): void
    {
        $this->run(['git', 'add', 'VERSION']);
        $this->run(['git', 'commit', '-m', "chore: release v{$version}"]);
        $this->run(['git', 'tag', '-a', "v{$version}", '-m', "v{$version}"]);
        $this->run(['git', 'push', 'origin', 'HEAD']);
        $this->run(['git', 'push', 'origin', "v{$version}"]);
    }

    /**
     * @param  list<string>  $command
     */
    private function run(array $command): Process
    {
        $process = new Process($command, base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'git command failed');
        }

        return $process;
    }
}
