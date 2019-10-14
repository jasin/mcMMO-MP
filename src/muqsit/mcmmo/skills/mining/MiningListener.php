<?php
namespace muqsit\mcmmo\skills\mining;

use muqsit\mcmmo\skills\SkillListener;

use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;

class MiningListener extends SkillListener {

    /** @var MiningConfig */
    private $config;

    protected function init() : void {
        $this->config = new MiningConfig();
    }

    /**
     * @param BlockBreakEvent
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onBlockBreak(BlockBreakEvent $event) : void {
        $block = $event->getBlock();
        $player = $event->getPlayer();
        $manager = $this->plugin->getSkillManager($player);
        $skill = $manager->getSkill(self::MINING);
        $drops = $this->config->getDrops($player, $event->getItem(), $event->getBlock(), $skill->getLevel(), $skill->hasAbility(), $xpreward);
        if(!empty($drops)) {
            $event->setDrops($drops);
        }

        if(!is_null($xpreward) && $xpreward > 0) {
            $manager->addSkillXp(self::MINING, $xpreward);
        }
    }
}
