<?php

namespace Tally\DataExchange\Import;

/**
 * Resolves parent references that may already exist or appear in the same file.
 */
class ParentLinks
{
    /**
     * @param  list<array{row: int, tokens: list<string>, parent: ?string}>  $nodes
     * @param  array<string, int>  $existing
     * @return array{links: array<int, array{mode: string, id: ?int, token: ?string}>, errors: list<array{row: int, message: string}>}
     */
    public static function resolve(array $nodes, array $existing): array
    {
        $file = [];

        foreach ($nodes as $index => $node) {
            foreach ($node['tokens'] as $token) {
                $file[$token] = $index;
            }
        }

        $links = [];
        $errors = [];

        foreach ($nodes as $index => $node) {
            $parent = $node['parent'];

            if ($parent === null || $parent === '') {
                $links[$index] = ['mode' => 'none', 'id' => null, 'token' => null];

                continue;
            }

            if (in_array($parent, $node['tokens'], true)) {
                $errors[] = ['row' => $node['row'], 'message' => 'A record cannot be its own parent.'];
                $links[$index] = ['mode' => 'none', 'id' => null, 'token' => null];

                continue;
            }

            if (isset($existing[$parent])) {
                $links[$index] = ['mode' => 'database', 'id' => $existing[$parent], 'token' => null];

                continue;
            }

            if (isset($file[$parent])) {
                $links[$index] = ['mode' => 'file', 'id' => null, 'token' => $nodes[$file[$parent]]['tokens'][0]];

                continue;
            }

            $errors[] = ['row' => $node['row'], 'message' => 'Parent "'.$parent.'" was not found.'];
            $links[$index] = ['mode' => 'none', 'id' => null, 'token' => null];
        }

        $errors = array_merge($errors, self::cycles($nodes, $links));

        return ['links' => $links, 'errors' => $errors];
    }

    /**
     * @param  list<array{row: int, tokens: list<string>}>  $nodes
     * @param  array<int, array{mode: string, token: ?string}>  $links
     * @return list<array{row: int, message: string}>
     */
    private static function cycles(array $nodes, array $links): array
    {
        $byToken = [];

        foreach ($nodes as $index => $node) {
            foreach ($node['tokens'] as $token) {
                $byToken[$token] = $index;
            }
        }

        $errors = [];

        foreach ($nodes as $start => $node) {
            $seen = [];
            $index = $start;
            $guard = 0;

            while ($guard < 50 && ($links[$index]['mode'] ?? '') === 'file') {
                if (isset($seen[$index])) {
                    $errors[] = ['row' => $node['row'], 'message' => 'Parent groups form a circle.'];
                    break;
                }

                $seen[$index] = true;
                $token = $links[$index]['token'] ?? '';
                $index = $byToken[$token] ?? -1;

                if ($index < 0) {
                    break;
                }

                $guard++;
            }
        }

        return $errors;
    }

    /**
     * @template T of array{parent_mode: string, parent_token: ?string, tokens: list<string>}
     *
     * @param  list<T>  $items
     * @return list<T>
     */
    public static function order(array $items): array
    {
        $ready = [];
        $pending = $items;
        $done = [];
        $guard = 0;

        while ($pending !== [] && $guard < count($items) + 2) {
            $guard++;
            $next = [];

            foreach ($pending as $item) {
                $parentReady = $item['parent_mode'] !== 'file'
                    || isset($done[$item['parent_token'] ?? '']);

                if ($parentReady) {
                    $ready[] = $item;

                    foreach ($item['tokens'] as $token) {
                        $done[$token] = true;
                    }
                } else {
                    $next[] = $item;
                }
            }

            if (count($next) === count($pending)) {
                break;
            }

            $pending = $next;
        }

        return array_merge($ready, $pending);
    }
}
