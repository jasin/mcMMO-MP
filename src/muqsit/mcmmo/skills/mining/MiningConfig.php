<?php
namespace muqsit\mcmmo\skills\mining;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\item\Item;
use pocketmine\item\Pickaxe;
use pocketmine\Player;

class MiningConfig {

    const TYPE_XPREWARD = 0;
    const TYPE_SKILLREQ = 1;
    const TYPE_CHANCE = 2;
    const TYPE_DROPS = 3;

    private $values = [];

    public function __construct() {
        $this->setDefaults();
    }

    public function set(Block $block, int $xpreward = 0, int $skillreq = 0, ?array $drops = null) : void {
        $this->values[BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())] = [
            MiningConfig::TYPE_XPREWARD => $xpreward,
            MiningConfig::TYPE_SKILLREQ => $skillreq,
            MiningConfig::TYPE_DROPS => $this->createDropsConfig($drops)
        ];
    }

    public function copy(Block $block, Block ...$blocks) : void {
        $copy_index = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage());
        foreach($blocks as $block) {
            $block_index = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage());
            $this->values[$block_index] = $this->values[$copy_index];
        }
    }

    public function addDrops(array $drops, Block ...$blocks) : void {
        $drops = $this->createDropsConfig($drops);
        if($drops !== null) {
            foreach($blocks as $block) {
                if(!isset($this->values[$index = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())])) {
                    throw new \InvalidArgumentException("Cannot modify block drops of an unconfigured block( " . get_class($block) . ")");
                }
                if(isset($this->values[$index][MiningConfig::TYPE_DROPS])) {
                    $this->values[$index][MiningConfig::TYPE_DROPS] = array_unique(array_merge($this->values[$index][MiningConfig::TYPE_DROPS], $drops), SORT_REGULAR);
                } else {
                    $this->values[$index][MiningConfig::TYPE_DROPS] = $drops;
                }
            }
        }
    }

    private function createDropsConfig(?array $drops) : ?array {
        if(empty($drops)) {
            return null;
        }

        $result = [];

        foreach($drops as [
            MiningConfig::TYPE_SKILLREQ => $skillreq,
            MiningConfig::TYPE_XPREWARD => $xpreward,
            MiningConfig::TYPE_CHANCE => $chance,
            MiningConfig::TYPE_DROPS => $drops
        ]){
            $result[$skillreq][] = [
                MiningConfig::TYPE_XPREWARD => $xpreward,
                MiningConfig::TYPE_CHANCE => (int) $chance * 100,
                MiningConfig::TYPE_DROPS => $drops
            ];
        }

        return array_unique($result, SORT_REGULAR);
    }

    private function isRightTool(Item $item) : bool {
        return $item instanceof Pickaxe;
    }

    public function getDrops(Player $player, Item $item, Block $block, int $skill_level, bool $has_ability, &$xpreward = null) : array{
        $xpreward = 0;
        $multiplier = $has_ability ? 3 : 1;
        if($this->isRightTool($item) && isset($this->values[$index = BlockFactory::toStaticRuntimeId($block->getId(), $block->getDamage())])) {
            $values = $this->values[$index];
            if($skill_level >= $values[MiningConfig::TYPE_SKILLREQ]) {
                $xpreward = $values[MiningConfig::TYPE_XPREWARD] * $multiplier;
            }

            if(isset($values[MiningConfig::TYPE_DROPS])) {
                foreach($values[MiningConfig::TYPE_DROPS] as $skillreq => $drops) {
                    if($skill_level >= $skillreq){
                        foreach($drops as [
                            MiningConfig::TYPE_XPREWARD => $xprew,
                            MingingConfig::TYPE_CHANCE => $chance,
                            MiningConfig::TYPE_DROPS => $drops
                        ]) {
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
        $this->set(Block::get(Block::COBBLESTONE), 40);
        $this->copy(Block::get(Block::COBBLESTONE),
            Block::get(Block::STONE), Block::get(Block::COAL_ORE), Block::get(Block::IRON_ORE),
            Block::get(Block::GOLD_ORE), Block::get(Block::REDSTONE_ORE),Block::get(Block::LAPIS_ORE),
            Block::get(Block::DIAMOND_ORE), Block::get(Block::OBSIDIAN));

    }
}
