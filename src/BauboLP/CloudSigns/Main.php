<?php


namespace BauboLP\CloudSigns;


use BauboLP\CloudSigns\Commands\SignCommand;
use BauboLP\CloudSigns\Events\BlockBreakListener;
use BauboLP\CloudSigns\Events\PlayerInteractListener;
use BauboLP\CloudSigns\Provider\CloudSignProvider;
use BauboLP\CloudSigns\Provider\ConfigProvider;
use BauboLP\CloudSigns\Tasks\CooldownTask;
use BauboLP\CloudSigns\Tasks\RefreshSignTask;
use BauboLP\CloudSigns\Tasks\StartAsyncSignTask;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class Main extends PluginBase
{

    /** @var Main */
    private static Main $instance;
    /** @var ConfigProvider */
    private static ConfigProvider $configProvider;
    /** @var CloudSignProvider */
    private static CloudSignProvider $cloudSignProvider;
    /** @var bool  */
    public static bool $isQueryDone = true;
    /** @var array  */
    public static array $cooldowns = [];
    /** @var array  */
    public static array $lobbys = [];

    public function onEnable()
    {
      self::$instance = $this;
      self::$configProvider = new ConfigProvider();
      self::$cloudSignProvider = new CloudSignProvider();

      CloudSignProvider::loadCloudSigns();

      $this->registerEvents();
      Server::getInstance()->getCommandMap()->register("Sign", new SignCommand());

      $this->getScheduler()->scheduleRepeatingTask(new RefreshSignTask(), 5);
      $this->getScheduler()->scheduleRepeatingTask(new CooldownTask(), 40);
      $this->getScheduler()->scheduleRepeatingTask(new StartAsyncSignTask(), 60);

    }

    public function registerEvents()
    {
        $events = [
            new BlockBreakListener(),
            new PlayerInteractListener()
        ];

        foreach ($events as $event) {
            Server::getInstance()->getPluginManager()->registerEvents($event, $this);
        }
    }

    /**
     * @return Main
     */
    public static function getInstance(): Main
    {
        return self::$instance;
    }

    /**
     * @return ConfigProvider
     */
    public static function getConfigProvider(): ConfigProvider
    {
        return self::$configProvider;
    }

    /**
     * @return CloudSignProvider
     */
    public static function getCloudSignProvider(): CloudSignProvider
    {
        return self::$cloudSignProvider;
    }
}