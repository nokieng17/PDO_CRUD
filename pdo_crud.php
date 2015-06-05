<?php 
/**
*Create by TK.KING NOKIENG 
*date 27/4/2015
* this class is use PDO to CRUD: CREATE, READ, UPDATE, DELETE table.
*this class is modified by Techgang ceit final poject 2015. advisor Tech Mekchone BOUNTHAN
*Copyright by NOKIENG
*/ 
class pdo_crud
{
	private $conn = false;	//check if DB connect or not, this must be correct first when new this class
	
	function __construct()
	{
		require_once './class/pdoconfig.class.php';
		if ($PDOConfig) {
			if (!$conn) {
				$conn = $PDOConfig->init_dbh();				
			}	else{
				$conn = $PDOConfig->init_dbh();
			}
		}	else{
			$PDOConfig = new PDOConfig();
			$conn = $PDOConfig->init_dbh();
		}
	}

	function __destruct()
	{
		if ($conn) {
			$PDOConfig = null;
			$conn = null;
		}
	}

/*
*table must not be null
*table must be a string
*if table is an array, the array must be none multidimention array, and with count = 1 or one value
*other format object would not be accepted
*/
private function checkTable($table = null){
	if ($table == null) {
		throw new Exception("Error no table to query : table");
	}	elseif (!is_string($table)) {
		if (is_array($table)) {
			if (!(count($table) == 1 && count($table) == count($table, COUNT_RECURSIVE))) {
				throw new Exception("Error table name except array size 1 and none multidimention array : table");	
			}
		}	else{
			throw new Exception("Error unsupport String or array table name : table");
		}
	}
}

/*
*columnHacks might be null in some case
*columnHacks might be string like _ID = ? for future bindParam
*columnHacks might be an none multidimention array
*other format object would not be accepted
*/
private function checkColumnHacks($columnHacks = null, $isAllowNull = true){
	if ($columnHacks != null) {
		if (is_array($columnHacks)) {
			if (count($columnHacks) != count($columnHacks, COUNT_RECURSIVE)) {
				throw new Exception("Error columnHacks accepted none multidimention array : columnHacks");			
			}
		}	else{
			throw new Exception("Error columnHacks accepted only none multidimention array : columnHacks");		
		}
	}	else{
		if ($isAllowNull == false) 
			throw new Exception("Error columnHacks is not allow null : columnHacks");			
	}
}

/*
*values must not be null
*values could be either none or multidimention array()
*if checkValues is sent signal $isMultiArr true 
*	values must be normal or none multidimention array()
*if checkValues is sent signal $isMultiArr false
*	values is multidimention array()
*	values must be BOX array() or all sub array must have the same count or size
*values is excepted only array()
*values is not supposed to be null
*by default this values is excepted only none multidimention array()
*/
private function checkValues($values = null, $isMultiArr = false){
	if ($values != null) {
		if (is_array($values)) {
			if ($isMultiArr == false) {
				if (!(count($values) == count($values, COUNT_RECURSIVE)))
					throw new Exception("Error you must pass none multidimention array : values");	
			}	else{
				$max = max(array_map('count', $values));
				$lenght = count($values);
				for ($i=0; $i < $lenght ; $i++) { 
					if ($max != count($values[$i])) 
						throw new Exception("Error array multidimensional does not BOX array. array with all child equally in size");
				}
			}
		}	else{
			throw new Exception("Error it is accepted only array : values");		
		}
	}	else{
		throw new Exception("Error values must not be null : values");		
	}
}
/*
*selection and selectionArgs might be null
*selection must be string, not excepted other
*selection must contain ? to make sure selection is passed in format like _ID = ?
*selectionArgs must be array()
*selectionArgs could be multidimention array() for bulkDelete 
*				(delete multiple row like _ID = ? and selectionArgs = array(array("1"), array("3")))
*selectionArgs if isBulk is true the array() must be BOX array
*/
private function checkSelectionAndSelectionArgs($selection = null, $selectionArgs = null, $isAllowNull = true, $isBulk = false){
	if ($selection != null && $selectionArgs != null) {
		if (!is_string($selection)) {
			throw new Exception("Error selection is excepted only String : selection");		
		}	else{
			if (!strpos($selection, "?")) 
				throw new Exception("Error invalid selection, it would look like _ID = ? : selection");			
		}
		if (!is_array($selectionArgs)) {
			throw new Exception("Error invalid selectionArgs, it excepted only array() : selectionArgs");
		}	else{
			if ($isBulk) {
				$maxCountArgs = max(array_map('count', $selectionArgs));
				for ($i=0; $i < count($selectionArgs) ; $i++) { 
					if (count($selectionArgs[$i]) != $maxCountArgs)
						throw new Exception("Error isBulk is true, and selectionArgs must be BOX array() only : selectionArgs");
				}
			}	else{
				if (count($selectionArgs) != count($selectionArgs, COUNT_RECURSIVE))
					throw new Exception("Error it could not be multidimention array(), because isBulk is false: selectionArgs");
			}
		}
	}	else {
		if ($isAllowNull == false) 
			throw new Exception("Error you must have selection where to update, and selectionArgs : selection || selectionArgs");
	}
}



