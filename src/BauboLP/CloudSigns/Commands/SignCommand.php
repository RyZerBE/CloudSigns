<?php


namespace BauboLP\CloudSigns\Commands;


use BauboLP\Cloud\CloudBridge;
use BauboLP\CloudSigns\Forms\SettingsMainForm;
use BauboLP\CloudSigns\Main;
use BauboLP\CloudSigns\Provider\CloudSignProvider;
use BauboLP\CloudSigns\Provider\ConfigProvider;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SignCommand extends Command
{

    public function __construct()
    {
        parent::__construct('sign', "CloudSigns Command", "/sign help", ['cs']);
        $this->setPermission("cloud.signs");
        $this->setPermissionMessage(CloudBridge::Prefix.TextFormat::RED."No Permissions!");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$sender instanceof Player) return;
        if(!$this->testPermission($sender)) return;

        if(empty($args[0])) {
            $sender->sendMessage(CloudBridge::Prefix.TextFormat::RED."/sign <add|remove> <Group>");
            $sender->sendMessage(CloudBridge::Prefix.TextFormat::RED."/sign settings <Group>");
            $sender->sendMessage(CloudBridge::Prefix.TextFormat::RED."/sign reload");
            return;
        }

        if(empty($args[1])) {
            if($args[0] == "reload") {
                Main::getConfigProvider()->reloadConfig();
                CloudSignProvider::loadCloudSigns();
                $sender->sendMessage(CloudBridge::Prefix.TextFormat::GREEN."CloudSigns neugeladen!");
                return;
            }
            if($args[0] == "remove") {
                CloudSignProvider::$unregisterSigns[$sender->getName()] = [];
                $sender->sendMessage(CloudBridge::Prefix.TextFormat::GREEN."Bitte zerstöre nun das Schild, welches entfernt werden soll.");
                return;
            }

            if($args[0] == "info") {
                CloudSignProvider::$infoSign[$sender->getName()] = [];
                $sender->sendMessage(CloudBridge::Prefix.TextFormat::GREEN."Bitte zerstöre nun das Schild, für welches du Informationen haben möchtest.");
                return;
            }
            $sender->sendMessage(CloudBridge::Prefix.TextFormat::RED."/sign add <Group>");
            $sender->sendMessage(CloudBridge::Prefix.TextFormat::RED."/sign remove");
            $sender->sendMessage(CloudBridge::Prefix.TextFormat::RED."/sign settings <Group>");
            $sender->sendMessage(CloudBridge::Prefix.TextFormat::RED."/sign reload");
            return;
        }
        $group = $args[1];

        if(!CloudBridge::getCloudProvider()->existGroup($group)) {
            $sender->sendMessage(CloudBridge::Prefix.TextFormat::RED."Die Gruppe existiert nicht.");
            return;
        }
        switch ($args[0]) {
            case "add":
                $array = ['group' => $group];
                CloudSignProvider::$registerSigns[$sender->getName()] = $array;
                $sender->sendMessage(CloudBridge::Prefix.TextFormat::GREEN."Bitte zerstöre nun das Schild, welches du als CloudSign registrieren möchtest.");
                break;
            case "settings":
                $sender->sendForm(new SettingsMainForm($group));
                $sender->sendMessage(CloudBridge::Prefix.TextFormat::GREEN."Öffne Settings..");
                break;
        }
    }
}