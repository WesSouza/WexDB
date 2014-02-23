<?php

/**
 * WexDB
 * 
 * Simple interface based on some methods from ADOdb. Uses and requires PDO.
 * Works only with MySQL.
 * 
 * @version 1.0
 * @author Wesley de Souza <dev@wex.vc>
 * @license MIT
 */

/**
 * Main database class
 *
 * The main class used to connect and make queries to the database.
 */
class WexDB
{
	/**
	 * PDO connection.
	 * @var object
	 */
	private $pdo = null;
	
	/**
	 * Cache for the function tableMeta.
	 * @var array
	 */
	private $tableMetaCache = array();
	
	/**
	 * Table prefix for including where {table_name} is used.
	 * @var string
	 */
	public $tablePrefix = '';
	
	/**
	 * Number of rows affected by the latest statement.
	 * @var integer
	 */
	public $affectedRows = 0;
	
	/**
	 * Constructor
	 * 
	 * Creates the connection with the database.
	 *
	 * @param string $hostname Host of the database server
	 * @param string $username Username
	 * @param string $password Password
	 * @param string $database Database to use
	 */
	public function __construct ( $hostname, $username, $password, $database )
	{
		try
		{
			$this->pdo = new PDO( 'mysql:host='. $hostname .';dbname='. $database .'', $username, $password );
		}
		catch ( PDOException $e )
		{
			$this->triggerError( $e->getCode() .' '. $e->getMessage() );
			exit;
		}
	}
	
	/**
	 * Error Trigger
	 * 
	 * Triggers an error to the Error class.
	 *
	 * @param string $message Error message
	 */
	private function triggerError ( $message )
	{
		throw new Exception( 'Database Error: '. $message, 1 );
	}
	
	/**
	 * Query Prepare
	 * 
	 * Prepares the query for execution, replacing numerous thing.
	 *
	 * @param string $query SQL Query
	 */
	private function queryPrepare ( $query )
	{
		$query = preg_replace( '/\{([^\}]+)\}/', $this->tablePrefix .'\\1', $query );
		return $query;
	}
	
	/**
	 * Query
	 * 
	 * Executes the query and return the resultset.
	 *
	 * @param string $query Query to be executed
	 * @param string $argument1 First bind argument
	 * @param string $argument2 Second bind argument
	 * @param string $argumentn Other bind arguments
	 * @return object WexDB_RS object with the executed resultset
	 */
	public function query ( )
	{
		$arguments = func_get_args();
		if ( is_array( $arguments[0] ) && count( $arguments ) == 1 )
			$arguments = $arguments[0];
		$query = array_shift( $arguments );
		if ( is_array( $arguments[0] ) && count( $arguments ) == 1 )
			$arguments = $arguments[0];
		
		$query = $this->queryPrepare( $query );
		
		$stmt = $this->pdo->prepare( $query );
		@$stmt->execute( $arguments );
		
		$this->affectedRows = $stmt->rowCount();
		
		if ( $stmt->errorCode() != '00000' )
			$this->triggerError( implode( ' ', $stmt->errorInfo() ) .' - '. $query );
		
		return new WexDB_RS( $this, $stmt );
	}
	
	/**
	 * Fetch all results
	 * 
	 * Executes the query and returns an array with all the fetched results.
	 *
	 * @param string $query Query to be executed
	 * @param string $argument1 First bind argument
	 * @param string $argument2 Second bind argument
	 * @param string $argumentn Other bind arguments
	 * @return array An array containing all rows
	 */
	public function fetchAll ( )
	{
		$arguments = func_get_args();
		$rs = $this->query( $arguments ); $res = array();
		while ( $ln = $rs->fetch() )
			$res[] = $ln;
		return $res;
	}
	
