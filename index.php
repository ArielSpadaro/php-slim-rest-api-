<?php 
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;

require __DIR__ . '/lib/Kosher/kosher.php';
require __DIR__ . '/config.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';

$app = AppFactory::create();

$app->setBasePath("/api");
$app->addBodyParsingMiddleware();

require __DIR__ . '/routes/products.php';
require __DIR__ . '/routes/products_brand.php';
require __DIR__ . '/routes/products_categories.php';
require __DIR__ . '/routes/products_images.php';


$app->add(function (Request $request, RequestHandler $handler){
    $response = $handler->handle($request);
    $authorization_header = $request->getHeader("access_token");
    
    if(empty($authorization_header) || ($authorization_header[0]!="123123123123")){

        $response = new Response();
        $data = array('msg' => 'ACCESO DENEGADO', 'status' => 401);
        $payload = json_encode($data);

        $response->getBody()->write($payload);
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    return $response;

});

//ERROR HANDLER

$errorMiddleware = $app->addErrorMiddleware(DEBUG, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json'); 

$app->run();
?>