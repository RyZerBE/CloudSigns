<?php


namespace BauboLP\CloudSigns\Forms;


use BauboLP\Cloud\CloudBridge;
use BauboLP\CloudSigns\Main;
use BauboLP\CloudSigns\Provider\CloudSignProvider;
use pocketmine\form\MenuForm;
use pocketmine\form\MenuOption;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SettingsMainForm extends MenuForm
{

    private $group;

    public function __construct(string $group)
    {
        $this->group = $group;
        $options = [];
        $buttons = [
           'whitelist',
           'maxPlayers'
        ];
        $pos = "";
        foreach (CloudSignProvider::getCloudSigns() as $cloudSign) {
            if($cloudSign->getGroup() == $this->group) {
                $pos .= $cloudSign->getVector3AString();
                break;
            }
        }
        $data = Main::getConfigProvider()->getConfig()->get('signs')[$pos];
        if($data['maintance'] == true || $data['maintance'] == "true") {
            $options[] = new MenuOption(TextFormat::GREEN."Maintenance");
        }else {
            $options[] = new MenuOption(TextFormat::DARK_RED."Maintenance");
        }

        $options[] = new MenuOption(TextFormat::GREEN."Maximal Players");

        $submit = function (Player $player, int $selectedOption) use ($buttons): void{
            $button = $buttons[$selectedOption];
            switch ($button) {
                case "whitelist":
                    $pos = "";
                    foreach (CloudSignProvider::getCloudSigns() as $cloudSign) {
                        if($cloudSign->getGroup() == $this->group) {
                            $pos .= $cloudSign->getVector3AString();
                            break;
                        }
                    }
                    $data = Main::getConfigProvider()->getConfig()->get('signs')[$pos];
                    if($data['maintance'] == true || $data['maintance'] == "true") {
                        Main::getConfigProvider()->setMaintenance($this->group, false);
                        $player->sendMessage(CloudBridge::Prefix.TextFormat::GREEN."Die CloudSigns der Gruppe §b{$this->group} §asind nun aus den Wartungen.");
                    }else {
                        Main::getConfigProvider()->setMaintenance($this->group, true);
                        $player->sendMessage(CloudBridge::Prefix.TextFormat::GREEN."Die CloudSigns der Gruppe §b{$this->group} §asind nun in Wartung.");
                    }
                    break;
                case "maxPlayers":
                    $player->sendForm(new MaxPlayersForm(Main::getConfigProvider()->getMaxPlayers($this->group), $this->group));
                    break;
            }
        };
        parent::__construct(CloudBridge::Prefix.TextFormat::YELLOW."Settings", "", $options, $submit);
    }
}