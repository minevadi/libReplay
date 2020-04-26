<?php

declare(strict_types=1);

namespace libReplay\actor;

use libReplay\data\entry\AnimationEntry;
use libReplay\data\entry\block\BlockBreakEntry;
use libReplay\data\entry\block\BlockPlaceEntry;
use libReplay\data\entry\block\ChestInteractionEntry;
use libReplay\data\entry\DataEntry;
use libReplay\data\entry\EffectEntry;
use libReplay\data\entry\EntryTypes;
use libReplay\data\entry\InventoryEditEntry;
use libReplay\data\entry\RegainHealthEntry;
use libReplay\data\entry\SpawnStateEntry;
use libReplay\data\entry\TakeDamageEntry;
use libReplay\data\entry\TransformEntry;
use libReplay\exception\DataEntryException;
use libReplay\ReplayViewer;
use NetherGames\NGEssentials\utils\packets\PacketManager;
use NetherGames\NGEssentials\utils\packets\PublicQueue;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\utils\Color;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function round;

/**
 * Class HumanActor
 * @package libReplay\actor
 * @internal
 */
class HumanActor extends Human
{

    private const BASE_INVENTORY = 0;
    private const ARMOR_INVENTORY = 1;

    /** @var float */
    protected $gravity = 0.0;
    /** @var float */
    protected $drag = 0.0;
    /** @var ReplayViewer */
    private $replayViewer;
    /** @var DataEntry[][] */
    private $script;
    /** @var int */
    private $step;
    /** @var Level This is a hack to go around PM's bad level|null checks. */
    private $replayLevel;
    /** @var Item[][] */
    private $emulatedInventoryList = [];
    /** @var PublicQueue */
    private $packetQueue;

    public function __construct(Level $level, CompoundTag $nbt, Skin $skin)
    {
        $this->setSkin($skin);

        parent::__construct($level, $nbt);
    }

    /**
     * Configure the actor.
     *
     * @param ReplayViewer $replayViewer
     * @param DataEntry[][] $script
     * @return void
     */
    public function configure(ReplayViewer $replayViewer, array $script): void
    {
        if ($this->level instanceof Level) {
            $this->replayLevel = $this->level;
        } else {
            throw new RuntimeException('The level was found not "Level". This crashed the actor.');
        }

        $this->replayViewer = $replayViewer;
        $this->script = $script;
        $baseKey = array_key_first($script);
        if ($baseKey !== null) {
            $this->step = (int)$baseKey;
        }

        $id = PacketManager::setup(true);
        $this->packetQueue = PacketManager::getPublicQueue($id);

        $this->getInventory()->setHeldItemIndex(0);
        $this->setNameTagVisible(true);
        $this->setNameTagAlwaysVisible(true);
        return;
    }

    /**
     * Runs at the base tick.
     *
     * @param int $tickDiff
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if (!$this->replayLevel instanceof Level) {
            $this->flagForDespawn();
            return parent::entityBaseTick($tickDiff);
        }
        $flaggedForDespawn = $this->isFlaggedForDespawn();
        if ($flaggedForDespawn) {
            return parent::entityBaseTick($tickDiff);
        }
        $playbackSpeed = $this->replayViewer->getPlaybackSpeed();
        $playerList = $this->replayLevel->getPlayers();
        $this->packetQueue->setPlayers($playerList);
        for ($i = 0; $i < $playbackSpeed; ++$i) {
            $this->handle();
            $this->packetQueue->deliverPackets(true);
        }
        return parent::entityBaseTick($tickDiff);
    }

    /**
     * Handle the entries.
     *
     * @return void
     */
    private function handle(): void
    {
        foreach ($this->script[$this->step] as $entry) {
            $type = $entry->getEntryType();
            switch ($type) {
                case EntryTypes::TRANSFORM:
                    $this->handleTransformEntry($entry);
                    break;
                case EntryTypes::TAKE_DAMAGE:
                    $this->handleTakeDamageEntry($entry);
                    break;
                case EntryTypes::REGAIN_HEALTH:
                    $this->handleRegainHealthEntry($entry);
                    break;
                case EntryTypes::ANIMATION:
                    $this->handleAnimationEntry($entry);
                    break;
                case EntryTypes::BLOCK_PLACE:
                    $this->handleBlockPlaceEntry($entry);
                    break;
                case EntryTypes::BLOCK_BREAK:
                    $this->handleBlockBreakEntry($entry);
                    break;
                case EntryTypes::INVENTORY_EDIT:
                    $this->handleInventoryEditEntry($entry);
                    break;
                case EntryTypes::CHEST_INTERACTION:
                    $this->handleChestInteractionEntry($entry);
                    break;
                case EntryTypes::SPAWN_STATE:
                    $this->handleSpawnStateEntry($entry);
                    break;
                case EntryTypes::EFFECT:
                    $this->handleEffectEntry($entry);
                    break;
            }
        }
        $this->tryForwardToNextStep();
        return;
    }

