<?php

declare(strict_types=1);

namespace ryzerbe\cloudsigns\cloudsign;

use pocketmine\math\Vector3;

class CloudSign {
    private Vector3 $vector3;
    private string $group;
    private int $state;

    private bool $maintenance;

    private int $players = 0;
    private int $maxPlayers = 0;
    private int $port = 0;
}