<?php

declare(strict_types=1);

namespace libReplay\data;

use libReplay\data\entry\DataEntry;
use libReplay\ReplayClient;
use libReplay\ReplayServer;
use libReplay\task\ReplayCompressionTask;

/**
 * Class ReplayCompressed
 * @package libReplay\data
 */
class ReplayCompressed
{

    /** @var string */
    private string $memory;
    /** @var float */
    private float $version;

    /**
     * ReplayCompressed constructor.
     * @param string $memory
     * @param float $version
     */
    public function __construct(string $memory, float $version = ReplayServer::API)
    {
        $this->memory = $memory;
        $this->version = $version;
    }

    /**
     * Get the replay memory.
     *
     * @return string
     */
    public function getMemory(): string
    {
        return $this->memory;
    }

    /**
     * Get the version of the ReplayServer
     * used to record this replay.
     *
     * @return float
     */
    public function getVersion(): float
    {
        return $this->version;
    }

    /**
     * Decompress the compressed replay object.
     *
     * @return Replay|null
     */
    public function decompress(): ?Replay
    {
        $memory = ReplayDecompressor::decompress($this->memory);
        $validationCheck = isset($memory[ReplayCompressionTask::MEMORY_TYPE_CLIENT], $memory[ReplayCompressionTask::MEMORY_TYPE_CLIENT]);
        if (!is_array($memory) || !$validationCheck) {
            return null;
        }
        $dataEntryMemory = [];
        foreach ($memory[ReplayCompressionTask::MEMORY_TYPE_REPLAY] as $tick => $nonVolatileDataEntryList) {
            $dataEntryListPerTick = [];
            foreach ($nonVolatileDataEntryList as $stepStamp => $nonVolatileDataEntry) {
                $dataEntry = DataEntry::readFromNonVolatile($nonVolatileDataEntry);
                if ($dataEntry === null) {
                    return null;
                }
                $dataEntryListPerTick[$stepStamp] = $dataEntry;
            }
            $dataEntryMemory[$tick] = $dataEntryListPerTick;
        }
        $recordedClientList = [];
        foreach ($memory[ReplayCompressionTask::MEMORY_TYPE_CLIENT] as $index => $nonVolatileClient) {
            $recordedClient = ReplayClient::constructFromNonVolatile($nonVolatileClient);
            if ($recordedClient === null) {
                return null;
            }
            $recordedClientList[$index] = $recordedClient;
        }
        return new Replay($dataEntryMemory, $recordedClientList, $this->version);
    }

}