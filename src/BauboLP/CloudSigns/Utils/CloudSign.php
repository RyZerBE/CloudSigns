<?php


namespace BauboLP\CloudSigns\Utils;


use BauboLP\Cloud\CloudBridge;
use BauboLP\CloudSigns\Provider\CloudSignProvider;
use BauboLP\CloudSigns\Provider\ConfigProvider;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\tile\Sign;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use function explode;

class CloudSign
{

    const SEARCH = 1;
    const FULL = 2;
    const JOIN = 3;
    const MAINTENANCE = 4;

    const IP = "5.181.151.61";

    /** @var String */
    private $group;
    /** @var int */
    private $state;
    /** @var String|null */
    private $founder;
    /** @var \pocketmine\math\Vector3 */
    private $vector3;
    /** @var bool */
    private $maintenance;
    /** @var int */
    private $animationCount;
    /** @var int */
    private $players = 0;
    /** @var int */
    private $maxPlayers = 0;
    /** @var int  */
    private $port = 0;

    private static $faces = [
        2 => 3,
        3 => 2,
        4 => 5,
        5 => 4,
        ];


    private $animation = [
        '▁',
        '▁ ▂',
        '▁ ▂ ▃',
        '▁ ▂ ▃ ▅',
        '▁ ▂ ▃ ▅ ▆',
        '▁ ▂ ▃ ▅ ▆ ▇',
        '▁ ▂ ▃ ▅ ▆ ▇ ▉',
        '▁ ▂ ▃ ▅ ▆ ▇',
        '▁ ▂ ▃ ▅ ▆',
        '▁ ▂ ▃ ▅',
        '▁ ▂ ▃',
        '▁ ▂',
        '▁',
        ' '
    ];

    public function __construct(Vector3 $vector3, string $group, bool $maintenance)
    {
        $this->group = $group;
        $this->state = ($maintenance == true) ? self::MAINTENANCE : self::SEARCH;
        $this->founder = null;
        $this->vector3 = $vector3;
        $this->maintenance = $maintenance;
        $this->animationCount = 1;
    }

    /**
     * @return String
     */
    public function getGroup(): String
    {
        return $this->group;
    }

    /**
     * @return String|null
     */
    public function getFounder(): ?String
    {
        return $this->founder;
    }

    /**
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @param String|null $founder
     */
    public function setFounder(?String $founder): void
    {
        $this->founder = $founder;
    }

    /**
     * @param int $state
     */
    public function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * @return \pocketmine\math\Vector3
     */
    public function getVector3(): \pocketmine\math\Vector3
    {
        return $this->vector3;
    }

    /**
     * @return string
     */
    public function getVector3AString(): string
    {
        return "{$this->getVector3()->x}:{$this->getVector3()->y}:{$this->getVector3()->z}";
    }

    /**
     * @return bool
     */
    public function isMaintenance(): bool
    {
        return $this->maintenance;
    }

    /**
     * @param bool $maintenance
     */
    public function setMaintenance(bool $maintenance): void
    {
        $this->maintenance = $maintenance;
    }

    public function getAnimation()
    {
        $this->animationCount++;
        if(empty($this->animation[$this->animationCount - 1])){
            $this->animationCount = 0;
            return "";
        }
        return TextFormat::WHITE . $this->animation[$this->animationCount - 1];
    }

    /**
     * @param array $array
     * @return array
     */
    private function sortServerArray(array $array): array{
        $sortArray = [];
        foreach ($array as $server) {
          if($server != "." && $server != ".." && $server != "blacklist")
            $sortArray[$server] = explode("-", $server)[1];
        }

        $serverList = [];
        asort($sortArray);
        foreach (array_keys($sortArray) as $serverName) {
            $serverList[] = $serverName;
        }

        return $serverList; #Wird sortiert, damit auf verschiedenen Lobbys die selben Schilder angezeigt werden ;)
    }

    public function nearPlayers(): bool
    {
        foreach(Server::getInstance()->getOnlinePlayers() as $player) {
            if($player->distanceSquared($this->getVector3()) <= 100) {
                return true;
            }
        }
        return false;
    }

    public function isBlacklisted(): bool
    {
        return CloudBridge::getCloudProvider()->isServerBlacklisted($this->getFounder() ?? "#CoVid19");
    }
    
