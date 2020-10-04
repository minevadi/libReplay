<?php

declare(strict_types=1);

namespace libReplay\event;

use libReplay\data\ReplayCompressed;
use libReplay\ReplayServer;
use pocketmine\event\plugin\PluginEvent;

/**
 * Class ReplayComposedEvent
 * @package libReplay\event
 */
class ReplayComposedEvent extends PluginEvent
{

    /** @var ReplayCompressed */
    private ReplayCompressed $replay;
    /** @var array */
    private array $extraData;

    /**
     * ReplayComposedEvent constructor.
     * @param ReplayCompressed $replay
     * @param array $extraData
     *
     * @internal
     */
    public function __construct(ReplayCompressed $replay, array $extraData)
    {
        $this->replay = $replay;
        $this->extraData = $extraData;
        parent::__construct(ReplayServer::getPlugin());
    }

    /**
     * Get the replay compressed.
     *
     * @return ReplayCompressed
     */
    public function getReplay(): ReplayCompressed
    {
        return $this->replay;
    }

    /**
     * Get the extra data added to this event.
     *
     * @return array
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

}