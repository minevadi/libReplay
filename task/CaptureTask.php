<?php

declare(strict_types=1);

namespace libReplay\task;

use libReplay\ReplayServer;
use pocketmine\scheduler\Task;

/**
 * Class CaptureTask
 * @package libReplay\task
 *
 * @internal
 */
class CaptureTask extends Task
{

    /** @var ReplayServer */
    private ReplayServer $replayServer;

    /**
     * CaptureTask constructor.
     * @param ReplayServer $replayServer
     */
    public function __construct(ReplayServer $replayServer)
    {
        $this->replayServer = $replayServer;
    }

    /**
     * @return void
     */
    public function onRun(): void
    {
        $this->replayServer->capture();
    }

}