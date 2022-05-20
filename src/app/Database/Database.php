<?php
namespace App\Database;

use PDO;

class Database
{
	private $host = 'localhost';
	private $user = 'root';
	private $pass = '';
	private $name = 'url_shortener';
	
	public function connect()
	{
		$prepare = "mysql:host=$this->host;dbname=$this->name";
		$conn = new PDO($prepare, $this->user, $this->pass);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $conn;
	}
}
