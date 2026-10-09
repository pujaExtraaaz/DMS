<?php

namespace Tally\Http\Controllers;

use Tally\Models\ApiToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function index(): View
    {
        return view('tally::settings.api-tokens', [
            'tokens' => request()->user()->apiTokens()->latest()->get(),
            'plainToken' => session('api_token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $issued = ApiToken::issue($request->user(), $data['name']);

        return back()->with('status', 'API token created. Copy it now. It will not be shown again.')->with('api_token', $issued['plain_text']);
    }

    public function destroy(ApiToken $apiToken): RedirectResponse
    {
        abort_unless($apiToken->user_id === request()->user()->id, 404);
        $apiToken->delete();

        return back()->with('status', 'API token revoked.');
    }
}
