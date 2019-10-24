<?php

namespace jasin\mcmmo\skills\mining;

use jasin\mcmmo\skills\Skill;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\Player;

class BlastMiningSkill extends Skill {

    const SKILL_ID = self::BLAST_MINING;

    public static function getListenerClass() : ?string {
        return BlastMiningListener::class;
    }

    public static function getItemIdentifies() : ?array {
        return [Item::FLINT_STEEL];
    }

    public function getName() : string {
        return "Blast Mining";
    }

    public function getShortDescription() : string {
        return "Blast Mining using TNT";
    }

    public function getAbilityName() : string {
        return "Blast Mining";
    }

    public function onActivateAbility(Player $player) : void {
    
    }
}
