<?php

declare(strict_types=1);

namespace libReplay\data\entry\block;

use libReplay\data\entry\DataEntry;
use libReplay\data\entry\EntryTypes;
use pocketmine\math\Vector3;

/**
 * Class BlockBreakEntry
 * @package libReplay\data\entry
 *
 * Converting to non-volatile is handled
 * by the parent abstract class:
 * {@link BlockEntry}.
 */
class BlockBreakEntry extends BlockEntry
{

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
        return new self($clientId, $position);
    }

    /**
     * BlockBreakEntry constructor.
     * @param string $clientId
     * @param Vector3 $position
     */
    public function __construct(string $clientId, Vector3 $position)
    {
        $this->entryType = EntryTypes::BLOCK_BREAK;
        parent::__construct($clientId, self::TYPE_BREAK, $position);
    }

}