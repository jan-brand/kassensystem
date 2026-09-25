<?php

namespace App\Modules\Sales\Services;

use InvalidArgumentException;

/**
 * Dependency-free QR Code Model 2 encoder for short UTF-8/ASCII payment URLs.
 *
 * V2 intentionally fixes the symbol to version 6 / error correction L / mask 0.
 * That gives 134 payload bytes, enough for the configured PayPal.me payment URL,
 * while keeping the implementation small and deterministic for an offline POS.
 */
final class QrCodeSvgService
{
    private const SIZE = 41;

    private const DATA_CODEWORDS = 136;

    private const BLOCK_DATA_CODEWORDS = 68;

    private const ECC_CODEWORDS = 18;

    private const MAX_PAYLOAD_BYTES = 134;

    private const QUIET_ZONE = 4;

    public function render(string $data): string
    {
        $matrix = $this->matrix($data);
        $dimension = self::SIZE + (self::QUIET_ZONE * 2);
        $rects = [];

        foreach ($matrix as $row => $modules) {
            foreach ($modules as $column => $dark) {
                if ($dark) {
                    $x = $column + self::QUIET_ZONE;
                    $y = $row + self::QUIET_ZONE;
                    $rects[] = "<rect x=\"{$x}\" y=\"{$y}\" width=\"1\" height=\"1\"/>";
                }
            }
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$dimension.' '.$dimension.'" role="img" aria-label="PayPal.me QR-Code" shape-rendering="crispEdges" style="display:block;width:100%;height:auto">'
            .'<rect width="'.$dimension.'" height="'.$dimension.'" fill="white"/>'
            .'<g fill="black">'.implode('', $rects).'</g>'
            .'</svg>';
    }

    /** @return array<int, array<int, bool>> */
    private function matrix(string $data): array
    {
        if (strlen($data) > self::MAX_PAYLOAD_BYTES) {
            throw new InvalidArgumentException('QR-Inhalt ist zu lang.');
        }

        $dataCodewords = $this->dataCodewords($data);
        $blocks = [
            array_slice($dataCodewords, 0, self::BLOCK_DATA_CODEWORDS),
            array_slice($dataCodewords, self::BLOCK_DATA_CODEWORDS, self::BLOCK_DATA_CODEWORDS),
        ];
        $eccBlocks = [
            $this->errorCorrectionCodewords($blocks[0]),
            $this->errorCorrectionCodewords($blocks[1]),
        ];
        $codewords = [];

        for ($i = 0; $i < self::BLOCK_DATA_CODEWORDS; $i++) {
            $codewords[] = $blocks[0][$i];
            $codewords[] = $blocks[1][$i];
        }

        for ($i = 0; $i < self::ECC_CODEWORDS; $i++) {
            $codewords[] = $eccBlocks[0][$i];
            $codewords[] = $eccBlocks[1][$i];
        }

        /** @var array<int, array<int, bool|null>> $matrix */
        $matrix = array_fill(0, self::SIZE, array_fill(0, self::SIZE, null));

        $this->placeProbePattern($matrix, 0, 0);
        $this->placeProbePattern($matrix, self::SIZE - 7, 0);
        $this->placeProbePattern($matrix, 0, self::SIZE - 7);
        $this->placeAlignmentPatterns($matrix);
        $this->placeTimingPatterns($matrix);
        $this->placeFormatInformation($matrix);
        $this->placeData($matrix, $codewords);

        return array_map(
            static fn (array $row): array => array_map(static fn (?bool $value): bool => $value ?? false, $row),
            $matrix,
        );
    }

    /** @return list<int> */
    private function dataCodewords(string $data): array
    {
        $bits = [];
        $this->appendBits($bits, 0b0100, 4); // byte mode
        $this->appendBits($bits, strlen($data), 8); // version 1-9 byte count

        foreach (unpack('C*', $data) ?: [] as $byte) {
            $this->appendBits($bits, $byte, 8);
        }

        $capacityBits = self::DATA_CODEWORDS * 8;
        $terminator = min(4, $capacityBits - count($bits));

        for ($i = 0; $i < $terminator; $i++) {
            $bits[] = 0;
        }

        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $codewords = [];

        for ($offset = 0; $offset < count($bits); $offset += 8) {
            $value = 0;

            for ($bit = 0; $bit < 8; $bit++) {
                $value = ($value << 1) | $bits[$offset + $bit];
            }

            $codewords[] = $value;
        }

        $pads = [0xEC, 0x11];
        $padIndex = 0;

        while (count($codewords) < self::DATA_CODEWORDS) {
            $codewords[] = $pads[$padIndex % 2];
            $padIndex++;
        }

        return $codewords;
    }

    /** @param list<int> $bits */
    private function appendBits(array &$bits, int $value, int $length): void
    {
        for ($bit = $length - 1; $bit >= 0; $bit--) {
            $bits[] = ($value >> $bit) & 1;
        }
    }

    /**
     * @param  list<int>  $data
     * @return list<int>
     */
    private function errorCorrectionCodewords(array $data): array
    {
        $generator = $this->generatorPolynomial(self::ECC_CODEWORDS);
        $result = array_fill(0, self::ECC_CODEWORDS, 0);

        foreach ($data as $byte) {
            $factor = $byte ^ $result[0];
            array_shift($result);
            $result[] = 0;

            for ($i = 0; $i < self::ECC_CODEWORDS; $i++) {
                $result[$i] ^= $this->gfMultiply($generator[$i + 1], $factor);
            }
        }

        return $result;
    }

    /** @return list<int> */
    private function generatorPolynomial(int $degree): array
    {
        $polynomial = [1];
        $root = 1;

        for ($i = 0; $i < $degree; $i++) {
            $next = array_fill(0, count($polynomial) + 1, 0);

            foreach ($polynomial as $index => $coefficient) {
                $next[$index] ^= $coefficient;
                $next[$index + 1] ^= $this->gfMultiply($coefficient, $root);
            }

            $polynomial = $next;
            $root = $this->gfMultiply($root, 2);
        }

        return $polynomial;
    }

    private function gfMultiply(int $x, int $y): int
    {
        $result = 0;

        while ($y > 0) {
            if (($y & 1) !== 0) {
                $result ^= $x;
            }

            $y >>= 1;
            $x <<= 1;

            if (($x & 0x100) !== 0) {
                $x ^= 0x11D;
            }
        }

        return $result & 0xFF;
    }

    /** @param array<int, array<int, bool|null>> $matrix */
    private function placeProbePattern(array &$matrix, int $row, int $column): void
    {
        for ($r = -1; $r <= 7; $r++) {
            if ($row + $r < 0 || $row + $r >= self::SIZE) {
                continue;
            }

            for ($c = -1; $c <= 7; $c++) {
                if ($column + $c < 0 || $column + $c >= self::SIZE) {
                    continue;
                }

                $matrix[$row + $r][$column + $c] = (
                    ($r >= 0 && $r <= 6 && ($c === 0 || $c === 6))
                    || ($c >= 0 && $c <= 6 && ($r === 0 || $r === 6))
                    || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)
                );
            }
        }
    }