    /**
     * Handle the spawn-state entry.
     *
     * @param DataEntry $entry
     * @return void
     */
    public function handleSpawnStateEntry(DataEntry $entry): void
    {
        if (!$entry instanceof SpawnStateEntry) {
            throw new DataEntryException([$entry], 'An unrecoverable exception occurred. Wrong entry provided.');
        }
        $spawned = $entry->isSpawned();
        if (!$spawned) {
            $keepInventory = $entry->isKeepInventory();
            if ($keepInventory) {
                $this->emulatedInventoryList[self::BASE_INVENTORY] = $this->inventory->getContents();
                $this->emulatedInventoryList[self::ARMOR_INVENTORY] = $this->armorInventory->getContents();
            } else {
                $exist = isset($this->emulatedInventoryList[self::BASE_INVENTORY]);
                if ($exist) {
                    unset($this->emulatedInventoryList[self::BASE_INVENTORY]);
                }
                $exist = isset($this->emulatedInventoryList[self::ARMOR_INVENTORY]);
                if ($exist) {
                    unset($this->emulatedInventoryList[self::BASE_INVENTORY]);
                }
            }
            $this->inventory->setContents([]);
            $this->armorInventory->setContents([]);
            $this->setInvisible(true);
        } else {
            $exist = isset($this->emulatedInventoryList[self::BASE_INVENTORY]);
            if ($exist) {
                $this->inventory->setContents($this->emulatedInventoryList[self::BASE_INVENTORY]);
                unset($this->emulatedInventoryList[self::BASE_INVENTORY]);
            }
            $exist = isset($this->emulatedInventoryList[self::ARMOR_INVENTORY]);
            if ($exist) {
                $this->armorInventory->setContents($this->emulatedInventoryList[self::ARMOR_INVENTORY]);
                unset($this->emulatedInventoryList[self::BASE_INVENTORY]);
            }
            $this->setInvisible(false);
        }
        $key = array_search($entry, $this->script[$this->step], true);
        unset($this->script[$this->step][$key]);
        return;
    }

    /**
     * Handle the transform entry.
     *
     * @param DataEntry $entry
     * @return void
     */
    private function handleTransformEntry(DataEntry $entry): void
    {
        if (!$entry instanceof TransformEntry) {
            throw new DataEntryException([$entry], 'An unrecoverable exception occurred. Wrong entry provided.');
        }
        $state = $entry->getState();
        switch ($state) {
            case TransformEntry::STATE_SPRINT:
                $this->setGenericFlag(self::DATA_FLAG_SPRINTING, true);
                break;
            case TransformEntry::STATE_SNEAK:
                $this->setGenericFlag(self::DATA_FLAG_SNEAKING, true);
                break;
            case TransformEntry::STATE_DEFAULT:
                $this->setGenericFlag(self::DATA_FLAG_SPRINTING, false);
                $this->setGenericFlag(self::DATA_FLAG_SNEAKING, false);
                break;
        }
        // $speed = $entry->getSpeed(); // switch to 0.25 for testing purposes when messing with this.
        $target = $entry->getPosition();
        $rotation = $entry->getRotation();

        $shouldTeleport = $entry->shouldTeleport();
        if ($shouldTeleport) {
            $this->teleport($target, $rotation->yaw, $rotation->pitch);
            $key = array_search($entry, $this->script[$this->step], true);
            unset($this->script[$this->step][$key]);
            return;
        }

        // Thank you Mojang for fucking up the movement!
        // Now I have to do this movement hack.
        $packet = new MovePlayerPacket();
        $packet->entityRuntimeId = $this->getId();
        $packet->position = $this->getOffsetPosition($target);
        $packet->pitch = $rotation->pitch;
        $packet->headYaw = $rotation->yaw;
        $packet->yaw = $rotation->yaw;
        $packet->mode = MovePlayerPacket::MODE_NORMAL;
        $this->packetQueue->addPacket($packet);

        // $motion = PhysX::calculateMotionVector($this, $target, $speed, false, 0.01);
        // $this->yaw = $rotation->yaw;
        // $this->pitch = $rotation->pitch;

        // START: This is unrelated to Mojang fucking up movement.
        // Execute hard-move if soft-move failed.
        // $delta = MathX::calculateXZDistance($this, $target, true);
        // if ($delta > $this->movementThreshold) {
        //     $this->teleport($target, $rotation->yaw, $rotation->pitch);
        // }
        // END: This is unrelated to Mojang fucking up movement.

        // $this->move($motion->x, $motion->y, $motion->z);
        // $this->updateMovement();

        $key = array_search($entry, $this->script[$this->step], true);
        unset($this->script[$this->step][$key]);
        return;
    }

