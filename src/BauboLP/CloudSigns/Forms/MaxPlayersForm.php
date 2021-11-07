<?php


namespace BauboLP\CloudSigns\Forms;


use BauboLP\Cloud\CloudBridge;
use BauboLP\CloudSigns\Main;
use pocketmine\form\CustomForm;
use pocketmine\form\CustomFormResponse;
use pocketmine\form\element\Slider;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class MaxPlayersForm extends CustomForm
{

    public function __construct(float $maxPlayers, string $group)
    {
        if($maxPlayers < 1) {
            $maxPlayers = 1;
        }
        $elements = [new Slider("Max Players", "", 1, 100, 1.0, $maxPlayers)];
        parent::__construct(CloudBridge::Prefix.TextFormat::YELLOW.'Settings', $elements, function (Player $player, CustomFormResponse $response) use($group): void{
            $maxPlayers = $response->getFloat($this->getElement(0)->getName());
            Main::getConfigProvider()->setMaxPlayers((int)$maxPlayers, $group);
            $player->sendMessage(CloudBridge::Prefix.TextFormat::GREEN."Die Maximale Anzahl an Spielern der Gruppe ".TextFormat::AQUA.$group.TextFormat::GREEN." beträgt nun ".TextFormat::AQUA.$maxPlayers." Spieler".TextFormat::GREEN.".");
        });
    }
}