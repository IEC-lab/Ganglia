<?php
  include_once("config.php");  

  class SQLiteDB extends SQLite3{
    function __construct(){
      try{	    
	global $sqlite3DbPath;
        $this->open($sqlite3DbPath);
      }catch(Exception $e){
        die($e->getMessage());
      }
    }
  }

  class LogSQLiteDB extends SQLite3{
    function __construct(){
      try{	    
	global $logSqlite3DbPath;
        $this->open($logSqlite3DbPath);
      }catch(Exception $e){
        die($e->getMessage());
      }
    }
  }
?>
