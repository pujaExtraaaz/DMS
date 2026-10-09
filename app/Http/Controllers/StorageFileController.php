<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StorageFileController extends Controller
{
    /**
     * Serve publicly accessible files from storage/app/public.
     *
     * Serves as a resilient fallback for production hosting environments where
     * the public/storage symlink does not exist, was not deployed, or is forbidden
     * by web server configuration (e.g. Options -FollowSymLinks).
     */
    public function show(string $path): Response
    {
        $cleanPath = ltrim($path, '/');

        // Prevent directory traversal attacks
        if (str_contains($cleanPath, '..') || str_contains($cleanPath, "\0")) {
            abort(404);
        }

        // Normalize path in case redundant prefixes were passed
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        } elseif (str_starts_with($cleanPath, 'public/')) {
            $cleanPath = substr($cleanPath, 7);
        }

        $cleanPath = ltrim($cleanPath, '/');

        if (blank($cleanPath) || ! Storage::disk('public')->exists($cleanPath)) {
            abort(404);
        }

        $extension = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));

        // Determine proper MIME type
        $mimeType = match ($extension) {
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => Storage::disk('public')->mimeType($cleanPath) ?: 'application/octet-stream',
        };

        return Storage::disk('public')->response($cleanPath, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400, must-revalidate',
        ]);
    }
}

