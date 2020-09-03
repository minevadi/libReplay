<?php

declare(strict_types=1);

namespace libReplay\data\entry\block;

use libReplay\data\entry\DataEntry;
use libReplay\data\entry\EntryTypes;
use libReplay\exception\DataEntryException;
use pocketmine\math\Vector3;

/**
 * Class ChestInteractionEntry
 * @package libReplay\data\entry\block
 *
 * @internal
 */
class ChestInteractionEntry extends BlockEntry
{

    private const TAG_ACTION_TYPE = 3;

    public const TYPE_CHEST_OPEN = 2;
    public const TYPE_CHEST_CLOSE = 3;

    /**
     * ChestInteractionEntry constructor.
     * @param string $clientId
     * @param int $actionType
     * @param Vector3 $position
     */
    public function __construct(string $clientId, int $actionType, Vector3 $position)
    {
        if ($actionType !== self::TYPE_CHEST_OPEN && $actionType !== self::TYPE_CHEST_CLOSE) {
            $dataDump = [
                $clientId,
                $actionType,
                $position
            ];
            throw new DataEntryException($dataDump, 'The action type is incorrect for this entry.');
        }
        $this->entryType = EntryTypes::CHEST_INTERACTION;
        parent::__construct($clientId, $actionType, $position);
    }

    /**
     * @inheritDoc
     */
    public static function constructFromNonVolatile(string $clientId, array $nonVolatileEntry): ?DataEntry
    {
        $position = self::readPositionFromNonVolatile($nonVolatileEntry);
        if ($position === null) {
            return null;
        }
        if (isset($nonVolatileEntry[self::TAG_ACTION_TYPE])) {
            return new self($clientId, $nonVolatileEntry[self::TAG_ACTION_TYPE], $position);
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function convertToNonVolatile(): array
    {
        $nonVolatileEntry = parent::convertToNonVolatile();
        $nonVolatileEntry[self::TAG_ACTION_TYPE] = $this->getActionType();
        return $nonVolatileEntry;
    }

}