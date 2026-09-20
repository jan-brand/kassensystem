<?php

namespace App\Modules\Reporting\Services;

use RuntimeException;

final class CsvExporter
{
    /**
     * @param list<string> $headers
     * @param iterable<array<int, scalar|null>> $rows
     */
    public function export(array $headers, iterable $rows): string
    {
        $stream = fopen('php://temp', 'w+');

        if ($stream === false) {
            throw new RuntimeException('Unable to create CSV stream.');
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $headers, ';', '"', '\\');

        foreach ($rows as $row) {
            fputcsv($stream, $row, ';', '"', '\\');
        }

        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);

        if ($contents === false) {
            throw new RuntimeException('Unable to read CSV stream.');
        }

        return $contents;
    }
}
