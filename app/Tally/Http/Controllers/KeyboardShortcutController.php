<?php

namespace Tally\Http\Controllers;

use Tally\Audit\AuditLogger;
use Tally\Keyboard\ShortcutRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeyboardShortcutController extends Controller
{
    public function index(ShortcutRegistry $registry): View
    {
        return view('tally::settings.shortcuts', [
            'shortcuts' => $registry->resolved(),
        ]);
    }

    public function update(Request $request, ShortcutRegistry $registry): RedirectResponse
    {
        $data = $request->validate([
            'shortcuts' => ['required', 'array'],
            'shortcuts.*.id' => ['required', 'string'],
            'shortcuts.*.keys' => ['required', 'string', 'max:40'],
            'shortcuts.*.enabled' => ['required', 'boolean'],
        ]);

        $registry->save($data['shortcuts']);
        app(AuditLogger::class)->record('updated', 'settings', null, 'Keyboard shortcuts updated.');

        return back()->with('status', 'Keyboard shortcuts saved.');
    }

    public function restore(ShortcutRegistry $registry, string $shortcut): RedirectResponse
    {
        $registry->restore($shortcut);
        app(AuditLogger::class)->record('updated', 'settings', null, 'Keyboard shortcut '.$shortcut.' restored.');

        return back()->with('status', 'Default shortcut restored.');
    }

    public function restoreAll(ShortcutRegistry $registry): RedirectResponse
    {
        $registry->restoreAll();
        app(AuditLogger::class)->record('updated', 'settings', null, 'All keyboard shortcuts restored.');

        return back()->with('status', 'All shortcuts restored to their defaults.');
    }
}
