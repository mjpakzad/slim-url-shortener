<?php
namespace App\Controllers;

use App\Database\Database;
use PDO;
use Psr\Container\ContainerInterface;

class DomainController
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
			$stmt = $db->query('SELECT * FROM domains');
			$domains = $stmt->fetchAll(PDO::FETCH_OBJ);
			$db = null;
			return json_encode($domains);
		} catch(PDOException $e) {
			return '{"error": {"msg": ' . $e->getMessage() . '}';
		}
	}
	
	public function store($request, $response, $args)
	{
		if($request->getparam('domain') == null OR $request->getparam('domain') == '' OR $request->getparam('token') == null OR $request->getparam('token') == '') {
			return $response->withJson([
			  'error' => true,
			  'message' => 'Your request body must containt domain and token.'
			], 403);
		}
		if(!filter_var($request->getparam('domain'), FILTER_VALIDATE_URL)) {
			return $response->withJson([
				'error' => true,
				'message' => 'The url of domain you entered in invalid.',
			], 403);
		}
		$domain = $request->getparam('domain');
		try {
			$db = new Database();
			$db = $db->connect();
			$stmt = $db->query("SELECT * FROM domains WHERE domain = '{$domain}'");
			$domains = $stmt->fetchAll(PDO::FETCH_OBJ);
		} catch(PDOException $e) {
			return '{"error": {"msg": ' . $e->getMessage() . '}';
		}
		if(count($domains)) {
			return $response->withJson([
				'error' => true,
				'message' => 'This domain already added.',
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
		$sql = "INSERT INTO domains (user_id, domain) VALUES (:user_id, :domain)";
		try {
			$stmt = $db->prepare($sql);
			$stmt->bindParam("user_id", $user['0']->id);
			$stmt->bindParam("domain", $domain);
			$stmt->execute();
			$id = $db->lastInsertId();
			$db = null;
			return $response->withJson([
				'id' => $id,
				'user_id' => $user[0]->id,
				'domain' => $domain,
				'status' => true,
			]);
		} catch(PDOException $e) {
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}
	
	public function update($request, $response, $args)
	{
		if($request->getparam('domain') == null OR $request->getparam('domain') == '') {
			return $response->withJson([
			  'error' => true,
			  'message' => 'Your request body must containt domain.'
			], 403);
		}
		if(!filter_var($request->getparam('domain'), FILTER_VALIDATE_URL)) {
			return $response->withJson([
				'error' => true,
				'message' => 'The url of domain you entered in invalid.',
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
		if($domains) {
			return $response->withJson([
				'error' => true,
				'message' => 'This domain already exist.',
			], 403);
		}
		$oldDomainId = $args['domain'];
		try {
			$db = new Database();
			$db = $db->connect();
			$stmt = $db->query("SELECT * FROM domains WHERE id = '{$oldDomainId}'");
			$domains = $stmt->fetch(PDO::FETCH_OBJ);
		} catch(PDOException $e) {
			return '{"error": {"msg": ' . $e->getMessage() . '}';
		}
		if(!$domains) {
			return $response->withJson([
				'error' => true,
				'message' => 'This domain does not exist.',
			], 403);
		}
		$status = $request->getParam('status') ?? 0;
		
		$id = $request->getAttribute('id');
		$sql = "UPDATE domains SET domain=:domain, status=:status WHERE id=:id";
		try {
			$stmt = $db->prepare($sql);
			$stmt->bindParam("domain", $domain);
			$stmt->bindParam("status", $status);
			$stmt->bindParam("id", $args['domain']);
			$stmt->execute();
			$db = null;
			return $response->withJson([
				'error' => false,
				'message' => 'The domain status has been changed successfully.',
			]);
		} catch(PDOException $e) {
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}
	
	public function delete($request, $response, $args)
	{
		$id = $args['domain'];
		$sql = "DELETE FROM domains WHERE id=:id";
		try {
			$db = new Database();
			$db = $db->connect();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("id", $id);
			$stmt->execute();
			$db = null;
			return $response->withJson([
				'error' => false,
				'message' => 'The domain successfully deleted!',
			]);
		} catch(PDOException $e) {
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}
}