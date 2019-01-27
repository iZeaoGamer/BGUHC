<?php

namespace bguhc\events;

use pocketmine\plugin\PluginBase;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

use pocketmine\Player;

use pocketmine\tile\Sign;

use pocketmine\utils\TextFormat as T;

use bguhc\tasks\WaitTask;

use bguhc\Main;

class UHCSignEvent extends PluginBase implements Listener {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onInteract(PlayerInteractEvent $event) {
        #$event->getPlayer()->sendMessage("debug");
        $player = $event->getPlayer();
        $level = $player->getLevel();
        // get the sign
        $block = $event->getBlock();
        $tile = $level->getTile($block);
        if($tile instanceof Sign) {
            if(!($tile->getText()[0] === $this->plugin->prefix || $tile->getText()[0] === "[BGUHC]")) return true;
            if($this->plugin->mode != 1)
                $this->joinGame($tile, $player);
            else
                $this->registerSign($tile, $player);
        }
    }

    /**
     * Send the player into the game queue.
     *
     * @param  \pocketmine\tile\Sign $tile
     * @param  \pocketmine\Player    $player
     * @return bool
     */

    private function joinGame(Sign $tile, Player $player) {
        $uhcworld = UHCWorld::getInstance();
        $text = $tile->getText();
        $worldname = $text[3]; // readability
        $uhcworld->teleportToWorldSpawn($player, $worldname);
        #$uhcworld->buildSpawnPoint($worldname);
        $state = $uhcworld->getSignState($worldname);
        if(!($state === "JOIN")) {
            $errorMsg = ($state === "FULL") ? " This game is full." : " This game has already started.";
            $player->sendMessage($this->plugin->prefix . T::BOLD . T::RED . $errorMsg);
            return false;
        }
        if(!$uhcworld->worldExists($worldname)) {
            $player->sendMessage($this->plugin->prefix . T::DARK_RED . " This world doesn't exist.");
            return false;
        }
        $name = $player->getName();
        $player->sendMessage($this->plugin->debug . T::YELLOW . " Joining UHC game on world " . T::GOLD . $worldname . T::YELLOW . "...");
        $wplayers = $uhcworld->getPlayers($worldname);
        foreach($wplayers as $wplayer) {
            $uhcworld->addPlayer($wplayer, $worldname);
            $countPlayers = $uhcworld->getPlayersCount($worldname);
            $maxPlayers = $uhcworld->getMaxPlayers();
            $wplayer->sendMessage($this->plugin->prefix . T::AQUA .  " $name" . T::DARK_AQUA . " joined the game. " . T::DARK_GRAY . "[" . T::YELLOW . "$countPlayers" . T::DARK_GRAY . "/" . T::YELLOW . "$maxPlayers" . T::DARK_GRAY . "]");
            $uhcworld->setWaittime($worldname, 120);
            return true;
        }
    }

    /**
     * Register a sign for a new UHC world.
     *
     * @param  \pocketmine\tile\Sign $tile
     * @param  \pocketmine\Player    $player
     * @return void
     */

    private function registerSign(Sign $tile, Player $player) {
        $uhcworld = UHCWorld::getInstance();
        $worldNo = $uhcworld->getWorldCount() + 1;
        $tile->setText(
            $this->plugin->prefix,
            T::BOLD . T::DARK_GREEN . "||" . T::GREEN . "JOIN" . T::DARK_GREEN . "||" . T::RESET,
            T::ITALIC . T::AQUA . "0" . T::DARK_AQUA . "/" . T::AQUA . $uhcworld->getMaxPlayers() . T::RESET,
            "world" . $worldNo
        );
        $player->sendMessage($this->plugin->prefix . " Sign registered. Creating UHC world...");
        $time = microtime(true);
        $uhcworld->generateWorld("world" . $worldNo);
        $player->sendMessage($this->plugin->prefix . " Level world" . $worldNo . " has been generated.");
        $player->sendMessage($this->plugin->debug . " Took: " . round((microtime(true) - $time), 2) . " seconds.");
        $this->plugin->mode = 0;
    }
}

?>
