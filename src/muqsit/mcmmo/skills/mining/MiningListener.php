<?php
namespace muqsit\mcmmo\skills\mining;

use muqsit\mcmmo\skills\SkillListener;

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
            if($this->config->isValidBlock($block)) {
                $player = $event->getPlayer();
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
        if($this->config->isValidBlock($block)) {
            $player = $event->getPlayer();
            $manager = $this->plugin->getSkillManager($player);
            $skill = $manager->getSkill(self::MINING);
            $drops = $this->config->getDrops($player, $event->getItem(), $event->getBlock(), $skill->getLevel(), $skill->hasAbility(), $xpreward);

            $event->setDrops($drops);
            
            if(!is_null($xpreward) && $xpreward > 0) {
                $manager->addSkillXp(self::MINING, $xpreward);
            }
        }
    }
}
