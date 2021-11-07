<?php

declare(strict_types=1);

namespace ryzerbe\cloudsigns\listener\cloud;

use BauboLP\Cloud\Events\CloudPacketReceiveEvent;
use BauboLP\Cloud\Packets\NetworkInfoPacket;
use pocketmine\event\Listener;
use function var_dump;

class CloudPacketReceiveListener implements Listener {
    public function onCloudPacketReceive(CloudPacketReceiveEvent $event): void{
        $packet = $event->getCloudPacket();
        if($packet instanceof NetworkInfoPacket){
            var_dump($packet->data);
        }
    }
}