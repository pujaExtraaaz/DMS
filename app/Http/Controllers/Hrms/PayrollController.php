<?php

namespace App\Http\Controllers\Hrms;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class PayrollController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('hrms.salary-structures.index');
    }
}