	/**
	*@param $table 		TABLE name
	*String
	*@param $column  	Which COLUMN will you count, if NULL it will count all
	*String
	*@param $selection  WHICH column Will you include CASE WHERE like _ID = ?
	*String
	*@param $selectionArgs IF YOU INDICAT @param $selection, you have to sent this value
	*						as array. 
	*note number ? in $selection must equal size of $selectionArgs
	*/

	
	public function getCount($table, $column = NULL, $selection =NULL, $selectionArgs = NULL){
		$this->checkTable($table);
		$this->checkSelectionAndSelectionArgs($selection, $selectionArgs, true, false);
		if ($column == NULL) {
			$column = "*";
		}
		$sql = "SELECT count($column) as count FROM ".$table;
		if ($selection != NULL) {
			$sql .=" WHERE " .$selection;
		}
		$PDOConfig = new PDOConfig();
		$conn = $PDOConfig->init_dbh();
		$count = $conn->prepare($sql);
		if ($selection != NULL) {
			$count->execute($selectionArgs);
		}	else{
			$count->execute();
		}
		if ($count) {
			$total = $count->fetch(PDO::FETCH_NUM);
			return $total[0];
		}
		$conn = NULL;
		return -1;
	}

	/**
	*this class is copy algorithm from ContentProvider android contentProvider class
	*	@param $table 		the table name that we want to query
	*	@param $columnHacks define column output, or column hacks. 
	*			(array)		if null it will select * (all column), but define some it will return column you insert
	*	@param $selection  A selection criteria to apply when filtering rows.
    *     		(String)	If {@code null} then all rows are included.
	*						example: userName = ? AND password = ?. leave "?" here
	*						but all values is insert to @param $selectionArgs as array with size equal num column $select
	*	@param $selectionArgs You may include ? in selection, which will be replaced by
    *    		(array)		   the values from selectionArgs, in order that they appear in the selection.
    *					       The values will be bound as Strings.
    *	@param $sortOrder How the rows in the cursor should be sorted.
    *     		(String)	   If {@code null} then the provider is free to define the sort order.
    *	@param $limit 		limit the output row. you may pass LIMIT 0,5 or LIMIT 10,20 (10 records)
	*/


	public function query($table = null, $columnHacks = null, $selection = null, $selectionArgs = null, $sortOrder = null, $limit = null){
		$this->checkTable($table);
		$this->checkColumnHacks($columnHacks, true);
		$this->checkSelectionAndSelectionArgs($selection, $selectionArgs, true, false);
		if ($columnHacks == null) {
			$sqlQuery = "SELECT * FROM ".$table;
		}	else{
			$count = count($columnHacks); 	//start with 1
			$columns = "";
			for ($i = 0; $i < $count ; $i++) {
				$columns .= $columnHacks[$i];
				if (($count -1) != $i) {
				 	//if last do not put "," after
				 	$columns .= ",";
				 }
			}
			$sqlQuery = "SELECT ".$columns." FROM ".$table;
		}
		if ($selection != null && $selectionArgs != null) {			
			$sqlQuery .= " WHERE ". $selection;
		}
		if ($sortOrder != null) {
			$sqlQuery .= " ORDER BY " .$sortOrder;
		}
		if ($limit != null) {
			$sqlQuery .= " LIMIT " .$limit;
		}
		//escape string before query. why we do here because PDO does not mind any '' or "" so escape here is better
		$sqlQuery = mysql_real_escape_string($sqlQuery);
		$PDOConfig = new PDOConfig();
      	$conn = $PDOConfig->init_dbh();
   	  	$query = $conn->prepare($sqlQuery);
   	  	if ($selectionArgs != null && $selection != null) {
   	  		$query->execute($selectionArgs);
   	  	}	else{
   	  		$query->execute(); 
   	  	}
   	  	// var_dump($query);
   	  	if ($query) {
   	  		return $query->fetchAll(PDO::FETCH_ASSOC);
   	  	}
   	  	$conn = NULL;
   	  	return false;
	}


