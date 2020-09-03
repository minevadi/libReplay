<?php

declare(strict_types=1);

namespace libReplay\data\entry;

use libPhysX\internal\Rotation;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class TransformEntry
 * @package libReplay\data\entry
 *
 * @internal
 */
class TransformEntry extends DataEntry
{

    private const TAG_POSITION = 2;
    private const TAG_ROTATION = 3;
    private const TAG_X = 0;
    private const TAG_Y = 1;
    private const TAG_Z = 2;
    private const TAG_YAW = 0;
    private const TAG_PITCH = 1;
    private const TAG_STATE = 4;
    private const TAG_SPEED = 5;
    private const TAG_TELEPORT = 6;

    public const STATE_DEFAULT = 0;
    public const STATE_SPRINT = 1;
    public const STATE_SNEAK = 2;

    private const SPEED_DEFAULT = 0.25;
    /** @var Vector3 */
    private Vector3 $position;
    /** @var Rotation */
    private Rotation $rotation;
    /** @var int */
    private int $state;
    /** @var float */
    private float $speed;
    /** @var bool */
    private bool $teleport;

    /**
     * TransformEntry constructor.
     * @param string $clientId
     * @param Vector3 $position
     * @param Rotation $rotation
     * @param int $state
     * @param float $speed
     * @param bool $teleport
     */
    public function __construct(string $clientId, Vector3 $position, Rotation $rotation, int $state,
                                float $speed = self::SPEED_DEFAULT, bool $teleport = false)
    {
        $this->position = $position;
        $this->rotation = $rotation;
        $this->state = $state;
        $this->speed = $speed;
        $this->teleport = $teleport;
        $this->entryType = EntryTypes::TRANSFORM;
        parent::__construct($clientId);
    }

    /**
     * @inheritDoc
     */
    public static function constructFromNonVolatile(string $clientId, array $nonVolatileEntry): ?DataEntry
    {
        if (isset(
            $nonVolatileEntry[self::TAG_POSITION],
            $nonVolatileEntry[self::TAG_ROTATION],
            $nonVolatileEntry[self::TAG_STATE],
            $nonVolatileEntry[self::TAG_SPEED],
            $nonVolatileEntry[self::TAG_TELEPORT]
        )) {
            $position = new Vector3();
            $positionProperty = $nonVolatileEntry[self::TAG_POSITION];
            if (isset(
                $positionProperty[self::TAG_X],
                $positionProperty[self::TAG_Y],
                $positionProperty[self::TAG_Z]
            )) {
                $position->x = $positionProperty[self::TAG_X];
                $position->y = $positionProperty[self::TAG_Y];
                $position->z = $positionProperty[self::TAG_Z];
            }
            $rotation = new Rotation();
            $rotationProperty = $nonVolatileEntry[self::TAG_ROTATION];
            $rotationIsValid = isset($rotationProperty[self::TAG_YAW], $rotationProperty[self::TAG_PITCH]);
            if ($rotationIsValid) {
                $rotation->yaw = $rotationProperty[self::TAG_YAW];
                $rotation->pitch = $rotationProperty[self::TAG_PITCH];
            }
            return new self(
                $clientId,
                $position,
                $rotation,
                $nonVolatileEntry[self::TAG_STATE],
                $nonVolatileEntry[self::TAG_SPEED],
                $nonVolatileEntry[self::TAG_TELEPORT]
            );
        }
        return null;
    }

    /**
     * Get a transform state based on the movement.
     *
     * @param Player $player
     * @return int
     */
    public static function getTransformStateFromPlayer(Player $player): int
    {
        $isSprinting = $player->isSprinting();
        if ($isSprinting) {
            return self::STATE_SPRINT;
        }
        $isSneaking = $player->isSneaking();
        if ($isSneaking) {
            return self::STATE_SNEAK;
        }
        return self::STATE_DEFAULT;
    }

    /**
     * Get the position of the transform entry.
     *
     * @return Vector3
     */
    public function getPosition(): Vector3
    {
        return $this->position;
    }

    /**
     * Get the rotation of the transform entry.
     *
     * @return Rotation
     */
    public function getRotation(): Rotation
    {
        return $this->rotation;
    }

    /**
     * Get the state of movement.
     *
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Get the speed of transformation.
     *
     * @return float
     */
    public function getSpeed(): float
    {
        return $this->speed;
    }

    /**
     * Check whether the transform should be handled
     * by teleportation.
     *
     * @return bool
     */
    public function shouldTeleport(): bool
    {
        return $this->teleport;
    }

    /**
     * @inheritDoc
     */
    public function convertToNonVolatile(): array
    {
        $nonVolatileEntry = parent::convertToNonVolatile();
        $nonVolatileEntry[self::TAG_POSITION] = [
            self::TAG_X => $this->position->x,
            self::TAG_Y => $this->position->y,
            self::TAG_Z => $this->position->z
        ];
        $nonVolatileEntry[self::TAG_ROTATION] = [
            self::TAG_YAW => $this->rotation->yaw,
            self::TAG_PITCH => $this->rotation->pitch
        ];
        $nonVolatileEntry[self::TAG_STATE] = $this->state;
        $nonVolatileEntry[self::TAG_SPEED] = $this->speed;
        $nonVolatileEntry[self::TAG_TELEPORT] = $this->teleport;
        return $nonVolatileEntry;
    }

}