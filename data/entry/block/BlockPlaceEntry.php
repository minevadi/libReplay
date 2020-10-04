<?php

declare(strict_types=1);

namespace libReplay\data\entry\block;

use libReplay\data\entry\DataEntry;
use libReplay\data\entry\EntryTypes;
use pocketmine\math\Vector3;

/**
 * Class BlockPlaceEntry
 * @package libReplay\data\entry
 */
class BlockPlaceEntry extends BlockEntry
{

    private const TAG_BLOCK_ID = 3;
    private const TAG_BLOCK_META = 4;

    /**
     * @inheritDoc
     *
     * @internal
     */
    public static function constructFromNonVolatile(string $clientId, array $nonVolatileEntry): ?DataEntry
    {
        $position = self::readPositionFromNonVolatile($nonVolatileEntry);
        if ($position === null) {
            return null;
        }
        $isValid = array_key_exists(self::TAG_BLOCK_ID, $nonVolatileEntry) &&
            array_key_exists(self::TAG_BLOCK_META, $nonVolatileEntry);
        if ($isValid) {
            return new self(
                $clientId,
                $position,
                $nonVolatileEntry[self::TAG_BLOCK_ID],
                $nonVolatileEntry[self::TAG_BLOCK_META]
            );
        }
        return null;
    }

    /** @var int */
    private int $blockId;
    /** @var int */
    private int $blockMeta;

    /**
     * BlockPlaceEntry constructor.
     * @param string $clientId
     * @param Vector3 $position
     * @param int $blockId
     * @param int $blockMeta
     */
    public function __construct(string $clientId, Vector3 $position, int $blockId, int $blockMeta)
    {
        $this->blockId = $blockId;
        $this->blockMeta = $blockMeta;
        $this->entryType = EntryTypes::BLOCK_PLACE;
        parent::__construct($clientId, self::TYPE_PLACE, $position);
    }

    /**
     * Get the block id to place.
     *
     * @return int
     */
    public function getBlockId(): int
    {
        return $this->blockId;
    }

    /**
     * Get the block meta to place.
     *
     * @return int
     */
    public function getBlockMeta(): int
    {
        return $this->blockMeta;
    }

    /**
     * @inheritDoc
     *
     * @internal
     */
    public function convertToNonVolatile(): array
    {
        $nonVolatileEntry = parent::convertToNonVolatile();
        $nonVolatileEntry[self::TAG_BLOCK_ID] = $this->blockId;
        $nonVolatileEntry[self::TAG_BLOCK_META] = $this->blockMeta;
        return $nonVolatileEntry;
    }

}