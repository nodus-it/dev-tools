<?php

declare(strict_types=1);

namespace App\Closures;

use App\Models\User;
use App\Support\SomeTrait;

use function App\Support\helper;

class UsesClosure
{
    use SomeTrait;

    public function make(User $user): callable
    {
        return function () use ($user): string {
            return helper($user);
        };
    }
}
