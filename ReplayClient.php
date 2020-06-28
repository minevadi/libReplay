<?php

declare(strict_types=1);

namespace libReplay;

use libPhysX\internal\Rotation;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

/**
 * Class ReplayClient
 * @package libReplay
 */
class ReplayClient
{

    private const TAG_CLIENT_ID = 0;
    private const TAG_POSITION = 1;
    private const TAG_ROTATION = 2;
    private const TAG_SKIN = 3;
    private const TAG_CUSTOM_NAME = 4;

    private const TAG_X = 0;
    private const TAG_Y = 1;
    private const TAG_Z = 2;
    private const TAG_YAW = 0;
    private const TAG_PITCH = 1;
    private const TAG_SKIN_ID = 0;
    private const TAG_SKIN_REGULAR_DATA = 1;
    private const TAG_SKIN_CAPE_DATA = 2;
    private const TAG_SKIN_GEOMETRY_NAME = 3;
    private const TAG_SKIN_GEOMETRY_DATA = 4;

    /**
     * Construct a client from non volatile.
     *
     * @param array $nonVolatileClient
     * @return ReplayClient|null
     *
     * @internal
     */
    public static function constructFromNonVolatile(array $nonVolatileClient): ?ReplayClient
    {
        $isValid = array_key_exists(self::TAG_CLIENT_ID, $nonVolatileClient) &&
            array_key_exists(self::TAG_POSITION, $nonVolatileClient) &&
            array_key_exists(self::TAG_ROTATION, $nonVolatileClient) &&
            array_key_exists(self::TAG_SKIN, $nonVolatileClient) &&
            array_key_exists(self::TAG_CUSTOM_NAME, $nonVolatileClient);
        if ($isValid) {
            $position = null;
            $positionProperty = $nonVolatileClient[self::TAG_POSITION];
            $positionIsValid = array_key_exists(self::TAG_X, $positionProperty) &&
                array_key_exists(self::TAG_Y, $positionProperty) &&
                array_key_exists(self::TAG_Z, $positionProperty);
            if ($positionIsValid) {
                $position = new Vector3(
                    $positionProperty[self::TAG_X],
                    $positionProperty[self::TAG_Y],
                    $positionProperty[self::TAG_Z]
                );
            }
            $rotation = null;
            $rotationProperty = $nonVolatileClient[self::TAG_ROTATION];
            $rotationIsValid = array_key_exists(self::TAG_YAW, $rotationProperty) &&
                array_key_exists(self::TAG_PITCH, $rotationProperty);
            if ($rotationIsValid) {
                $rotation = new Rotation($rotationProperty[self::TAG_YAW], $rotationProperty[self::TAG_PITCH]);
            }
            $skin = null;
            $skinProperty = $nonVolatileClient[self::TAG_SKIN];
            $skinIsValid = array_key_exists(self::TAG_SKIN_ID, $skinProperty) &&
                array_key_exists(self::TAG_SKIN_REGULAR_DATA, $skinProperty) &&
                array_key_exists(self::TAG_SKIN_CAPE_DATA, $skinProperty) &&
                array_key_exists(self::TAG_SKIN_GEOMETRY_NAME, $skinProperty) &&
                array_key_exists(self::TAG_SKIN_GEOMETRY_DATA, $skinProperty);
            if ($skinIsValid) {
                $skin = new Skin(
                    utf8_decode($skinProperty[self::TAG_SKIN_ID]),
                    utf8_decode($skinProperty[self::TAG_SKIN_REGULAR_DATA]),
                    utf8_decode($skinProperty[self::TAG_SKIN_CAPE_DATA]),
                    utf8_decode($skinProperty[self::TAG_SKIN_GEOMETRY_NAME]),
                    utf8_decode($skinProperty[self::TAG_SKIN_GEOMETRY_DATA])
                );
            }
            if ($position instanceof Vector3 && $rotation instanceof Rotation && $skin instanceof Skin) {
                return new self(
                    $nonVolatileClient[self::TAG_CLIENT_ID],
                    $skin,
                    $position,
                    $rotation,
                    $nonVolatileClient[self::TAG_CUSTOM_NAME]
                );
            }
        }
        return null;
    }

    /**
     * Read a replay client referenced as a player.
     *
     * @param Player $player
     * @return ReplayClient
     *
     * @internal
     */
    public static function readFromPlayer(Player $player): ReplayClient
    {
        $clientId = $player->getName();
        $customName = $player->getNameTag();
        $player->saveNBT();
        $skin = $player->getSkin();
        $location = $player->getLocation();
        $position = $location->asVector3();
        $rotation = new Rotation($location->yaw, $location->pitch);
        return new self($clientId, $skin, $position, $rotation, $customName);
    }

    /** @var string This is the player's name */
    private $clientId;
    /** @var Skin */
    private $skin;
    /** @var Vector3 */
    private $position;
    /** @var Rotation */
    private $rotation;
    /** @var string A custom name for the actor at replay time */
    private $customName;

    /** @var bool */
    private $recorded = false;

    /**
     * ReplayClient constructor.
     * @param string $clientId
     * @param Skin $skin
     * @param Vector3 $position
     * @param Rotation $rotation
     * @param string $customName
     *
     * @internal
     */
    public function __construct(string $clientId, Skin $skin, Vector3 $position, Rotation $rotation,
                                string $customName = '')
    {
        $this->clientId = $clientId;
        $this->skin = $skin;
        $this->position = $position;
        $this->rotation = $rotation;
        $this->customName = $customName;
    }

    /**
     * Get the client's id.
     * Also called the name.
     *
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Get the skin.
     *
     * @return Skin
     */
    public function getSkin(): Skin
    {
        return $this->skin;
    }

    /**
     * Check if the client is being recorded.
     *
     * @return bool
     */
    public function isRecorded(): bool
    {
        return $this->recorded;
    }

    /**
     * Get original position of the client.
     *
     * @return Vector3
     */
    public function getPosition(): Vector3
    {
        return $this->position;
    }

    /**
     * Get original rotation of the client.
     *
     * @return Rotation
     */
    public function getRotation(): Rotation
    {
        return $this->rotation;
    }

    /**
     * Get the custom name of the client.
     *
     * @return string
     */
    public function getCustomName(): string
    {
        return $this->customName;
    }

    /**
     * Start or stop recording the client.
     *
     * @return void
     */
    public function toggleRecord(): void
    {
        $this->recorded = !$this->recorded;
    }

    /**
     * Convert the client into a safe data-transmittable
     * format.
     *
     * @return array
     *
     * @internal
     */
    public function convertToNonVolatile(): array
    {
        return [
            self::TAG_CLIENT_ID => $this->clientId,
            self::TAG_POSITION => [
                self::TAG_X => $this->position->x,
                self::TAG_Y => $this->position->y,
                self::TAG_Z => $this->position->z
            ],
            self::TAG_ROTATION => [
                self::TAG_YAW => $this->rotation->yaw,
                self::TAG_PITCH => $this->rotation->pitch
            ],
            self::TAG_SKIN => [
                self::TAG_SKIN_ID => utf8_encode($this->skin->getSkinId()),
                self::TAG_SKIN_REGULAR_DATA => utf8_encode($this->skin->getSkinData()),
                self::TAG_SKIN_CAPE_DATA => utf8_encode($this->skin->getCapeData()),
                self::TAG_SKIN_GEOMETRY_NAME => utf8_encode($this->skin->getGeometryName()),
                self::TAG_SKIN_GEOMETRY_DATA => utf8_encode($this->skin->getGeometryData())
            ],
            self::TAG_CUSTOM_NAME => $this->customName
        ];
    }

}