<?php

declare(strict_types=1);

namespace libReplay\data\entry;

use libReplay\exception\DataEntryException;
use pocketmine\event\entity\EntityRegainHealthEvent;

/**
 * Class RegainHealthEntry
 * @package libReplay\data\entry
 *
 * @internal
 */
class RegainHealthEntry extends DataEntry
{

    private const TAG_HEALTH_REGAINED = 2;
    private const TAG_REGAIN_REASON = 3;
    /** @var float */
    private float $healthRegained;
    /** @var int */
    private int $regainReason;

    /**
     * RegainHealthEntry constructor.
     * @param string $clientId
     * @param float $healthRegained
     * @param int $regainReason
     */
    public function __construct(string $clientId, float $healthRegained, int $regainReason = EntityRegainHealthEvent::CAUSE_CUSTOM)
    {
        $this->healthRegained = $healthRegained;
        $isValidReason = $regainReason >= EntityRegainHealthEvent::CAUSE_REGEN && $regainReason <= EntityRegainHealthEvent::CAUSE_SATURATION;
        if (!$isValidReason) {
            $dataDump = [
                $clientId,
                $healthRegained,
                $regainReason
            ];
            throw new DataEntryException($dataDump, 'The cause argument is incorrect. You must use a verified reason.');
        }
        $this->regainReason = $regainReason;
        $this->entryType = EntryTypes::REGAIN_HEALTH;
        parent::__construct($clientId);
    }

    /**
     * @inheritDoc
     */
    public static function constructFromNonVolatile(string $clientId, array $nonVolatileEntry): ?DataEntry
    {
        $isValid = isset($nonVolatileEntry[self::TAG_HEALTH_REGAINED], $nonVolatileEntry[self::TAG_REGAIN_REASON]);
        if ($isValid) {
            return new self(
                $clientId,
                $nonVolatileEntry[self::TAG_HEALTH_REGAINED],
                $nonVolatileEntry[self::TAG_REGAIN_REASON]
            );
        }
        return null;
    }

    /**
     * Get the health regained.
     *
     * @return float
     */
    public function getHealthRegained(): float
    {
        return $this->healthRegained;
    }

    /**
     * Get the reason for regaining health.
     *
     * @return int
     */
    public function getRegainReason(): int
    {
        return $this->regainReason;
    }

    /**
     * @inheritDoc
     */
    public function convertToNonVolatile(): array
    {
        $nonVolatileEntry = parent::convertToNonVolatile();
        $nonVolatileEntry[self::TAG_HEALTH_REGAINED] = $this->healthRegained;
        $nonVolatileEntry[self::TAG_REGAIN_REASON] = $this->regainReason;
        return $nonVolatileEntry;
    }

}