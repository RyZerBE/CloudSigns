<?php


namespace BauboLP\CloudSigns\Provider;


use BauboLP\CloudSigns\Utils\CloudSign;
use pocketmine\utils\Config;

class ConfigProvider
{

    const PATH = "/root/RyzerCloud/data/cloudsigns.json";
    private $config;

    public function __construct()
    {
        if(!file_exists(self::PATH)) {
            $c = new Config(self::PATH, Config::JSON);
            $c->set("signs", []);
            $c->save();
        }

        if(!is_dir('/root/RyzerCloud/servers/blacklist'))
            mkdir('/root/RyzerCloud/servers/blacklist');

        $this->config = new Config(self::PATH, Config::JSON);
    }

    /**
     * @param \BauboLP\CloudSigns\Utils\CloudSign $cloudSign
     */
    public function registerSign(CloudSign $cloudSign): void
    {
        $c = new Config(self::PATH, Config::JSON);
        $signs = $c->get("signs");
        $signs[$cloudSign->getVector3AString()] = ['group' => $cloudSign->getGroup(), 'maintance' => false, 'maxPlayers' => 0];
        $c->set('signs', $signs);
        $c->save();
    }

    /**
     * @param \BauboLP\CloudSigns\Utils\CloudSign $cloudSign
     */
    public function unregisterSign(CloudSign $cloudSign): void
    {
        $c = new Config(self::PATH, Config::JSON);
        $signs = $c->get("signs");
        unset($signs[$cloudSign->getVector3AString()]);
        $c->set('signs', $signs);
        $c->save();
    }

    /**
     * @param string $group
     * @param bool $maintenance
     */
    public function setMaintenance(string $group, bool $maintenance): void
    {
        $json = [];
        foreach ($this->getConfig()->get('signs') as $key => $data) {
           if($data['group'] == $group) {
               $data['maintance'] = $maintenance;
               $json[$key] = $data;

               CloudSignProvider::getCloudSignByPosition($key)->setMaintenance($maintenance);
               if($maintenance) {
                   CloudSignProvider::getCloudSignByPosition($key)->setState(CloudSign::MAINTENANCE);
               }else {
                   CloudSignProvider::getCloudSignByPosition($key)->setState(CloudSign::SEARCH);
                 //  CloudSignProvider::getCloudSignByPosition($key)->setFounder(null);
               }
           }else {
               $json[$key] = $data;
           }
        }

        $this->getConfig()->set('signs', $json);
        $this->getConfig()->save();
        $this->reloadConfig();
    }

    /**
     * @param string $group
     * @return mixed
     */
    public function getMaxPlayers(string $group)
    {
        $pos = "";
        foreach (CloudSignProvider::getCloudSigns() as $cloudSign) {
            if($cloudSign->getGroup() == $group) {
                $pos .= $cloudSign->getVector3AString();
                break;
            }
        }
        return $this->getConfig()->get('signs')[$pos]['maxPlayers'];
    }

    /**
     * @param int $maxPlayers
     * @param string $group
     */
    public function setMaxPlayers(int $maxPlayers, string $group): void
    {
        $json = [];
        foreach ($this->getConfig()->get('signs') as $key => $data) {
            if ($data['group'] == $group) {
                $data['maxPlayers'] = $maxPlayers;
                $json[$key] = $data;

                CloudSignProvider::getCloudSignByPosition($key)->setMaxPlayers($maxPlayers);
            }else {
                $json[$key] = $data;
            }
        }

        $this->getConfig()->set('signs', $json);
        $this->getConfig()->save();
        $this->reloadConfig();
    }

    /**
     * @return \pocketmine\utils\Config
     */
    public function getConfig(): \pocketmine\utils\Config
    {
        return $this->config;
    }

    public function reloadConfig(): void
    {
        $this->config = new Config(self::PATH);
    }
}