<?php

declare(strict_types=1);

namespace libReplay\event;

use libPhysX\internal\Rotation;
use libReplay\data\entry\AnimationEntry;
use libReplay\data\entry\block\BlockBreakEntry;
use libReplay\data\entry\block\BlockPlaceEntry;
use libReplay\data\entry\block\ChestInteractionEntry;
use libReplay\data\entry\EffectEntry;
use libReplay\data\entry\InventoryEditEntry;
use libReplay\data\entry\RegainHealthEntry;
use libReplay\data\entry\SpawnStateEntry;
use libReplay\data\entry\TakeDamageEntry;
use libReplay\data\entry\TransformEntry;
use libReplay\ReplayClient;
use libReplay\ReplayServer;
use NetherGames\NGEssentials\player\NGPlayer;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Consumable;
use pocketmine\Player;
use pocketmine\tile\Chest;

/**
 * Class ReplayListener
 * @package libReplay\data
 *
 * @internal
 */
class ReplayListener implements Listener
{

    /** @var ReplayServer */
    private ReplayServer $replayServer;

    /**
     * ReplayListener constructor.
     * @param ReplayServer $replayServer
     */
    public function __construct(ReplayServer $replayServer)
    {
        $this->replayServer = $replayServer;
    }

    /**
     * @param PlayerDeathEvent $event
     * @return void
     *
     * @priority            MONITOR
     */
    public function handleSpawnStateEntryOnDespawn(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        $client = $this->replayServer->getConnectedClientByPlayer($player);
        if (!$client instanceof ReplayClient) {
            return;
        }
        $isRecorded = $client->isRecorded();
        if (!$isRecorded) {
            return;
        }
        $clientId = $client->getClientId();
        $entry = new SpawnStateEntry($clientId, false); // keepInventory: false
        $this->replayServer->addEntryToTickMemory($entry);
    }

    /**
     * @param PlayerRespawnEvent $event
     * @return void
     *
     * @priority            MONITOR
     */
    public function handleSpawnStateEntryOnRespawn(PlayerRespawnEvent $event): void
    {
        $player = $event->getPlayer();
        $client = $this->replayServer->getConnectedClientByPlayer($player);
        if (!$client instanceof ReplayClient) {
            return;
        }
        $isRecorded = $client->isRecorded();
        if (!$isRecorded) {
            return;
        }
        $clientId = $client->getClientId();
        $position = $event->getRespawnPosition();
        $safePosition = $position->asVector3();
        $rotation = new Rotation($player->yaw, $player->pitch);
        $entry = new TransformEntry($clientId, $safePosition, $rotation, TransformEntry::STATE_DEFAULT, 0,
            true); // speed: 0 because teleport: true
        $this->replayServer->addEntryToTickMemory($entry);
        $entry = new SpawnStateEntry($clientId, true); // keepInventory: false
        $this->replayServer->addEntryToTickMemory($entry);
    }

    /**
     * @param PlayerMoveEvent $event
     * @return void
     *
     * @priority            MONITOR
     * @ignoreCancelled     true
     */
    public function handleTransformEntry(PlayerMoveEvent $event): void
    {
        /** @var NGPlayer $player */
        $player = $event->getPlayer();
        $client = $this->replayServer->getConnectedClientByPlayer($player);
        if (!$client instanceof ReplayClient) {
            return;
        }
        $isRecorded = $client->isRecorded();
        if (!$isRecorded) {
            return;
        }
        $clientId = $client->getClientId();
        $position = $event->getTo();
        $safePosition = $position->asVector3();
        $rotation = new Rotation($player->yaw, $player->pitch);
        $state = TransformEntry::getTransformStateFromPlayer($player);
        $speed = 0.25; // default speed: 1 / 4.
        $entry = new TransformEntry($clientId, $safePosition, $rotation, $state, $speed);
        $this->replayServer->addEntryToTickMemory($entry);
    }

    /**
     * @param ProjectileHitEvent $event
     * @return void
     *
     * @priority            MONITOR
     * @ignoreCancelled     true
     */
    public function handleTransformEntryOnEnderPearl(ProjectileHitEvent $event): void
    {
        $projectile = $event->getEntity();
        if (!$projectile instanceof EnderPearl) {
            return;
        }
        $expectedPlayer = $projectile->getOwningEntity();
        if ($expectedPlayer instanceof Player) {
            $client = $this->replayServer->getConnectedClientByPlayer($expectedPlayer);
            if (!$client instanceof ReplayClient) {
                return;
            }
            $isRecorded = $client->isRecorded();
            if (!$isRecorded) {
                return;
            }
            $clientId = $client->getClientId();
            $rayTraceResult = $event->getRayTraceResult();
            $target = $rayTraceResult->getHitVector();
            $safePosition = $target->asVector3();
            $rotation = new Rotation($expectedPlayer->yaw, $expectedPlayer->pitch);
            $entry = new TransformEntry($clientId, $safePosition, $rotation, TransformEntry::STATE_DEFAULT, 0,
                true);
            $this->replayServer->addEntryToTickMemory($entry);
        }
    }

