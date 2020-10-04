<?php

declare(strict_types=1);

namespace libReplay\data\entry;

use libReplay\exception\DataEntryException;
use pocketmine\event\entity\EntityDamageEvent;

/**
 * Class TakeDamageEntry
 * @package libReplay\data\entry
 *
 * @internal
 */
class TakeDamageEntry extends DataEntry
{

    private const TAG_DAMAGE_TAKEN = 2;
    private const TAG_CAUSE = 3;

    /**
     * @inheritDoc
     */
    public static function constructFromNonVolatile(string $clientId, array $nonVolatileEntry): ?DataEntry
    {
        $isValid = array_key_exists(self::TAG_DAMAGE_TAKEN, $nonVolatileEntry) &&
            array_key_exists(self::TAG_CAUSE, $nonVolatileEntry);
        if ($isValid) {
            return new self(
                $clientId,
                $nonVolatileEntry[self::TAG_DAMAGE_TAKEN],
                $nonVolatileEntry[self::TAG_CAUSE]
            );
        }
        return null;
    }

    /** @var float */
    private float $damageTaken;
    /** @var int */
    private int $cause;

    /**
     * TakeDamageEntry constructor.
     * @param string $clientId
     * @param float $damageTaken
     * @param int $cause
     */
    public function __construct(string $clientId, float $damageTaken, int $cause = EntityDamageEvent::CAUSE_CUSTOM)
    {
        $this->damageTaken = $damageTaken;
        $isValidCause = $cause >= EntityDamageEvent::CAUSE_CONTACT && $cause <= EntityDamageEvent::CAUSE_STARVATION;
        if (!$isValidCause) {
            $dataDump = [
                $clientId,
                $damageTaken,
                $cause
            ];
            throw new DataEntryException($dataDump, 'The cause argument is incorrect. You must use a verified cause.');
        }
        $this->cause = $cause;
        $this->entryType = EntryTypes::TAKE_DAMAGE;
        parent::__construct($clientId);
    }

    /**
     * Get the damage taken.
     *
     * @return float
     */
    public function getDamageTaken(): float
    {
        return $this->damageTaken;
    }

    /**
     * Get the damage cause.
     * This returns a constant from EntityDamageEvent.
     *
     * @return int
     */
    public function getCause(): int
    {
        return $this->cause;
    }

    /**
     * @inheritDoc
     */
    public function convertToNonVolatile(): array
    {
        $nonVolatileEntry = parent::convertToNonVolatile();
        $nonVolatileEntry[self::TAG_DAMAGE_TAKEN] = $this->damageTaken;
        $nonVolatileEntry[self::TAG_CAUSE] = $this->cause;
        return $nonVolatileEntry;
    }

}