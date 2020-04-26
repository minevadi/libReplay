<?php

declare(strict_types=1);

namespace libReplay;

use libReplay\actor\HumanActor;
use libReplay\data\entry\DataEntry;
use libReplay\data\Replay;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\utils\TextFormat;

/**
 * Class ReplayViewer
 * @package libReplay
 */
class ReplayViewer
{

    /**
     * Setup the replay player.
     *
     * @return void
     */
    public static function setup(): void
    {
        Entity::registerEntity(HumanActor::class, true);
        return;
    }

    /** @var Replay */
    private $replay;
    /** @var HumanActor[] */
    private $actorList;
    /** @var Level */
    private $level;
    /** @var DataEntry[][] */
    private $unassignedDataEntryMemory;

    /** @var int */
    private $playbackSpeed = 1;

    /**
     * ReplayViewer constructor.
     * @param Replay $replay
     * @param Level $level
     */
    public function __construct(Replay $replay, Level $level)
    {
        $this->replay = $replay;
        $this->level = $level;
        $this->unassignedDataEntryMemory = $replay->getDataEntryMemory();
        $this->setupActorList();
    }

    /**
     * Setup the actor list.
     *
     * @return void
     */
    private function setupActorList(): void
    {
        $clientList = $this->replay->getRecordedClientList();
        foreach ($clientList as $client) {
            $clientId = $client->getClientId();
            $position = $client->getPosition();
            $rotation = $client->getRotation();
            $skin = $client->getSkin();
            $nbt = Entity::createBaseNBT($position, null, $rotation->yaw, $rotation->pitch);
            $this->actorList[$clientId] = new HumanActor($this->level, $nbt, $skin);
            $customName = $client->getCustomName();
            $this->actorList[$clientId]->setNameTag($customName);
            $scoreTag = TextFormat::WHITE . TextFormat::BOLD . '10' .  TextFormat::RED . ' ❤';
            $this->actorList[$clientId]->setScoreTag($scoreTag);
            /** @var DataEntry[][] $clientScript */
            $clientScript = [];
            foreach ($this->unassignedDataEntryMemory as $tick => $dataEntryList) {
                $clientScriptForTick = [];
                foreach ($dataEntryList as $index => $dataEntry) {
                    $dataEntryClientId = $dataEntry->getClientId();
                    if ($dataEntryClientId === $clientId) {
                        $clientScriptForTick[] = $dataEntry;
                        unset($dataEntryList[$index]);
                    }
                }
                $clientScript[$tick] = $clientScriptForTick;
            }
            $this->actorList[$clientId]->configure($this, $clientScript);
        }
        return;
    }

    /**
     * Play the replay currently set.
     *
     * @return void
     */
    public function play(): void
    {
        $isPlaying = $this->isReplayPlaying();
        if ($isPlaying) {
            return;
        }
        foreach ($this->actorList as $actor) {
            $actor->spawnToAll();
        }
        return;
    }

    /**
     * Pause the currently running replay.
     *
     * @return void
     */
    public function pause(): void
    {
        $isPlaying = $this->isReplayPlaying();
        if (!$isPlaying) {
            return;
        }
        $this->playbackSpeed = 0;
    }

    /**
     * Resume a paused replay.
     *
     * @return void
     */
    public function resume(): void
    {
        $isPaused = $this->playbackSpeed === 0;
        if ($isPaused) {
            $this->playbackSpeed = 1;
        }
    }

    /**
     * Stop the replay currently set.
     *
     * @return void
     */
    public function stop(): void
    {
        $isPlaying = $this->isReplayPlaying();
        if (!$isPlaying) {
            return;
        }
        foreach ($this->actorList as $actor) {
            $actor->flagForDespawn();
        }
        return;
    }

    /**
     * Get playback speed.
     *
     * @return int
     */
    public function getPlaybackSpeed(): int
    {
        return $this->playbackSpeed;
    }

    /**
     * Increase/decrease playback speed.
     *
     * @param bool $increase
     * @return void
     */
    public function changePlaybackSpeed(bool $increase = true): void
    {
        if ($increase === false && $this->playbackSpeed > 1) {
            $this->playbackSpeed--;
            return;
        }
        $this->playbackSpeed++;
    }

    /**
     * Check if the replay is currently
     * being played by this viewer.
     *
     * @return bool
     */
    public function isReplayPlaying(): bool
    {
        foreach ($this->actorList as $actor) {
            $flaggedForDespawn = $actor->isFlaggedForDespawn();
            if (!$flaggedForDespawn) {
                return true;
            }
        }
        return false;
    }

}