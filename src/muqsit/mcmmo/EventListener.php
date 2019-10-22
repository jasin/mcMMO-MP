<?php
namespace muqsit\mcmmo;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Tool;
use pocketmine\block\Block;
use pocketmine\utils\TextFormat;

class EventListener implements Listener{

    /** @var int[] */
    private $blocks = [
        Block::CRAFTING_TABLE, Block::CHEST, Block::ENCHANTING_TABLE, Block::FENCE_GATE,
        Block::ITEM_FRAME_BLOCK, Block::FURNACE, Block::HOPPER_BLOCK
    ];

    /** @var Loader */
    private $plugin;

    /** @var int[] */
    private $last_interactions = [];

    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }

    public function onPlayerLogin(PlayerLoginEvent $event) : void{
        $player = $event->getPlayer();
        $this->plugin->getDatabase()->load($player);
        $this->last_interactions[$player->getId()] = 0;
    }

    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        $this->plugin->getDatabase()->save($player, true);
        unset($this->last_interactions[$player->getId()]);
    }

    public function onPlayerInteract(PlayerInteractEvent $event) : void{
        $action = $event->getAction();
        if($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            $block = $event->getBlock();
            if(!in_array($block->getId(), $this->blocks)) {
                $item = $event->getItem();
                if($item instanceof Tool){
                    $player = $event->getPlayer();
                    $pid = $player->getId();
                    if(!isset($this->last_interactions[$pid]) || $this->last_interactions[$pid] !== $player->ticksLived){
                        $this->last_interactions[$pid] = $player->ticksLived;
                        $skill_manager = $this->plugin->getSkillManager($player);
                        if($skill_manager->canUseAbilities()) {
                            $skill = $skill_manager->getSkillByItem($item);
                            if($skill !== null) {
                                $player->sendMessage($skill_manager->activateAbility($skill->getId()) ? TextFormat::GREEN . "**" . strtoupper($skill->getAbilityName()) . TextFormat::GREEN . " ACTIVATED**" :
                                    TextFormat::RED . "You are too tired to use that ability again. " . TextFormat::YELLOW . "(" . $skill->getAbilityCooldownExpire() . "s)"
                                );
                            }
                        }
                    }
                }
            }
        }
    }
}
