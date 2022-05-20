<?php
namespace App\Controllers;

use App\Database\Database;
use PDO;
use Psr\Container\ContainerInterface;

class AuthController
{
	protected $container;
	
	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}
	
	public function login($request, $response, $args)
	{
		if($request->getparam('email') == null OR $request->getparam('email') == '' OR $request->getparam('password') == null OR $request->getparam('password') == '') {
			return $response->withJson([
			  'error' => true,
			  'message' => 'Your request body must containt email and password.'
			], 403);
		}
		if(!filter_var($request->getparam('email'), FILTER_VALIDATE_EMAIL)) {
			return $response->withJson([
				'error' => true,
				'message' => 'The email you entered in invalid.',
			], 403);
		}
		$email = $request->getparam('email');
		$password = $request->getparam('password');
		try {
			$db = new Database();
			$db = $db->connect();
			$stmt = $db->query("SELECT * FROM users WHERE email = '{$email}'");
			$user = $stmt->fetch(PDO::FETCH_OBJ);
		} catch(PDOException $e) {
			return '{"error": {"msg": ' . $e->getMessage() . '}';
		}
		if(!$user) {
			return $response->withJson([
				'error' => true,
				'message' => 'The user with this email does not exist!',
			], 403);
		}
		if(!password_verify($password, $user->password)) {
			return $response->withJson([
				'error' => true,
				'message' => 'This email and password does not belong to anyone in our database.',
			], 403);
        }
		$token = sha1(mt_rand(1, 99999) . 'Mj.Pakzad');;
		$sql = "UPDATE users SET token=:token WHERE email=:email";
		try {
			$stmt = $db->prepare($sql);
			$stmt->bindParam("token", $token);
			$stmt->bindParam("email", $email);
			$stmt->execute();
			$db = null;
		} catch(PDOException $e) {
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}
		return $response->withJson([
			'error' => false,
			'message' => 'You can use this token: ' . $token,
			'token' => $token,
		]);
	}
}