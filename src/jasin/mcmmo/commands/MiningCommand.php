<?php
namespace jasin\mcmmo\commands;

use jasin\mcmmo\skills\Skill;
use jasin\mcmmo\skills\mining\MiningConfig;

use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MiningCommand extends SkillCommand {

    public function getSkillId() : int {
        return self::MINING;
    }

    public function getSkillEffects(Player $player, Skill $skill) : string {

        return TextFormat::DARK_AQUA . $skill->getAbilityName() . TextFormat::EOL;
    }

    public function getSkillStats(Player $player, Skill $skill) : string {

        return TextFormat::DARK_AQUA . $skill->getName() . TextFormat::EOL;
    }
}