    /**
     * Handle the take-damage entry.
     *
     * @param DataEntry $entry
     * @return void
     */
    private function handleTakeDamageEntry(DataEntry $entry): void
    {
        if (!$entry instanceof TakeDamageEntry) {
            throw new DataEntryException([$entry], 'An unrecoverable exception occurred. Wrong entry provided.');
        }
        $damage = $entry->getDamageTaken();
        $cause = $entry->getCause();
        $event = new EntityDamageEvent($this, $cause, $damage);
        $this->attack($event, true);

        $health = $this->getHealth();
        $roundedHealth = round($health / 2, 1);
        $scoreTag = TextFormat::WHITE . TextFormat::BOLD . $roundedHealth . TextFormat::RED . ' ❤';
        $this->setScoreTag($scoreTag);

        $key = array_search($entry, $this->script[$this->step], true);
        unset($this->script[$this->step][$key]);
        return;
    }

    /**
     * Handle the regain-health entry.
     *
     * @param DataEntry $entry
     * @return void
     */
    private function handleRegainHealthEntry(DataEntry $entry): void
    {
        if (!$entry instanceof RegainHealthEntry) {
            throw new DataEntryException([$entry], 'An unrecoverable exception occurred. Wrong entry provided.');
        }
        $healthRegained = $entry->getHealthRegained();
        $regainReason = $entry->getRegainReason();
        $event = new EntityRegainHealthEvent($this, $healthRegained, $regainReason);
        $this->heal($event);

        $health = $this->getHealth();
        $roundedHealth = round($health / 2, 1);
        $scoreTag = TextFormat::WHITE . TextFormat::BOLD . $roundedHealth . TextFormat::RED . ' ❤';
        $this->setScoreTag($scoreTag);

        $key = array_search($entry, $this->script[$this->step], true);
        unset($this->script[$this->step][$key]);
        return;
    }

    /**
     * Handle the animation entry.
     *
     * @param DataEntry $entry
     * @return void
     */
    private function handleAnimationEntry(DataEntry $entry): void
    {
        if (!$entry instanceof AnimationEntry) {
            throw new DataEntryException([$entry], 'An unrecoverable exception occurred. Wrong entry provided.');
        }
        $animation = $entry->getAnimation();
        $packet = null;
        switch ($animation) {
            case AnimationEntry::ANIMATION_SWING_ARM:
                $packet = new AnimatePacket();
                $packet->action = AnimatePacket::ACTION_SWING_ARM;
                $packet->entityRuntimeId = $this->id;
                break;
            case AnimationEntry::ANIMATION_ITEM_CONSUME:
                $packet = new ActorEventPacket();
                $packet->event = ActorEventPacket::EATING_ITEM;
                $packet->data = $entry->getDuration();
                $packet->entityRuntimeId = $this->id;
                break;
        }
        if (!$packet instanceof DataPacket) {
            return;
        }
        $this->packetQueue->addPacket($packet);
        $key = array_search($entry, $this->script[$this->step], true);
        unset($this->script[$this->step][$key]);
        return;
    }

    /**
     * Handle the block place entry.
     *
     * @param DataEntry $entry
     * @return void
     */
    private function handleBlockPlaceEntry(DataEntry $entry): void
    {
        if (!$entry instanceof BlockPlaceEntry) {
            throw new DataEntryException([$entry], 'An unrecoverable exception occurred. Wrong entry provided.');
        }
        $blockId = $entry->getBlockId();
        $blockMeta = $entry->getBlockMeta();
        $block = BlockFactory::get($blockId, $blockMeta);
        $position = $entry->getPosition();
        $this->replayLevel->setBlock($position, $block);
        $key = array_search($entry, $this->script[$this->step], true);
        unset($this->script[$this->step][$key]);
        return;
    }

