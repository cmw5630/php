<?
class db {
	var $db_host, $db_name, $db_user, $db_pass, $db_conn;
	
	function db ($arrayDBInfo) {
		$this->db_host = $arrayDBInfo['host'];
		$this->db_name = $arrayDBInfo['name'];
		$this->db_user = $arrayDBInfo['user'];
		$this->db_pass = $arrayDBInfo['pass'];

		$this->dbConnect();
	}
	function dbConnect () {
		if (!$this->db_conn = mysqli_connect($this->db_host, $this->db_user, $this->db_pass)) {
			$this->error('DB connection error..');
		}
		else {
			if (!mysqli_select_db($this->db_conn, $this->db_name)) {
				$this->error('DB selection error..');
			}
		}
		@mysqli_query($this->db_conn, 'set names euckr');
	}

	function fetch ($res, $mode=0)
	{
		if ($res) {
			if (!is_object($res)) $res = $this->query($res);
			return (!$mode) ? @mysqli_fetch_array($res, MYSQLI_BOTH) : @mysqli_fetch_array($res, MYSQLI_ASSOC);
		}
	}

	function count_($result)
	{
		if(is_object($result)) $rows = mysqli_num_rows($result);
		if ($rows !== null) return $rows;
	}

	function query ($sql) {
		$sql			= trim($sql);
		$result			= mysqli_query($this->db_conn, $sql);
		return $result;
	}

	function select ( $table, $where, $field = "*" ) {
		$sql				= "Select $field from $table $where";
		$result			= $this->query($sql);
		return $result;
	}

	function select_field ( $table, $where, $field ) {
		$sql				= "Select $field from $table $where";
		$result			= $this->query( $sql );
		return $result;
	}

	function all_select ( $sql ) {
		$sql				= trim( $sql );
		$result		= $db->select_all( $sql);
//		$result			= $this->query( $sql );
		return $result;
	}

	function object ( $table, $where, $field = "*" ) {
		$sql				= "Select $field from $table $where";
		$result			= $this->query( $sql );
		$row			= @mysqli_fetch_object($result);
		return $row;
	}

	function object_field ( $table, $where, $field) {
		$sql				= "Select $field from $table $where";
		$result			= $this->query( $sql );
		$row			= @mysqli_fetch_object($result);
		return $row;
	}

	function row ( $table, $where, $field = "*" ) {
		$sql				= "Select $field from $table $where";
		$result			= $this->query( $sql );
		$row			= @mysqli_fetch_row($result);
		return $row;
	}

	function row_field ( $table, $where, $field) {
		$sql				= "Select $field from $table $where";
		$result			= $this->query( $sql );
		$row			= @mysqli_fetch_row($result);
		return $row;
	}


	function sum ( $table, $where, $field = "*" ) {
		$sql				= "Select sum($field) from $table $where";
		$result			= $this->query( $sql );
		$row			=  @mysqli_fetch_row($result);
		if( $row[0] ) { return $row[0]; } else { return 0;}
	}

	function cnt ( $table, $where, $field = "F_idx" ) {
		$sql				= "Select count($field) from $table $where";
		$result			= $this->query( $sql );
		$row			=  @mysqli_fetch_row($result);
		if( $row[0] ) { return $row[0]; } else { return 0;}
	}

	function max_ ( $table, $where, $field = "idx" ) {
		$sql				= "Select max($field) from $table $where";
		$result			= $this->query( $sql );
		$row			=  @mysqli_fetch_row($result);
		if( $row[0] ) { return $row[0]; } else { return 0;}
	}

	function min_ ( $table, $where, $field = "idx" ) {
		$sql				= "Select min($field) from $table $where";
		$result			= $this->query( $sql );
		$row			=  @mysqli_fetch_row($result);
		if( $row[0] ) { return $row[0]; } else { return 0;}
	}

	function insert ( $table, $data ) {
		$sql				= "insert into $table set $data";
		if($this->query( $sql )) { return true; } else { return false; }
	}

	function update ( $table, $data ) {
		$sql				= "update $table set $data";
		if($this->query( $sql )) { return true; } else { return false; }
	}
	
	function delete ( $table, $data ) {
		$sql				= "delete from $table $data";

		if($this->query( $sql )) { return true; } else { return false; }
	}
	
	function dropTable ( $data ) {
		$sql				= "drop table $data";
		if($this->query( $sql )) { return true; } else { return false; }
	}

	function createTable ( $data ) {
		$sql				= "create table $data";
		if($this->query( $sql )) { return true; } else { return false; }
	}

	function stripSlash ( $str ) {
		$str				= trim( $str );
		$str				= stripslashes( $str );
		return $str;
	}

	function addSlash ( $str ) {
		$str				= trim( $str );
		$str				= addslashes( $str );
		if(empty( $str )) {
			$str			= "NULL";
		}
		return $str;
	}

	function addSlash_chk ( $str ) {
		$str				= trim( $str );
		$str				= stripslashes( $str );
		$str				= addslashes( $str );
		$str				= mysqli_real_escape_string( $str );
		return $str;
	}

	function stripSlash_chk ( $str ) {
		$str				= trim( $str );
		$str				= stripslashes( $str );
		$str				= mysqli_real_escape_string( $str );
		return $str;
	}

	function prints ( $table, $where, $field = "*" ) {
		echo "Select $field from $table $where<br>";		
		return;
	}

	function printu ( $table, $data ) {
		echo "update $table set $data<br>";	
		return;
	}

	function printi ( $table, $data ) {
		echo "insert into $table set $data<br>";	
		return;
	}

	function error ($errorMsg) {
		echo '<div>' . mysqli_errno() . '</div><div>' . $errorMsg . '</div>';
		return;
	}
}

?>