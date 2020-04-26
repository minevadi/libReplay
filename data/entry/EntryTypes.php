<?php

declare(strict_types=1);

namespace libReplay\data\entry;

/**
 * Interface EntryTypes
 * @package libReplay\data\entry
 *
 * @internal
 */
interface EntryTypes
{

    public const DEFAULT = 0;
    public const TRANSFORM = 1;
    public const TAKE_DAMAGE = 2;
    public const REGAIN_HEALTH = 3;
    public const ANIMATION = 4;
    public const BLOCK_PLACE = 5;
    public const BLOCK_BREAK = 6;
    public const INVENTORY_EDIT = 7;
    public const CHEST_INTERACTION = 8;
    public const SPAWN_STATE = 9;
    public const EFFECT = 10;

}