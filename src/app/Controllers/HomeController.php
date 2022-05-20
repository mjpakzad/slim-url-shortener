<?php
namespace App\Controllers;

use App\Database\Database;
use PDO;
use Psr\Container\ContainerInterface;

class HomeController
{
	protected $container;
	
	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}
	
	public function index($request, $response, $args)
	{
		$response->getBody()->write('Use api routs.');
		return $response;
	}
}