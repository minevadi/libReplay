<?php

declare(strict_types=1);

namespace libReplay\data\entry;

use libReplay\exception\DataEntryException;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;

/**
 * Class InventoryEditEntry
 * @package libReplay\data\entry
 */
class InventoryEditEntry extends DataEntry
{

    private const TAG_INVENTORY_ID = 2;
    private const TAG_SLOT = 3;
    private const TAG_ITEM = 4;

    public const INVENTORY_INVALID = -1;
    public const INVENTORY_BASE = 0;
    public const INVENTORY_ARMOR = 1;

    /**
     * @inheritDoc
     */
    public static function constructFromNonVolatile(string $clientId, array $nonVolatileEntry): ?DataEntry
    {
        $isValid = array_key_exists(self::TAG_INVENTORY_ID, $nonVolatileEntry) &&
            array_key_exists(self::TAG_SLOT, $nonVolatileEntry) &&
            array_key_exists(self::TAG_ITEM, $nonVolatileEntry);
        if ($isValid) {
            return new self(
                $clientId,
                $nonVolatileEntry[self::TAG_INVENTORY_ID],
                $nonVolatileEntry[self::TAG_SLOT],
                Item::jsonDeserialize($nonVolatileEntry[self::TAG_ITEM])
            );
        }
        return null;
    }

    /**
     * Get the right inventory id constant
     * for the accepted inventories.
     *
     * Returns -1 if the inventory isn't accepted.
     *
     * @param Inventory $inventory
     * @return int
     */
    public static function getInventoryIdFromInventory(Inventory $inventory): int
    {
        if ($inventory instanceof PlayerInventory) {
            return self::INVENTORY_BASE;
        }
        if ($inventory instanceof ArmorInventory) {
            return self::INVENTORY_ARMOR;
        }
        return self::INVENTORY_INVALID;
    }

    /** @var int */
    private $inventoryId;
    /** @var int */
    private $slot;
    /** @var Item */
    private $item;

    /**
     * InventoryEditEntry constructor.
     * @param string $clientId
     * @param int $inventoryId
     * @param int $slot
     * @param Item $item
     */
    public function __construct(string $clientId, int $inventoryId, int $slot, Item $item)
    {
        if ($inventoryId !== self::INVENTORY_BASE && $inventoryId !== self::INVENTORY_ARMOR) {
            $dataDump = [
                $clientId,
                $inventoryId,
                $slot,
                $item
            ];
            throw new DataEntryException($dataDump, 'Incorrect inventory id provided.');
        }
        if ($inventoryId === self::INVENTORY_BASE) {
            $slot = 0;
        }
        if ($slot > 4 && $inventoryId === self::INVENTORY_ARMOR) {
            $dataDump = [
                $clientId,
                $inventoryId,
                $slot,
                $item
            ];
            throw new DataEntryException($dataDump, 'Slot does not exist.');
        }
        $this->inventoryId = $inventoryId;
        $this->slot = $slot;
        $this->item = $item;
        $this->entryType = EntryTypes::INVENTORY_EDIT;
        parent::__construct($clientId);
    }

    /**
     * Returns an inventory type constant.
     * The constant determines the inventory
     * that was edited.
     *
     * @return int
     */
    public function getInventoryId(): int
    {
        return $this->inventoryId;
    }

    /**
     * Get the slot modified.
     *
     * @return int
     */
    public function getSlot(): int
    {
        return $this->slot;
    }

    /**
     * Get the item replaced in the slot.
     *
     * @return Item
     */
    public function getItem(): Item
    {
        return $this->item;
    }

    /**
     * @inheritDoc
     */
    public function convertToNonVolatile(): array
    {
        $nonVolatileEntry = parent::convertToNonVolatile();
        $nonVolatileEntry[self::TAG_INVENTORY_ID] = $this->inventoryId;
        $nonVolatileEntry[self::TAG_SLOT] = $this->slot;
        $nonVolatileEntry[self::TAG_ITEM] = $this->item->jsonSerialize();
        return $nonVolatileEntry;
    }

}