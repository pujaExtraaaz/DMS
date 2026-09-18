<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerController extends Controller
{
    public function index(Request $request): View
    {
        $items = Employee::query()
            ->with(['department', 'designation', 'company', 'branch'])
            ->withCount('directReports')
            ->where(function ($q) {
                $q->where('status', 'active')->orWhereNull('status');
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('employee_code', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('hrms.managers.index', compact('items'));
    }
}
