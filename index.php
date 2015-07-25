<?php

use \Phalcon\Logger\Adapter\File as PhLoggerFile;
use \Phalcon\Logger\Formatter\Line as PhLoggerFormatter;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Micro;
use Phalcon\Http\Response;
use Phalcon\Http\Request;

error_reporting(E_ALL);

try {

    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', dirname(dirname(__FILE__)));
    }

    // Using require once because I want to get the specific
    // bootloader class here. The loader will be initialized
    // in my bootstrap class
    require_once 'libs/Bootstrap.php';
    require_once 'libs/Error.php';

    $error_logger = new PhLoggerFile("logs/error.log");
    $formatter = new PhLoggerFormatter("[%date%][%type%] %message%");
    $error_logger->setFormatter($formatter);

    $di  = new \Phalcon\DI\FactoryDefault();
    //prepare all settings
    $app = new \NDN\Bootstrap($di);

    $di = $app->run(array());
    $connection = $di->get('db');

    if(!is_null($connection)){
        //$di->get('logger')->log('testing',\Phalcon\Logger::INFO);
        //$REST_di = new FactoryDefault();
        $di["response"] = function () {
        	return new Response();
        };
        $di["request"] = function () {
        	return new Request();
        };
        $app = new Micro();
        $app->setDI( $di );
        $app->get( '/api', function () use ( $app ) {
            $serverIpAddressString = " Server IP=" . $app->request->getServerAddress();
            $clientIpAddressString = " Client IP=" . $app->request->getClientAddress();
            $app->getDI()->get('logger')->log( $clientIpAddressString .' - '. $serverIpAddressString . $clientIpAddressString,\Phalcon\Logger::INFO);
            echo "Welcome" . "</br>";

        } );
        $app->post( '/api', function () use ( $app ) {
            $post = $app->request->getPost();
            print_r( $post );
        } );
        $app->notFound(
            function () use ( $app ) {
                $app->response->setStatusCode( 404, "Not Found" )->sendHeaders();
                echo 'This is crazy, but this page was not found!';
            }
        );
        $app->handle();
    }

} catch (\Phalcon\Exception $e) {
    $error_logger->log('['. __FILE__ .']' . $e->getMessage(),\Phalcon\Logger::ERROR);
}




//use Phalcon\DI\FactoryDefault,
//	Phalcon\Mvc\Micro,
//	Phalcon\Http\Response,
//	Phalcon\Http\Request,
//	Phalcon\Logger\Adapter\File as FileAdapter,
//	Phalcon\Config\Adapter\Ini as ConfigIni;
//
////Initialize variables
//$di = new FactoryDefault();
//$config = new ConfigIni("config/config.ini");
//$database_settings = array(
//	"host" => $config->database->host,
//	//"port" => "",
//	"username" => $config->database->username,
//	"password" => $config->database->password,
//	"dbname" => $config->database->dbname
//	);
//$database_settings["persistent"] = false;
//$connection = new \Phalcon\Db\Adapter\Pdo\Mysql($database_settings);
//
////Using an anonymous function, the instance will be lazy loaded
//$di["response"] = function () {
//	return new Response();
//};
//$di["request"] = function () {
//	return new Request();
//};
//
//$app = new Micro();
//$app->setDI( $di );
//$app->get( '/api', function () use ( $app ) {
//	$logger = new FileAdapter("logs/access.log");
//	$serverIpAddressString = " Server IP=" . $app->request->getServerAddress();
//	$clientIpAddressString = " Client IP=" . $app->request->getClientAddress();
//	$logger->log("This is a message" . $serverIpAddressString . $clientIpAddressString,\Phalcon\Logger::INFO);
//	echo "Welcome" . "</br>";
//	if ($app->request->isSecureRequest()) {
//    	echo "The request was made using a secure layer";
//	}else{
//		echo "The request was not made using a secure layer";
//	}
//
//} );
//$app->post( '/api', function () use ( $app ) {
//	$post = $app->request->getPost();
//	print_r( $post );
//} );
//$app->notFound(
//	function () use ( $app ) {
//		$app->response->setStatusCode( 404, "Not Found" )->sendHeaders();
//		echo 'This is crazy, but this page was not found!';
//	}
//);
//$app->handle();