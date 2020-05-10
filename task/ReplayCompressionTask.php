<?php

declare(strict_types=1);

namespace libReplay\task;

use BadMethodCallException;
use libReplay\data\ReplayCompressed;
use libReplay\data\ReplayCompressor;
use libReplay\event\ReplayComposedEvent;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

/**
 * Class ReplayCompressionTask
 * @package libReplay\task
 *
 * @internal
 */
class ReplayCompressionTask extends AsyncTask
{

    public const MEMORY_TYPE_REPLAY = 0;
    public const MEMORY_TYPE_CLIENT = 1;

    /** @var array */
    private $memory = [
        self::MEMORY_TYPE_REPLAY => [],
        self::MEMORY_TYPE_CLIENT => []
    ];

    /** @var string */
    private $compressedMemory;

    /**
     * Wrapper over {@link AsyncTask::storeLocal()}.
     *
     * @param array $extraData
     * @return void
     */
    public function storeExtraData(array $extraData): void
    {
        $this->storeLocal($extraData);
    }

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

    /**
     * Add client data.
     *
     * @param array $clientData
     * @return void
     */
    public function addClientData(array $clientData): void
    {
        $this->memory[self::MEMORY_TYPE_CLIENT][] = $clientData;
    }

    /**
     * @inheritDoc
     */
    public function onRun(): void
    {
        $this->compressedMemory = ReplayCompressor::compress($this->memory);
    }

    /**
     * @inheritDoc
     */
    public function onCompletion(Server $server): void
    {
        $replay = new ReplayCompressed($this->compressedMemory);
        $extraData = [];
        try {
            $extraData = $this->fetchLocal();
        } catch (BadMethodCallException $exception) {
            $server->getLogger()->info('A replay was compressed without any extra data.');
        }
        $event = new ReplayComposedEvent($replay, $extraData);
        $event->call();
    }

}