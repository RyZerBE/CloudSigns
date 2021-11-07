<?php


namespace BauboLP\CloudSigns\Tasks;


use BauboLP\CloudSigns\Main;
use BauboLP\CloudSigns\Provider\CloudSignProvider;
use BauboLP\CloudSigns\Utils\CloudSign;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use function is_bool;

class SignAsyncTask extends AsyncTask {
    private array $founders;

    public function __construct(array $founders){
        $this->founders = $founders;
    }

    public function onRun(): void{
        $data = [];
        foreach ($this->founders as $key => $signData) {
            $info = CloudSignProvider::getQueryInfo($signData['address'], $signData['port']);
            $data[$key] = $info;
        }
        $this->setResult($data);
    }

    public function onCompletion(Server $server): void{
        $result = $this->getResult();
        foreach ($this->founders as $key => $data) {
            foreach (CloudSignProvider::getCloudSigns() as $cloudSign) {
                if ($cloudSign instanceof CloudSign) {
                    if ($cloudSign->getState() != CloudSign::MAINTENANCE) {
                        if ($key == $cloudSign->getVector3AString()) {
                            if (isset($result[$key])) {
                                $resultData = $result[$key];
                                if (!is_bool($resultData) && $resultData['online'] != null && $resultData['max'] != null) {
                                    $cloudSign->setPlayers($resultData['online']);
                                    $maxPlayers = Main::getConfigProvider()->getMaxPlayers($cloudSign->getGroup());
                                    $cloudSign->setMaxPlayers($maxPlayers);
                                    if ($resultData['online'] >= $maxPlayers) {
                                        $cloudSign->setState(CloudSign::FULL);
                                    } else {
                                        $cloudSign->setState(CloudSign::JOIN);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        Main::$isQueryDone = true;
    }
}