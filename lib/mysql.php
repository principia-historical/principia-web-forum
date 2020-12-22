<?php

$sql = new mysql;
$sql->connect($sqlhost,$sqluser,$sqlpass, $sqldb) or die("Couldn't connect to MySQL server");

class mysql {
	public $db = null;

	function connect($host, $user, $pass, $db) {
		$options = [
			PDO::ATTR_ERRMODE				=> PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES		=> false,
		];

		$this->db = new PDO("mysql:host=$host;dbname=$db;charset=latin1", $user, $pass, $options);

		return $this->db;
	}

	function query($query, $params = []) {
		//echo "$query<br>";
		$res = $this->db->prepare($query);
		$res->execute($params);
		return $res;
	}

	function fetch($query, $params = []) {
		$res = $this->query($query, $params);
		return $res->fetch();
	}

	function result($query, $params = []) {
		$res = $this->query($query, $params);
		return $res->fetchColumn();
	}

	function insertid() {
		return $this->db->lastInsertId();
	}
}
