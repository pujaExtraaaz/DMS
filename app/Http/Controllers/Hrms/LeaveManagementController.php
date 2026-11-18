<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class LeaveManagementController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('hrms.leave-requests.index');
    }
}
