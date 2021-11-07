<?php


namespace BauboLP\CloudSigns\Tasks;


use BauboLP\CloudSigns\Main;
use BauboLP\CloudSigns\Provider\CloudSignProvider;
use BauboLP\CloudSigns\Utils\CloudSign;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class StartAsyncSignTask extends Task
{

    public function onRun(int $currentTick)
    {
        $data = [];
        foreach (CloudSignProvider::getCloudSigns() as $cloudSign) {
            if ($cloudSign instanceof CloudSign) {
                if ($cloudSign->getFounder() != null) {
                    $data[$cloudSign->getVector3AString()] = ['address' => CloudSign::IP, 'port' => $cloudSign->getServerPort()];
                }
            }
        }

        if(Main::$isQueryDone) {
            Server::getInstance()->getAsyncPool()->submitTask(new SignAsyncTask($data));
        }
    }
}