	/**
	* this function is for advanced query, if you want to query with multiple table or JOIN table use it
	*@param $sql 		param sql must contain SELECT .. FROM table JOIN 
	*					if select item sql must contain like _ID = ? for specific column,
	*					and refer values to $params as array values
	*/
	public function advanceQuery($sql = NULL, $params = NULL, $sortOrder = NULL, $limit = NULL){
		//sql must not be null
		if ($sql == NULL || trim($sql) == "")
			throw new Exception("Error Empty sql command : sql");
		//sql command must contain SELECT to perform SELECT
		if (!preg_match("/select/", strtolower($sql)))
			throw new Exception("Error query must contain SELECT command : sql");
		//if sql contain replace string ?, $params must not be null
		if (strpos($sql, "?"))
			if ($params == NULL || trim($params) == "") {
				throw new Exception("Error you get something like _ID = ? so you have to pass array as vales : sql");
			}
		//sql must contain JOIN and WHERE to perform multiple query or advance query
		if (!strpos(strtolower($sql), "join"))
			throw new Exception("Error sql must contain command JOIN : sql");
		//if $params is not null, $params must be normal array() only
		if ($params != NULL || trim($params) != "")
			if (!(count($params) == count($params, COUNT_RECURSIVE)))
				throw new Exception("Error you must pass none multidimention array : values");

		if ($sortOrder != null) {
			$sql .= " ORDER BY " .$sortOrder;
		}
		if ($limit != null) {
			$sql .= " LIMIT " .$limit;
		}
		$sql = mysql_real_escape_string($sql);
		$config = new PDOConfig();
		$conn = $config->init_dbh();
		$query = $conn->prepare($sql);
		//if $params is empty, or not select specific values, or item, let it execute()
		if (empty($params) || $params == NULL || trim($params) == ""){
			$query->execute();
		}	else{
			$query->execute($params);			
		}
		if ($query) {
			return $query->fetchAll(PDO::FETCH_ASSOC);
		}
		return false;
	}

	/**
	*This function is used to insert only one row, if multiple row might use bulkInsert instead
	*	@param $table 			define table name
	*	@param $columnHacks 	define column which you want to insert, custom insert into specific column
	*	@param $arrValues 		this is only one row array (one set of array). 
	*	note: 					size of columnHacks and $arrValues must be the same
	*	note: 					for instance insert or BULK_INSERT you can pass multidimensional array
	*							here. it will loop to insert automatically.:D
	*							and return multidimension array with array set of lastinsertID				
	*/
	public function insert($table = null, $columnHacks = null, $arrValues = null){
		$this->checkTable($table);
		$this->checkColumnHacks($columnHacks, false);
		$this->checkValues($arrValues, true);

		if (count($arrValues) == count($arrValues, COUNT_RECURSIVE)) {
			//in case of normal array
			$countV = count($arrValues);
		}	else{
			//incase of multidimensional array
			$countV = max(array_map('count', $arrValues));
		}

		$columns = "";
		if ($columnHacks != null) {		
			if (is_array($columnHacks)) {		
				$countH = count($columnHacks);
				if ($countH != $countV) 
					throw new Exception("Error number of columnHacks and arrValues not match : columnHacks | arrValues");
				$columns = "( ";
				for ($i=0; $i < $countH ; $i++) { 
					$columns .= $columnHacks[$i];
					if (($countH -1) != $i) {
						$columns .= ", ";
					}	else{
						$columns .= " )";
					}
				}
			}	else{
				$columns = $columnHacks;
			}				
		}
		
		$mark = "";
		for ($i=0; $i < $countV ; $i++) { 
			$mark .= " ? ";
			if (($countV - 1) != $i) {
				$mark .= ", ";
			}
		}
		$sqlInsert = mysql_real_escape_string($sqlInsert);
		$sqlInsert = "INSERT INTO " .$table .$columns. " VALUES( " .$mark. " )";
		$PDOConfig = new PDOConfig();
   		$conn = $PDOConfig->init_dbh();
   		$insert = $conn->prepare($sqlInsert);

   		//check if values insert is multidimention
   		if (count($arrValues) == count($arrValues, COUNT_RECURSIVE)) {
   			// echo "array is not multidimensional";
   			$insert->execute($arrValues);
   			if ($insert) return $conn->lastInsertId();
   		}	else{
   			// echo "array is multidimensional";
   			$numberInsert = 0;
   			$returnArray = array();
   			$max = max(array_map('count', $arrValues));
   			// var_dump($arrValues[0]);
   			for ($i=0; $i < count($arrValues); $i++) {
   				//box array should have COUNT_RECURSIVE(all array element) and array count * sub array count
   				$arr = $arrValues[$i];
   				$insert->execute($arr);
   				$lastInsertId = $conn->lastInsertId();
   				$numberInsert += 1;
   				$returnArray[$i] = array($numberInsert => $lastInsertId);
   			}
   			if ($insert) return $returnArray;
   		}
/*	   		var_dump($insert);
   		var_dump($arrValues);*/
		return false;

	}