	/**
	 * Fetch one row
	 * 
	 * Executes the query and returns an array with just the first row of the
	 * resultset.
	 *
	 * @param string $query Query to be executed
	 * @param string $argument1 First bind argument
	 * @param string $argument2 Second bind argument
	 * @param string $argumentn Other bind arguments
	 * @return array An array containing all columns from the first row
	 */
	public function fetchRow ( )
	{
		$arguments = func_get_args();
		$rs = $this->query( $arguments ); $res = array();
		if ( $ln = $rs->fetch() )
			$res = $ln;
		return $res;
	}
	
	/**
	 * Fetch associatively
	 * 
	 * Executes the query and return the resultset in an array where the first
	 * column is the key. If there are more then two columns, each row will have
	 * an array with all fetched columns, otherwise the value will be the column
	 * value itself.
	 *
	 * @param string $query Query to be executed
	 * @param string $argument1 First bind argument
	 * @param string $argument2 Second bind argument
	 * @param string $argumentn Other bind arguments
	 * @return array Associative array from the resultset
	 */
	public function fetchAssoc ( )
	{
		$arguments = func_get_args();
		$rs = $this->query( $arguments ); $res = array();
		while ( $ln = $rs->fetch() )
		{
			$key = array_shift( $ln );
			if ( count( $ln ) == 1 )
				$res[ $key ] = array_shift( $ln );
			else
				$res[ $key ] = $ln;
		}
		return $res;
	}
	
	/**
	 * Fetch first column
	 * 
	 * Executes the query and return an array where the value is the first column
	 * from the query.
	 *
	 * @param string $query Query to be executed
	 * @param string $argument1 First bind argument
	 * @param string $argument2 Second bind argument
	 * @param string $argumentn Other bind arguments
	 * @return array Array containing only the first column
	 */
	public function fetchCol ( )
	{
		$arguments = func_get_args();
		$rs = $this->query( $arguments ); $res = array();
		while ( $ln = $rs->fetch( false ) )
			$res[] = $ln[0];
		return $res;
	}
	
	/**
	 * Fetch first cell
	 * 
	 * Executes the query and return only the first column of the first row.
	 *
	 * @param string $query Query to be executed
	 * @param string $argument1 First bind argument
	 * @param string $argument2 Second bind argument
	 * @param string $argumentn Other bind arguments
	 * @return mixed The value as returned from the database
	 */
	public function fetchOne ( )
	{
		$arguments = func_get_args();
		$rs = $this->query( $arguments );
		while ( $ln = $rs->fetch( false ) )
			return $ln[0];
	}
	
	/**
	 * Table Meta
	 * 
	 * Returns the meta from the table and caches the results for the next run.
	 *
	 * @param
	 * @return integer The ID of the last inserted row
	 */
	public function tableMeta ( $table )
	{
		// PDO::quote doesn't work on table and column names, so let's implement safety
		if ( !preg_match( '/^[a-zA-Z0-9_\{\}\.]+$/', $table ) )
			$this->triggerError( 'WexDB::tableMeta - Invalid table name: '. $table .'' );
		
		if ( !$this->tableMetaCache[ $table ] )
			$this->tableMetaCache[ $table ] = $this->fetchAssoc( 'SHOW COLUMNS FROM `'. str_replace( '.', '`.`', $table ) .'`' );
		
		return $this->tableMetaCache[ $table ];
	}
	
