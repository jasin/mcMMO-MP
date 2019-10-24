<?php

namespace jasin\mcmmo\skills\mining;

use jasin\mcmmo\skills\Skill;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\Player;

class MiningSkill extends Skill {

    const SKILL_ID = self::MINING;

    public static function getListenerClass() : ?string {
        return MiningListener::class;
    }

    public static function getItemIdentifies() : ?array {
        return [
            Item::IRON_PICKAXE,
            Item::WOODEN_PICKAXE,
            Item::STONE_PICKAXE,
            Item::DIAMOND_PICKAXE,
            Item::GOLD_PICKAXE
        ];
    }

    public function getName() : string {
        return "Mining";
    }

    public function getShortDescription() : string {
        return "Mining ores";
    }

    public function getAbilityName() : string {
        return "Super Breaker";
    }

    public function onActivateAbility(Player $player) : void {
    
    }
}
