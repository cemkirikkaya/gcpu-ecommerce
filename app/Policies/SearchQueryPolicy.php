<?php

namespace App\Policies;

use App\Models\SearchQuery;
use App\Models\User;

class SearchQueryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, SearchQuery $searchQuery): bool
    {
        return $user->isAdmin();
    }
}
