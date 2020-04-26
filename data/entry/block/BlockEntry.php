<?php

declare(strict_types=1);

namespace libReplay\data\entry\block;

use libReplay\data\entry\DataEntry;
use pocketmine\math\Vector3;

/**
 * Class BlockEntry
 * @package libReplay\data\entry
 */
abstract class BlockEntry extends DataEntry
{

    private const TAG_POSITION = 2;
    private const TAG_X = 0;
    private const TAG_Y = 1;
    private const TAG_Z = 2;

    protected const TYPE_BREAK = 0;
    protected const TYPE_PLACE = 1;

    /**
     * Read the position type from non-volatile.
     *
     * @param array $nonVolatileEntry
     * @return Vector3|null
     *
     * @internal
     */
    protected static function readPositionFromNonVolatile(array $nonVolatileEntry): ?Vector3
    {
        if (array_key_exists(self::TAG_POSITION, $nonVolatileEntry)) {
            $position = $nonVolatileEntry[self::TAG_POSITION];
            $isValid = array_key_exists(self::TAG_X, $position) &&
                array_key_exists(self::TAG_Y, $position) &&
                array_key_exists(self::TAG_Z, $position);
            if ($isValid) {
                return new Vector3($position[self::TAG_X], $position[self::TAG_Y], $position[self::TAG_Z]);
            }
        }
        return null;
    }

    /** @var int */
    private $actionType;
    /** @var Vector3 */
    private $position;

    /**
     * BlockEntry constructor.
     * @param string $clientId
     * @param int $actionType
     * @param Vector3 $position
     */
    public function __construct(string $clientId, int $actionType, Vector3 $position)
    {
        $this->actionType = $actionType;
        $this->position = $position;
        parent::__construct($clientId);
    }

    /**
     * Get the type of action for this block.
     *
     * @return int
     */
    public function getActionType(): int
    {
        return $this->actionType;
    }

    /**
     * Get the type
     *
     * @return Vector3
     */
    public function getPosition(): Vector3
    {
        return $this->position;
    }

    /**
     * @inheritDoc
     *
     * @internal
     */
    public function convertToNonVolatile(): array
    {
        $nonVolatileEntry = parent::convertToNonVolatile();
        $nonVolatileEntry[self::TAG_POSITION] = [
            self::TAG_X => $this->position->x,
            self::TAG_Y => $this->position->y,
            self::TAG_Z => $this->position->z
        ];
        return $nonVolatileEntry;
    }

}