<?php

declare(strict_types=1);

namespace App\Nested\Deep;

use App\Models\User;

class Item
{
    public function __construct(public User $user) {}
}
