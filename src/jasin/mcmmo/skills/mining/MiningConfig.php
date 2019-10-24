<?php
namespace jasin\mcmmo\skills\mining;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\TNT;
use pocketmine\item\Item;
use pocketmine\item\Pickaxe;
use pocketmine\item\FlintSteel;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class MiningConfig {

    /** @var float */
    private $modifier = 0.01;

    /** @var Plugin */
    private $plugin;

    /** @var values[] */
    private $values = [];

    /** @var Vector3 */
    public $last_tnt_drop;

    /** @var array */
    private $metaBlocks = [
        "Prismarine" => [0, 1, 2],
        "Stonebrick" => [0, 1, 2, 3],
        "Purpur_Block" => [0, 1, 2, 3, 6, 10],
        "Stone" => [0, 1, 2, 3, 4, 5, 6],
        "Terracotta" => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15]
    ];

    public function __construct() {
        $server = Server::getInstance();
        $this->plugin = $server->getPluginManager()->getPlugin("mcMMO");
        $this->setDefaults();
    }

    private function set(Block $block, int $xpreward = 0, int $skillreq = 0, ?array $drops = null) : void {
        $this->values[BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())] = $xpreward;
    }

    public function isTNT(Block $block) : bool {
        return $block instanceof TNT;
    }

    public function isFlintSteel(Item $item) :bool {
        return $item instanceof FlintSteel;
    } 

    public function isRightTool(Item $item) : bool {
        return $item instanceof Pickaxe;
    }

    public function isValidBlock(Block $block) : bool {
         return isset($this->values[BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())]);
    }

    public function getDrops(Player $player, Item $item, Block $block, int $skill_level, bool $has_ability, &$xpreward = null) : array {
        $xpreward = 0;
        $drops = $block->getDrops($item);
        $multiplier = $has_ability ? 3 : 1;
        if($this->isRightTool($item) && isset($this->values[$index = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())])) {
            $xpreward = $this->values[$index] * $multiplier;
            $chance = $skill_level * $this->modifier * $multiplier;
            if(mt_rand(1, 100) <= $chance) {
                foreach($drops as $drop) {
                    $drop->setCount(3);
                }
            }
        }
        return $drops;
    }

    private function setDefaults() {

        /** 
         * Because of the way Bedrock handles blocks like Red_Sand as Block Sand
         * with a meta data value > 0. As a result XP and extra drops will be linked.
         * Best solution to keep code clean and concise. Advice???
         */

        // Sets block xp rewards
        $config = new Config($this->plugin->getDataFolder() . "xpreward.yml", Config::YAML);
        $blocks = $config->get("Mining", []); 
        foreach($blocks as $key => $block) {
            $states = $this->metablocks[$key] ?? array(0);
            foreach($states as $state) {
                $id = constant("pocketmine\block\Block::" . strtoupper($key));
                $this->set(Block::get($id, $state), $block);
            }
        }
    }
}
