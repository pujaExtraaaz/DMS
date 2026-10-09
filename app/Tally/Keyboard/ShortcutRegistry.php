<?php

namespace Tally\Keyboard;

use Tally\Models\KeyboardShortcut;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * One registry for application shortcuts.
 * Future modules call extend() or add a row in config/keyboard.php.
 * They must not install a separate keyboard listener.
 */
class ShortcutRegistry
{
    /**
     * @var list<array<string, mixed>>
     */
    private array $extra = [];

    /**
     * @var list<array<string, mixed>>|null
     */
    private ?array $resolved = null;

    /**
     * @param  array<string, mixed>  $shortcut
     */
    public function extend(array $shortcut): void
    {
        foreach (['id', 'keys', 'label', 'action'] as $field) {
            if (! isset($shortcut[$field]) || $shortcut[$field] === '') {
                throw new InvalidArgumentException('A shortcut needs an id, keys, label, and action.');
            }
        }

        $shortcut['keys'] = ShortcutCombo::normalize((string) $shortcut['keys']);
        $this->extra[] = $shortcut;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function defaults(): array
    {
        return [...config('keyboard.shortcuts', []), ...$this->extra];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function resolved(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $overrides = collect($this->overrides())->keyBy('shortcut_id');
        $items = [];

        foreach ($this->defaults() as $item) {
            $override = $overrides->get($item['id']);
            $defaultKeys = ShortcutCombo::normalize((string) $item['keys']);
            $keys = $defaultKeys;

            if (is_array($override) && ($override['keys'] ?? '') !== '') {
                try {
                    $keys = ShortcutCombo::normalize((string) $override['keys']);
                } catch (InvalidArgumentException) {
                    $keys = $defaultKeys;
                }
            }

            if (! empty($item['route']) && tally_route_has($item['route'])) {
                $item['url'] = tally_route($item['route'], $item['route_params'] ?? []);
            }

            $item['keys'] = $keys;
            $item['default_keys'] = $defaultKeys;
            $item['enabled'] = is_array($override) ? (bool) $override['is_enabled'] : true;
            $item['customized'] = $override !== null;
            $item['when'] = $item['when'] ?? 'always';
            $item['scope'] = $item['scope'] ?? 'global';
            $item['intercept'] = $item['intercept'] ?? true;
            $items[] = $item;
        }

        return $this->resolved = $items;
    }

    /**
     * @return list<array{shortcut_id: string, keys: ?string, is_enabled: bool}>
     */
    private function overrides(): array
    {
        if (! Schema::hasTable('acct_keyboard_shortcuts')) {
            return [];
        }

        $load = fn (): array => KeyboardShortcut::query()
            ->get(['shortcut_id', 'keys', 'is_enabled'])
            ->map(fn (KeyboardShortcut $row) => [
                'shortcut_id' => $row->shortcut_id,
                'keys' => $row->keys,
                'is_enabled' => (bool) $row->is_enabled,
            ])
            ->all();

        if (app()->runningUnitTests()) {
            return $load();
        }

        return Cache::remember('keyboard.shortcut-overrides', 3600, $load);
    }

    private function forgetShortcuts(): void
    {
        $this->resolved = null;
        Cache::forget('keyboard.shortcut-overrides');
    }

    /**
     * @param  list<array{id: string, keys: string, enabled: bool}>  $rows
     */
    public function save(array $rows): void
    {
        $defaults = collect($this->defaults())->keyBy('id');
        $current = collect($this->resolved())->keyBy('id');
        $proposed = [];

        foreach ($current as $id => $item) {
            $proposed[$id] = [
                'keys' => $item['keys'],
                'enabled' => (bool) $item['enabled'],
            ];
        }

        foreach ($rows as $row) {
            $id = (string) $row['id'];

            if (! $defaults->has($id)) {
                throw ValidationException::withMessages([
                    'shortcuts' => 'Unknown shortcut.',
                ]);
            }

            try {
                $keys = ShortcutCombo::normalize((string) $row['keys']);
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages([
                    'shortcuts' => $exception->getMessage(),
                ]);
            }

            $proposed[$id] = [
                'keys' => $keys,
                'enabled' => (bool) $row['enabled'],
            ];
        }

        $owners = [];

        foreach ($proposed as $id => $row) {
            if (! $row['enabled']) {
                continue;
            }

            if (isset($owners[$row['keys']])) {
                $first = $defaults->get($owners[$row['keys']])['label'] ?? $owners[$row['keys']];
                $second = $defaults->get($id)['label'] ?? $id;

                throw ValidationException::withMessages([
                    'shortcuts' => $first.' and '.$second.' both use '.$row['keys'].'.',
                ]);
            }

            $owners[$row['keys']] = $id;
        }

        $this->forgetShortcuts();

        foreach ($proposed as $id => $row) {
            $defaultKeys = ShortcutCombo::normalize((string) $defaults->get($id)['keys']);

            if ($row['keys'] === $defaultKeys && $row['enabled']) {
                KeyboardShortcut::query()->where('shortcut_id', $id)->delete();

                continue;
            }

            KeyboardShortcut::query()->updateOrCreate(
                ['shortcut_id' => $id],
                ['keys' => $row['keys'], 'is_enabled' => $row['enabled']],
            );
        }
    }

    public function restore(string $id): void
    {
        if (collect($this->defaults())->where('id', $id)->isEmpty()) {
            throw ValidationException::withMessages([
                'shortcuts' => 'Unknown shortcut.',
            ]);
        }

        KeyboardShortcut::query()->where('shortcut_id', $id)->delete();
        $this->forgetShortcuts();
    }

    public function restoreAll(): void
    {
        KeyboardShortcut::query()->delete();
        $this->forgetShortcuts();
    }
}
