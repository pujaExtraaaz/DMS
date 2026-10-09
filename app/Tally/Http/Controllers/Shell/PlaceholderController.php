<?php

namespace Tally\Http\Controllers\Shell;

use Tally\Http\Controllers\Controller;
use Tally\Support\Shell\Navigation;
use Illuminate\Contracts\View\View;

class PlaceholderController extends Controller
{
    public function show(Navigation $navigation, string $page): View
    {
        $item = $navigation->placeholder($page);

        abort_unless($item, 404);

        return view('tally::shell.placeholder', [
            'page' => $item,
        ]);
    }
}
