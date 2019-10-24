<?php
namespace muqsit\mcmmo;

use muqsit\mcmmo\commands\McMMOCommand;
use muqsit\mcmmo\commands\SkillCommand;
use muqsit\mcmmo\database\Database;
use muqsit\mcmmo\skills\SkillManager;
use muqsit\mcmmo\block\TNT;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\block\BlockFactory;

class Loader extends PluginBase{

    /** @var Loader */
    private static $instance;

    /** @var Database */
    private $database;

    /** @var Player[] */
    private $onlinePlayers = [];

    public function onLoad() : void {
        BlockFactory::registerBlock(new TNT, true);
    }

    public function onEnable() : void{

        self::$instance = $this;
    
        $this->saveResource("database.yml");
        $this->saveResource("xpreward.yml");
        $this->saveResource("drops.yml");
        $this->saveResource("help.ini");

        McMMOCommand::registerDefaults($this);
        SkillCommand::loadHelpPages($this->getDataFolder() . "help.ini");
        SkillManager::registerDefaults();

        $database = new Config($this->getDataFolder() . "database.yml");
        $dbtype = $database->get("type");
        $args = $database->get(strtoupper($dbtype));

        $this->database = Database::getFromString($dbtype, $this->getDataFolder() . $args["datafolder"] . DIRECTORY_SEPARATOR);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        /**
         * During a server /reload, online players
         * are unloaded but never re-loaded because 
         * the onPlayerLogin event is never called.
         * Reload online players to avoid a server crash.
         */

        $onlinePlayers = $this->getServer()->getOnlinePlayers();
        if(!is_null($onlinePlayers)) {
            foreach($onlinePlayers as $player) {
                $this->getDatabase()->load($player);
            }
        }
    }

    public function onDisable() : void {
        $this->getDatabase()->saveAll();
        $this->getDatabase()->onClose();
    }

    public function getDatabase() : Database{
        return $this->database;
    }

    public function getSkillManager(Player $player) : SkillManager{
        return $this->database->getLoaded($player);
    }
}
