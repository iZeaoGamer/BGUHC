<?php

namespace bguhc\tasks;

use pocketmine\scheduler\PluginTask;

use bguhc\events\UHCWorld;

use pocketmine\utils\TextFormat as T;

use pocketmine\tile\Sign;

use bguhc\Main;

class RefreshSignTask extends PluginTask {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($tick) {
        $level = $this->plugin->getServer()->getDefaultLevel();
        $tiles = $level->getTiles();
        foreach($tiles as $tile) {
            if(!($tile instanceof Sign)) return false;
            $text = $tile->getText();
            if(!($text[0] === $this->plugin->prefix)) return false;
            $worldname = $text[3]; // readability
            $uhcworld = UHCWorld::getInstance();
            $state = $uhcworld->getSignState($worldname);
            $countPlayers = $uhcworld->getPlayersCount($worldname);
            $maxPlayers = $uhcworld->getMaxPlayers();
            $tile->setText(
                $this->plugin->prefix,
                T::BOLD . T::DARK_GREEN . "||" . T::GREEN . $state . T::DARK_GREEN . "||" . T::RESET,
                T::ITALIC . T::AQUA . $countPlayers . T::DARK_AQUA . "/" . T::AQUA . $maxPlayers . T::RESET,
                $worldname
            );
            return true;
        }
    }
}

?>