    /**
     * @param EntityDamageByEntityEvent $event
     * @return void
     *
     * @priority            MONITOR
     * @ignoreCancelled     true
     */
    public function handleDamageAnimationEntry(EntityDamageByEntityEvent $event): void
    {
        $expectedDamagingPlayer = $event->getDamager();
        if ($expectedDamagingPlayer instanceof Player) {
            $client = $this->replayServer->getConnectedClientByPlayer($expectedDamagingPlayer);
            if (!$client instanceof ReplayClient) {
                return;
            }
            $isRecorded = $client->isRecorded();
            if (!$isRecorded) {
                return;
            }
            $clientId = $client->getClientId();
            $this->addAnimationEntry($clientId); // using default animation.
        }
    }

    /**
     * Add an animation entry.
     *
     * This method has been created due
     * to repetitive adding of animations.
     *
     * @param string $clientId
     * @param int $animation
     */
    public function addAnimationEntry(string $clientId, int $animation = AnimationEntry::ANIMATION_SWING_ARM): void
    {
        $entry = new AnimationEntry($clientId, $animation);
        $this->replayServer->addEntryToTickMemory($entry);
    }

    /**
     * @param PlayerItemConsumeEvent $event
     * @return void
     *
     * @priority            MONITOR
     * @ignoreCancelled     true
     */
    public function handleEatAnimationEntry(PlayerItemConsumeEvent $event): void
    {
        $item = $event->getItem();
        if ($item instanceof Consumable) {
            $player = $event->getPlayer();
            $client = $this->replayServer->getConnectedClientByPlayer($player);
            if (!$client instanceof ReplayClient) {
                return;
            }
            $isRecorded = $client->isRecorded();
            if (!$isRecorded) {
                return;
            }
            $clientId = $client->getClientId();
            $this->addAnimationEntry($clientId, AnimationEntry::ANIMATION_ITEM_CONSUME);
        }
    }

    /**
     * @param EntityDamageEvent $event
     * @return void
     *
     * @priority            MONITOR
     * @ignoreCancelled     true
     */
    public function handleTakeDamageEntry(EntityDamageEvent $event): void
    {
        $expectedPlayer = $event->getEntity();
        if ($expectedPlayer instanceof Player) {
            $client = $this->replayServer->getConnectedClientByPlayer($expectedPlayer);
            if (!$client instanceof ReplayClient) {
                return;
            }
            $isRecorded = $client->isRecorded();
            if (!$isRecorded) {
                return;
            }
            $clientId = $client->getClientId();
            $damage = $event->getFinalDamage();
            $cause = $event->getCause();
            $entry = new TakeDamageEntry($clientId, $damage, $cause);
            $this->replayServer->addEntryToTickMemory($entry);
        }
    }

    /**
     * @param EntityRegainHealthEvent $event
     * @return void
     *
     * @priority            MONITOR
     * @ignoreCancelled     true
     */
    public function handleRegainHealthEntry(EntityRegainHealthEvent $event): void
    {
        $expectedPlayer = $event->getEntity();
        if ($expectedPlayer instanceof Player) {
            $client = $this->replayServer->getConnectedClientByPlayer($expectedPlayer);
            if (!$client instanceof ReplayClient) {
                return;
            }
            $isRecorded = $client->isRecorded();
            if (!$isRecorded) {
                return;
            }
            $clientId = $client->getClientId();
            $healthRegained = $event->getAmount();
            $reason = $event->getRegainReason();
            $entry = new RegainHealthEntry($clientId, $healthRegained, $reason);
            $this->replayServer->addEntryToTickMemory($entry);
        }
    }

    /**
     * @param BlockPlaceEvent $event
     * @return void
     *
     * @priority            MONITOR
     * @ignoreCancelled     true
     */
    public function handleBlockPlaceEntry(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $client = $this->replayServer->getConnectedClientByPlayer($player);
        if (!$client instanceof ReplayClient) {
            return;
        }
        $isRecorded = $client->isRecorded();
        if (!$isRecorded) {
            return;
        }
        $clientId = $client->getClientId();
        $block = $event->getBlock();
        $safePosition = $block->asVector3();
        $blockId = $block->getId();
        $blockMeta = $block->getDamage();
        $entry = new BlockPlaceEntry($clientId, $safePosition, $blockId, $blockMeta);
        $this->addAnimationEntry($clientId); // using default animation.
        $this->replayServer->addEntryToTickMemory($entry);
    }

