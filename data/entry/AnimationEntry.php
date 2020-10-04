<?php

declare(strict_types=1);

namespace libReplay\data\entry;

use libReplay\exception\DataEntryException;

/**
 * Class AnimationEntry
 * @package libReplay\data\entry
 *
 * @internal
 */
class AnimationEntry extends DataEntry
{

    private const TAG_ANIMATION = 2;
    private const TAG_DURATION = 3;

    public const ANIMATION_SWING_ARM = 0;
    public const ANIMATION_ITEM_CONSUME = 1;

    /**
     * @inheritDoc
     */
    public static function constructFromNonVolatile(string $clientId, array $nonVolatileEntry): ?DataEntry
    {
        $isValid = array_key_exists(self::TAG_ANIMATION, $nonVolatileEntry) &&
            array_key_exists(self::TAG_DURATION, $nonVolatileEntry);
        if ($isValid) {
            return new self($clientId, $nonVolatileEntry[self::TAG_ANIMATION], $nonVolatileEntry[self::TAG_DURATION]);
        }
        return null;
    }

    /** @var int */
    private int $animation;
    /** @var int Duration in ms */
    private int $duration;

    /**
     * AnimationEntry constructor.
     * @param string $clientId
     * @param int $animation
     * @param int $duration
     */
    public function __construct(string $clientId, int $animation = self::ANIMATION_SWING_ARM, int $duration = 0)
    {
        $isValidAnimation = $animation === self::ANIMATION_SWING_ARM || $animation === self::ANIMATION_ITEM_CONSUME;
        if (!$isValidAnimation) {
            $dataDump = [
                $clientId,
                $animation
            ];
            throw new DataEntryException($dataDump, 'The animation argument is incorrect. You must use a verified cause.');
        }
        $this->animation = $animation;
        $this->duration = $duration;
        $this->entryType = EntryTypes::ANIMATION;
        parent::__construct($clientId);
    }

    /**
     * Get the entry's animation.
     *
     * @return int
     */
    public function getAnimation(): int
    {
        return $this->animation;
    }

    /**
     * Get the duration of the animation.
     * This returns in ms.
     *
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @inheritDoc
     */
    public function convertToNonVolatile(): array
    {
        $nonVolatileEntry = parent::convertToNonVolatile();
        $nonVolatileEntry[self::TAG_ANIMATION] = $this->animation;
        $nonVolatileEntry[self::TAG_DURATION] = $this->duration;
        return $nonVolatileEntry;
    }

}