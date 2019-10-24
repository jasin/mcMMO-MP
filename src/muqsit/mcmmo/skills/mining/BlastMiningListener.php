<?php
namespace muqsit\mcmmo\skills\mining;

use muqsit\mcmmo\skills\SkillListener;
use muqsit\mcmmo\blocks\CustomTNT;

use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\sound\PopSound;

class BlastMiningListener extends SkillListener {

    /** @var MiningConfig */
    private $config;

    protected function init() : void {
        $this->config = new MiningConfig();
    }

    /**
     * @param PlayerInteractEvent $event
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onPlayerInteract(PlayerInteractEvent $event) : void {
        $item = $event->getItem();
        $player = $event->getPlayer();
        $manager = $this->plugin->getSkillManager($player);
        if($player->isSneaking() && $this->config->isFlintSteel($item) && $manager->last_tnt_drop !== null) {
            $pos = $manager->last_tnt_drop;
            $level = $player->getLevel();
            $block = $level->getBlockAt($pos->x, $pos->y, $pos->z);
            if($this->config->isTNT($block)) {
                $skill = $manager->getSkill(self::BLAST_MINING);
                if($skill->hasAbility()) {
                    $block->onActivate($item, $player, 0);
                }
            }
        }
    }

    /** 
     * @param BlockPlaceEvent $event
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onBlockPlace(BlockPlaceEvent $event) : void {
        $block = $event->getBlock();
        if($this->config->isTNT($block)) {
            $player = $event->getPlayer();
            $manager = $this->plugin->getSkillManager($player);
            $manager->last_tnt_drop = $block->asVector3();
        }
    }

    /**
     * @param BlockBreakEvent $event
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onBlockBreak(BlockBreakEvent $event) : void {
        $block = $event->getBlock();
        $item = $event->getItem();
        if($this->config->isTNT($block) && $block->asVector3() === $this->config->last_tnt_drop) {
            $player = $event->getPlayer();
            $manager = $this->plugin->getSkillManager($player);
            $manager->last_tnt_drop = null;
        } 
    }

     /**
     * @param EntityExplodeEvent $event
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onExplosion(EntityExplodeEvent $event) : void {
        $player = $event->getEntity()->getOwningEntity();
        if(!$player === null) {
            $manager = $this->plugin->getSkillManager($player);
            $skill = $manager->getSkill(self::BLAST_MINING);
        }
        $drops = $event->getBlockList();
    }
}
