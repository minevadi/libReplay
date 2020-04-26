<?php

declare(strict_types=1);

namespace libReplay\data;

/**
 * Static decompression class
 * for libReplay\thread\ThreadedMemory
 * based decompression.
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
        return [];
    }

}