<?php

/**
 * The one-liner a consuming project drops in, applied to dev-tools itself — so
 * a broken facade shows up here, not in the first project that pulls it.
 */

declare(strict_types=1);

use Nodus\DevTools\Arch;

// `passthru` is the point of this package: the d:* commands hand compose calls
// to the shell. This is what a justified exception looks like.
Arch::baseline('Nodus\DevTools', 'src', ignoring: ['passthru']);
