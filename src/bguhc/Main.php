<?php

namespace bguhc;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;

use pocketmine\utils\Config;

use pocketmine\level\Level;

use bguhc\tasks\WaitTask;
use bguhc\tasks\GameTask;
use bguhc\tasks\RefreshSignTask;

use bguhc\events\UHCSignEvent;
use bguhc\events\UHCWorld;

use bguhc\commands\UHCCommand;

class Main extends PluginBase {

    public $prefix = "§8[§cBG§4UHC§8]§r§f";
    public $debug = "§7§o[DEBUG]§f";

    public $devmode = false;

    public $mode = 0;

    public function onEnable() {
        $this->getLogger()->info("§bLoading...");
        $this->initializeConfig();
        $this->loadWorlds();
        $this->registerEvents();
        $this->registerCommands();
        $this->registerTasks();
        $this->getLogger()->info("§aEverything loaded.");
    }

    private function initializeConfig() {
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "/players");
        if(!file_exists($this->getDataFolder() . "settings.yml"))
            $config = new Config($this->getDataFolder() . "settings.yml", Config::YAML);
        if(!file_exists($this->getDataFolder() . "worlds.json"))
            $config = new Config($this->getDataFolder() . "worlds.json", Config::JSON);
        $this->getLogger()->info("§aConfigurations loaded.");
    }

    private function loadWorlds() {
        $config = new Config($this->getDataFolder() . "worlds.json", Config::JSON);
        $worlds = $config->getAll();
        foreach($worlds as $world => $data) {
            if($world !== null && ($this->getServer()->getLevelByName($world) instanceof Level))
                $this->getServer()->loadLevel($world);
        }
        $this->getLogger()->info("§bWorlds loaded.");
    }

    private function registerEvents() {
        $this->getServer()->getPluginManager()->registerEvents(new UHCSignEvent($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new UHCWorld($this), $this);
    }

    private function registerCommands() {
        $this->getCommand("bguhc")->setExecutor(new UHCCommand($this), $this);
    }

    private function registerTasks() {
        $this->getScheduler()->scheduleRepeatingTask(new WaitTask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new GameTask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new RefreshSignTask($this), 20);
    }
}

?>