    /**
     * @param BlockBreakEvent $event
     * @return void
     *
     * @priority            MONITOR
     * @ignoreCancelled     true
     */
    public function handleBlockBreakEntry(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $client = $this->replayServer->getConnectedClientByPlayer($player);
        if (!$client instanceof ReplayClient) {
            return;
        }
        $isRecorded = $client->isRecorded();
        if (!$isRecorded) {
            return;
        }
        $clientId = $client->getClientId();
        $block = $event->getBlock();
        $safePosition = $block->asVector3();
        $entry = new BlockBreakEntry($clientId, $safePosition);
        $this->addAnimationEntry($clientId); // using default animation.
        $this->replayServer->addEntryToTickMemory($entry);
    }

    /**
     * @param InventoryTransactionEvent $event
     * @return void
     *
     * @priority            MONITOR
     * @ignoreCancelled     true
     */
    public function handleInventoryEditEntry(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();
        $client = $this->replayServer->getConnectedClientByPlayer($player);
        if (!$client instanceof ReplayClient) {
            return;
        }
        $isRecorded = $client->isRecorded();
        if (!$isRecorded) {
            return;
        }
        $actionList = $transaction->getActions();
        // this is usually a twice-iterable loop at maximum.
        foreach ($actionList as $action) {
            if ($action instanceof SlotChangeAction) {
                $actionInventory = $action->getInventory();
                $inventoryId = InventoryEditEntry::getInventoryIdFromInventory($actionInventory);
                if ($inventoryId !== InventoryEditEntry::INVENTORY_INVALID) {
                    $clientId = $client->getClientId();
                    $slot = $action->getSlot();
                    $item = $action->getTargetItem();
                    $entry = new InventoryEditEntry($clientId, $inventoryId, $slot, $item);
                    $this->replayServer->addEntryToTickMemory($entry);
                    break;
                }
            }
        }
    }

    /**
     * @param InventoryOpenEvent $event
     * @return void
     *
     * @priority            MONITOR
     * @ignoreCancelled     true
     */
    public function handleChestInteractionEntryOnOpen(InventoryOpenEvent $event): void
    {
        $inventory = $event->getInventory();
        if (!$inventory instanceof ChestInventory) {
            return;
        }
        $player = $event->getPlayer();
        $client = $this->replayServer->getConnectedClientByPlayer($player);
        if (!$client instanceof ReplayClient) {
            return;
        }
        $isRecorded = $client->isRecorded();
        if (!$isRecorded) {
            return;
        }
        $clientId = $client->getClientId();
        $holder = $inventory->getHolder();
        if (!$holder instanceof Chest) {
            return;
        }
        $position = $holder->getBlock();
        $safePosition = $position->asVector3();
        $entry = new ChestInteractionEntry($clientId, ChestInteractionEntry::TYPE_CHEST_OPEN, $safePosition);
        $this->replayServer->addEntryToTickMemory($entry);
    }

    /**
     * @param InventoryCloseEvent $event
     * @return void
     *
     * @priority            MONITOR
     * @ignoreCancelled     true
     */
    public function handleChestInteractionEntryOnClose(InventoryCloseEvent $event): void
    {
        $inventory = $event->getInventory();
        if (!$inventory instanceof ChestInventory) {
            return;
        }
        $player = $event->getPlayer();
        $client = $this->replayServer->getConnectedClientByPlayer($player);
        if (!$client instanceof ReplayClient) {
            return;
        }
        $isRecorded = $client->isRecorded();
        if (!$isRecorded) {
            return;
        }
        $clientId = $client->getClientId();
        $holder = $inventory->getHolder();
        if (!$holder instanceof Chest) {
            return;
        }
        $position = $holder->getBlock();
        $safePosition = $position->asVector3();
        $entry = new ChestInteractionEntry($clientId, ChestInteractionEntry::TYPE_CHEST_CLOSE, $safePosition);
        $this->replayServer->addEntryToTickMemory($entry);
    }

    /**
     * @param EntityEffectAddEvent $event
     * @return void
     *
     * @priority            MONITOR
     * @ignoreCancelled     true
     */
    public function handleEffectEntryOnAdd(EntityEffectAddEvent $event): void
    {
        $expectedPlayer = $event->getEntity();
        if ($expectedPlayer instanceof Player) {
            $client = $this->replayServer->getConnectedClientByPlayer($expectedPlayer);
            if (!$client instanceof ReplayClient) {
                return;
            }
            $isRecorded = $client->isRecorded();
            if (!$isRecorded) {
                return;
            }
            $clientId = $client->getClientId();
            $effect = $event->getEffect();
            $effectId = $effect->getId();
            $level = $effect->getEffectLevel();
            $duration = $effect->getDuration();
            $entry = new EffectEntry($clientId, $effectId, $level, $duration, true);
            $this->replayServer->addEntryToTickMemory($entry);
        }
    }

}