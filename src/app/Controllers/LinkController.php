<?php
namespace App\Controllers;

use App\Database\Database;
use PDO;
use Psr\Container\ContainerInterface;

class LinkController
{
	protected $container;
	
	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}
	
	public function index($request, $response, $args)
	{
		try {
			$db = new Database();
			$db = $db->connect();
			$stmt = $db->query('SELECT * FROM links');
			$links = $stmt->fetchAll(PDO::FETCH_OBJ);
			$db = null;
			return json_encode($links);
		} catch(PDOException $e) {
			return '{"error": {"msg": ' . $e->getMessage() . '}';
		}
	}
	
	public function store($request, $response, $args)
	{
		if(
			$request->getparam('domain') == null OR $request->getparam('domain') == '' OR 
			$request->getparam('short') == null OR $request->getparam('short') == '' OR 
			$request->getparam('long') == null OR $request->getparam('long') == '' OR 
			$request->getparam('token') == null OR $request->getparam('token') == ''
		) {
			return $response->withJson([
			  'error' => true,
			  'message' => 'Your request body must contain domain, token, short slug and long url!'
			], 403);
		}
		if(!filter_var($request->getparam('domain'), FILTER_VALIDATE_URL)) {
			return $response->withJson([
				'error' => true,
				'message' => 'The url of domain you entered in invalid.',
			], 403);
		}
		if(!filter_var($request->getparam('long'), FILTER_VALIDATE_URL)) {
			return $response->withJson([
				'error' => true,
				'message' => 'The url of long you entered in invalid.',
			], 403);
		}
		$domain = $request->getparam('domain');
		try {
			$db = new Database();
			$db = $db->connect();
			$stmt = $db->query("SELECT * FROM domains WHERE domain = '{$domain}'");
			$domains = $stmt->fetch(PDO::FETCH_OBJ);
		} catch(PDOException $e) {
			return '{"error": {"msg": ' . $e->getMessage() . '}';
		}
		if(!$domains) {
			return $response->withJson([
				'error' => true,
				'message' => 'The domain you entered does not exist.',
			], 403);
		}
		$token = $request->getparam('token');
		try {
			$stmt = $db->query("SELECT * FROM users WHERE token = '{$token}'");
			$user = $stmt->fetchAll(PDO::FETCH_OBJ);
		} catch(PDOException $e) {
			return '{"error": {"msg": ' . $e->getMessage() . '}';
		}
		if(count($user) == 0) {
			return $response->withJson([
				'error' => true,
				'message' => 'The token you have been inserted is not valid!',
			], 403);
		}
		$domain_id = $domains->id;
		$short = $request->getparam('short');
		$long = $request->getparam('long');
		$sql = "INSERT INTO links (user_id, domain_id, short_url, long_url) VALUES (:user_id, :domain_id, :short, :long)";
		try {
			$stmt = $db->prepare($sql);
			$stmt->bindParam("user_id", $user['0']->id);
			$stmt->bindParam("domain_id", $domain_id);
			$stmt->bindParam("short", $short);
			$stmt->bindParam("long", $long);
			$stmt->execute();
			$id = $db->lastInsertId();
			$db = null;
			return $response->withJson([
				'id' => $id,
				'user_id' => $user[0]->id,
				'domain' => $domain_id,
				'short' => $short,
				'long' => $long,
			]);
		} catch(PDOException $e) {
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}
	
	public function show($request, $response, $args)
	{
		$short = $args['link'];
		try {
			$db = new Database();
			$db = $db->connect();
			$stmt = $db->query("SELECT * FROM links WHERE short_url = '{$short}'");
			$link = $stmt->fetch(PDO::FETCH_OBJ);
		} catch(PDOException $e) {
			return '{"error": {"msg": ' . $e->getMessage() . '}';
		}
		if(!$link) {
			return $response->withJson([
				'error' => true,
				'message' => 'This link does not exist.',
			], 403);
		}
		
		return $response
		  ->withHeader('Location', $link->long_url)
		  ->withStatus(301);
	}
	
	public function update($request, $response, $args)
	{
		if($request->getparam('short') == null OR $request->getparam('short') == '') {
			return $response->withJson([
			  'error' => true,
			  'message' => 'Your request body must contain new short url!'
			], 403);
		}
		$short = $args['link'];
		try {
			$db = new Database();
			$db = $db->connect();
			$stmt = $db->query("SELECT * FROM links WHERE short_url = '{$short}'");
			$link = $stmt->fetch(PDO::FETCH_OBJ);
		} catch(PDOException $e) {
			return '{"error": {"msg": ' . $e->getMessage() . '}';
		}
		if(!$link) {
			return $response->withJson([
				'error' => true,
				'message' => 'This link does not exist.',
			], 403);
		}
		$new_short = $request->getparam('short');
		$id = $link->id;
		$sql = "UPDATE links SET short_url=:short WHERE id=:id";
		try {
			$stmt = $db->prepare($sql);
			$stmt->bindParam("short", $new_short);
			$stmt->bindParam("id", $id);
			$stmt->execute();
			$db = null;
			return $response->withJson([
				'error' => false,
				'message' => 'The link short url has been updated successfully.',
			]);
		} catch(PDOException $e) {
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}
	
	public function delete($request, $response, $args)
	{
		$short = $args['link'];
		$sql = "DELETE FROM links WHERE short_url=:short";
		try {
			$db = new Database();
			$db = $db->connect();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("short", $short);
			$stmt->execute();
			$db = null;
			return $response->withJson([
				'error' => false,
				'message' => 'The link successfully deleted!',
			]);
		} catch(PDOException $e) {
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}
}