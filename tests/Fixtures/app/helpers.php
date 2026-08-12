<?php

declare(strict_types=1);

// Deliberately without a namespace: global helpers are allowed.

if (! function_exists('nodus_noop')) {
    function nodus_noop(): void {}
}
