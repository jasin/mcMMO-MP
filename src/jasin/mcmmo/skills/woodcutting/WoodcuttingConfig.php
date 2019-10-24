<?php
namespace jasin\mcmmo\skills\woodcutting;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Leaves;
use pocketmine\block\Leaves2;
use pocketmine\block\Wood;
use pocketmine\block\Wood2;
use pocketmine\item\Axe;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class WoodcuttingConfig{

    const MINIMUM_LEAFBLOWER_LEVEL = 100;
    const MAX_LEVEL = 1000;

    const TREE_FELLER_DIRECTIONS = [
        [2, 0, -2], [2, 0, -1], [2, 0, 0], [2, 0, 1], [2, 0, 2],
        [1, 0, -2], [1, 0, -1], [1, 0, 0], [1, 0, 1], [1, 0, 2],
        [0, 0, -2], [0, 0, -1],            [0, 0, 1], [0, 0, 2],
        [-1, 0, -2], [-1, 0, -1], [-1, 0, 0], [-1, 0, 1], [-1, 0, 2],
        [-2, 0, -2], [-2, 0, -1], [-2, 0, 0], [-2, 0, 1], [-2, 0, 2]
    ];

    /** @var int[] */
    private $values = [];

    /** @var Plugin */
    private $plugin;

    /** @var array */
    private $metablocks = [
        "Log" => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
        "Log2" => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
        "Wood" => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
        "Red_Mushroom_Block" => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15],
        "Brown_Mushroom_Block" => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15]
    ];

    public function __construct() {

        $server = Server::getInstance();
        $this->plugin = $server->getPluginManager()->getPlugin("mcMMO");
        $this->setDefaults();
    }

    public function set(Block $block, int $xpreward) : void{
        $this->values[BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())] = $xpreward;
    }

    public function isRightTool(Item $item) : bool{
        return $item instanceof Axe;
    }

    public function isValidBlock(Block $block) : bool {
        return isset($this->values[BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())]);
    }

    public function isLeaf(Block $block) : bool{
        return $block instanceof Leaves || $block instanceof Leaves2;
    }

    private function clearBlock(Level $level, Vector3 $pos) : void {
        $level->setBlockIdAt($pos->x, $pos->y, $pos->z, Block::AIR);
        $level->setBlockDataAt($pos->x, $pos->y, $pos->z, 0);
    }

    private function treeFellerSearch(Vector3 $pos, Level $level, int $logId, int $leafId, int &$i) : \Generator {
        $future_pos = $pos->add(0, 1, 0);
        $futureBlockId = $level->getBlockIdAt($future_pos->x, $future_pos->y, $future_pos->z);
        if($futureBlockId === $leafId || $futureBlockId === $logId) {
            yield from $this->treeFellerSearch($future_pos, $level, $logId, $leafId, $i);
        }
        foreach(self::TREE_FELLER_DIRECTIONS as [$xOffset, $yOffset, $zOffset]) {
            $new_pos = $pos->add($xOffset, $yOffset, $zOffset);
            $blockId = $level->getBlockIdAt($new_pos->x, $new_pos->y, $new_pos->z);
            if($blockId === $logId || $blockId === $leafId) {
                $this->clearBlock($level, $new_pos);
                if($blockId === $logId) { $i++; }
            }
        }
        yield $pos;
    }

    public function getDrops(Player $player, Item $item, Block $block, int $skill_level, bool $has_ability, &$xpreward = null) : array {
        $xpreward = 0;
        $drops = $block->getDrops($item);
        $logId = $block->getId();

        if($this->isRightTool($item) && isset($this->values[$index = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())])) {
            $xpreward = $this->values[$index];
            $multiplier = ($skill_level >= self::MAX_LEVEL || mt_rand(1, self::MAX_LEVEL) <= $skill_level) ? 2 : 1;

            if($has_ability) {
                $i = 0;
                $level = $player->getLevel();

                // Recursively find the top of tree and clear the blocks top to bottom
                foreach($this->treeFellerSearch($block->asVector3(), $level, $block->getId(), $block instanceof Wood ? Block::LEAVES : Block::LEAVES2, $i) as $pos) {
                    $blockId = $level->getBlockIdAt($pos->x, $pos->y, $pos->z);
                    if($blockId === $logId) { $i++; }
                    $this->clearBlock($level, $pos);
                }
                $multiplier *= $i;
                $xpreward *= $i;
            }

            foreach($drops as $drop) {
                $drop->setCount($multiplier); 
            }

        } elseif(mt_rand(1, 20) === 1 && $this->isLeaf($block) && $skill_level >= WoodcuttingConfig::MINIMUM_LEAFBLOWER_LEVEL) {
            $sapling = $block->getSaplingItem();
            $drops_sapling = false;

            foreach($drops as $drop) {
                if($drop->equals($sapling, false, false)) {
                    $drops_sapling = true;
                    break;
                }
            }

            if(!$drops_sapling) {
                $drops[] = $sapling;
            }
        }

        return $drops;
    }

    private function setDefaults() : void {
        $woodcutting = new Config($this->plugin->getDataFolder() . "xpreward.yml", Config::YAML);
        $blocks = $woodcutting->get("Woodcutting", []); 
        foreach($blocks as $key => $block) {
            $states = $this->metablocks[$key] ?? array(0);
            foreach($states as $state) {
                $id = constant("pocketmine\block\Block::" . strtoupper($key));
                $this->set(Block::get($id, $state), $block);
            }
        }
    }
}
