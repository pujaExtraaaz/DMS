<?php

namespace App\Support\Traits;

use App\Support\DocumentExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

trait ExportsListing
{
    protected function exportIfRequested(Request $request, Builder $query, string $basename, string $title, array $headers, callable $map): ?Response
    {
        if (! $request->filled('export')) {
            return null;
        }

        $format = strtolower($request->string('export')->toString());
        if (! in_array($format, ['csv', 'xlsx', 'excel', 'pdf'], true)) {
            return null;
        }

        $rows = [];
        foreach ($query->get() as $item) {
            $rows[] = array_values($map($item));
        }

        return DocumentExporter::exportTabular($basename, $title, $headers, $rows, $format);
    }
}