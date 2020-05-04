<?php

declare(strict_types=1);

namespace libReplay\thread;

use JsonException;
use pocketmine\Thread;
use function var_dump;

/**
 * Class ThreadedMemory
 * @package libReplay\thread
 *
 * @internal
 */
class ThreadedMemory extends Thread
{

    public const MEMORY_TYPE_REPLAY = 0;
    public const MEMORY_TYPE_CLIENT = 1;

    /** @var bool */
    private $finishedCompression = false;
    /** @var bool */
    private $readyToFlush = false;

    /** @var array */
    private $memory = [
        self::MEMORY_TYPE_REPLAY => [],
        self::MEMORY_TYPE_CLIENT => []
    ];

    /** @var string|null */
    private $compressedMemory;

    /**
     * Add the current tick to memory.
     *
     * @param int $currentTick
     * @param array $tickMemory
     * @return void
     */
    public function addCurrentTickToMemory(int $currentTick, array $tickMemory): void
    {
        $this->memory[self::MEMORY_TYPE_REPLAY][$currentTick] = $tickMemory;
        return;
    }

    public function addClientData(array $clientData): void
    {
        $this->memory[self::MEMORY_TYPE_CLIENT][] = $clientData;
    }

    /**
     * Compress the memory.
     *
     * @param bool $useZStandard
     * @return void
     * @throws JsonException
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public function compress(bool $useZStandard = true): void
    {
        $json = json_encode($this->memory, JSON_THROW_ON_ERROR, 512);
        if ($json !== false) {
            if ($useZStandard) {
                $compressedMemory = zstd_compress($json, ZSTD_COMPRESS_LEVEL_MAX);
            } else {
                $compressedMemory = gzdeflate($json, 9);
            }

            if ($compressedMemory !== false) {
                $this->compressedMemory = $compressedMemory;
                return;
            }
        }
        $this->compressedMemory = null;
        return;
    }

    /**
     * Run the threaded memory operation.
     *
     * @return void
     */
    public function run(): void
    {
        $this->registerClassLoader();

        try {
            $this->compress();
            $this->finishedCompression = true;
            while (!$this->readyToFlush) {
                $this->wait(1000);
            }
        } catch (JsonException $exception){
            /** @noinspection ForgottenDebugOutputInspection */
            var_dump($exception->getMessage());
        }
        return;
    }

    /**
     * Poll to check whether compression has
     * finished yet.
     *
     * @return bool
     */
    public function pollForCompression(): bool
    {
        return $this->finishedCompression;
    }

    /**
     * Get the result of the completed operation.
     * Please note this function will return
     * an integer (0x101) if the result hasn't
     * been properly made yet.
     *
     * @return string|int|null
     */
    public function getResult()
    {
        if (!$this->finishedCompression) {
            return 0x101;
        }

        return $this->compressedMemory;
    }

    /**
     * Mark thread ready to be flushed.
     *
     * @return void
     */
    public function markReadyToFlush(): void
    {
        $this->readyToFlush = true;
    }

}