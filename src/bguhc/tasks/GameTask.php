<?php

namespace bguhc\tasks;

use pocketmine\scheduler\Task;

use bguhc\events\UHCWorld;

use pocketmine\utils\TextFormat as T;

use pocketmine\Player;

use bguhc\Main;

class GameTask extends Task {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun($tick) {
        $uhcworld = UHCWorld::getInstance();
        $config = $uhcworld->getWorldConfig();
        $worlds = $config->getAll();
        #$world = $uhcworld->getWorldInstance($worldname);
        foreach($worlds as $worldname => $data) {
            if(!$uhcworld->worldExists($worldname)) return false;
            $gametime = $uhcworld->getGametime($worldname);
            $players = $uhcworld->getPlayers($worldname);
            if($gametime !== -1) { // game has started
                if(count($players) >= 2) {
                    foreach($players as $player) {
                        if($gametime == 0) {
                            $this->gameEnd($player, $worldname);
                            $player->sendMessage($this->plugin->debug . " GameTask ended.");
                            return true;
                        }
                        if($gametime == 1)
                            $player->sendMessage($this->plugin->prefix . T::YELLOW . " Game ending in " . T::RED . "$gametime"  . T::YELLOW . " second...");
                        elseif(($gametime % 30) == 0 || $gametime < 11)
                            $player->sendMessage($this->plugin->prefix . T::YELLOW . " Game ending in " . T::RED . "$gametime"  . T::YELLOW . " seconds...");
                    }
                    $gametime--;
                    $uhcworld->setGametime($worldname, $gametime);
                    return true;
                }
                foreach($players as $player)
                    $player->sendPopup(T::ITALIC . T::GRAY . "Waiting for players...");
            }
            foreach($players as $player)
                $this->gameEnd($player, $worldname);
        }
    }

    /**
     * Do some finishing things after game end.
     *
     * @param  \pocketmine\Player $player
     * @param  string             $worldname
     * @return void
     */

    private function gameEnd(Player $player, string $worldname) {
        $uhcworld = UHCWorld::getInstance();
        $uhcworld->resetAll($worldname);
        $player->sendMessage($this->plugin->prefix . T::BOLD . T::YELLOW . " The game has ended!");
        $player->teleport($this->plugin->getServer()->getDefaultLevel()->getSpawnLocation(), 0, 0);
    }
}

?>
