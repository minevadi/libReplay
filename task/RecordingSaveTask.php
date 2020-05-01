<?php

declare(strict_types=1);

namespace libReplay\task;

use libReplay\data\ReplayCompressed;
use libReplay\event\ReplayComposedEvent;
use libReplay\thread\ThreadedMemory;
use pocketmine\scheduler\Task;

/**
 * Class RecordingSaveTask
 * @package libReplay\task
 *
 * @internal
 */
class RecordingSaveTask extends Task
{

    /** @var ThreadedMemory */
    private $memory;
    /** @var array */
    private $extraSaveData;

    /**
     * RecordingSaveTask constructor.
     * @param ThreadedMemory $memory
     * @param array $extraSaveData
     */
    public function __construct(ThreadedMemory $memory, array $extraSaveData)
    {
        $this->memory = $memory;
        $this->extraSaveData = $extraSaveData;
    }

    /**
     * @inheritDoc
     */
    public function onRun(int $currentTick): void
    {
        if ($this->memory->pollForCompression()) {
            $result = $this->memory->getResult();
            if (is_string($result)) {
                $replay = new ReplayCompressed($result);
                $event = new ReplayComposedEvent($replay, $this->extraSaveData);
                $event->call();

                if (($handler = $this->getHandler()) !== null) {
                    $handler->cancel();
                }
                $this->memory->markReadyToFlush();
                return;
            }
            $this->memory->markReadyToFlush();
        }
        return;
    }

}