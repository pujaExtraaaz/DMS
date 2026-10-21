<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class GuideController extends Controller
{
    public function __invoke(): View
    {
        return view('guide.index', [
            'modules' => config('project_guide.modules', []),
        ]);
    }
}
