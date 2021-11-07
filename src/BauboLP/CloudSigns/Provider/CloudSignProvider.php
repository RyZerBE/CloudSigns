<?php


namespace BauboLP\CloudSigns\Provider;


use BauboLP\Cloud\CloudBridge;
use BauboLP\Cloud\Provider\CloudProvider;
use BauboLP\CloudSigns\Main;
use BauboLP\CloudSigns\Utils\CloudSign;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\utils\MainLogger;
use function floor;

class CloudSignProvider
{
    /** @var \BauboLP\CloudSigns\Utils\CloudSign[] */
    private static $signs = [];
    /** @var array  */
    private static $servers = [];
    /** @var array  */
    public static $registerSigns = [];
    /** @var array  */
    public static $unregisterSigns = [];
    /** @var array  */
    public static $infoSign = [];

    /**
     * @return \BauboLP\CloudSigns\Utils\CloudSign[]
     */
    public static function getCloudSigns(): array
    {
        return self::$signs;
    }

    /**
     * @param string $group
     * @return array
     */
    public static function getCloudSignByGroup(string $group): array
    {
        $signs = [];
        foreach (self::getCloudSigns() as $cloudSign) {
            if($cloudSign->getGroup() == $group) {
                $signs[] = $cloudSign;
            }
        }
        return $signs;
    }

    public static function loadCloudSigns(): void
    {
        CloudSignProvider::$signs = [];
        CloudSignProvider::$servers = [];

        $c = Main::getConfigProvider()->getConfig();
        $json = $c->get('signs');
        if($json == null) {
            MainLogger::getLogger()->critical("JSON Config occurred an error!");
            return;
        }
        foreach ($json as $key => $data) {
            $ex = explode(":", $key);
            $vector3 = new Vector3((float)$ex[0], (float)$ex[1], (float)$ex[2]);
            CloudSignProvider::addSign(new CloudSign($vector3, $data['group'], $data['maintance']));
        }
    }
    /**
     * @param \BauboLP\CloudSigns\Utils\CloudSign $cloudSign
     */
    public static function addSign(CloudSign $cloudSign)
    {
        self::$signs[$cloudSign->getVector3AString()] = $cloudSign;
    }

    /**
     * @param string $coords
     * @return \BauboLP\CloudSigns\Utils\CloudSign|null
     */
    public static function getCloudSignByPosition(string $coords): ?CloudSign
    {
        if(empty(self::getCloudSigns()[$coords])) return null;
        return self::getCloudSigns()[$coords];
    }

    /**
     * @param \BauboLP\CloudSigns\Utils\CloudSign $cloudSign
     */
    public static function removeSign(CloudSign $cloudSign)
    {
        unset(self::$signs[array_search($cloudSign, self::$signs)]);
    }

    /**
     * @param \BauboLP\CloudSigns\Utils\CloudSign $cloudSign
     */
    public static function registerSign(CloudSign $cloudSign): void
    {
        Main::getConfigProvider()->registerSign($cloudSign);
        Main::getConfigProvider()->reloadConfig();
        self::addSign($cloudSign);
    }

    /**
     * @param \BauboLP\CloudSigns\Utils\CloudSign $cloudSign
     */
    public static function unregisterSign(CloudSign $cloudSign)
    {
        if($cloudSign->getFounder() != null) {
            self::removeServer($cloudSign->getFounder());
        }
        Main::getConfigProvider()->unregisterSign($cloudSign);
        self::removeSign($cloudSign);
    }

    /**
     * @param \pocketmine\block\Block $block
     * @return bool
     */
    public static function isCloudSign(Block $block): bool
    {
        $block = "{$block->x}:{$block->y}:{$block->z}";
        return isset(self::getCloudSigns()[$block]);
    }

    public static function getQueryInfo(string $address, int $port, int $timeout = 4)
    {
        $socket = @fsockopen('udp://' . $address, $port, $errno, $errstr, $timeout);
        if($errno or $socket === false) {
            return FALSE;
        }
        stream_Set_Timeout($socket, $timeout);
        stream_Set_Blocking($socket, true);
        $randInt = mt_rand(1, 999999999);
        $reqPacket = "\x01";
        $reqPacket .= pack('Q*', $randInt);
        $reqPacket .= "\x00\xff\xff\x00\xfe\xfe\xfe\xfe\xfd\xfd\xfd\xfd\x12\x34\x56\x78";
        $reqPacket .= pack('Q*', 0);
        fwrite($socket, $reqPacket, strlen($reqPacket));
        $response = fread($socket, 4096);
        fclose($socket);
        if (empty($response) or $response === false) {
            return FALSE;
        }
        if (substr($response, 0, 1) !== "\x1C") {
            return FALSE;
        }
        $serverInfo = substr($response, 35);
        $serverInfo = explode(';', $serverInfo);
        return [
            'serverName' => $serverInfo[1],
            'online' => $serverInfo[4],
            'max' => $serverInfo[5],
            'version' =>  $serverInfo[3]
        ];
    }

    /**
     * @param string $serverName
     * @return bool
     */
    public static function isServerFree(string $serverName)
    {
        return !in_array($serverName, self::$servers) && !CloudBridge::getCloudProvider()->isServerBlacklisted($serverName) && !CloudBridge::getCloudProvider()->isServerPrivate($serverName);
    }

    /**
     * @param string $serverName
     */
    public static function addServer(string $serverName): void
    {
        self::$servers[] = $serverName;
    }

    /**
     * @param string $serverName
     */
    public static function removeServer(string $serverName): void
    {
        unset(self::$servers[array_search($serverName, self::$servers)]);
    }

    /**
     * @return array
     */
    public static function getSelectedServers(): array
    {
        return self::$servers;
    }
}