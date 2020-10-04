<?php

declare(strict_types=1);

namespace libReplay\data\entry;

/**
 * Class SpawnStateEntry
 * @package libReplay\data\entry
 *
 * This entry is made to dictate a change in spawn-state.
 * Usually when a player dies or when he comes back alive,
 * to signal the replay viewer to remove the actor from
 * visibility.
 */
class SpawnStateEntry extends DataEntry
{

    private const TAG_SPAWNED = 'spawned';
    private const TAG_KEEP_INVENTORY = 'keepInventory';

    /**
     * @inheritDoc
     *
     * @internal
     */
    public static function constructFromNonVolatile(string $clientId, array $nonVolatileEntry): ?DataEntry
    {
        $isValid = array_key_exists(self::TAG_SPAWNED, $nonVolatileEntry) &&
            array_key_exists(self::TAG_KEEP_INVENTORY, $nonVolatileEntry);
        if ($isValid) {
            return new self(
                $clientId,
                $nonVolatileEntry[self::TAG_SPAWNED],
                $nonVolatileEntry[self::TAG_KEEP_INVENTORY]
            );
        }
        return null;
    }

    /** @var bool The state of the client. True means spawned. False means despawned. */
    private bool $spawned;
    /** @var bool Whether to keep the previous inventory state. */
    private bool $keepInventory;

    /**
     * SpawnStateEntry constructor.
     * @param string $clientId
     * @param bool $spawned
     * @param bool $keepInventory
     */
    public function __construct(string $clientId, bool $spawned = true, bool $keepInventory = false)
    {
        $this->spawned = $spawned;
        $this->keepInventory = $keepInventory;
        $this->entryType = EntryTypes::SPAWN_STATE;
        parent::__construct($clientId);
    }

    /**
     * Check if client is spawned.
     *
     * @return bool
     */
    public function isSpawned(): bool
    {
        return $this->spawned;
    }

    /**
     * Check if inventory should be preserved.
     *
     * @return bool
     */
    public function isKeepInventory(): bool
    {
        return $this->keepInventory;
    }

    /**
     * @inheritDoc
     *
     * @internal
     */
    public function convertToNonVolatile(): array
    {
        $nonVolatileEntry = parent::convertToNonVolatile();
        // maybe it's better to use some other type of tag for 1 byte (bool) values.
        // ShortTag might be better? Haven't tried it yet.
        $nonVolatileEntry[self::TAG_SPAWNED] = $this->spawned;
        $nonVolatileEntry[self::TAG_KEEP_INVENTORY] = $this->keepInventory;
        return $nonVolatileEntry;
    }

}