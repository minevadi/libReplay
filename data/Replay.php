<?php

declare(strict_types=1);

namespace libReplay\data;

use libReplay\data\entry\DataEntry;
use libReplay\ReplayClient;
use libReplay\ReplayServer;

/**
 * Class Replay
 * @package libReplay\data
 *
 * @internal
 */
class Replay
{

    /** @var DataEntry[][] */
    private array $dataEntryMemory;
    /** @var ReplayClient[] */
    private array $recordedClientList;
    /** @var float */
    private float $version;

    /**
     * Replay constructor.
     * @param DataEntry[][] $dataEntryMemory
     * @param ReplayClient[] $recordedClientList
     * @param float $version
     */
    public function __construct(array $dataEntryMemory, array $recordedClientList, float $version = ReplayServer::API)
    {
        $this->dataEntryMemory = $dataEntryMemory;
        $this->recordedClientList = $recordedClientList;
        $this->version = $version;
    }

    /**
     * Get all recorded data entries.
     *
     * @return DataEntry[][]
     */
    public function getDataEntryMemory(): array
    {
        return $this->dataEntryMemory;
    }

    /**
     * Get recorded client lists.
     *
     * @return ReplayClient[]
     */
    public function getRecordedClientList(): array
    {
        return $this->recordedClientList;
    }

    /**
     * Get the version of the ReplayServer used
     * to record this replay.
     *
     * This is also referred to the version of the
     * replay for ease of definition.
     *
     * @return float
     */
    public function getVersion(): float
    {
        return $this->version;
    }

}