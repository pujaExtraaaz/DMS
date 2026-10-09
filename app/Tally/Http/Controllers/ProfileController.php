<?php

namespace Tally\Http\Controllers;

use Tally\Http\Requests\UpdatePasswordRequest;
use Tally\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('tally::profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('books.tally.profile.edit')->with('status', 'Profile updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->validated('password'),
        ]);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('books.tally.profile.edit')->with('status', 'Password updated.');
    }
}
