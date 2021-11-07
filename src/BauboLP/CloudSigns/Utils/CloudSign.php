<?php

namespace BauboLP\CloudSigns\Utils;

use BauboLP\Cloud\CloudBridge;
use BauboLP\CloudSigns\Provider\CloudSignProvider;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\tile\Sign;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use function explode;

class CloudSign {
    public const SEARCH = 1;
    public const FULL = 2;
    public const JOIN = 3;
    public const MAINTENANCE = 4;

    public const IP = "5.181.151.61";

    private static array $faces = [
        2 => 3,
        3 => 2,
        4 => 5,
        5 => 4,
    ];

    private array $animation = [
        "▁",
        "▁ ▂",
        "▁ ▂ ▃",
        "▁ ▂ ▃ ▅",
        "▁ ▂ ▃ ▅ ▆",
        "▁ ▂ ▃ ▅ ▆ ▇",
        "▁ ▂ ▃ ▅ ▆ ▇ ▉",
        "▁ ▂ ▃ ▅ ▆ ▇",
        "▁ ▂ ▃ ▅ ▆",
        "▁ ▂ ▃ ▅",
        "▁ ▂ ▃",
        "▁ ▂",
        "▁",
        " ",
    ];

    private string $group;
    private int $state;
    private ?string $founder;
    private Vector3 $vector3;
    private bool $maintenance;
    private int $animationCount;
    private int $players = 0;
    private int $maxPlayers = 0;
    private int $port = 0;

    public function __construct(Vector3 $vector3, string $group, bool $maintenance){
        $this->group = $group;
        $this->state = ($maintenance == true) ? self::MAINTENANCE : self::SEARCH;
        $this->founder = null;
        $this->vector3 = $vector3;
        $this->maintenance = $maintenance;
        $this->animationCount = 1;
    }

    public function getVector3AString(): string{
        return "{$this->getVector3()->x}:{$this->getVector3()->y}:{$this->getVector3()->z}";
    }

    public function getVector3(): Vector3{
        return $this->vector3;
    }

    public function setMaintenance(bool $maintenance): void{
        $this->maintenance = $maintenance;
    }

    public function nearPlayers(): bool{
        foreach(Server::getInstance()->getOnlinePlayers() as $player){
            if($player->distanceSquared($this->getVector3()) <= 100){
                return true;
            }
        }
        return false;
    }

    public function isBlacklisted(): bool{
        return CloudBridge::getCloudProvider()->isServerBlacklisted($this->getFounder() ?? "#CoVid19");
    }

    public function getFounder(): ?string{
        return $this->founder;
    }

