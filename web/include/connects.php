<?php
class dbconnection
{

    public $connection;
    public $host;
    public $login;
   public $pass;
   public $dbname;
    
   public function __construct() {
    
    $this->host = "localhost";
    $this->login = "elitewave360";
   $this->pass = "oIWUIhhjF9Tsm0K2lT0GNHtWD";
   $this->dbname = "elitewave360web";
   if ($this->connection = mysqli_connect($this->host,$this->login,$this->pass) or die(mysqli_error()))
   {
       mysqli_select_db($this->connection,$this->dbname) or die(mysqli_error($this->connection)." in line ".__LINE__);
       return $this->connection; 
   }
   else{
    die(mysqli_error());		
   }
    	
   }
    
	function query($query,$method)	{
      
		$finArr = array();
		$result = mysqli_query($this->connection,$query) or die("Error".mysqli_error($this->connection));
		
		if (!$error = mysqli_error($this->connection))
		{
			if ($method == 1)	{			
				while ($currObj == mysqli_fetch_assoc($result))
					$resultArray[] = $currObj;
					return $resultArray;
			}			
			elseif($method == 2) { 
				while($reArray = mysqli_fetch_array($result))
					$finArr[] = $reArray;
				return $finArr;
			}
		
		}
		else
			return $error;
	}
	function action_query($query,$return_insert_id='')
	{
        
		$result = mysqli_query($this->connection,$query) or die("Error".mysqli_error($this->connection));
		if($return_insert_id != "")
		{
			return mysqli_insert_id($this->connection);
		}else{
        	return true;
        }
	}
	

	

}
?>