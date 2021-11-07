<?php


namespace BauboLP\CloudSigns\Events;


use BauboLP\Cloud\CloudBridge;
use BauboLP\CloudSigns\Provider\CloudSignProvider;
use BauboLP\CloudSigns\Provider\ConfigProvider;
use BauboLP\CloudSigns\Utils\CloudSign;
use BauboLP\Core\Provider\LanguageProvider;
use pocketmine\block\SignPost;
use pocketmine\block\WallSign;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat;

class BlockBreakListener implements Listener
{

    public function onBlockBreak(BlockBreakEvent $event)
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        if (!$player->hasPermission("cloud.signs") && $block instanceof WallSign) {
            $player->sendMessage(CloudBridge::Prefix . LanguageProvider::getMessageContainer('cloudsign-noperm', $player->getName()));
            return;
        }
        if (isset(CloudSignProvider::$registerSigns[$player->getName()])) {
            if ($block instanceof WallSign || $block instanceof SignPost) {
               $event->setCancelled();
                if (CloudSignProvider::isCloudSign($block)) {
                    $player->sendMessage(CloudBridge::Prefix . TextFormat::RED . "Dieses Schild wird bereits verwendet!");
                    return;
                }

                CloudSignProvider::registerSign(new CloudSign($block->asVector3(), CloudSignProvider::$registerSigns[$player->getName()]['group'], false));
                unset(CloudSignProvider::$registerSigns[$player->getName()]);
                $player->sendMessage(CloudBridge::Prefix.TextFormat::GREEN."Das CloudSign wurde erfolgreich registriert.");
            }
        } else if (isset(CloudSignProvider::$unregisterSigns[$player->getName()])) {
            if ($block instanceof WallSign || $block instanceof SignPost) {
                $event->setCancelled();
                $vectorString = "{$block->x}:{$block->y}:{$block->z}";

                if (!CloudSignProvider::isCloudSign($block)) {
                    $player->sendMessage(CloudBridge::Prefix . TextFormat::RED . "Dieses Schild ist KEIN CloudSign!");
                    return;
                }
                CloudSignProvider::unregisterSign(CloudSignProvider::getCloudSignByPosition($vectorString));
                unset(CloudSignProvider::$unregisterSigns[$player->getName()]);
                $player->sendMessage(CloudBridge::Prefix.TextFormat::GREEN."Das CloudSign wurde erfolgreich entfernt.");
            }
        }else if(isset(CloudSignProvider::$infoSign[$player->getName()])) {
            if($block instanceof WallSign || $block instanceof SignPost) {
                $event->setCancelled();
                $vectorString = "{$block->x}:{$block->y}:{$block->z}";

                if (!CloudSignProvider::isCloudSign($block)) {
                    $player->sendMessage(CloudBridge::Prefix . TextFormat::RED . "Dieses Schild ist KEIN CloudSign!");
                    return;
                }
                $sign = CloudSignProvider::getCloudSignByPosition($vectorString);
                $player->sendMessage(CloudBridge::Prefix
                    .TextFormat::YELLOW."Group: ".TextFormat::AQUA.$sign->getGroup()."\n"
                    .TextFormat::YELLOW."Founder: ".TextFormat::AQUA.$sign->getFounder()."\n"
                    .TextFormat::YELLOW."Server-Port: ".TextFormat::AQUA.$sign->getServerPort()
                );
                unset(CloudSignProvider::$infoSign[$player->getName()]);
            }
        }
    }
}