    /** @param array<int, array<int, bool|null>> $matrix */
    private function placeAlignmentPatterns(array &$matrix): void
    {
        foreach ([6, 34] as $row) {
            foreach ([6, 34] as $column) {
                if ($matrix[$row][$column] !== null) {
                    continue;
                }

                for ($r = -2; $r <= 2; $r++) {
                    for ($c = -2; $c <= 2; $c++) {
                        $matrix[$row + $r][$column + $c] = $r === -2
                            || $r === 2
                            || $c === -2
                            || $c === 2
                            || ($r === 0 && $c === 0);
                    }
                }
            }
        }
    }

    /** @param array<int, array<int, bool|null>> $matrix */
    private function placeTimingPatterns(array &$matrix): void
    {
        for ($row = 8; $row < self::SIZE - 8; $row++) {
            if ($matrix[$row][6] === null) {
                $matrix[$row][6] = $row % 2 === 0;
            }
        }

        for ($column = 8; $column < self::SIZE - 8; $column++) {
            if ($matrix[6][$column] === null) {
                $matrix[6][$column] = $column % 2 === 0;
            }
        }
    }

    /** @param array<int, array<int, bool|null>> $matrix */
    private function placeFormatInformation(array &$matrix): void
    {
        $formatBits = $this->formatBits();

        for ($i = 0; $i < 15; $i++) {
            $dark = (($formatBits >> $i) & 1) === 1;

            if ($i < 6) {
                $matrix[$i][8] = $dark;
            } elseif ($i < 8) {
                $matrix[$i + 1][8] = $dark;
            } else {
                $matrix[self::SIZE - 15 + $i][8] = $dark;
            }

            if ($i < 8) {
                $matrix[8][self::SIZE - $i - 1] = $dark;
            } elseif ($i < 9) {
                $matrix[8][15 - $i] = $dark;
            } else {
                $matrix[8][15 - $i - 1] = $dark;
            }
        }

        $matrix[self::SIZE - 8][8] = true;
    }

    private function formatBits(): int
    {
        // Error correction L = 01, mask pattern 0 = 000.
        $data = 0b01000;
        $generator = 0x537;
        $value = $data << 10;

        while ($this->bchDigit($value) - $this->bchDigit($generator) >= 0) {
            $value ^= $generator << ($this->bchDigit($value) - $this->bchDigit($generator));
        }

        return (($data << 10) | $value) ^ 0x5412;
    }

    private function bchDigit(int $value): int
    {
        $digits = 0;

        while ($value !== 0) {
            $digits++;
            $value >>= 1;
        }

        return $digits;
    }

    /**
     * @param  array<int, array<int, bool|null>>  $matrix
     * @param  list<int>  $codewords
     */
    private function placeData(array &$matrix, array $codewords): void
    {
        $increment = -1;
        $row = self::SIZE - 1;
        $bitIndex = 7;
        $byteIndex = 0;

        for ($baseColumn = self::SIZE - 1; $baseColumn > 0; $baseColumn -= 2) {
            $column = $baseColumn;

            if ($column <= 6) {
                $column--;
            }

            while (true) {
                foreach ([$column, $column - 1] as $currentColumn) {
                    if ($matrix[$row][$currentColumn] !== null) {
                        continue;
                    }

                    $dark = false;

                    if ($byteIndex < count($codewords)) {
                        $dark = (($codewords[$byteIndex] >> $bitIndex) & 1) === 1;
                    }

                    // Mask 0.
                    if (($row + $currentColumn) % 2 === 0) {
                        $dark = ! $dark;
                    }

                    $matrix[$row][$currentColumn] = $dark;
                    $bitIndex--;

                    if ($bitIndex === -1) {
                        $byteIndex++;
                        $bitIndex = 7;
                    }
                }

                $row += $increment;

                if ($row < 0 || $row >= self::SIZE) {
                    $row -= $increment;
                    $increment = -$increment;
                    break;
                }
            }
        }
    }
}
