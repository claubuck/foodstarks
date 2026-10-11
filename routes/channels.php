<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('branch.{branchId}', function ($user, $branchId) {
    $branch = $user->restaurant?->branches()->where('id', $branchId)->where('is_active', true)->first();

    return $branch !== null;
});
