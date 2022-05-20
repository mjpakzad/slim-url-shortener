<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require '../app/Database/Database.php';

$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$app = new \Slim\App(['settings' => $config]);
$container = $app->getContainer();
$container['view'] = new \Slim\Views\PhpRenderer('../templates/');

$auth = function($request, $response, $next) {
	$token = $request->getParam('token');
	try {
		$db = new \App\Database\Database();
		$db = $db->connect();
		$stmt = $db->query("SELECT * FROM users WHERE token = '{$token}'");
		$user = $stmt->fetch(\PDO::FETCH_OBJ);
	} catch(PDOException $e) {
		return '{"error": {"msg": ' . $e->getMessage() . '}';
	}
	if(!$user) {
		return $response->withJson([
			'error' => true,
			'message' => 'The token is not valid and you have not access to this route!',
		], 403);
	}
	$response = $next($request, $response);
	return $response;
};

$container['logger'] = function($c) {
	$logger = new \Monolog\Logger('my_logger');
	$file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
	$logger->pushHandler($file_handler);
	return $logger;
};
$container['db'] = function($c) {
	$db = $c['settings']['db'];
	$pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'], $db['user'], $db['pass']);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	return $pdo;
};

$container['HomeController'] = function ($container) {
	return new \App\Controllers\HomeController($container);
};
$container['AuthController'] = function ($container) {
	return new \App\Controllers\AuthController($container);
};
$container['DomainController'] = function ($container) {
	return new \App\Controllers\DomainController($container);
};
$container['LinkController'] = function ($container) {
	return new \App\Controllers\LinkController($container);
};

$app->get('/', 'HomeController:index')->setName('home');

$app->group('/api/v1', function () use ($app, $auth) {
	$app->post('/login', 'AuthController:login')->setName('auth-login');
	$app->get('/domains', 'DomainController:index')->setName('domains-index')->add($auth);
	$app->post('/domains', 'DomainController:store')->setName('domains-store')->add($auth);
	$app->patch('/domains/{domain}', 'DomainController:update')->setName('domains-update')->add($auth);
	$app->delete('/domains/{domain}', 'DomainController:delete')->setName('domains-delete')->add($auth);
	$app->get('/links', 'LinkController:index')->setName('links-index')->add($auth);
	$app->post('/links', 'LinkController:store')->setName('links-store')->add($auth);
	$app->get('/links/{link}', 'LinkController:show')->setName('links-show')->add($auth);
	$app->patch('/links/{link}', 'LinkController:update')->setName('links-update')->add($auth);
	$app->delete('/links/{link}', 'LinkController:delete')->setName('links-delete')->add($auth);
});

$app->run();