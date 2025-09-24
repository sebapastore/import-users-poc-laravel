<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserController
{
    // This is a very basic index implementation
    // In a production EP we should use pagination
    public function index(): Collection
    {
        return User::query()
            ->select('id', 'name', 'email', 'role', 'salary', 'start_date')
            ->get();
    }
}
