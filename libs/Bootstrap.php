<?php

namespace NDN;

use \Phalcon\Config\Adapter\Ini as PhConfig;
use \Phalcon\Loader as PhLoader;
use \Phalcon\Logger as PhLogger;
use \Phalcon\Logger\Adapter\File as PhLoggerFile;
use \Phalcon\Logger\Formatter\Line as PhLoggerFormatter;
use \Phalcon\Db\Adapter\Pdo\Mysql as PhMysql;
use \Phalcon\Mvc\Application as PhApplication;
use \Phalcon\Mvc\Dispatcher as PhDispatcher;
use \Phalcon\Mvc\Url as PhUrl;
use \Phalcon\Mvc\View as PhView;
use \Phalcon\Mvc\View\Engine\Volt as PhVolt;
use \Phalcon\Exception as PhException;

class Bootstrap
{
    private $_di;

    /**
     * Constructor
     * 
     * @param $di
     */
    public function __construct($di)
    {
        $this->_di = $di;
    }

    /**
     * Runs the application performing all initializations
     * 
     * @param $options
     *
     * @return mixed
     */
    public function run($options)
    {
        $loaders = array(
            'config',
            'timezone',
            'logger',
            'database',
        );
        $logger = new PhLoggerFile("logs/error.log");
        $formatter = new PhLoggerFormatter("[%date%][%type%] %message%");
        $logger->setFormatter($formatter);

        try {

            foreach ($loaders as $service)
            {
                $function = 'init' . ucfirst($service);

                $this->$function($options);
            }

//            $application = new PhApplication();
//            $application->setDI($this->_di);

            return $this->_di;

        } catch (PhException $e) {
            $logger->log('['. __FILE__ .']' . $e->getMessage() ,\Phalcon\Logger::ERROR);
            //echo $e->getMessage();
        } catch (\PDOException $e) {
            $logger->log('['. __FILE__ .']' . $e->getMessage() ,\Phalcon\Logger::ERROR);
            //echo $e->getMessage();
        }
    }

    // Protected functions

    /**
     * Initializes the config. Reads it from its location and
     * stores it in the Di container for easier access
     *
     * @param array $options
     */
    protected function initConfig($options = array())
    {
        $configFile = 'config/config.ini';

        // Create the new object
        $config = new PhConfig($configFile);

        // Store it in the Di container
        $this->_di->set('config', $config);
    }

    /**
     * Initializes the timezone
     *
     * @param array $options
     */
    protected function initTimezone($options = array())
    {
        $config = $this->_di->get('config');

        $timezone = (isset($config->app->timezone)) ?
                    $config->app->timezone      :
                    'Asia/Bangkok';

        date_default_timezone_set($timezone);

        $this->_di->set('timezone_default', $timezone);
    }

    /**
     * Initializes the logger
     *
     * @param array $options
     */
    protected function initLogger($options = array())
    {
        $config = $this->_di->get('config');

        $this->_di->set(
            'logger',
            function() use ($config)
            {
                $logger = new PhLoggerFile($config->app->logger->file);

                $formatter = new PhLoggerFormatter($config->app->logger->format);
                $logger->setFormatter($formatter);

                return $logger;
            }
        );
    }

    /**
     * Initializes the database adapter
     *
     * @param array $options
     */
    protected function initDatabase($options = array())
    {
        $config = $this->_di->get('config');
        $logger = new PhLoggerFile("logs/error.log");
        $formatter = new PhLoggerFormatter("[%date%][%type%] %message%");
        $logger->setFormatter($formatter);

        $this->_di->set(
            'db',
            function() use ($config, $logger)
            {

                $params = array(
                    "host"     => $config->database->host,
                    "username" => $config->database->username,
                    "password" => $config->database->password,
                    "dbname"   => $config->database->dbname,
                );

                try {
                    $conn = new PhMysql($params);
                }catch (\PDOException $e){
                    $logger->log('['. __FILE__ .']' . $e->getMessage() ,\Phalcon\Logger::ERROR);
                }

                return $conn;
            }
        );
    }

}