	/**
	*this function is used to update your table. 
	*	@param 	$table 			it define table name.
	*			(String)
	*	@param 	$columnHacks 	define column which you want to update into specific column
	*			(Array)
	*	@param 	$values 		values you want to update. you must pass here as array, 
	*			(array)				array with multidimention is not excepted	
	*/

	public function update($table = null, $columnHacks = null, $values = null, $selection = null, $selectionArgs = null){
		$this->checkTable($table);
		$this->checkColumnHacks($columnHacks, false);
		$this->checkValues($values, false);
		//here it got valid values both columnHacks and values
		//but not, if it is passed with different both size or count
		if (is_array($columnHacks)) {
			if (count($columnHacks) != count($values))
				throw new Exception("Error size of columnHacks and values must be equally : columnHacks || values");				
		}
		$this->checkSelectionAndSelectionArgs($selection, $selectionArgs, false, false);

		$sqlUpdate = "UPDATE " .$table. " SET ";
		$columns = "";
		if (!(is_array($columnHacks))) {
			$columns = $columnHacks;
		}	else{
			$countH = count($columnHacks);
			for ($i=0; $i < $countH; $i++) {
				$columns .= $columnHacks[$i]. " = ? ";
				if (!(($countH -1 ) == $i)) {
					$columns .= ", ";
				}
			}
		}
		
		$sqlUpdate .= $columns;
		$sqlUpdate .= " WHERE " .$selection;
		$param = array_merge($values, $selectionArgs);
		$sqlUpdate = mysql_real_escape_string($sqlUpdate);
		$PDOConfig = new PDOConfig();
   		$conn = $PDOConfig->init_dbh();
   		$update = $conn->prepare($sqlUpdate);
   		$update->execute($param);
   		if ($update) {
   			return $update->rowCount();
   		}
   		$conn = NULL;
		return false;
	}

/**
*this delete function
*@param $table 			table name you want to delete record from
*		(String)
*@param $selection 		selection of your argument, you must sent selection like _ID = ?
*		(String)
*@param $selectionArgs 	selectionArgs of your param that you want to replace ? assign abouve in selection
*		(Array)			array with multidimention is not excepted
*						this if delete success, it return num row effected, if failed return false
*/

	public function delete($table = null, $selection = null, $selectionArgs = null){
		$this->checkTable($table);
		if ($selection == null && $selectionArgs == null)
			throw new Exception("Error selection and selectionArgs is null, if you wish to empty table use selection : _ID = ? and selectionArgs : IS NOT NULL instead");			
		$this->checkSelectionAndSelectionArgs($selection, $selectionArgs, false, true);

		$sqlDelete = "DELETE FROM " .$table. " WHERE " .$selection;
		$sqlDelete = mysql_real_escape_string($sqlDelete);
		$PDOConfig = new PDOConfig();
		$conn = $PDOConfig->init_dbh();
		$delete = $conn->prepare($sqlDelete);
		$countArgs = count($selectionArgs);
		if ($countArgs == count($selectionArgs, COUNT_RECURSIVE)) {			
			$delete->execute($selectionArgs);
			if ($delete) {
				$rowEffect = $delete->rowCount();
				return $rowEffect;
			}
		}	else{
			$result = array();
			for ($i=0; $i < $countArgs ; $i++) { 
				$delete->execute($selectionArgs[$i]);
				$rowEffect = $delete->rowCount();
				$result[$i] = $rowEffect;
			}
			return $result;
		}
		$conn = NULL;
		return false;
	}
	
}
?>
