<?php

declare(strict_types=1);

namespace App\Support;

use app\Models\User;

class WrongImport
{
    public function __construct(public User $user) {}
}
