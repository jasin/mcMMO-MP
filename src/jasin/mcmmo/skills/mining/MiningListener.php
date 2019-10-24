<?php
namespace jasin\mcmmo\skills\mining;

use jasin\mcmmo\skills\SkillListener;

use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\sound\PopSound;

class MiningListener extends SkillListener {

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
        if($this->config->isRightTool($item)) {
            $block = $event->getBlock();
            $player = $event->getPlayer();
            if($this->config->isValidBlock($block)) {
                $skill = $this->plugin->getSkillManager($player)->getSkill(self::MINING);
                if($skill->hasAbility()) {
                    $level = $block->getLevel();
                    $level->useBreakOn($block, $item, $player);
                    $level->addSound(new PopSound($block));
                }
            }
        }
    }

    /**
     * @param BlockBreakEvent
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onBlockBreak(BlockBreakEvent $event) : void {
        $block = $event->getBlock();
        $item = $event->getItem();
        if($this->config->isValidBlock($block) && $this->config->isRightTool($item)) {
            $player = $event->getPlayer();
            $manager = $this->plugin->getSkillManager($player);
            $skill = $manager->getSkill(self::MINING);
            $drops = $this->config->getDrops($player, $item, $block, $skill->getLevel(), $skill->hasAbility(), $xpreward);

            $event->setDrops($drops);
            
            if(!is_null($xpreward) && $xpreward > 0) {
                $manager->addSkillXp(self::MINING, $xpreward);
            }
        }
    }
}