    public function refreshSign(): void{
        $sign = Server::getInstance()->getDefaultLevel()->getTile($this->getVector3());
        if($this->getFounder() == null && $this->isMaintenance() == false){
            $this->setState(self::SEARCH);
        }
        if($sign instanceof Sign){
            if($this->state === CloudSign::SEARCH){
                if($this->animationCount == 6){
                    $servers = scandir("/root/RyzerCloud/servers");
                    $sortedServers = $this->sortServerArray($servers);
                    foreach($sortedServers as $server){
                        if($server != "." && $server != ".."){
                            $group = explode("-", $server)[0];
                            if($group === $this->getGroup() && $this->getFounder() === null){
                                if(CloudSignProvider::isServerFree($server)){
                                    if(file_exists("/root/RyzerCloud/servers/$server/server.properties")){
                                        $this->setFounder($server);
                                        $c = new Config("/root/RyzerCloud/servers/$server/server.properties");
                                        $port = $c->get("server-port");
                                        $this->setServerPort($port);
                                        CloudSignProvider::addServer($server);
                                    }
                                }
                                else{
                                    if(CloudBridge::getCloudProvider()->isServerBlacklisted($server) || !is_bool(CloudBridge::getCloudProvider()->isServerPrivate($server))){
                                        CloudSignProvider::removeServer($server);
                                    }
                                }
                            }
                        }
                    }
                }
                $animation = $this->getAnimation();
                $sign->setText("", TextFormat::RED . "Server loading", $animation, "");
            }
            else{
                if($this->state == CloudSign::FULL){
                    if(CloudBridge::getCloudProvider()->isServerBlacklisted($this->getFounder())){
                        self::setState(self::SEARCH);
                        self::setFounder(null);
                    }
                    $sign->setText(TextFormat::YELLOW . $this->getFounder(), TextFormat::RED . "FULL", TextFormat::RED . $this->getPlayers() . TextFormat::GRAY . " / " . TextFormat::RED . $this->getMaxPlayers(), TextFormat::GOLD . "ONLY VIP");
                }
                else{
                    if($this->state == CloudSign::MAINTENANCE){
                        $animation = $this->getAnimation();
                        $sign->setText("", TextFormat::DARK_RED . "Maintenance", $animation, "");
                    }
                    else{
                        if($this->state == CloudSign::JOIN){
                            if(!is_dir("/root/RyzerCloud/servers/{$this->getFounder()}")){
                                CloudSignProvider::removeServer($this->getFounder());
                                $this->setState(self::SEARCH);
                                $this->setFounder(null);
                                return;
                            }
                            if(CloudBridge::getCloudProvider()->isServerBlacklisted($this->getFounder())){
                                CloudSignProvider::removeServer($this->getFounder());
                                self::setState(self::SEARCH);
                                self::setFounder(null);
                                return;
                            }
                            if($this->getPlayers() >= $this->getMaxPlayers() / 2){
                                $color = TextFormat::YELLOW;
                            }
                            else{
                                $color = TextFormat::GREEN;
                            }
                            $sign->setText((strlen($this->getFounder()) < 11) ? TextFormat::WHITE . "» " . TextFormat::YELLOW . $this->getFounder() . TextFormat::WHITE . " «" : TextFormat::YELLOW . $this->getFounder(), TextFormat::GREEN . "LOBBY", $color . $this->getPlayers() . TextFormat::GRAY . " / " . TextFormat::RED . $this->getMaxPlayers(), TextFormat::GRAY . "");
                            if($this->getPlayers() >= $this->getMaxPlayers()){
                                self::setState(self::FULL);
                            }
                        }
                    }
                }
            }
            $server = Server::getInstance();
            if(isset(self::$faces[$sign->getBlock()->getDamage()])){
                if($this->getState() == CloudSign::JOIN){
                    $server->getDefaultLevel()->setBlock($sign->getBlock()->getSide(self::$faces[$sign->getBlock()->getDamage()]), Block::get(Block::TERRACOTTA, 5));
                }
                else{
                    if($this->getState() == CloudSign::FULL || $this->getState() == CloudSign::MAINTENANCE || $this->getState() == CloudSign::SEARCH){
                        $server->getDefaultLevel()->setBlock($sign->getBlock()->getSide(self::$faces[$sign->getBlock()->getDamage()]), Block::get(Block::TERRACOTTA, 14));
                    }
                }
            }
        }
    }

    public function isMaintenance(): bool{
        return $this->maintenance;
    }

    public function setState(int $state): void{
        $this->state = $state;
    }

    private function sortServerArray(array $array): array{
        $sortArray = [];
        foreach($array as $server){
            if($server != "." && $server != ".." && $server != "blacklist"){
                $sortArray[$server] = explode("-", $server)[1];
            }
        }
        $serverList = [];
        asort($sortArray);
        foreach(array_keys($sortArray) as $serverName){
            $serverList[] = $serverName;
        }
        return $serverList;
    }

    public function getGroup(): string{
        return $this->group;
    }

    public function setFounder(?string $founder): void{
        $this->founder = $founder;
    }

    public function setServerPort(int $port): void{
        $this->port = $port;
    }

    public function getAnimation(){
        $this->animationCount++;
        if(empty($this->animation[$this->animationCount - 1])){
            $this->animationCount = 0;
            return "";
        }
        return TextFormat::WHITE . $this->animation[$this->animationCount - 1];
    }

    public function getPlayers(): int{
        return $this->players;
    }

    public function getMaxPlayers(): int{
        return $this->maxPlayers;
    }

    public function getState(): int{
        return $this->state;
    }

    public function setMaxPlayers(int $maxPlayers): void{
        $this->maxPlayers = $maxPlayers;
    }

    public function setPlayers(int $players): void{
        $this->players = $players;
    }

    public function getServerPort(): int{
        return $this->port;
    }
}