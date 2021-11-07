<?php


namespace BauboLP\CloudSigns\Tasks;


use BauboLP\CloudSigns\Provider\CloudSignProvider;
use BauboLP\CloudSigns\Utils\CloudSign;
use pocketmine\scheduler\Task;

class RefreshSignTask extends Task
{

    public function onRun(int $currentTick) {
        foreach (CloudSignProvider::getCloudSigns() as $cloudSign) {
            if($cloudSign instanceof CloudSign) {
                if ($cloudSign->nearPlayers()) $cloudSign->refreshSign();
            }
        }
    }
}