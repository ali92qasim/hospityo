<?php

namespace App\Support\Release;

final class CommitMessage
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body = '',
    ) {}
}