    /**
     * Handle the block break entry.
     *
     * @param DataEntry $entry
     * @return void
     */
    private function handleBlockBreakEntry(DataEntry $entry): void
    {
        if (!$entry instanceof BlockBreakEntry) {
            throw new DataEntryException([$entry], 'An unrecoverable exception occurred. Wrong entry provided.');
        }
        $position = $entry->getPosition();
        $block = BlockFactory::get(BlockIds::AIR);
        $this->replayLevel->setBlock($position, $block);
        $key = array_search($entry, $this->script[$this->step], true);
        unset($this->script[$this->step][$key]);
        return;
    }

    /**
     * Handle the inventory edit entry.
     *
     * @param DataEntry $entry
     * @return void
     */
    private function handleInventoryEditEntry(DataEntry $entry): void
    {
        if (!$entry instanceof InventoryEditEntry) {
            throw new DataEntryException([$entry], 'An unrecoverable exception occurred. Wrong entry provided.');
        }
        $inventoryId = $entry->getInventoryId();
        /** @var PlayerInventory|ArmorInventory|null $inventory */
        $inventory = null;

        $item = $entry->getItem();
        $slot = $entry->getSlot();

        switch ($inventoryId) {
            case InventoryEditEntry::INVENTORY_BASE:
                $this->inventory->setItemInHand($item);
                $playerList = $this->replayLevel->getPlayers();
                $this->inventory->sendHeldItem($playerList);
                break;
            case InventoryEditEntry::INVENTORY_ARMOR:
                $this->armorInventory->setItem($slot, $item);
                break;
        }

        $key = array_search($entry, $this->script[$this->step], true);
        unset($this->script[$this->step][$key]);
        return;
    }

    /**
     * Handle the chest interaction entry.
     *
     * @param DataEntry $entry
     * @return void
     */
    public function handleChestInteractionEntry(DataEntry $entry): void
    {
        if (!$entry instanceof ChestInteractionEntry) {
            throw new DataEntryException([$entry], 'An unrecoverable exception occurred. Wrong entry provided.');
        }
        $actionType = $entry->getActionType();
        $position = $entry->getPosition();
        $packet = new BlockEventPacket();
        $packet->x = (int)$position->x;
        $packet->y = (int)$position->y;
        $packet->z = (int)$position->z;
        $packet->eventType = 1; // always 1 for chest.
        $packet->eventData = 1; // open
        if ($actionType === ChestInteractionEntry::TYPE_CHEST_CLOSE) {
            $packet->eventData = 0; // close
        }
        $this->packetQueue->addPacket($packet);
        $key = array_search($entry, $this->script[$this->step], true);
        unset($this->script[$this->step][$key]);
        return;
    }

    /**
     * Handle the effect entry.
     *
     * @param DataEntry $entry
     * @return void
     */
    public function handleEffectEntry(DataEntry $entry): void
    {
        if (!$entry instanceof EffectEntry) {
            throw new DataEntryException([$entry], 'An unrecoverable exception occurred. Wrong entry provided.');
        }
        $effectId = $entry->getEffectId();
        $effectLevel = $entry->getLevel();
        $effectDuration = $entry->getDuration();
        $color = new Color(100, 100, 100);
        $effectType = new Effect($effectId, (string)$this->ticksLived, $color);
        $effect = new EffectInstance($effectType, $effectDuration, $effectLevel, true);
        $this->addEffect($effect);
        $key = array_search($entry, $this->script[$this->step], true);
        unset($this->script[$this->step][$key]);
    }

    /**
     * Try forwarding to next step in script
     * if the current step's script is done.
     *
     * @return void
     */
    private function tryForwardToNextStep(): void
    {
        $count = count($this->script[$this->step]);
        if ($count === 0) {
            $this->step++;
        }
        $stepExistence = array_key_exists($this->step, $this->script);
        if (!$stepExistence) {
            $this->flagForDespawn();
        }
        return;
    }

    /**
     * Gets called when an entity is being attacked.
     *
     * @param EntityDamageEvent $source
     * @param bool $replaySide
     * @return void
     */
    public function attack(EntityDamageEvent $source, bool $replaySide = false): void
    {
        if (!$replaySide) {
            $source->setCancelled();
        }

        parent::attack($source);
    }

    /**
     * Called prior to EntityDamageEvent execution to apply modifications to the event's damage, such as reduction due
     * to effects or armour.
     *
     * Makes sure modifiers don't get applied twice.
     *
     * @param EntityDamageEvent $source
     * @return void
     */
    public function applyDamageModifiers(EntityDamageEvent $source): void {}

}