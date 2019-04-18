<?php

	class DB extends UserConfig {

		// CONNECTION PROPERTY
		private static $connection;

		// TABLE PROPERTY
		private static $table;

		// WHERE PROPERTY
		private static $where;

		// ORDER BY PROPERTY
		private static $order;

		// PAGINATE PROPERTY
		private static $paginate;

		// DEFAULT CONNECTION
		private static function _init() {
			$conn = parent::$config['connection'];
			$default = $conn['default_connection'];
			$host = $conn[0][$default]['database_host'];
			$user = $conn[0][$default]['database_user'];
			$pass = $conn[0][$default]['database_pass'];
			$name = $conn[0][$default]['database_name'];
			self::$connection = mysqli_connect($host,$user,$pass,$name);
		}

		// OTHER CONNECTION
		public static function connection($value) {
			$conn = parent::$config['connection'];
			$host = $conn[0][$value]['database_host'];
			$user = $conn[0][$value]['database_user'];
			$pass = $conn[0][$value]['database_pass'];
			$name = $conn[0][$value]['database_name'];
			self::$connection = mysqli_connect($host,$user,$pass,$name);
			return new static;
		}

		// TABLE
		public static function table($value) {

			// check connection (if null, default use)
			if (!self::$connection) {
				DB::_init();
			}

			// select table
			if (is_array($value)) {
				self::$table = [key($value),$value[key($value)]];
			} else {
				self::$table = $value;
			}
			return new static;
		}

		// WHERE (default = AND)
		public static function where($value) {
			foreach ($value as $field => $condition) {

				// check if $condition contain function condition (change to =)
				$char = '[\s]*(=|<|>|<=|>=|<>|LIKE)[\s]*';
				if (!preg_match('/'.$char.'/', $condition)) {

					// check is string or int
					if (is_string($condition)) {
						$condition = " = '$condition'";
					} else {
						$condition = ' = '.$condition;
					}
				}

				// check where value and write
				if (!self::$where) {
					self::$where = "WHERE ".$field.$condition;
				} else {
					self::$where .= " AND ".$field.$condition;
				}

			}

			return new static;
		}

		// WHERE OR
		public static function whereOr($value) {
			foreach ($value as $field => $condition) {

				// check if $condition not contain function condition (change to =)
				$char = '[\s]*(=|<|>|<=|>=|<>|LIKE)[\s]*';
				if (!preg_match('/'.$char.'/', $condition)) {

					// check is string or int
					if (is_string($condition)) {
						$condition = " = '$condition'";
					} else {
						$condition = ' = '.$condition;
					}
				}

				// check where value and write
				if (!self::$where) {
					self::$where = "WHERE ".$field.$condition;
				} else {
					self::$where .= " OR ".$field.$condition;
				}

			}

			return new static;
		}

		// WHERE AND
		public static function whereAnd($value) {

			// taken from where default function
			self::where($value);
			return new static;
		}

		// CONDITION = (string|int)
		public static function equal($value) {
			if (is_string($value)) {
				return " = '".$value."'";
			} else {
				return " = ".$value;
			}
		}

		// CONDITION < (int)
		public static function lower($value) {
				return " < ".$value;
		}

		// CONDITION <= (int)
		public static function lowerEqual($value) {
				return " <= ".$value;
		}

		// CONDITION > (int)
		public static function higher($value) {
				return " > ".$value;
		}

		// CONDITION >= (int)
		public static function higherEqual($value) {
				return " >= ".$value;
		}

		// CONDITION <> (int)
		public static function not($value) {
				return " <> ".$value;
		}

		// CONDITION %LIKE% (string)
		public static function like($value) {
				return " LIKE %".$value."%";
		}

		// CONDITION LIKE% (string)
		public static function likeFront($value) {
				return " LIKE ".$value."%";
		}

		// CONDITION %LIKE (string)
		public static function likeBack($value) {
				return " LIKE %".$value;
		}

		// SELECT (return array)
		public static function get($value=false) {
			
			// select column/s
			if ($value) {
				if (is_array($value)) {
					$column = implode(',', $value); // multiple column
				} else {
					$column = $value; // single column (maybe can multiple)
				}
			} else {
				$column = '*'; // all column
			}

			// check where string
			if (self::$where) {
				$where = self::$where;
			} else {
				$where = '';
			}

			// check order
			if (self::$order) {
				$order = self::$order;
			} else {
				$order = '';
			}

			// check paginate
			if (self::$paginate) {
				$paginate = self::$paginate;
			} else {
				$paginate = '';
			}

			// select join/single table
			$tableRaw = self::$table;
			if (is_array($tableRaw)) {

				// join table
				$tables = [];
				$joins = [];
				foreach ($tableRaw as $name) {
					$name = explode('.', $name);
					array_push($tables, $name[0]);
					array_push($joins, $name[0].'.'.$name[1]);
				}

				$conn = self::$connection;
				$query = "SELECT $column FROM $tables[0] INNER JOIN $tables[1] ON $joins[0] = $joins[1] $where $order $paginate";
				$handle = mysqli_query($conn, $query);
				$data = [];
				while ($sqlData = mysqli_fetch_assoc($handle)) {
					array_push($data, $sqlData);
				}
				$data = json_encode($data);
				$data = json_decode($data);
				return $data;
			
			} else {

				// single table
				$conn = self::$connection;
				$query = "SELECT $column FROM $tableRaw $where $order $paginate";
				$handle = mysqli_query($conn, $query);
				$data = [];
				while ($sqlData = mysqli_fetch_assoc($handle)) {
					array_push($data, $sqlData);
				}
				$data = json_encode($data);
				$data = json_decode($data);
				return $data;
			}
		}

		// SELECT (return JSON)
		public static function api($value=false) {
			$data = self::get($value);
			return json_encode($data);
		}

		// ORDER BY
		public static function orderBy($value) {
			$field = key($value);
			$option = $value[key($value)]; // desc|asc
			self::$order = "ORDER BY $field $option";
			return new static;
		}

		// PAGINATE (FYI : page start from 0)
		public static function paginate($page, $count) {
			$page = $count * $page;
			$query = "LIMIT $count OFFSET $page";
			self::$paginate = $query;
			return new static;
		}

		// INSERT
		public static function insert($value) {
			$conn = self::$connection;
			$table = self::$table;

			// extract the value
			$fields = [];
			$values = [];
			foreach ($value as $field => $item) {
				array_push($fields, $field);
				if (is_string($item)) {
					array_push($values, "'".$item."'");
				} else {
					array_push($values, $item);
				}
			}
			$fields = implode(',', $fields);
			$values = implode(',', $values);

			// execute
			$query = "INSERT INTO $table ($fields) VALUES ($values)";
			mysqli_query($conn, $query);
		}

		// UPDATE
		public static function update($value) {
			$conn = self::$connection;
			$table = self::$table;
			$where = self::$where;

			// extract the value
			$sets;
			foreach ($value as $field => $item) {

				// check item is string or int
				if (is_string($item)) {
					$item = "'".$item."'";
				}

				// check existable items in $sets
				if (!isset($sets)) {
					$sets = "$field = $item";
				} else {
					$sets .= ",$field = $item";
				}
			}

			// execute
			$query = "UPDATE $table SET $sets $where";
			mysqli_query($conn, $query);
		}

		// DELETE
		public static function delete() {
			$conn = self::$connection;
			$table = self::$table;
			$where = self::$where;

			// execute
			$query = "DELETE FROM $table $where";
			mysqli_query($conn, $query);
		}

		// TESTING FAKE DATA
		public static function testing($value, $count) {
			$conn = self::$connection;
			$table = self::$table;

			// sample names
			$nameItems = [];
			$names = file_get_contents('sample/names.sample');
			$names = explode("\n", $names);
			foreach ($names as $item) {
				array_push($nameItems, $item);
			}

			// sample string
			$stringItems = [
				'a','b','c','d','e','f','g','h','i','j','k','l','m',
				'n','o','p','q','r','s','t','u','v','w','x','y','z'
			];

			// save loop insert
			$loopData = [];
			for ($i=0; $i < $count; $i++) {
				
				// change field value to random
				foreach ($value as $field => $item) {

					// random name
					if ($item == 'name') {
						$name1 = DB::random($nameItems);
						$name2 = DB::random($nameItems);
						$loopData[$i][$field] = $name1.' '.$name2;
					}

					// random string
					elseif ($item == 'string') {
						$string = '';
						for ($ii=0; $ii < 20; $ii++) { 
							$string .= DB::random($stringItems);
						}
						$loopData[$i][$field] = $string;
					}

					// random email
					elseif ($item == 'email') {
						$name1 = DB::random($nameItems);
						$name2 = DB::random($nameItems);
						$email = strtolower($name1).strtolower($name2).'@email.com';
						$loopData[$i][$field] = $email;
					}

					// random number
					elseif ($item == 'number') {
						$number = 0;
						for ($ii=0; $ii < 50; $ii++) { 
							$number += rand(0,1000);
						}
						$loopData[$i][$field] = $number;
					}

					// random number defined
					elseif (preg_match('/number\.[0-9]*\.[0-9]/', $item)) {
						$item = explode('.', $item);
						$number = rand($item[1],$item[2]);
						$loopData[$i][$field] = $number;
					}

					// random money (per 1000)
					elseif (preg_match('/money/', $item)) {
						$money = rand(1,1000) * 1000;
						$loopData[$i][$field] = $money;
					}

					// random defined
					elseif (is_array($item)) {
						$defined = DB::random($item);
						$loopData[$i][$field] = $defined;
					}

				}
			}

			// execute loop insert
			foreach ($loopData as $item) {
				DB::table($table)->insert($item);		
			}

		}

		// SYSTEM RANDOM (for TESTING FAKE DATA)
		private static function random($array) {
			$count = count($array) - 1;
			$random = rand(0, $count);
			return $array[$random];
		}

	}

	// simplyfing where condition function
	function equal($val) {return DB::equal($val);}
	function lower($val) {return DB::lower($val);}
	function lowerEqual($val) {return DB::lowerEqual($val);}
	function higher($val) {return DB::higher($val);}
	function higherEqual($val) {return DB::higherEqual($val);}
	function like($val) {return DB::like($val);}
	function likeFront($val) {return DB::likeFront($val);}
	function likeBack($val) {return DB::likeBack($val);}

	// FYI : this is list of function for must use in last sintax
	// * get()
	// * api()
	// * insert()
	// * update()
	// * delete()
	// * testing()

?>