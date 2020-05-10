<?php

declare(strict_types=1);

namespace libReplay\data;

use RuntimeException;

/**
 * Static decompression class
 * for decompression of ReplayCompressionTask
 * based compression.
 *
 * Class ReplayDecompressor
 * @package libReplay\data
 *
 * @internal
 */
class ReplayDecompressor
{

    /**
     * Decompress the data.
     *
     * @param string $data
     * @return array
     */
    public static function decompress(string $data): array
    {
        if ($data !== null) {
            $json = zstd_uncompress($data);
            if ($json !== false) {
                $decompressedMemory = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                if ($decompressedMemory !== false) {
                    return $decompressedMemory;
                }
            }
        }

        throw new RuntimeException('Decompression failed');
    }

}