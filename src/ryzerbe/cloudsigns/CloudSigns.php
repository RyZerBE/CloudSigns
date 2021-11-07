<?php

declare(strict_types=1);

namespace ryzerbe\cloudsigns;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use ReflectionClass;
use ReflectionException;
use function is_dir;
use function scandir;
use function str_replace;

class CloudSigns extends PluginBase {
    private static CloudSigns $instance;

    public static function getInstance(): CloudSigns{
        return self::$instance;
    }

    /**
     * @throws ReflectionException
     */
    public function onEnable(): void{
        self::$instance = $this;

        $this->initListener(__DIR__ . "/listener/");
    }

    /**
     * @throws ReflectionException
     */
    private function initListener(string $directory): void{
        foreach(scandir($directory) as $listener){
            if($listener === "." || $listener === "..") continue;
            if(is_dir($directory.$listener)){
                $this->initListener($directory.$listener."/");
                continue;
            }
            $dir = str_replace([$this->getFile()."src/", "/"], ["", "\\"], $directory);
            $refClass = new ReflectionClass($dir.str_replace(".php", "", $listener));
            $class = new ($refClass->getName());
            if($class instanceof Listener){
                Server::getInstance()->getPluginManager()->registerEvents($class, $this);
                Server::getInstance()->getLogger()->debug("Registered ".$refClass->getShortName()." listener");
            }
        }
    }
}