<?php

declare(strict_types=1);

namespace Nodus\DevTools\Analysis;

/** A finding of {@see NamespaceCasing}: file, line, plain text. */
final class NamespaceViolation
{
    public function __construct(
        public readonly string $file,
        public readonly int $line,
        public readonly string $message,
    ) {}

    public function __toString(): string
    {
        return sprintf('%s:%d — %s', $this->file, $this->line, $this->message);
    }
}
