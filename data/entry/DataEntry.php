<?php

declare(strict_types=1);

namespace libReplay\data\entry;

use libReplay\data\entry\block\BlockBreakEntry;
use libReplay\data\entry\block\BlockPlaceEntry;
use libReplay\data\entry\block\ChestInteractionEntry;

/**
 * Class DataEntry
 * @package libReplay\data\entry
 *
 * @internal
 */
abstract class DataEntry
{

    protected const ENTRY_TYPE_TAG = 0;
    protected const CLIENT_ID_TAG = 1;

    /**
     * Read from a non-volatile data package.
     *
     * @param array $nonVolatileEntry
     * @return DataEntry|null
     */
    public static function readFromNonVolatile(array $nonVolatileEntry): ?DataEntry
    {
        $isValid = array_key_exists(self::ENTRY_TYPE_TAG, $nonVolatileEntry) &&
            array_key_exists(self::CLIENT_ID_TAG, $nonVolatileEntry);
        if ($isValid) {
            $clientId = $nonVolatileEntry[self::CLIENT_ID_TAG];
            switch ($nonVolatileEntry[self::ENTRY_TYPE_TAG]) {
                case EntryTypes::TRANSFORM:
                    return TransformEntry::constructFromNonVolatile($clientId, $nonVolatileEntry);
                case EntryTypes::TAKE_DAMAGE:
                    return TakeDamageEntry::constructFromNonVolatile($clientId, $nonVolatileEntry);
                case EntryTypes::REGAIN_HEALTH:
                    return RegainHealthEntry::constructFromNonVolatile($clientId, $nonVolatileEntry);
                case EntryTypes::ANIMATION:
                    return AnimationEntry::constructFromNonVolatile($clientId, $nonVolatileEntry);
                case EntryTypes::BLOCK_PLACE:
                    return BlockPlaceEntry::constructFromNonVolatile($clientId, $nonVolatileEntry);
                case EntryTypes::BLOCK_BREAK:
                    return BlockBreakEntry::constructFromNonVolatile($clientId, $nonVolatileEntry);
                case EntryTypes::INVENTORY_EDIT:
                    return InventoryEditEntry::constructFromNonVolatile($clientId, $nonVolatileEntry);
                case EntryTypes::CHEST_INTERACTION:
                    return ChestInteractionEntry::constructFromNonVolatile($clientId, $nonVolatileEntry);
                case EntryTypes::SPAWN_STATE:
                    return SpawnStateEntry::constructFromNonVolatile($clientId, $nonVolatileEntry);
                case EntryTypes::EFFECT:
                    return EffectEntry::constructFromNonVolatile($clientId, $nonVolatileEntry);
            }
        }
        return null;
    }

    /**
     * Construct an entry from non volatile.
     *
     * @param string $clientId
     * @param array $nonVolatileEntry
     * @return DataEntry|null
     */
    abstract public static function constructFromNonVolatile(string $clientId, array $nonVolatileEntry): ?DataEntry;

    /** @var int */
    protected $entryType = EntryTypes::DEFAULT;
    /** @var string */
    protected string $clientId;

    /**
     * DataEntry constructor.
     * @param string $clientId
     */
    public function __construct(string $clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * Get the entry type.
     *
     * @return int
     */
    public function getEntryType(): int
    {
        return $this->entryType;
    }

    /**
     * Get the id of the client for which this data entry was made.
     *
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Convert the data entry into a safe data-transmittable
     * format.
     *
     * @return array
     */
    public function convertToNonVolatile(): array
    {
        return [
            self::ENTRY_TYPE_TAG => $this->entryType,
            self::CLIENT_ID_TAG => $this->clientId
        ];
    }

}