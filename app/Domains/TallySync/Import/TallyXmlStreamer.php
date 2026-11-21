<?php

namespace App\Domains\TallySync\Import;

use App\Domains\TallySync\Bridge\TallyInbound;
use Illuminate\Support\Facades\File;
use XMLReader;

/**
 * Stream Tally XML with XMLReader — one pass, low memory.
 * Writes entity outer-XML blocks into a work directory grouped by tag.
 */
final class TallyXmlStreamer
{
    /**
     * Normalize encoding to a UTF-8 cache file (reuses ImportTallyCommand cache layout).
     */
    public static function utf8Path(string $path): string
    {
        $cacheDir = storage_path('app/tally-imports/utf8_cache');
        File::ensureDirectoryExists($cacheDir);
        $cache = $cacheDir.'/'.md5($path.filesize($path).filemtime($path)).'.xml';
        if (is_file($cache) && filesize($cache) > 100) {
            return $cache;
        }

        $raw = File::get($path);
        if (str_starts_with($raw, "\xFF\xFE") || str_starts_with($raw, "\xFE\xFF")) {
            $converted = @mb_convert_encoding($raw, 'UTF-8', 'UTF-16');
            if (is_string($converted) && $converted !== '') {
                $raw = $converted;
            }
        } elseif (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }
        if (str_contains($raw, "\0")) {
            $raw = str_replace("\0", '', $raw);
        }
        $xml = TallyInbound::sanitizeTallyXml($raw);
        unset($raw);
        File::put($cache, $xml);
        unset($xml);

        return $cache;
    }

    /**
     * Stream the file once and bucket entity blocks by tag into $workDir/{TAG}/n.xml
     * and optional chunk packs $workDir/{TAG}/chunks/chunk_XXXX.xml (wrapped ROOT).
     *
     * @param  list<string>  $tags  e.g. ['LEDGER','GROUP','VOUCHER']
     * @return array<string, int> tag => entity count
     */
    public static function indexToDisk(string $xmlPath, array $tags, string $workDir, int $chunkSize = 200): array
    {
        $utf8 = self::utf8Path($xmlPath);
        File::ensureDirectoryExists($workDir);

        $wanted = [];
        foreach ($tags as $tag) {
            $wanted[strtoupper($tag)] = true;
            File::ensureDirectoryExists($workDir.'/'.strtoupper($tag).'/chunks');
        }

        $counts = array_fill_keys(array_map('strtoupper', $tags), 0);
        $buffers = array_fill_keys(array_map('strtoupper', $tags), []);
        $chunkIndex = array_fill_keys(array_map('strtoupper', $tags), 0);

        $reader = new XMLReader;
        if (! @$reader->open($utf8, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE)) {
            // Fallback: regex split when XMLReader cannot open (rare malformed dumps).
            return self::indexToDiskRegex($utf8, $tags, $workDir, $chunkSize);
        }

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }
            $tag = strtoupper($reader->localName ?: $reader->name);
            if (! isset($wanted[$tag])) {
                continue;
            }
            // Skip deeply nested clones; TallyMESSAGE children are typically depth ≤ 5.
            if ($reader->depth > 8) {
                continue;
            }

            $outer = $reader->readOuterXml();
            if ($outer === '' || $outer === false) {
                continue;
            }

            $counts[$tag]++;
            $buffers[$tag][] = $outer;

            if (count($buffers[$tag]) >= $chunkSize) {
                $chunkIndex[$tag]++;
                self::flushChunk($workDir, $tag, $chunkIndex[$tag], $buffers[$tag]);
                $buffers[$tag] = [];
            }
        }
        $reader->close();

        foreach ($buffers as $tag => $blocks) {
            if ($blocks === []) {
                continue;
            }
            $chunkIndex[$tag]++;
            self::flushChunk($workDir, $tag, $chunkIndex[$tag], $blocks);
        }

        return $counts;
    }

    /**
     * @param  list<string>  $blocks
     */
    private static function flushChunk(string $workDir, string $tag, int $index, array $blocks): void
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?><ROOT>'.implode('', $blocks).'</ROOT>';
        File::put(sprintf('%s/%s/chunks/chunk_%04d.xml', $workDir, $tag, $index), $body);
    }

    /**
     * @param  list<string>  $tags
     * @return array<string, int>
     */
    private static function indexToDiskRegex(string $utf8Path, array $tags, string $workDir, int $chunkSize): array
    {
        $xml = File::get($utf8Path);
        $counts = [];
        foreach ($tags as $tag) {
            $upper = strtoupper($tag);
            $counts[$upper] = 0;
            File::ensureDirectoryExists($workDir.'/'.$upper.'/chunks');
            $pattern = '/<'.preg_quote($tag, '/').'\b[^>]*>.*?<\/'.preg_quote($tag, '/').'>/is';
            if (! preg_match_all($pattern, $xml, $matches)) {
                continue;
            }
            $buffers = [];
            $chunkNo = 0;
            foreach ($matches[0] as $outer) {
                $counts[$upper]++;
                $buffers[] = $outer;
                if (count($buffers) >= $chunkSize) {
                    $chunkNo++;
                    self::flushChunk($workDir, $upper, $chunkNo, $buffers);
                    $buffers = [];
                }
            }
            if ($buffers !== []) {
                $chunkNo++;
                self::flushChunk($workDir, $upper, $chunkNo, $buffers);
            }
        }
        unset($xml);

        return $counts;
    }

    /**
     * @return list<string> absolute chunk file paths
     */
    public static function chunkFiles(string $workDir, string $tag): array
    {
        $dir = $workDir.'/'.strtoupper($tag).'/chunks';
        if (! is_dir($dir)) {
            return [];
        }
        $files = glob($dir.'/chunk_*.xml') ?: [];
        sort($files);

        return $files;
    }

    /**
     * Build one combined ROOT document for a master tag (for smaller collections).
     */
    public static function combinedXml(string $workDir, string $tag): string
    {
        $chunks = self::chunkFiles($workDir, $tag);
        if ($chunks === []) {
            return '<?xml version="1.0" encoding="UTF-8"?><ROOT></ROOT>';
        }
        if (count($chunks) === 1) {
            return File::get($chunks[0]);
        }

        $parts = [];
        foreach ($chunks as $file) {
            $xml = File::get($file);
            if (preg_match_all('/<'.preg_quote(strtoupper($tag), '/').'\b[^>]*>.*?<\/'.preg_quote(strtoupper($tag), '/').'>/is', $xml, $m)) {
                foreach ($m[0] as $block) {
                    $parts[] = $block;
                }
            }
            unset($xml);
        }

        return '<?xml version="1.0" encoding="UTF-8"?><ROOT>'.implode('', $parts).'</ROOT>';
    }
}
