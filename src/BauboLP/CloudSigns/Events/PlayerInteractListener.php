<?php


namespace BauboLP\CloudSigns\Events;


use BauboLP\Cloud\Bungee\BungeeAPI;
use BauboLP\Cloud\CloudBridge;
use BauboLP\CloudSigns\Main;
use BauboLP\CloudSigns\Provider\CloudSignProvider;
use BauboLP\CloudSigns\Utils\CloudSign;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Server;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

class PlayerInteractListener implements Listener
{

    public function Interact(PlayerInteractEvent $event)
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        if(array_key_exists($player->getName(), Main::$cooldowns)) return;

        $sign = Server::getInstance()->getDefaultLevel()->getTile($block->asVector3());

        if($sign instanceof Sign) {
            if(CloudSignProvider::isCloudSign($block)) {
                Main::$cooldowns[$player->getName()] = time() + 3;

                $cloudSign = CloudSignProvider::getCloudSignByPosition("{$block->x}:{$block->y}:{$block->z}");
                $serverName = $cloudSign->getFounder();
                if($cloudSign->getState() == CloudSign::MAINTENANCE) {
                    if($player->hasPermission("cloud.join.maintenance") && $cloudSign->getFounder() != null) {
                        BungeeAPI::transfer($player->getName(), $serverName);
                    }
                    return;
                }else if($cloudSign->getState() == CloudSign::JOIN) {
                    BungeeAPI::transfer($player->getName(), $serverName);
                }
            }else {
                Main::$cooldowns[$player->getName()] = time() + 3;
                if(TextFormat::clean($sign->getLine(0)) == "oO ClanWar Oo") {
                    if(TextFormat::clean($sign->getLine(2)) == "[Elo]") {
                        CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan cw elo");
                    }else if(TextFormat::clean($sign->getLine(2)) == "[Fun]") {
                        CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan cw fun");
                        //CloudBridge::getCloudProvider()->dispatchProxyCommand($player->getName(), "clan cw <Type>");
                    }
                }
            }
        }
    }
}