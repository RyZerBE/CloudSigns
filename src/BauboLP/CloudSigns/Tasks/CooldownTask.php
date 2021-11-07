<?php


namespace BauboLP\CloudSigns\Tasks;


use BauboLP\CloudSigns\Main;
use BauboLP\CloudSigns\Provider\CloudSignProvider;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class CooldownTask extends Task
{

    public function onRun(int $currentTick)
    {
       foreach (Main::$cooldowns as $player => $time) {
           if($time < time()) {
               unset(Main::$cooldowns[$player]);
           }
       }
    }
}