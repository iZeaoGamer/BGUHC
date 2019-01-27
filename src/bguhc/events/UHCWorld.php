<?php

namespace bguhc\events;

use pocketmine\event\Listener;
use pocketmine\event\level\ChunkPopulateEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDamageEvent;

use pocketmine\Player;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\utils\Config;

use pocketmine\level\Level;
use pocketmine\level\Position;

use pocketmine\math\Vector3;

use pocketmine\block\Block;

use pocketmine\tile\Sign;

use bguhc\Main;

class UHCWorld implements Listener {

    private $plugin;
    private $pos1 = [], $pos2 = [], $spawnBlocks = [], $copypos = [];
    private $worldname;
    private $spawnpoint;

    static $instance = null;

    const MAX_PLAYERS = 15;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->spawnpoint = new Vector3(128, 100, 128);
        self::$instance = $this;
    }

    /**
     * Generate a new world with a random seed, and register the world in the config.
     *
     * @param  string $worldname
     * @return bool
     */

    public function generateWorld(string $worldname) {
        if($this->plugin->getServer()->isLevelGenerated($worldname)) return false;
        #echo "debug: $worldname\n";
        $this->copypos = new Vector3(125, 100, 125);
        $this->worldname = $worldname;
        $this->plugin->getServer()->generateLevel($worldname);
        #$this->plugin->getServer()->loadLevel($worldname);
        $spawnpoint = $this->getSpawnConfig()->get("spawnpoint");
        $this->getLevelInstance($worldname)->setSpawnLocation(new Vector3($spawnpoint["x"], $spawnpoint["y"], $spawnpoint["z"]));
        #$this->loadSpawnChunks($worldname);
        $this->addWorldToConfig($worldname);
        return true;
    }

    /**
     * Load the spawn chunks of the world.
     *
     * @param  string $worldname
     * @return void
     */

    public function loadSpawnChunks(string $worldname) {
        $world = $this->getLevelInstance($worldname);
        $x = 128;
        $z = 128;
        $world->loadChunk($x >> 4, $z >> 4);
        $world->loadChunk(($x + 16) >> 4, $z >> 4);
        $world->loadChunk($x >> 4, ($z + 16) >> 4);
        $world->loadChunk(($x + 16) >> 4, ($z + 16) >> 4);
    }

    /**
     * Save the first position of the spawn structure.
     *
     * @param  \pocketmine\math\Vector3 $pos
     * @return void
     */

    public function setPos1(Vector3 $pos) { $this->pos1 = $pos; }

    /**
     * Save the second position of the spawn structure.
     *
     * @param  \pocketmine\math\Vector3 $pos
     * @return void
     */

    public function setPos2(Vector3 $pos) { $this->pos2 = $pos; }

    /**
     * Save the block data from the selection into an array.
     *
     * @param  \pocketmine\Player $player
     * @return void
     */

    public function copyStructure(Player $player) {
        $level = $player->getLevel();
        $pos1 = $this->pos1; // readability
        $pos2 = $this->pos2;
        $pos = new Vector3(min($pos1->x, $pos2->x), min($pos1->y, $pos2->y), min($pos1->z, $pos2->z));
        for($x = 0; $x <= abs($pos1->x - $pos2->x); $x++)
            for($y = 0; $y <= abs($pos1->y - $pos2->y); $y++)
                for($z = 0; $z <= abs($pos1->z - $pos2->z); $z++) {
                    $this->spawnBlocks[$x][$y][$z]["id"] = $level->getBlock($pos->add($x, $y, $z))->getId();
                    $this->spawnBlocks[$x][$y][$z]["damage"] = $level->getBlock($pos->add($x, $y, $z))->getDamage();
                }
    }

    /**
     * Paste the spawn structure.
     *
     * @param  \pocketmine\level\Level $level
     * @return void
     */

    public function pasteStructure(Level $level) {
        $pos = $this->copypos;
        $config = $this->getSpawnConfig();
        $blocks = $config->get("blocks");
        $time = microtime(true);
        for($x = 0; $x < count(array_keys($blocks)); $x++)
            for($y = 0; $y < count(array_keys($blocks[$x])); $y++)
                for($z = 0; $z < count(array_keys($blocks[$x][$y])); $z++) {
                    if(!$level->isChunkLoaded($x >> 4, $z >> 4)) $level->loadChunk($x >> 4, $z >> 4, true);
                    $level->setBlockIdAt($pos->x + $x, $pos->y + $y, $pos->z + $z, $blocks[$x][$y][$z]["id"]);
                    $level->setBlockDataAt($pos->x + $x, $pos->y + $y, $pos->z + $z, $blocks[$x][$y][$z]["damage"]);
                    #echo "blocks set\n";
                    #echo "X: " . ($pos->x + $x) . "\n";
                    #echo "Y: " . ($pos->y + $y) . "\n";
                    #echo "Z: " . ($pos->z + $z) . "\n";
                    #var_dump("Copypos: " . $this->copypos);
                    #var_dump("Pos: " . $pos);
                }
        echo "[debug] spawn platform completed. Took " . round((microtime(true) - $time), 2) ." seconds.\n";
        #$level->doChunkGarbageCollection();
    }

    /**
     * Get the default spawnpoint coordinates.
     *
     * @param  void
     * @return \pocketmine\math\Vector3
     */

    public function getDefaultSpawnpoint() { return $this->spawnpoint; }

    /**
     * Set the default spawnpoint coordinates.
     *
     * @param  \pocketmine\math\Vector3
     * @return void
     */

    public function setDefaultSpawnpoint(Vector3 $pos) { $this->spawnpoint = $pos; }

    /**
     * Save the spawn data into config to use it later.
     *
     * @param  void
     * @return void
     */

    public function saveSpawnData() {
        $config = new Config($this->plugin->getDataFolder() . "spawndata.json", Config::JSON);
        $config->set("blocks", $this->spawnBlocks);
        $config->set("spawnpoint", [
                "x" => $this->spawnpoint->x,
                "y" => $this->spawnpoint->y,
                "z" => $this->spawnpoint->z
            ]
        );
        $config->save();
        var_dump($this->spawnBlocks);
    }

    /**
     * Get the spawn data config.
     *
     * @param  void
     * @return \pocketmine\utils\Config
     */

    public function getSpawnConfig() {
        return  new Config($this->plugin->getDataFolder() . "spawndata.json", Config::JSON);
    }

    /**
     * Create a new data array for the world and add it to the config.
     *
     * @param  string $worldname
     * @return void
     */

    public function addWorldToConfig(string $worldname) {
        $config = $this->getWorldConfig();
        $data = [
            "players" => [],
            "waittime" => -1,
            "gametime" => -1,
            "enableDamage" => false
        ];
        $config->set($worldname, $data);
        $config->save();
    }

    /**
     * Get the config that stores all world data.
     *
     * @param  void
     * @return \pocketmine\utils\Config
     */

    public function getWorldConfig() {
        return new Config($this->plugin->getDataFolder() . "worlds.json", Config::JSON);
    }

    /**
     * Get the wait time of the world specified.
     *
     * @param  string $worldname
     * @return int
     */

    public function getWaittime(string $worldname) {
        $worlddata = $this->getWorldConfig()->get($worldname);
        return $worlddata["waittime"];
    }

    /**
     * Get the game time of the world specified.
     *
     * @param  string $worldname
     * @return int
     */

    public function getGametime(string $worldname) {
        $worlddata = $this->getWorldConfig()->get($worldname);
        return $worlddata["gametime"];
    }

    /**
     * Set the wait time of the world specified.
     *
     * @param  string $worldname
     * @param  int    $waittime
     * @return void
     */

    public function setWaittime(string $worldname, int $waittime) {
        $config = $this->getWorldConfig();
        $worlddata = $config->get($worldname);
        $worlddata["waittime"] = $waittime;
        $config->set($worldname, $worlddata);
        $config->save();
    }

    /**
     * Set the game time of the world specified.
     *
     * @param  string $worldname
     * @param  int    $gametime
     * @return void
     */

    public function setGametime(string $worldname, int $gametime) {
        $config = $this->getWorldConfig();
        $worlddata = $config->get($worldname);
        $worlddata["gametime"] = $gametime;
        $config->set($worldname, $worlddata);
        $config->save();
    }

    /**
     * Get the count of all registered UHC worlds.
     *
     * @param  void
     * @return int
     */

    public function getWorldCount() { return count($this->getWorldConfig()->getAll()); }

    /**
     * Teleport a player to the spawnpoint of the world specified.
     *
     * @param  \pocketmine\Player $player
     * @param  string             $worldname
     * @return bool
     */

    public function teleportToWorldSpawn(Player $player, string $worldname) {
        $config = $this->getWorldConfig();
        $worlddata = $config->get($worldname);
        if(!$this->plugin->getServer()->isLevelGenerated($worldname)) return false;
        if(!$this->plugin->getServer()->isLevelLoaded($worldname)) {
            $player->sendMessage($this->plugin->debug . " Level is not loaded, loading level...");
            if(!$this->plugin->getServer()->loadLevel($worldname))
                $player->sendMessage($this->plugin->debug . " Something went wrong.");
                return false;
        }
        $world = $this->getLevelInstance($worldname);
        $player->teleport($world->getSpawnLocation(), 0, 0);
        # this does nothing
        #$this->loadSpawnChunks($worldname);
        return true;
    }

    /**
     * Add a player to the array of players for the world specified.
     *
     * @param  \pocketmine\Player $player
     * @param  string             $worldname
     * @return bool
     */

    public function addPlayer(Player $player, string $worldname) {
        $name = $player->getName();
        if($name === null || $name === "") return false;
        $config = $this->getWorldConfig();
        $worlddata = $config->get($worldname);
        $worlddata["players"][$name] = 0; // 0 = playing; 1 = spectating
        $config->set($worldname, $worlddata);
        $config->save();
        return true;
    }

    /**
     * Remove a player from the array of players for the world specified.
     *
     * @param  \pocketmine\Player $player
     * @param  string             $worldname
     * @return bool
     */

    public function removePlayer(Player $player, string $worldname) {
        $name = $player->getName();
        if($name === null || $name === "") return false;
        $config = $this->getWorldConfig();
        $worlddata = $config->get($worldname);
        if(isset($worlddata["players"][$name])) unset($worlddata["players"][$name]);
        $config->set($worldname, $worlddata);
        $config->save();
        return true;
    }

    /**
     * Remove all players from the array of players for the world specified.
     *
     * @param  string $worldname
     * @return void
     */

    public function removeAllPlayers(string $worldname) {
        $config = $this->getWorldConfig();
        $worlddata = $config->get($worldname);
        if(!empty($worlddata["players"])) $worlddata["players"] = [];
        $config->set($worldname, $worlddata);
        $config->save();
    }

    /**
     * Reset all config data for the world specified.
     *
     * @param  string $worldname
     * @return void
     */

    public function resetAll(string $worldname) {
        $this->setWaittime($worldname, -1);
        $this->setGametime($worldname, -1);
        $this->removeAllPlayers($worldname);
        $this->disableDamage($worldname);
    }

    /**
     * Get the game state of the world specified.
     *
     * @param  string $worldname
     * @return string
     */

    public function getSignState(string $worldname) {
        $countPlayers = $this->getPlayersCount($worldname);
        $maxPlayers = $this->getMaxPlayers($worldname);
        if($countPlayers < $maxPlayers)
            return "JOIN";
        if($countPlayers === $maxPlayers)
            return "FULL";
        if($this->getGametime($worldname) !== -1) // game has started
            return "IN-GAME";
    }

    /**
     * Get the number of max players per UHC game.
     *
     * @param  void
     * @return int
     */

    public function getMaxPlayers() {
        return self::MAX_PLAYERS;
    }

    /**
     * Get the number of players in the world specified.
     *
     * @param  string $worldname
     * @return int
     */

    public function getPlayersCount(string $worldname) {
        return count($this->getPlayers($worldname));
    }

    /**
     * Get the players in the world specified.
     *
     * @param  string $worldname
     * @return bool|array
     */

    public function getPlayers(string $worldname) {
        if(!$this->worldExists($worldname)) return false;
        return $this->getLevelInstance($worldname)->getPlayers();
    }

    /**
     * Check wether the world specified exists.
     *
     * @param  string $worldname
     * @return bool
     */

     public function worldExists(string $worldname) {
         $config = $this->getWorldConfig();
         $worlds = $config->getAll();
         if(!(isset($worlds[$worldname]))) return false;
         if(!($this->plugin->getServer()->isLevelGenerated($worldname))) return false;
         return true;
     }

    /**
     * Get a level instance from the world name specified.
     *
     * @param  string $worldname
     * @return \pocketmine\level\Level
     */

    public function getLevelInstance(string $worldname) {
        if(!$this->plugin->getServer()->isLevelLoaded($worldname))
            $this->plugin->getServer()->loadLevel($worldname);
        return $this->plugin->getServer()->getLevelByName($worldname);
    }

    /**
     * Enable damage in the world specified.
     *
     * @param  string $worldname
     * @return void
     */

    public function enableDamage(string $worldname) {
        $config = $this->getWorldConfig();
        $worlddata = $config->get($worldname);
        $worlddata["enableDamage"] = true;
        $config->set($worldname, $worlddata);
        $config->save();
    }

    /**
     * Enable damage in the world specified.
     *
     * @param  string $worldname
     * @return void
     */

    public function disableDamage(string $worldname) {
        $config = $this->getWorldConfig();
        $worlddata = $config->get($worldname);
        $worlddata["enableDamage"] = false;
        $config->set($worldname, $worlddata);
        $config->save();
    }

    /**
     * Check wether damage is enabled in the world specified.
     *
     * @param  string $worldname
     * @return bool
     */

    public function isDamageEnabled(string $worldname) {
        $config = $this->getWorldConfig();
        $worlddata = $config->get($worldname);
        return $worlddata["enableDamage"];
    }

    /**
     * Get an instance of this class.
     *
     * @param  void
     * @return UHCWorld
     */

    public static function getInstance() {
        return self::$instance;
    }

    /** SERVER EVENTS **/

    /**
     * Cancel the event if damage is disabled for the world.
     *
     * @param  \pocketmine\event\entity\EntityDamageEvent $event
     * @return void
     */

    public function onDamage(EntityDamageEvent $event) {
        $config = $this->getWorldConfig();
        $worlds = $config->getAll();
        foreach($worlds as $worldname => $data)
            if(!$this->isDamageEnabled($worldname)) $event->setCancelled(true);
            else $event->setCancelled(false);
    }

    /**
     * Call pasteStructure() when ChunkPopulateEvent is fired by the spawn chunk.
     *
     * @param  \pocketmine\event\level\ChunkPopulateEvent $event
     * @return bool
     */

    public function onChunkPopulate(ChunkPopulateEvent $event) {
        if(!isset($this->worldname)) return false;
        $level = $this->getLevelInstance($this->worldname);
        $x = 128;
        $y = 100;
        $z = 128;
        $chunkX = $x >> 4;
        $chunkZ = $z >> 4;
        if($event->getLevel() !== $level) return false;
        if($event->getChunk()->getX() === $chunkX && $event->getChunk()->getZ() === $chunkZ) {
            $this->pasteStructure($level);
            return true;
        }
    }

    /**
     * Teleport the player to the server's default world whenver they join the game.
     *
     * @param  \pocketmine\event\player\PlayerJoinEvent $event
     * @return void
     */

     public function onJoin(PlayerJoinEvent $event) {
         $player = $event->getPlayer();
         $defaultLevel = $this->plugin->getServer()->getDefaultLevel();
         if($player->getLevel() !== $defaultLevel)
            $player->teleport($defaultLevel->getSpawnLocation(), 0, 0);
     }
}

?>
