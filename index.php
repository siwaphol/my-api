<?php

use \Phalcon\Logger\Adapter\File as PhLoggerFile;
use \Phalcon\Logger\Formatter\Line as PhLoggerFormatter;
use Phalcon\DI\FactoryDefault;
use Phalcon\Mvc\Micro;
use Phalcon\Http\Response;
use Phalcon\Http\Request;
use Phalcon\Crypt;

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
    $crypt = new Crypt();

    $di  = new \Phalcon\DI\FactoryDefault();
    //prepare all settings
    $app = new \NDN\Bootstrap($di);

    $di = $app->run(array());
    $connection = $di->get('db');

//    $key     = 'qwertyuiopasdfgh';
//    $text    = '12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890';
//
//    $hash = hash_hmac ('sha1', $text, $key);
//    echo "HMAC: " .$hash."</br>";
//    $encrypt = $crypt->encryptBase64($hash, $key);
//
//    echo "Base64 digest: " . $encrypt ."</br>";
//    echo "real text after Base64 decrypt: ".$crypt->decryptBase64($encrypt, $key);
//    echo "real text after HMAC decrypt: ".hash_hmac('sha1',$crypt->decryptBase64($encrypt, $key),$key);
//    echo "</br>". base64_encode($text);
//    die;

    if(!is_null($connection)){
        $di["response"] = function () {
        	return new Response();
        };
        $di["request"] = function () {
        	return new Request();
        };
        $app = new Micro();
        $app->setDI( $di );
//        $app->get( '/api', function () use ( $app ) {
//            $serverIpAddressString = " Server IP=" . $app->request->getServerAddress();
//            $clientIpAddressString = " Client IP=" . $app->request->getClientAddress();
//            $app->getDI()->get('logger')->log( $clientIpAddressString .' - '. $serverIpAddressString . $clientIpAddressString,\Phalcon\Logger::INFO);
//            $year = $app->request;
//            echo "Welcome " . $year . "</br>";
//
//        } );
        $app->get("/login?{query_string}", function () use ($app) {
            $queryStringComplete = $app->request->hasQuery('username') && $app->request->hasQuery('password') && $app->request->hasQuery('appid');
            $queryStringNotEmpty = !empty($app->request->getQuery('username')) &&!empty($app->request->getQuery('password')) &&!empty($app->request->getQuery('appid'));

            if($queryStringComplete&&$queryStringNotEmpty){
                $sql = "SELECT * FROM applications";
                $result = $app->getDI()->get('db')->query($sql);
                while ($robot = $result->fetch()) {
                    echo $robot["app_id"];
                }
                echo "username: ".$app->request->getQuery('username')."</br>password: ".$app->request->getQuery('password')."</br>appid: ".$app->request->getQuery('appid'). "</br>";
            }else{
                $app->response->setStatusCode(400, "Bad Request")->sendHeaders();
            }
        });
        $app->get( '/getUserInfo', function () use ( $app ) {
            $queryStringComplete = $app->request->hasQuery('username') && $app->request->hasQuery('access_token') && $app->request->hasQuery('appid');
            $queryStringNotEmpty = !empty($app->request->getQuery('username')) &&!empty($app->request->getQuery('access_token')) &&!empty($app->request->getQuery('appid'));

            if($queryStringComplete&&$queryStringNotEmpty){
                echo "username: ".$app->request->getQuery('username')."</br>access_token: ".$app->request->getQuery('access_token')."</br>appid: ".$app->request->getQuery('appid'). "</br>";
            }else{
                $app->response->setStatusCode(400, "Bad Request")->sendHeaders();
            }
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
    $response = new Response();
    $response->setStatusCode(500, "Internal Server Error");
    $response->send();
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