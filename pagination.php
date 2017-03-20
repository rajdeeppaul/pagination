<?php
	/*
	MIT License

	Copyright (c) 2016 Rajdeep Paul

	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all
	copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
	SOFTWARE.
	
	VERSION: 1.02
	AUTHOR: Rajdeep Paul
	*/
	class Pagination{
		private $connection;
		private $databaseDriver;
		private $statement;
		private $rowsPerPage;
		private $numOfPaginationLinks;
		private $queryString;
		private $parameters;
		private $currentPage;

		function __construct($databaseDriver, $hostname, $username, $password, $databaseName){
			$this->databaseDriver = strtolower($databaseDriver);
			switch($this->databaseDriver){
				case 'mysqli':
					$this->connection = new mysqli($hostname, $username, $password, $databaseName);
					if ($this->connection->connect_errno) {
						die("Failed to connect to MySQL: (" . $this->connection->connect_errno . ") " . $this->connection->connect_error);
					}
					break;
				case 'pdo':
					try{
						$this->connection = new PDO('mysql:host='.$hostname.';dbname='.$databaseName, $username, $password);
						$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					}catch(PDOException $except){
						die('Error: ' . $except->getMessage());
					}
					break;
				default:
					die('Database driver could not be recognized. Please use either MySQLi or PDO.');
			}
		}
		
		function __destruct(){
			if($this->databaseDriver == 'mysqli' && isset($this->connection)){
				$this->connection->close();
			}
			$this->connection = null;
		}
		
		public function setPaginationParameters($rowsPerPage = 10, $numOfPaginationLinks = 5){
			$this->rowsPerPage = (is_int($rowsPerPage) && $rowsPerPage > 0) ? $rowsPerPage : 10;
			$this->numOfPaginationLinks = (is_int($numOfPaginationLinks) && $numOfPaginationLinks > 0) ? $numOfPaginationLinks : 5;
		}
		
		public function getResult($queryString = NULL, $parameters = NULL, $getGlobalArray = NULL, $key = NULL, $paginationQuery = false){
			if(!isset($this->queryString)){ $this->queryString = $queryString; }
			if($paginationQuery){
				if(!isset($this->currentPage)){
					$this->currentPage = (is_array($getGlobalArray) && count($getGlobalArray) && isset($getGlobalArray[$key]) && is_numeric($getGlobalArray[$key]) && $getGlobalArray[$key] > 0) ? $getGlobalArray[$key] : 1;
				}
				$offset = ($this->currentPage - 1) * $this->rowsPerPage;
				if(is_array($parameters)){
					$parameters[] = $offset;
				}else{
					$parameters = array($offset);
				}
				$queryString = $this->queryString . ' LIMIT ?,' . $this->rowsPerPage;
			}else{
				$queryString = $this->queryString;
			}
			if(isset($this->parameters)){
				array_pop($this->parameters);
			}else{
				$this->parameters = $parameters;
			}
			
			$resultSet = array();
			switch($this->databaseDriver){
				case 'mysqli':
					if(!($this->statement = $this->connection->prepare($queryString))){
						die("Prepare failed: (" . $this->connection->errno . ") " . $this->connection->error);
					}
					if(is_array($this->parameters) && count($this->parameters)){
						$param = array(); 
						$paramType = '';
						$count = count($this->parameters);
						for($i = 0; $i < $count; ++$i){
							switch(gettype($this->parameters[$i])){
								case 'boolean':
								case 'NULL':
								case 'integer':
									$paramType .= 'i';
									break;
								case 'double':
									$paramType .= 'd';
									break;
								case 'string':
									$paramType .= 's';
									break;
								default:
									$paramType .= 'b';
							}
							$param[] = &$this->parameters[$i];
						}
						array_unshift($param, $paramType);
						if(!call_user_func_array(array($this->statement, 'bind_param'), $param)){
							die("Binding parameters failed: (" . $this->connection->errno . ") " . $this->connection->error);
						}
					}
					if(!$this->statement->execute()){
						die("Execute failed: (" . $this->connection->errno . ") " . $this->connection->error);
					}
					$result = $this->statement->get_result();
					while($res = $result->fetch_array()){
						$resultSet[] = $res;
					}
					break;
				case 'pdo':
					try{
						$this->statement = $this->connection->prepare($queryString);
						if(is_array($this->parameters) && count($this->parameters)){
							$count = count($this->parameters);
							for($i = 0; $i < $count; ++$i){
								switch(gettype($this->parameters[$i])){
									case 'boolean':
										$this->statement->bindParam($i+1, $this->parameters[$i], PDO::PARAM_BOOL);
										break;
									case 'NULL':
										$this->statement->bindParam($i+1, $this->parameters[$i], PDO::PARAM_NULL);
										break;
									case 'integer':
										$this->statement->bindParam($i+1, $this->parameters[$i], PDO::PARAM_INT);
										break;
									case 'string':
										$this->statement->bindParam($i+1, $this->parameters[$i], PDO::PARAM_STR);
										break;
									default:
										$this->statement->bindParam($i+1, $this->parameters[$i], PDO::PARAM_LOB);
								}
							}		
						}
						$this->statement->execute();
						$resultSet = $this->statement->fetchAll();
					}catch(PDOException $except){
						die('Error: ' . $except->getMessage());
					}
					break;
			}
			return $resultSet;
		}
		
		public function getPaginationLinks(){
			$resultSet = $this->getResult();
			$totalRecords = count($resultSet);
			if($totalRecords){
				$totalPages = ceil($totalRecords / $this->rowsPerPage);
				if($totalRecords > $this->rowsPerPage && $this->currentPage <= $totalPages){
					$supersetRange = range(1, $totalPages);
					if($this->numOfPaginationLinks % 2 == 0){
						$pagesOnLeftSide = (int)(($this->numOfPaginationLinks - 1) / 2);
						$pagesOnRightSide = $this->numOfPaginationLinks - ($pagesOnLeftSide + 1) ;
						$subsetRange = range($this->currentPage - $pagesOnLeftSide, $this->currentPage + $pagesOnRightSide);
					}else{
						$pagesOnEitherSide = ($this->numOfPaginationLinks - 1) / 2;
						$subsetRange = range($this->currentPage - $pagesOnEitherSide, $this->currentPage + $pagesOnEitherSide);
					}
					foreach($subsetRange as $p){
						if($p < 1){
							array_shift($subsetRange);
							if(in_array($subsetRange[count($subsetRange) - 1] + 1, $supersetRange)){
								$subsetRange[] = $subsetRange[count($subsetRange) - 1] + 1;
							}
						}elseif($p > $totalPages){
							array_pop($subsetRange);
							if(in_array($subsetRange[0] - 1, $supersetRange)){
								array_unshift($subsetRange, $subsetRange[0] - 1);
							}
						}
					}
					$pgLinks = array();
					$pgLinks['prev'] = ($subsetRange[0] > $supersetRange[0]) ? true : false;
					foreach($subsetRange as $p){
						$pgLinks['links'][] = $p;
					}
					$pgLinks['next'] = ($subsetRange[count($subsetRange) - 1] < $supersetRange[count($supersetRange) - 1]) ? true : false;
					return $pgLinks;
				}
			}else{
				return array();
			}
		}
	}

?>