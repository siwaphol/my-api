<?php
use Phalcon\DI\FactoryDefault,
	Phalcon\Mvc\Micro,
	Phalcon\Http\Response,
	Phalcon\Http\Request,
	Phalcon\Logger\Adapter\File as FileAdapter,
	Phalcon\Config\Adapter\Ini as ConfigIni;

//Initialize variables
$di = new FactoryDefault();
$config = new ConfigIni("config/config.ini");

//Using an anonymous function, the instance will be lazy loaded
$di["response"] = function () {
	return new Response();
};
$di["request"] = function () {
	return new Request();
};

$app = new Micro();
$app->setDI( $di );
$app->get( '/api', function () use ( $app ) {
	$logger = new FileAdapter("logs/access.log");
	$serverIpAddressString = " Server IP=" . $app->request->getServerAddress();
	$clientIpAddressString = " Client IP=" . $app->request->getClientAddress();
	$logger->log("This is a message" . $serverIpAddressString . $clientIpAddressString,\Phalcon\Logger::INFO);
	echo "Welcome" . "</br>";
	if ($app->request->isSecureRequest()) {
    	echo "The request was made using a secure layer";
	}else{
		echo "The request was not made using a secure layer";
	}
	
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