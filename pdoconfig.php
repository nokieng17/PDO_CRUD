<?php 
/**
* 
*/
class PDOConfig 
{
	private $serverName = "";
	private $userName = "";
	private $password = "";
	private $dbName = "";
	private $conn;
	function __construct()
	{		
		$this->serverName = "localhost";
		$this->userName = "NOKIENG";
		$this->password = "12345";
		$this->dbName = "ceitdatabase";
	}
/*	function __destruct() {
		if (isset($conn)) {
			if (!empty($conn)) {
				if ($conn->isOpen) {
					# code...
				}
			}
		}
	}*/
    public function init_dbh()
    {
       try{
			$conn  = new PDO("mysql:host=$this->serverName;dbname=$this->dbName", $this->userName, $this->password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			// echo "Success";
			return $conn;
		}	catch(PDOException $e){
			print_r($e);
			// echo "<br> failed";
			return false;
		}
	}
}

?>
