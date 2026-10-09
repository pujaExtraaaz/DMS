<?php

namespace Tally\Keyboard;

use InvalidArgumentException;

class ShortcutCombo
{
    /**
     * @var array<string, string>
     */
    private const NAMED = [
        'enter' => 'Enter',
        'escape' => 'Esc',
        'esc' => 'Esc',
        'tab' => 'Tab',
        'home' => 'Home',
        'end' => 'End',
        'pageup' => 'PageUp',
        'pagedown' => 'PageDown',
        'arrowup' => 'ArrowUp',
        'arrowdown' => 'ArrowDown',
        'arrowleft' => 'ArrowLeft',
        'arrowright' => 'ArrowRight',
        'up' => 'ArrowUp',
        'down' => 'ArrowDown',
        'left' => 'ArrowLeft',
        'right' => 'ArrowRight',
        'space' => 'Space',
        'spacebar' => 'Space',
        '?' => '?',
        '/' => '/',
    ];

    public static function normalize(string $combo): string
    {
        $parts = array_values(array_filter(array_map(
            fn (string $part) => strtolower(trim($part)),
            preg_split('/\s*\+\s*/', trim($combo)) ?: []
        )));

        if ($parts === []) {
            throw new InvalidArgumentException('Enter a key combination.');
        }

        $mods = [];
        $key = null;

        foreach ($parts as $part) {
            if (in_array($part, ['ctrl', 'control', 'cmd', 'meta'], true)) {
                $mods['ctrl'] = 'Ctrl';

                continue;
            }

            if (in_array($part, ['alt', 'option'], true)) {
                $mods['alt'] = 'Alt';

                continue;
            }

            if ($part === 'shift') {
                $mods['shift'] = 'Shift';

                continue;
            }

            if ($key !== null) {
                throw new InvalidArgumentException('A shortcut can have only one main key.');
            }

            $key = self::key($part);
        }

        if ($key === null) {
            throw new InvalidArgumentException('Add a main key, not only Ctrl, Alt, or Shift.');
        }

        if (in_array($key, ['?', '/'], true)) {
            unset($mods['shift']);
        }

        $ordered = [];

        foreach (['ctrl', 'alt', 'shift'] as $name) {
            if (isset($mods[$name])) {
                $ordered[] = $mods[$name];
            }
        }

        $ordered[] = $key;

        return implode('+', $ordered);
    }

    private static function key(string $part): string
    {
        if (isset(self::NAMED[$part])) {
            return self::NAMED[$part];
        }

        if (preg_match('/^f([1-9]|1[0-2])$/', $part) === 1) {
            return strtoupper($part);
        }

        if (preg_match('/^[a-z0-9]$/', $part) === 1) {
            return strtoupper($part);
        }

        throw new InvalidArgumentException('"' . $part . '" is not a supported key.');
    }
}
