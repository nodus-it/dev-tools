<?php

declare(strict_types=1);

/** Pest bootstrap. No framework: dev-tools pulls in no illuminate/*. */
function fixturePath(string $relative = ''): string
{
    return rtrim(__DIR__.'/Fixtures/'.ltrim($relative, '/'), '/');
}
