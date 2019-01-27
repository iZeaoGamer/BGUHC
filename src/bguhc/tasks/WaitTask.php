<?php

namespace bguhc\tasks;

use pocketmine\scheduler\PluginTask;

use bguhc\events\UHCWorld;

use pocketmine\utils\TextFormat as T;

use bguhc\Main;

class WaitTask extends PluginTask {

    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function onRun($tick) {
        $uhcworld = UHCWorld::getInstance();
        $config = $uhcworld->getWorldConfig();
        $worlds = $config->getAll();
        #$world = $uhcworld->getWorldInstance($worldname);
        foreach($worlds as $worldname => $data) {
            if(!$uhcworld->worldExists($worldname)) return false;
            $waittime = $uhcworld->getWaittime($worldname);
            $players = $uhcworld->getPlayers($worldname);
            if(count($players) >= 2) {
                if($waittime !== -1) {
                    foreach($players as $player) {
                        if($waittime == 0) {
                            $player->sendMessage($this->plugin->prefix . T::GREEN . " Game is starting." . T::BOLD . "GO!");
                            $uhcworld->setWaittime($worldname, -1);
                            $uhcworld->setGametime($worldname, 60);
                            $uhcworld->enableDamage($worldname);
                            $player->sendMessage($this->plugin->debug . " WaitTask ended.");
                            return true;
                        }
                        if($waittime == 1)
                            $player->sendMessage($this->plugin->prefix . T::YELLOW . " Game starting in " . T::RED . "$waittime"  . T::YELLOW . " second...");
                        elseif(($waittime % 30) == 0 || $waittime < 11)
                            $player->sendMessage($this->plugin->prefix . T::YELLOW . " Game starting in " . T::RED . "$waittime"  . T::YELLOW . " seconds...");
                    }
                    $waittime--;
                    $uhcworld->setWaittime($worldname, $waittime);
                }
            }
            foreach($players as $player)
                $player->sendPopup(T::ITALIC . T::GRAY . "Waiting for players...");
            return true;
        }
    }
}

?>
