<?php

namespace bguhc\commands;

use pocketmine\plugin\PluginBase;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\utils\TextFormat as T;

use pocketmine\Player;

use pocketmine\math\Vector3;

use bguhc\events\RegisterSignEvent;
use bguhc\events\UHCWorld;

use bguhc\Main;

class UHCCommand extends PluginBase {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        if($command->getName() == "bguhc") {
            if(!isset($args[0])) {
                $this->executeHelpPage($sender);
                return true;
            }

            switch($args[0]) {
                case "setsign":
                    $sender->sendMessage($this->plugin->prefix . " Tap a sign to register a new UHC game.");
                    $this->plugin->mode = 1;
                    return true;
                break;
                case "delsign":
                    $sender->sendMessage($this->plugin->prefix . " Tap a sign to unregister it.");
                    $this->plugin->mode = 2;
                    return true;
                break;
                case "resetmode":
                    $this->plugin->mode = 0;
                    return true;
                break;
                case "debugcfg":
                    var_dump(UHCWorld::getInstance()->getWorldConfig()->getAll());
                    return true;
                break;
                case "devmode":
                    $this->plugin->devmode = true;
                    return true;
                break;
                case "setspawn":
                    if(!isset($args[1])) return false;
                    $uhcworld = UHCWorld::getInstance();
                    $x = $sender->getFloorX();
                    $y = $sender->getFloorY();
                    $z = $sender->getFloorZ();
                    switch($args[1]) {
                        case "pos1":
                            $uhcworld->setPos1(new Vector3($x, $y, $z));
                            $sender->sendMessage($this->plugin->prefix . " Pos1 registered at ($x, $y, $z).");
                            return true;
                        break;
                        case "pos2":
                            $uhcworld->setPos2(new Vector3($x, $y, $z));
                            $sender->sendMessage($this->plugin->prefix . " Pos2 registered at ($x, $y, $z).");
                            return true;
                        break;
                        case "copy":
                            $time = microtime(true);
                            $uhcworld->copyStructure($sender);
                            $sender->sendMessage($this->plugin->prefix . " Selection copied.");
                            $sender->sendMessage($this->plugin->debug . " Took " . round((microtime(true) - $time), 2) . " seconds.");
                            return true;
                        break;
                        case "debug":
                            #$uhcworld->testBlocks();
                        break;
                        case "spawnpoint":
                            $uhcworld->setDefaultSpawnpoint(new Vector3($x, $y + 1, $z));
                            $sender->sendMessage($this->plugin->prefix . " World spawnpoint set to ($x, $y, $z).");
                            return true;
                        break;
                        case "config":
                            $uhcworld->saveSpawnData();
                            $sender->sendMessage($this->plugin->prefix . " Spawn data saved.");
                            return true;
                        break;
                    }
                break;
            }
            $sender->sendMessage($this->plugin->prefix . " Invalid arguments.");
            return false;
        }
    }

    private function executeHelpPage($player) {
        $player->sendMessage($this->plugin->prefix . T::DARK_AQUA . "========[" . T::AQUA . "UHC HELP" . T::DARK_AQUA . "]========");
        $player->sendMessage($this->plugin->prefix . T::LIGHT_PURPLE . " - " . T::AQUA . "/bguhc setsign: " . T::WHITE . "Register a new sign for an UHC match.");
        $player->sendMessage($this->plugin->prefix . T::LIGHT_PURPLE . " - " . T::AQUA . "/bguhc delsign: " . T::WHITE . "Delete an already exising UHC sign.");
        $player->sendMessage($this->plugin->prefix . T::LIGHT_PURPLE . " - " . T::AQUA . "/bguhc resetmode: " . T::WHITE . "Set the plugin mode to its default value.");
        $player->sendMessage($this->plugin->prefix . T::LIGHT_PURPLE . " - " . T::AQUA . "/bguhc devmode enable|disable: " . T::WHITE . "Enable/disable dev mode.");
        $player->sendMessage($this->plugin->prefix . T::LIGHT_PURPLE . " - " . T::AQUA . "/bguhc debugcfg: " . T::WHITE . "Debug the UHC world data config.");
        $player->sendMessage($this->plugin->prefix . T::LIGHT_PURPLE . " - " . T::AQUA . "/bguhc resetcfg: " . T::WHITE . "Clear the UHC world data config.");
        $player->sendMessage($this->plugin->prefix . T::LIGHT_PURPLE . " - " . T::AQUA . "/bguhc setspawn pos1: " . T::WHITE . "Set first position of the spawn selection.");
        $player->sendMessage($this->plugin->prefix . T::LIGHT_PURPLE . " - " . T::AQUA . "/bguhc setspawn pos2: " . T::WHITE . "Set second position of the spawn selection.");
        $player->sendMessage($this->plugin->prefix . T::LIGHT_PURPLE . " - " . T::AQUA . "/bguhc setspawn copy: " . T::WHITE . "Copy the blocks in the spawn selection.");
        $player->sendMessage($this->plugin->prefix . T::LIGHT_PURPLE . " - " . T::AQUA . "/bguhc setspawn spawnpoint: " . T::WHITE . "Set the spawnpoint of the UHC world.");
        $player->sendMessage($this->plugin->prefix . T::LIGHT_PURPLE . " - " . T::AQUA . "/bguhc setspawn config: " . T::WHITE . "Save the current data into the spawn config.");
        $player->sendMessage($this->plugin->prefix . T::LIGHT_PURPLE . " - " . T::AQUA . "/bguhc setspawn debug: " . T::WHITE . "Debug the spawn data.");
        $player->sendMessage($this->plugin->prefix . T::DARK_AQUA . "========[" . T::AQUA . "UHC HELP" . T::DARK_AQUA . "]========");
    }
}

?>