    public function refreshSign(): void
    {
        $sign = Server::getInstance()->getDefaultLevel()->getTile($this->getVector3());
        if ($this->getFounder() == null && $this->isMaintenance() == false) {
            $this->setState(self::SEARCH);
        }
        if ($sign instanceof Sign) {
            if ($this->state === CloudSign::SEARCH) {
                if ($this->animationCount == 6) {
                    $servers = scandir("/root/RyzerCloud/servers");
                    $sortedServers = $this->sortServerArray($servers);

                    foreach ($sortedServers as $server) {
                        if ($server != "." && $server != "..") {
                            $group = explode("-", $server)[0];
                            if ($group === $this->getGroup() && $this->getFounder() === null) {
                                if (CloudSignProvider::isServerFree($server)) {
                                    if (file_exists("/root/RyzerCloud/servers/$server/server.properties")) {
                                        $this->setFounder($server);
                                        $c = new Config("/root/RyzerCloud/servers/$server/server.properties");
                                        $port = $c->get("server-port");
                                        $this->setServerPort($port);
                                        CloudSignProvider::addServer($server);
                                    }
                                }else {
                                    if(CloudBridge::getCloudProvider()->isServerBlacklisted($server) || !is_bool(CloudBridge::getCloudProvider()->isServerPrivate($server)))
                                    CloudSignProvider::removeServer($server);
                                }
                            }
                        }
                    }
                }
                $animation = $this->getAnimation();
                $sign->setText(
                    "",
                    TextFormat::RED . "Server loading",
                    $animation,
                    ""
                );

            } else if ($this->state == CloudSign::FULL) {
                if (CloudBridge::getCloudProvider()->isServerBlacklisted($this->getFounder())) {
                    self::setState(self::SEARCH);
                    self::setFounder(null);
                }
                $sign->setText(
                    TextFormat::YELLOW . $this->getFounder(),
                    TextFormat::RED . "FULL",
                    TextFormat::RED . $this->getPlayers() . TextFormat::GRAY . " / " . TextFormat::RED . $this->getMaxPlayers(),
                    TextFormat::GOLD . "ONLY VIP"
                );
            } else if ($this->state == CloudSign::MAINTENANCE) {
                $animation = $this->getAnimation();
                $sign->setText(
                    "",
                    TextFormat::DARK_RED . "Maintenance",
                    $animation,
                    ""
                );
            } else if ($this->state == CloudSign::JOIN) {
                if (!is_dir("/root/RyzerCloud/servers/{$this->getFounder()}")) {
                    CloudSignProvider::removeServer($this->getFounder());
                    $this->setState(self::SEARCH);
                    $this->setFounder(null);
                    return;
                }
                if (CloudBridge::getCloudProvider()->isServerBlacklisted($this->getFounder())) {
                    CloudSignProvider::removeServer($this->getFounder());
                    self::setState(self::SEARCH);
                    self::setFounder(null);
                    return;
                }
                if ($this->getPlayers() >= $this->getMaxPlayers() / 2) {
                    $color = TextFormat::YELLOW;
                } else {
                    $color = TextFormat::GREEN;
                }
                $sign->setText(
                    (strlen($this->getFounder()) < 11) ? TextFormat::WHITE . "» " . TextFormat::YELLOW . $this->getFounder() . TextFormat::WHITE . " «" : TextFormat::YELLOW . $this->getFounder(),
                    TextFormat::GREEN . "LOBBY",
                    $color . $this->getPlayers() . TextFormat::GRAY . " / " . TextFormat::RED . $this->getMaxPlayers(),
                    TextFormat::GRAY . ""
                );

                if ($this->getPlayers() >= $this->getMaxPlayers()) {
                    self::setState(self::FULL);
                }
            }

            $server = Server::getInstance();
            if (isset(self::$faces[$sign->getBlock()->getDamage()])) {
                if ($this->getState() == CloudSign::JOIN) {
                    $server->getDefaultLevel()->setBlock($sign->getBlock()->getSide(self::$faces[$sign->getBlock()->getDamage()]), Block::get(Block::TERRACOTTA, 5));
                } else if ($this->getState() == CloudSign::FULL || $this->getState() == CloudSign::MAINTENANCE || $this->getState() == CloudSign::SEARCH) {
                    $server->getDefaultLevel()->setBlock($sign->getBlock()->getSide(self::$faces[$sign->getBlock()->getDamage()]), Block::get(Block::TERRACOTTA, 14));
                }
            }
        }
    }

    /**
     * @param int $maxPlayers
     */
    public function setMaxPlayers(int $maxPlayers): void
    {
        $this->maxPlayers = $maxPlayers;
    }

    /**
     * @param int $players
     */
    public function setPlayers(int $players): void
    {
        $this->players = $players;
    }

    /**
     * @return int
     */
    public function getMaxPlayers(): int
    {
        return $this->maxPlayers;
    }

    /**
     * @return int
     */
    public function getPlayers(): int
    {
        return $this->players;
    }

    /**
     * @return int
     */
    public function getServerPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setServerPort(int $port): void
    {
        $this->port = $port;
    }
}