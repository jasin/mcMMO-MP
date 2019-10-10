<?php
namespace muqsit\mcmmo\skills\excavation;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\item\Item;
use pocketmine\item\Shovel;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\Server;

class ExcavationConfig{

    const TYPE_XPREWARD = 0;
    const TYPE_SKILLREQ = 1;
    const TYPE_CHANCE = 2;
    const TYPE_DROPS = 3;
	
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

    public function set(Block $block, int $xpreward = 0, int $skillreq = 0, ?array $drops = null) : void {
        $this->values[BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())] = [
            ExcavationConfig::TYPE_XPREWARD => $xpreward,
            ExcavationConfig::TYPE_SKILLREQ => $skillreq,
            ExcavationConfig::TYPE_DROPS => $this->createDropsConfig($drops)
        ];
    }

    public function addDrops(array $drops, array $blocks) : void{
        $drops = $this->createDropsConfig($drops);
        if($drops !== null){
            foreach($blocks as $block){
                if(!isset($this->values[$index = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())])){
                    throw new \InvalidArgumentException("Cannot modify block drops of an unconfigured block (" . get_class($block) . ")");
                }

                if(isset($this->values[$index][ExcavationConfig::TYPE_DROPS])){
                    $this->values[$index][ExcavationConfig::TYPE_DROPS] = array_unique(array_merge($this->values[$index][ExcavationConfig::TYPE_DROPS], $drops), SORT_REGULAR);
                }else{
                    $this->values[$index][ExcavationConfig::TYPE_DROPS] = $drops;
                }
            }
        }
    }

    private function createDropsConfig(?array $drops) : ?array{
        if(empty($drops)){
            return null;
        }

        $result = [];

        foreach($drops as [
            ExcavationConfig::TYPE_SKILLREQ => $skillreq,
            ExcavationConfig::TYPE_XPREWARD => $xpreward,
            ExcavationConfig::TYPE_CHANCE => $chance,
            ExcavationConfig::TYPE_DROPS => $drops
        ]){
            $result[$skillreq][] = [
                ExcavationConfig::TYPE_XPREWARD => $xpreward,
                ExcavationConfig::TYPE_CHANCE => (int) $chance * 100,//$chance = percentage with a precision of 2
                ExcavationConfig::TYPE_DROPS => $drops
            ];
        }

        return array_unique($result, SORT_REGULAR);
    }

    private function isRightTool(Item $item) : bool{
        return $item instanceof Shovel;
    }

    public function getDrops(Player $player, Item $item, Block $block, int $skill_level, bool $has_ability, &$xpreward = null) : array{
        $xpreward = 0;
        $multiplier = $has_ability ? 3 : 1;

        if($this->isRightTool($item) && isset($this->values[$index = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())])){
            $values = $this->values[$index];
            if($skill_level >= $values[ExcavationConfig::TYPE_SKILLREQ]){
                $xpreward = $values[ExcavationConfig::TYPE_XPREWARD] * $multiplier;
            }

            if(isset($values[ExcavationConfig::TYPE_DROPS])){
                foreach($values[ExcavationConfig::TYPE_DROPS] as $skillreq => $drops){
                    if($skill_level >= $skillreq){
                        foreach($drops as [
                            ExcavationConfig::TYPE_XPREWARD => $xprew,
                            ExcavationConfig::TYPE_CHANCE => $chance,
                            ExcavationConfig::TYPE_DROPS => $drops
                        ]){
                            $chance *= $multiplier;
                            if(mt_rand($chance, 10000) <= $chance){
                                $xpreward = $xprew * $multiplier;
                                return $drops;
                            }
                        }
                    }
                }
            }
        }

        return $block->getDrops($item);
    }

    private function setDefaults() : void {

        /** 
         * Because of the way Bedrock handles blocks like Red_Sand as Block Sand
         * with a meta data value > 0. As a result XP and extra drops will be linked.
         * Best solution to keep code clean and concise. Advice???
         */

        // Sets block xp rewards
        $excavation = new Config($this->plugin->getDataFolder() . "xpreward.yml", Config::YAML);
        $blocks = $excavation->get("Excavation", []); 
        foreach($blocks as $key => $block) {
            $states = $this->metablocks[$key] ?? array(0);
            foreach($states as $state) {
                $id = constant("pocketmine\block\Block::" . strtoupper($key));
                $this->set(Block::get($id, $state), $block);
            }
        }

        // Add extra drops
        $excavation = new Config($this->plugin->getDataFolder() . "drops.yml", Config::YAML);
        $drops = $excavation->get("Excavation", []);
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
                    ExcavationConfig::TYPE_CHANCE => $data["Chance"],
                    ExcavationConfig::TYPE_DROPS => [Item::get(constant("pocketmine\item\Item::$drop"))]
                ]
            ],
                $blocks
            );
        }
    }
}