	/**
	 * Automatic Insert/Update
	 * 
	 * Generates and executes a query to either insert or update a row on the
	 * database. If the $where argument is present, executes an UPDATE, if not,
	 * an INSERT.
	 *
	 * @param string $table Table name
	 * @param array $fields Associative array with the column values
	 * @param string $where 'Where' section of the UPDATE query
	 * @param string $argument1 First bind argument
	 * @param string $argument2 Second bind argument
	 * @param string $argumentn Other bind arguments
	 */
	public function insertUpdate ( )
	{
		$arguments = func_get_args();
		if ( is_array( $arguments[0] ) && count( $arguments ) == 1 )
			$arguments = $arguments[0];
		$table = array_shift( $arguments );
		$fields = array_shift( $arguments );
		if ( is_array( $arguments[0] ) && count( $arguments ) == 1 )
			$arguments = $arguments[0];
		
		// PDO::quote doesn't work on table and column names, so let's implement safety
		if ( !preg_match( '/^[a-zA-Z0-9_\{\}\.]+$/', $table ) )
			$this->triggerError( 'WexDB::insertUpdate - Invalid table name: '. $table .'' );
		
		$tableMeta = $this->tableMeta( $table );
		
		foreach ( $fields as $column => $value )
		{
			if ( !$tableMeta[ $column ] )
				$this->error->trigger( 'WexDB::insertUpdate - Invalid column: '. $column .'' );
			
			switch ( $tableMeta[ $column ]['type'] )
			{
				case 'date':
					if ( preg_match( '/^[0-9]+$/', $value ) )
						$fields[ $column ] = date( 'Y-m-d', $value );
					break;
				case 'datetime':
					if ( preg_match( '/^[0-9]+$/', $value ) )
						$fields[ $column ] = date( 'Y-m-d H:i:s', $value );
					break;
			}
		}
		
		$columns = array();
		$values = array();
		
		foreach ( $fields as $column => $value )
		{
			$columns[] = '`'. $column .'`';
			$values[] = $value;
		}
		
		if ( count( $columns ) == 0 )
			$this->error->trigger( 'WexDB::insertUpdate - Zero columns to insert/update' );
		
		if ( !count( $arguments ) )
		{
			$query = 'INSERT INTO '. $table .' ( '. implode( ', ', $columns ) .' ) VALUES ( '. implode( ', ', array_fill( 0, count( $values ), '?' ) ) .' )	';
			$arguments = $values;
		}
		else
		{
			$where = array_shift( $arguments );
			$query = 'UPDATE '. $table .' SET '. implode( ' = ?, ', $columns ) .' = ? WHERE '. $where .'';
			$arguments = array_merge( $values, $arguments );
		}
		
		$this->query( $query, $arguments );
	}
	
	/**
	 * Last Insert ID
	 * 
	 * Returns the last inserted ID from a table with auto_increment attribute.
	 *
	 * @return integer The ID of the last inserted row
	 */
	public function lastId ( )
	{
		return $this->pdo->lastInsertId();
	}
	
	/**
	 * Quote
	 * 
	 * Safely puts the parameter string into quotes.
	 *
	 * @param string $string The string to be quoted
	 * @return string Quoted string
	 */
	public function q ( $string )
	{
		return $this->pdo->quote( $string );
	}
}

/**
 * Resultset Class
 *
 * The class used to hold a resultset and navigate through it.
 */
class WexDB_RS
{
	/**
	 * Parent WexDB object.
	 * @var object
	 */
	private $db = null;
	
	/**
	 * PDO statement object.
	 * @var object
	 */
	private $stmt = null;
	
	/**
	 * Constructor
	 * 
	 * Configures the resultset.
	 *
	 * @param object $stmt PDO statement object
	 */
	public function __construct ( $db, $stmt )
	{
		$this->db = $db;
		$this->stmt = $stmt;
	}
	
	/**
	 * Fetch
	 * 
	 * Fetches the current row and jumps to the next. Returns an associative
	 * array where the key is the column's name or a sequential number.
	 *
	 * @param boolean $assoc Whether the resulting array should be associative (true) or numeric (false)
	 * @param boolean $keep_key_case Whether to keep the associative keys' cases (true) or change them to lowercase (false) (default: false)
	 * @return array Current row's array
	 */
	public function fetch ( $assoc = true, $keep_key_case = false )
	{
		$ln = $this->stmt->fetch( $assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM );
		if ( is_array( $ln ) && $assoc && !$keep_key_case )
			$ln = array_change_key_case( $ln );
		return $ln;
	}
	
	/**
	 * Next resultset
	 * 
	 * Stored procedures can return more than one resultset. In this case, calling this method will switch to the next resultset.
	 */
	public function nextRs ( )
	{
		return $this->stmt->nextRowset();
	}
}
