<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Current branch ID set by SetCurrentBranch middleware (request attribute or session).
     * Branch IDs are UUIDs (string).
     */
    protected function currentBranchId(?Request $request = null): ?string
    {
        $request = $request ?? request();

        return $request->attributes->get('current_branch_id') ?? session('current_branch_id');
    }
}
