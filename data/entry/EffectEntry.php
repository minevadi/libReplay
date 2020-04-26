<?php

declare(strict_types=1);

namespace libReplay\data\entry;

/**
 * Class EffectEntry
 * @package libReplay\data\entry
 */
class EffectEntry extends DataEntry
{

    private const TAG_EFFECT_ID = 2;
    private const TAG_LEVEL = 3;
    private const TAG_DURATION = 4;
    private const TAG_ADD = 5;

    /**
     * @inheritDoc
     *
     * @internal
     */
    public static function constructFromNonVolatile(string $clientId, array $nonVolatileEntry): ?DataEntry
    {
        $isValid = array_key_exists(self::TAG_EFFECT_ID, $nonVolatileEntry) &&
            array_key_exists(self::TAG_LEVEL, $nonVolatileEntry) &&
            array_key_exists(self::TAG_DURATION, $nonVolatileEntry) &&
            array_key_exists(self::TAG_ADD, $nonVolatileEntry);
        if ($isValid) {
            return new self(
                $clientId,
                $nonVolatileEntry[self::TAG_EFFECT_ID],
                $nonVolatileEntry[self::TAG_LEVEL],
                $nonVolatileEntry[self::TAG_DURATION],
                $nonVolatileEntry[self::TAG_ADD]
            );
        }
        return null;
    }

    /** @var int */
    private $effectId;
    /** @var int */
    private $level;
    /** @var int */
    private $duration;
    /** @var bool */
    private $add;

    /**
     * EffectEntry constructor.
     * @param string $clientId
     * @param int $effectId
     * @param int $level
     * @param int $duration
     * @param bool $add
     */
    public function __construct(string $clientId, int $effectId, int $level, int $duration, bool $add = true)
    {
        $this->effectId = $effectId;
        $this->level = $level;
        $this->duration = $duration;
        $this->add = $add;
        $this->entryType = EntryTypes::EFFECT;
        parent::__construct($clientId);
    }

    /**
     * Get the effect id.
     *
     * @return int
     */
    public function getEffectId(): int
    {
        return $this->effectId;
    }

    /**
     * Get the level of the effect.
     *
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Get the duration to add the effect for.
     *
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * Find out whether you should add
     * the effect or remove it.
     *
     * @return bool
     */
    public function shouldAdd(): bool
    {
        return $this->add;
    }

    /**
     * @inheritDoc
     *
     * @internal
     */
    public function convertToNonVolatile(): array
    {
        $nonVolatileEntry = parent::convertToNonVolatile();
        $nonVolatileEntry[self::TAG_EFFECT_ID] = $this->effectId;
        $nonVolatileEntry[self::TAG_LEVEL] = $this->level;
        $nonVolatileEntry[self::TAG_DURATION] = $this->duration;
        $nonVolatileEntry[self::TAG_ADD] = $this->add;
        return $nonVolatileEntry;
    }

}