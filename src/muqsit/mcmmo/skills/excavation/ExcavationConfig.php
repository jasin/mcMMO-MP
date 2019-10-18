<?php

declare(strict_types=1);

namespace muqsit\mcmmo\skills\excavation;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\item\Item;
use pocketmine\item\Shovel;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\Server;

class ExcavationConfig{

    const TYPE_XP = 0;
    const TYPE_DROPS = 1;
    const TYPE_XPREWARD = 2;
    const TYPE_SKILLREQ = 3;
    const TYPE_CHANCE = 4;
    const TYPE_DROP = 5;
	
    /** @var array[] */
    private $values = [];

    /** @var Plugin */
    private $plugin;

    /** @var array */
    private $metablocks = [
        "Sand" => [0, 1]
    ];

    public function __construct() {

        $server = Server::getInstance();
        $this->plugin = $server->getPluginManager()->getPlugin("mcMMO");
        $this->setDefaults();
    }

    private function set(Block $block, int $xpreward = 0, int $skillreq = 0, ?array $drops = null) : void {
        $this->values[BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())][ExcavationConfig::TYPE_XP] = $xpreward;
    }


    private function addDrops(array $drops, array $blocks) : void{
        if($drops !== null){
            foreach($blocks as $block){
                if(!isset($this->values[$index = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())])){
                    throw new \InvalidArgumentException("Cannot modify block drops of an unconfigured block (" . get_class($block) . ")");
                }

                if(isset($this->values[$index][ExcavationConfig::TYPE_DROPS])) {
                    $this->values[$index][ExcavationConfig::TYPE_DROPS] = array_unique(array_merge($this->values[$index][ExcavationConfig::TYPE_DROPS], $drops), SORT_REGULAR);
                } else {
                    $this->values[$index][ExcavationConfig::TYPE_DROPS] = $drops;
                }
            }
        }
    }

    public function isRightTool(Item $item) : bool{
        return $item instanceof Shovel;
    }

    public function isValidBlock($block) : bool {
        return isset($this->values[BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())]);
    }

    public function getDrops(Player $player, Item $item, Block $block, int $skill_level, bool $has_ability, &$xpreward = null) : array{
        $xpreward = 0;
        $drops = $block->getDrops($item);
        $multiplier = $has_ability ? 3 : 1;

        if($this->isRightTool($item) && isset($this->values[$index = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())])) {
            $drops = $block->getDrops($item);
            var_dump($drops);
            $values = $this->values[$index];
            $xpreward = $values[ExcavationConfig::TYPE_XP] * $multiplier;
            if(isset($values[ExcavationConfig::TYPE_DROPS])) {
                foreach($values[ExcavationConfig::TYPE_DROPS] as [
                    ExcavationConfig::TYPE_SKILLREQ => $skillreq,
                    ExcavationConfig::TYPE_CHANCE => $chance,
                    ExcavationConfig::TYPE_XPREWARD => $drop_xp,
                    ExcavationConfig::TYPE_DROP => $drop
                ]) {
                    if($skill_level >= $skillreq) {
                        $chance *= $multiplier;
                        if(mt_rand(1, 10000) <= $chance * 100) {
                            $drop_xp *= $multiplier;
                            $xpreward += $drop_xp;
                            $drops[] = $drop;
                        }
                    }
                }
            }
        }
        return $drops;
    }

    private function setDefaults() : void {

        /** 
         * Because of the way Bedrock handles blocks like Red_Sand as Block Sand
         * with a meta data value > 0. As a result XP and extra drops will be linked.
         * Best solution to keep code clean and concise. Advice???
         */

        // Sets block xp rewards
        $config = new Config($this->plugin->getDataFolder() . "xpreward.yml", Config::YAML);
        $blocks = $config->get("Excavation", []); 
        foreach($blocks as $key => $block) {
            $states = $this->metablocks[$key] ?? array(0);
            foreach($states as $state) {
                $id = constant("pocketmine\block\Block::" . strtoupper($key));
                $this->set(Block::get($id, $state), $block);
            }
        }

        // Add extra drops
        $config_drops = new Config($this->plugin->getDataFolder() . "drops.yml", Config::YAML);
        $drops = $config_drops->get("Excavation", []);
        foreach($drops as $drop => $data) {
            $blocks = [];
            foreach($data["From"] as $block) {
                $states = $this->metablocks[$key] ?? array(0);
                foreach($states as $state) {
                    $blockId = Block::get(constant("pocketmine\block\Block::" . strtoupper($block)), $state);
                    array_push($blocks, $blockId);
                }
            }
            $this->addDrops([
                [
                    ExcavationConfig::TYPE_SKILLREQ => $data["Level"],
                    ExcavationConfig::TYPE_XPREWARD => $data["XP"],
                    ExcavationConfig::TYPE_CHANCE => (int)($data["Chance"] * 100),
                    ExcavationConfig::TYPE_DROP => Item::get(constant("pocketmine\item\Item::$drop"))
                ]
            ],
                $blocks
            );
        }
    }
}
