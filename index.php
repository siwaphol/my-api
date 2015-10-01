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

    if (!defined("ROOT_PATH")) {
        define("ROOT_PATH", dirname(dirname(__FILE__)));
    }

    // Using require once because I want to get the specific
    // bootloader class here. The loader will be initialized
    // in my bootstrap class
    require_once "libs/Bootstrap.php";
    require_once "libs/Error.php";

    $error_logger = new PhLoggerFile("logs/error.log");
    $formatter = new PhLoggerFormatter("[%date%][%type%] %message%");
    $error_logger->setFormatter($formatter);
    $crypt = new Crypt();

    $di  = new \Phalcon\DI\FactoryDefault();
    //prepare all settings
    $app = new \NDN\Bootstrap($di);

    $di = $app->run(array());

    //test
    $connection = $di->get("db");

//    $resultset = $connection->query("SELECT * FROM applications");
//    if($resultset){
//        echo "ha you got me";
//    }
//    $robot = $connection->fetchOne("SELECT * FROM applications");
//    print_r($robot["secret"]);

    //end test

//    $key     = "qwertyuiopasdfgh";
//    $text    = "12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890";
//
//    $hash = hash_hmac ("sha1", $text, $key);
//    echo "HMAC: " .$hash."</br>";
//    $encrypt = $crypt->encryptBase64($hash, $key);
//
//    echo "Base64 digest: " . $encrypt ."</br>";
//    echo "real text after Base64 decrypt: ".$crypt->decryptBase64($encrypt, $key);
//    echo "real text after HMAC decrypt: ".hash_hmac("sha1",$crypt->decryptBase64($encrypt, $key),$key);
//    echo "</br>". base64_encode($text);
//    die;
//    function createStringToSign($http_method, $uri, $variable, $timestamp){
//        return $http_method . "\n" .$uri . "\n" . $variable . "\n" . $timestamp;
//    }
    function createSignature($stringToSign, $secret){
        return base64_encode(hash_hmac("sha256", $stringToSign, $secret));
    }
    function isAcceptTimeStamp($timestamp){
        date_default_timezone_set("Asia/Bangkok");
        return $timestamp > time() - 300 && $timestamp < time() + 300;
    }
    function checkSignature($recv_sig, $created_sig){
        return $recv_sig === $created_sig;
    }

    if(!is_null($connection)){
        $di["response"] = function () {
        	return new Response();
        };
        $di["request"] = function () {
        	return new Request();
        };
        $app = new Micro();
        $app->setDI( $di );
        $timestamp = $app->request->getHeader("X-TimeStamp");
        if(!empty($timestamp)){
            echo "X-TimeStamp just came in " . $timestamp . "<br>";
        }
        if(!isAcceptTimeStamp($timestamp)){
            $app->response->setStatusCode(400, "Bad Request")->sendHeaders();
        }
        //Login Service
        $app->post("/login", function () use ($app) {
            $param1 = "appid";
            $param2 = "password";
            $optional_param1 = "userinfo";
            $param3 = "username";
            $param4 = "signature";

            $record = $this->connection->fetchOne("SELECT * FROM applications WHERE appid='CMUMIS'");

            $queryStringComplete = $app->request->hasPost($param1) && $app->request->hasPost($param2) && $app->request->hasPost($param3) && $app->request->hasPost($param4);
            $queryStringNotEmpty = !empty($app->request->getPost($param1)) &&!empty($app->request->getPost($param2)) && !empty($app->request->getPost($param3)) && !empty($app->request->getPost($param4));

            if($queryStringComplete&&$queryStringNotEmpty){
                if($app->request->hasPost($optional_param1) && !empty($app->request->getPost($optional_param1))){
                    $sorted_variable = $param1 . "=" . $app->request->getPost($param1) .
                        "&" . $param2 ."=" . $app->request->getPost($param2) .
                        "&" . $optional_param1 . "=" . $app->request->getPost($optional_param1) .
                        "&" . $param3 . "=" . $app->request->getPost($param3);
                }else{
                    $sorted_variable = $param1 . "=" . $app->request->getPost($param1) .
                        "&" . $param2 ."=" . $app->request->getPost($param2) .
                        "&" . $param3 . "=" . $app->request->getPost($param3);
                }
                $stringToSign = "POST\n" ."/login\n" . $sorted_variable . "\n" . $app->request->getHeader("X-TimeStamp");
                //test
                $signature = createSignature($stringToSign, $record["secret"]);
                echo $signature;
                //end test
            }else{
                $app->response->setStatusCode(400, "Bad Request")->sendHeaders();
            }
        });
        //Get User data Service
        $app->get( "/userinfo?{query_string}", function () use ( $app ) {
            $param1 = "access_token";
            $param2 = "appid";
            $param3 = "username";
            $param4 = "signature";

            $queryStringComplete = $app->request->hasQuery($param1) && $app->request->hasQuery($param2) && $app->request->hasQuery($param3) && $app->request->hasQuery($param4);
            $queryStringNotEmpty = !empty($app->request->getQuery($param1)) && $app->request->hasQuery($param2) &&!empty($app->request->getQuery($param3)) &&!empty($app->request->getQuery($param4));

            if($queryStringComplete&&$queryStringNotEmpty){
                $sorted_variable = $param1 . "=" . $app->request->getPost($param1) .
                    "&" . $param2 ."=" . $app->request->getPost($param2) .
                    "&" . $param3 . "=" . $app->request->getPost($param3);
                $stringToSign = "GET\n" ."/userinfo\n" . $sorted_variable . "\n" . $app->request->getHeader("X-TimeStamp");
                //test
                $signature = createSignature($stringToSign, "mysecret555");
                //end test
            }else{
                $app->response->setStatusCode(400, "Bad Request")->sendHeaders();
            }
        } );

        $app->notFound(
            function () use ( $app ) {
                $app->response->setStatusCode( 404, "Not Found" )->sendHeaders();
            }
        );
        $app->handle();
    }

} catch (\Phalcon\Exception $e) {
    $error_logger->log("[". __FILE__ ."]" . $e->getMessage(),\Phalcon\Logger::ERROR);
    $response = new Response();
    $response->setStatusCode(500, "Internal Server Error");
    $response->send();
}
