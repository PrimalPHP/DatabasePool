<?php
namespace Primal\Database;
use \PDO;

/**
 * Primal\Database\Pool - PDO Database link management library
 * 
 * @package Primal
 * @author Jarvis Badgley
 * @copyright 2008 - 2012 Jarvis Badgley
 */

class Pool {
	
	/**
	 * Database link pointer
	 * @var array->PDO
	 */
	protected $pdos = array();
	
	/**
	 * Database configurations
	 *
	 * @var array->array
	 */
	protected $configs = array();
	
	/**
	 * Controls if database errors trigger PDO exceptions
	 *
	 * @var boolean
	 */
	protected $enable_exceptions = true;
	
	
	/**
	 * PDO link recall function.  Returns the named connection, opening it if none exists.
	 *
	 * @param string $name Optional link name. If omitted will return the first connection defined in settings
	 * @static
	 */
	public function getLink ($name = null) {

		if ($name === null) {
			$name = array_keys($this->$configs);
			$name = reset($name);
		}

		if (!isset($this->$pdos[$name])) {
			return $this->openLink($name);
		}
		
		return $this->$pdos[$name];
	}
	
	
	public function openLink($name = null) {
		if ($name === null) {
			$name = array_keys($this->$configs);
			$name = reset($name);
		}

		$config = $this->configs[$name];
		
		$pdo = new PDO(
			$config->dsn,
			$config->username, 
			$config->password, 
			$opts
		);
		
		if ($this->enable_exceptions) {
			$pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); //enable database exceptions
		}
		
		
		return $this->pdos[$name] = $pdo;
		
	}
	
	public function dropLink($name = null) {
		if ($name === null) {
			$name = array_keys($this->$configs);
			$name = reset($name);
		}
		
		$this->pdos[$name] = null;
		unset($this->pdos[$name]);
	}
	
	
	public function addMySQL($name, $host, $username, $password, $database = null, $options = null, $port = null) {
		$dsn = array("mysql:host=$host");
		
		if ($port) {
			$dsn[] = "port={$socketport}";
		}
		
		if ($database) {
			$dsn[] = "dbname={$database}";
		}
		
		$opts = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
		if (is_array($options)) {
			$opts = array_merge($opts, $options);
		}
		
		$this->configs[$name] = (object)array(
			'dsn'=>implode(';', $dsn),
			'username'=>$username,
			'password'=>$password,
			'options'=>$opts,
		);
		
		return $this;
	}
	
	public function addMySQLSocket($name, $socket, $username, $password, $database = null, $options = null) {
		$dsn = array("mysql:".($socket ? "unix_socket=$socket" : ''));
		
		if ($database) {
			$dsn[] = "dbname={$database}";
		}
		
		$opts = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
		if (is_array($options)) {
			$opts = array_merge($opts, $options);
		}

		$this->configs[$name] = (object)array(
			'dsn'=>implode(';', $dsn),
			'username'=>$username,
			'password'=>$password,
			'options'=>$opts,
		);
		
		return $this;
	}
	
	public function addSQLite($name, $database = null, $options = null) {
		$this->configs[$name] = (object)array(
			'dsn'=>"sqlite:$database",
			'username'=>null,
			'password'=>null,
			'options'=>$options,
		);
		
		return $this;
	}
	
	public function addPostgreSQL($name, $host, $username, $password, $database = null, $options = null, $port = null) {
		$dsn = array("pgsql:host=$host");
		
		if ($port) {
			$dsn[] = "port={$socketport}";
		}
		
		if ($database) {
			$dsn[] = "dbname={$database}";
		}
		
		$this->configs[$name] = (object)array(
			'dsn'=>implode(';', $dsn),
			'username'=>$username,
			'password'=>$password,
			'options'=>$options,
		);
		
		return $this;
	}
	
	public function addPostgreSQLSocket($name, $socket, $username, $password, $database = null, $options = null) {
		$dsn = array("pgsql:".($socket ? "unix_socket=$socket" : ''));
		
		if ($database) {
			$dsn[] = "dbname={$database}";
		}
		
		$this->configs[$name] = (object)array(
			'dsn'=>implode(';', $dsn),
			'username'=>$username,
			'password'=>$password,
			'options'=>$options,
		);
		
		return $this;
	}
	
	
	/**
	 * Performs the request query as a prepared statement and returns the results in the format constant specified:
	 *  Pool::RETURN_NONE               Returns the number of affected rows.
	 *  Pool::RETURN_FULL               Returns an indexed array of column named arrays for all rows returned.
	 *  Pool::RETURN_SINGLE_ROW         Returns a column named array of the first row returned
	 *  Pool::RETURN_SINGLE_COLUMN      Returns an indexed array of all row results in the first column
	 *  Pool::RETURN_SINGLE_CELL        Returns a string containing the first column of the first row
	 *
	 * @param string $query The SQL query to perform.
	 * @param array $data Array containing the bound parameters
	 * @param integer $mode Format to return the requested data as.
	 * @param string $name optional Name of the link to use. If omitted, grabs the first config defined
	 * @return array|string
	 * @static
	 */
	public function runQuery($query, array $data = null, $mode=self::RETURN_FULL, $name = null) {
		if ($name === null) {
			$name = array_keys($this->$configs);
			$name = reset($name);
		}
		
		$pdo = $this->getLink($name);
		
		$result = $pdo->prepare($query);
		$result->execute($data);
		
		$c = $result->rowCount();
		
		switch ((int)$mode) {
			case self::RETURN_NONE:
				return $c;
			
			case self::RETURN_SINGLE_ROW:
				return $c ? $result->fetch(PDO::FETCH_ASSOC):array();
				
			case self::RETURN_SINGLE_COLUMN:
				return $c ? $result->fetchAll(PDO::FETCH_COLUMN, 0):array();				
				
			case self::RETURN_SINGLE_CELL:
				if ($c) {
					$row = $result->fetch(PDO::FETCH_NUM);
					return $row[0];
				} else {
					return null;
				}
					
			case self::RETURN_FULL:
			default:
				return $c ? $result->fetchAll(PDO::FETCH_ASSOC):array();
		}
	}
	
	
	protected static $instance;
	public static function Singleton() {
		return static::$instance ?: static::$instance = new static();
	}
}