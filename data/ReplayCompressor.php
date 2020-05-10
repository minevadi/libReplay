<?php

declare(strict_types=1);

namespace libReplay\data;

use RuntimeException;

/**
 * Static compression/decompression class
 * for threaded environments.
 *
 * Class ReplayCompressor
 * @package libReplay\data
 *
 * @internal
 */
class ReplayCompressor
{

    /**
     * Compress the memory.
     *
     * @param array $memory
     * @param bool $useZStandard
     * @return string
     */
    public static function compress(array $memory, bool $useZStandard = true): string
    {
        $json = json_encode($memory, JSON_THROW_ON_ERROR, 512);
        if ($json !== false) {
            if ($useZStandard) {
                $compressedMemory = zstd_compress($json, ZSTD_COMPRESS_LEVEL_MAX);
            } else {
                $compressedMemory = gzdeflate($json, 9);
            }

            if ($compressedMemory !== false) {
                return $compressedMemory;
            }
        }

        throw new RuntimeException('Compression failed');
    }

    /**
     * Decompress the data.
     *
     * @param string $data
     * @param bool $useZStandard
     * @return array
     */
    public static function decompress(string $data, bool $useZStandard = true): array
    {
        if ($data !== null) {
            if ($useZStandard) {
                $json = zstd_uncompress($data);
            } else {
                $json = gzinflate($data);
            }
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