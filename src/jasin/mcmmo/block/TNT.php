<?php

namespace jasin\mcmmo\block;

use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\item\Item;
use pocketmine\item\FlintSteel;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\utils\Random;
use pocketmine\math\Vector3;

class TNT extends \pocketmine\block\TNT {

	public function onActivate(Item $item, Player $player=null, int $fuse=80) : bool {
		if($item instanceof FlintSteel or $item->hasEnchantment(Enchantment::FIRE_ASPECT)) {
			if($item instanceof Durable) {
				$item->applyDamage(1);
			}
			$this->ignite($fuse, $player);
			return true;
		}
		return false;
	}

	public function ignite(int $fuse=80, Player $player=null) {
		$this->getLevel()->setBlock($this, BlockFactory::get(Block::AIR), true);
		$mot = (new Random())->nextSignedFloat() * M_PI * 2;
		$nbt = Entity::createBaseNBT($this->add(0.5, 0, 0.5), new Vector3(-sin($mot) * 0.02, 0.2, -cos($mot) * 0.02));
		$nbt->setShort("Fuse", $fuse);
		$tnt = Entity::createEntity("PrimedTNT", $this->getLevel(), $nbt);
        $tnt->setOwningEntity($player);

		if($tnt !== null) {
			$tnt->spawnToAll();
		}
    }
}
