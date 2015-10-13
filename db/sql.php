<?php
namespace nx\db;

/**
 * Class sql
 * @package nx\db
 */
class sql{

	const sortASC = 'ASC', sortDESC = 'DESC';
	/**
	 * @var callable
	 */
	private $_dbcb =null;
	private $_db_cfg = null;//Ĭ�����ݿ�������
	static public $db = [];//hDB���飬���� $_db_cfg����

	public  $table = null;//����
	public  $primary = null;//����
	private $_sql = '';//ÿ��ִ�в�ѯ���ɵ�sql
	public $args = []; //���β�ѯ��ʱ����

	public static $history = [];//��ʷ��¼

	/**
	 * ���� ����ʵ��
	 * @param string $Table ����
	 * @param string $Primary ������
	 * @param callable $DB
	 * @return static
	 */
	public static function factory($Table, $Primary = null, $DB =null){
		return new static($Table, $Primary, $DB);
	}
	public function __construct($Table, $Primary = null, $DB=null){
		$this->table = $Table;
		$this->primary = $Primary;
		$this->_dbcb = $DB;
	}
	public function __destruct(){}

	public function _clone(){
		$clone =static::factory($this->table, $this->primary, $this->_db);
		$clone->args =$this->args;
		return $clone;
	}
	public function clear(){
		return static::factory($this->table, $this->primary, $this->_db);
	}

	/**
	 * ����¼�¼�� ֻ��Ե�ǰ����в���
	 * $tab->create('SQL')��          ֱ��ִ��sql���
	 * $tab->create([field=>value, ...]);            ��׼���÷�ʽ
	 * $tab->create([[],[],[]]);    ͬʱ������¼����
	 *
	 * @param array|string          $Fields
	 * @param boolean $IsReplace    MySQL�� REPLACEģʽ����
	 * @return bool|mixed           ������id��false
	 */
	public function create($Fields, $IsReplace = null){
		if(is_array($Fields)){
			$_first =current($Fields);
			if(!is_array($_first)){
				$__fields = [];
				foreach($Fields as $k => $v) $__fields[$k] = $this->_db()->quote($v);
				$_cols = array_keys($Fields);
				$_vals = array_values($__fields);
				$_vals = implode(", ", $_vals);
			} else {//һ�β������ģʽ
				$_cols = array_keys($_first);
				$_vals = [];
				foreach($Fields as $_n => $_fields){
					$__fields = [];
					foreach($_fields as $k => $v) $__fields[$k] = $this->_db()->quote($v);
					$_vals2 = array_values($__fields);
					$_vals[] = implode(", ", $_vals2);
				}
				$_vals = implode("), (", $_vals);
			}
			$_cols = implode('`, `', $_cols);
			$this->_sql = ($IsReplace ?"REPLACE" :"INSERT")." INTO `{$this->table}` (`{$_cols}`) VALUES ({$_vals})";
		} elseif(is_string($Fields) && func_num_args()==1){
			$this->_sql =$Fields;
		} else{
			$this->args = [];
			return false;
		}
		self::$history[] = $this->_sql;
		$o = $this->_db()->query($this->_sql);
		$this->args = [];//����
		if($o && $this->lastId()) return $this->lastId();
		return $o;
	}
	/**
	 * ִ��mysql���������������Ӧ�Ľ��
	 * @param string $fun   ������
	 * @param string $N     ����
	 * @param string $SQL
	 * @param bool   $Clear
	 * @return bool|string
	 */
	public function fun($fun, $N = '*', $SQL = '', $Clear =true){
		$this->args['select'] = "{$fun}({$N}) `RESULT`";
		return $this->readOne("RESULT", $SQL, $Clear);
	}
	/**
	 * �������м�¼
	 * @param string $SQL ֱ�Ӳ�ѯ��SQL��ʡȴʱʹ�����к�������
	 * @return array
	 */
	public function read($SQL = "", $Clear =true){
		if(empty($SQL)){
			if(empty($this->args['select'])) $this->selectFields();
			$this->_sql =$this->_buildSELECT($this->table, $this->args);
		}
		else $this->_sql = $SQL;
		if($Clear) $this->args = [];
		self::$history[] = $this->_sql;
		$r = $this->_db()->query($this->_sql);
		if($r->errorCode() !=='00000') return false;
		return $r->fetchAll(\PDO::FETCH_ASSOC);
	}
	/**
	 * ���ص�һ����¼
	 * @param string $Col �ֶ����������ã�ֱ�ӷ����ֶ�����
	 * @param string $SQL ֱ�Ӳ�ѯ��SQL��ʡȴʱʹ�����к�������
	 * @return boolean|string
	 */
	public function readOne($Col = null, $SQL = "", $Clear =true){
		if(empty($SQL)){
			if(empty($this->args['select']) && !is_null($Col)) $this->selectFields($Col);
			$this->_sql =$this->_buildSELECT($this->table, $this->args);
		}
		else $this->_sql = $SQL;
		if($Clear) $this->args = [];
		self::$history[] = $this->_sql;
		$r = $this->_db()->query($this->_sql);
		if($r->errorCode() !=='00000') return false;
		$result = $r->fetch(\PDO::FETCH_ASSOC);
		if(!is_null($Col) && is_array($result)) return $result[$Col];
		return $result;
	}
	/**
	 * ���¼�¼
	 * @param string $SQL ֱ�Ӳ�ѯ��SQL��ʡȴʱʹ�����к�������
	 * @return boolean|mixed    false���޸ĵ���Ŀ��
	 */
	public function update($SQL = ""){
		if(empty($SQL)){
			if(empty($this->args['set'])){
				$this->args = [];
				return false;
			}
			$this->_sql =$this->_buildUPDATE($this->table, $this->args);
		}
		else $this->_sql = $SQL;
		$this->args = [];
		self::$history[] = $this->_sql;
		return $this->_db()->exec($this->_sql);
	}
	/**
	 * ɾ����¼
	 * @param string $SQL ֱ�Ӳ�ѯ��SQL��ʡȴʱʹ�����к�������
	 * @return mixed        false ��ɾ������Ŀ��
	 */
	public function delete($SQL = ""){
		if(empty($SQL)){
			$this->_sql =$this->_buildDELETE($this->table, $this->args);
		}
		else $this->_sql = $SQL;
		$this->args = [];
		self::$history[] = $this->_sql;
		return $this->_db()->exec($this->_sql);
	}
	/**
	 * �̳� select, filter
	 * join(\nx\db\sql, ['id'])->join('user', ['id'=>'user_id'])->join('user', ['user.id'=>'editor.user_id'])
	 *
	 * @param string|\nx\db\sql $Table //����
	 * @param null   $Conditions
	 * @param string $Join
	 * @return $this
	 */
	public function join($Table, $Conditions=null, $Join='LEFT'){
		$_table = (is_object($Table)) ?$Table->table :$Table;
		if(strpos($_table, ' ') !==false){
			list($_table, $_as) =explode(' ', $_table);
			$_as =" `{$_as}`";
		} else $_as ='';
		$s = " {$Join} JOIN `{$_table}`".$_as;
		if(is_array($Conditions)){
			$_c =[];
			foreach($Conditions as $Row => $As){
				$__table =$_table;
				$__row =is_numeric($Row) ?$As :$Row;
				if(strpos($Row, '.') !==false) list($__table, $__row) =explode('.',$Row);
				$__table2 =$this->table;
				$__as =$As;
				if(strpos($As, '.') !==false) list($__table2, $__as) =explode('.',$As);
				$_c[] = "`{$__table}`.`{$__row}` = `{$__table2}`.`{$__as}`";
			}
			$s .= " ON (".implode(' AND ', $_c).")";
		}
		elseif(is_string($Conditions)) $s .= $Conditions;

		$_join =[$s, '', ''];
		if(is_object($Table)){
			$_join[1] =empty($Table->args['select']) ?'' :$Table->args['select'];
			$_join[2] =empty($Table->args['filter']) ?'':$Table->args['filter'];
		}
		$this->args['join'][] = $_join;
		return $this;
	}

	/**
	 * select('id', 'name')->select(['id', 'name'=>'user', 'info.name', 'count'=>['count', '*']])->select(['user'=>['id', 'name'], 'info'=>[]])
	 * @param string $Fields
	 * @return $this
	 */
	public function select($Fields ='*'){
		$_tables =[];
		if(func_num_args() ==1){
			if (is_array($Fields)){
				if(is_array(current($Fields))) $_tables =$Fields;		//->select(['user'=>['id', 'name'], 'info'=>[]])
				else $_tables[$this->table] =$Fields;					//->select(['id', 'name'=>'user', 'info.name'])
			} elseif(is_string($Fields)){
				if((strpos($Fields, '`') !==false || strpos($Fields, '(') !==false || strpos($Fields, ',') !==false)){
					$this->args['select'] =$Fields;						//->select("COUNT(*) `COUNT`, `name`")
					return $this;
				} else $_tables[$this->table]=func_get_args();			//->select('id')
			} else $_tables[$this->table]=['*'];							//->select(unknow)
		} else $_tables[$this->table] =func_get_args();					//->select('id', 'name')

		$_fs =[];
		foreach($_tables as $_table =>$_fields){						//[tab1=>fields, tab2=>fields]
			foreach($_fields as $_key =>$_field){						//$_fields =['tab.field', 'field']
				$_tab =$_table;
				if(is_numeric($_key)){
					if(strpos($_field, '.') !==false) list($_tab, $_field) =explode('.', $_field); //['tab.field']
					$_field =($_field =='*') ?$_field :"`{$_field}`";
					$_fs[] ="`{$_tab}`.{$_field}";
				} else{
					if(is_array($_field)){								//$_fields =['count'=>['count', '*']]
						if(isset($_field[2])) $_tab =$_field[2];
						$_fs[] =isset($_field[1]) ?"{$_field[0]}(`{$_tab}`.`{$_field[1]}`) `{$_key}`" :"{$_field[0]}() `{$_key}`";
					}else{												//$_fields =['tab.field'=>'field', 'COUNT(*)'=>'field']
						if(strpos($_key, '(') !== false){
							$_fs[] = "{$_key} `{$_field}`";
						} else{
							if(strpos($_key, '.') !== false) list($_tab, $_key) = explode('.', $_key);
							$_fs[] = "`{$_tab}`.`{$_key}` `{$_field}`";
						}
					}
				}
			}
		}
		$this->args['select'] =implode(', ', $_fs);
		return $this;
	}

	/**
	 * һ��ֻ����һ�ű�
	 * set(['name'=>'vea', 'login'=>[1, '+'], 'count'=>['num', 'COUNT'], 'nickname'=>'`user.name`'])->set('name', 'vea')
	 *
	 * @param      $field
	 * @param bool $value
	 * @return $this
	 */
	public function set($field, $value=false){
		$_fields =[];
		if(is_array($field)){
			$_fields =$field;					//->set(['name'=>'vea', 'login'=>[1, '+'], 'count'=>['num', 'COUNT'], 'nickname'=>'`user.name`'])
		} elseif(is_string($field)){
			if(func_num_args()==1){
				$this->args['set'] =$field;		//->set("`name`=1, `login`=23")
				return $this;
			}
			$_fields[$field] =$value;			//->set('name', 'vea')
		} else return $this;

		$_set =[];
		foreach($_fields as $_field =>$_value){
			if(!is_array($_value)){
				$_len =strlen($_value);
				if($_len >0){
					if(is_string($_value[0]) && $_value[0] =='`' && $_value[$_len-1] =='`'){
						$_tab2 =$this->table;
						$_val =substr($_value, 1, -1);
						if(strpos($_value, '.') !==false) list($_tab2, $_val) =explode('.', $_value);
						$_val ="`{$_tab2}`.`{$_val}`";
						/*} elseif(strpos($_value, '.') !==false && $_len<20){
							list($_tab2, $_col) =explode('.', $_value);
							$_val ="`{$_tab2}`.`{$_col}`";*/
					} else $_val =$this->_db()->quote($_value);
				} else $_val="''";
			} else {
				list($_val, $_opt) =$_value;
				switch($_opt){
					case '+':
					case '-':
					case '*':
					case '/':
						$_val = "`{$this->table}`.`{$_field}` {$_opt} '$_val'";
						break;
					default:// sql function
						$_len =strlen($_val);
						if(is_string($_val[0]) && $_val[0] =='`' && $_val[$_len-1] =='`'){
							$_tab2 = $this->table;
							$_val = substr($_val, 1, -1);
							if(strpos($_val, '.') !== false){
								list($_tab2, $_col) = explode('.', $_val);
								$_val ="{$_opt}(`{$_tab2}`.`{$_col}`)";
							} else $_val ="{$_opt}(`{$_val}`)";
						} else $_val ="{$_opt}({$_val})";
						/*
						if(is_string($_val[0]) && $_val[0] =='`' && $_val[$_len-1] =='`' && strpos($_val, '.') !==false){
							$_val =substr($_val, 1, -1);
							list($_tab2, $_col) =explode('.', $_val);
							$_val ="{$_opt}(`{$_tab2}`.`{$_col}`)";
						} else $_val ="{$_opt}(`{$_val}`)";*/
						break;
				}
			}
			//$_set[] ="`{$this->table}`.`{$_field}` ={$_val}";
			$_set[] ="`{$_field}` ={$_val}";
		}
		$this->args['set'] = implode(", ", $_set);
		return $this;
	}

	/**
	 * ->sort()->sort(`create`, `desc`)->sort(['create'=>'desc', 'upload.last'->'asc'])
	 *
	 * @param bool $field
	 * @param bool $asc
	 * @return $this
	 */
	public function sort($field =false, $asc =true){
		$_sorts =[];
		if(is_array($field)) $_sorts =$field;
		else $_sorts[($field ===false) ?$this->primary :$field] =$asc;

		$_s =[];
		foreach($_sorts as $_field =>$_asc){
			$_tab =$this->table;
			$_sort ='ASC';
			if(strpos($_field, '.') !==false)  list($_tab, $_field) =explode('.', $_field);

			if(is_bool($_asc)) $_sort =($_asc) ?'ASC' :'DESC';
			elseif(is_string($_asc)){
				$_sort =(strtolower($_asc[0]) =='a') ?'ASC' :'DESC';
			}
			$_s[] =$_field[0]=='`' ?"{$_field} {$_sort}" :"`{$_tab}`.`{$_field}` {$_sort}";
		}
		$this->args['sort'] = " ORDER BY ".implode(", ", $_s);
		return $this;
	}

	/**
	 * ��ҳ
	 * @param int $Rows ��ѯ��������
	 * @param int  $Offset ��ѯ��ʼ����
	 * @return $this
	 */
	public function limit($Rows =false, $Offset = 0){
		$this->args['limit'] = empty($Rows) ?''
			:((func_num_args() == 1) ?" LIMIT {$Rows}" :" LIMIT {$Offset}, {$Rows}");
		return $this;
	}

	/**
	 * �ɶ�ε��ã�ÿ�� AND()
	 * ->where(1)->where('id', 1)->where('user.id', 1, '>', 'or)
	 * ->where([['id', 1, '>', 'or'], ['stutas', 0, '=', 'or']])
	 * ->where(['id'=>1, 'stutas'=>[0, '>'], 'user.name'=>['a', 'like', 'or']], 'AND')
	 * @param $conds
	 * @return $this
	 */
	public function where($conds){
		return $this->_withWHERE(func_get_args(), func_num_args());
	}
	/**
	 * ������һ�β�ѯ��SQL
	 * @return string
	 */
	public function getLastSql(){
		return $this->_sql;
	}
	/**
	 * @param $field
	 * @return $this
	 */
	public function group($field){
		$_tab =$this->table;
		if(strpos($field, '.') !==false) list($_tab, $field) =explode('.', $field);
		$this->args['group'] =" GROUP BY `{$_tab}`.`{$field}`";
		return $this;
	}
	/**
	 * @return \PDO
	 */
	private function _db(){
		$cb =$this->_dbcb;
		return $cb();
	}
	/**
	 * ����������ݿ����
	 */
	public function lastError(){
		return $this->_db()->errorCode();
	}
	/**
	 * ����������id
	 */
	public function lastId(){
		return $this->_db()->lastInsertId();
	}
	/*--------------- build ----------------------------------------------------------*/
	private function _withWHERE($Args, $Num){
		if($Num ==0) return $this;
		$conds =$Args[0];
		if(!isset($this->args['filter'])) $this->args['filter'] ='';
		$_conds =[];
		$link ='AND';

		if(is_array($conds)){
			$_conds =$conds;
			if($Num >1) $link =$Args[1];
		} else{
			switch($Num){
				case 0:
					return $this;
					break;
				case 1://(1)
					if(strpos($conds, '`')!==false || strpos($conds, '=')!==false || strpos($conds, '(')!==false){
						$this->args['filter'] .=empty($this->args['filter']) ?"({$conds})" :" {$link} ({$conds})";
						return $this;
					}
					$_conds =[$this->primary=>$conds];
					break;
				default://(id, 1) ('id', 1, '>') ('id', 1, '>', 'or)
					$_conds[] =$Args;
					break;
			}
		}
		if(!empty($_conds)){
			$_where ='';
			foreach($_conds as $_col => $_val){
				$_opt = '=';
				$_link =$link;
				$_tab =$this->table;
				if(is_array($_val)){
					if(is_numeric($_col)){								//[['id', 1], ['stutas', 0]]
						$_opt = (isset($_val[2])) ?$_val[2] :$_opt;		//[['id', 1, '>'], ['stutas', 0, '=']]
						$_col = $_val[0];
						$_link =(isset($_val[3])) ?$_val[3] :$link;		//[['id', 1, '>', 'or'], ['stutas', 0, '=', 'or']]
						$_val = $_val[1];
					}
					else{												// ['id'=>[1], 'stutas'=>[0]]
						$_opt = (isset($_val[1])) ?$_val[1] :$_opt;		// ['id'=>[1, '>'], 'stutas'=>[0, '=']]
						$_link =(isset($_val[2])) ?$_val[2] :$link;		//['id'=>[1, '>', 'or'], 'stutas'=>[0, '=', 'or']]
						$_val = $_val[0];
					}
				}														//['id'=>1, 'stutas'=>0]
				if(strpos($_col, '.') !==false) list($_tab, $_col) =explode('.', $_col);
				if(is_string($_val) && $_val[0] =='`' && $_val[strlen($_val)-1] =='`'){
					$_tab2 =$this->table;
					$_val =substr($_val, 1, -1);
					if(strpos($_val, '.') !==false) list($_tab2, $_val) =explode('.', $_val);
					$_val ="`{$_tab2}`.`{$_val}`";
				}
				//$_val =$this->_db()->quote($_val);
				switch($_opt){
					case '+':
					case '-':
					case '*':
					case '/':
					case '+=':
					case '-=':
					case '*=':
					case '/=':
						$_opt = "=`{$_tab}`.`{$_col}` {$_opt[0]}";
						break;
					case 'not':
					case 'NOT':
					case 'not in':
					case 'NOT IN':
						$_opt ='NOT IN';
					case 'in':
					case 'IN':
						$_val = " ('".implode("','", $_val)."')";
						break;
					case 'is':
					case 'IS':
						$_opt ='IS';
						$_val =strtoupper($_val);
						break;
					case 'LIKE':
					case 'like':
					case '%':
						$_opt ='LIKE';
						$_val = "%".$_val."%";
						$_val =$this->_db()->quote($_val);
						break;
					default:
						if(strpos($_val, '(') === false) $_val =$this->_db()->quote($_val);
						break;
				}
				if(!empty($_where)) $_where .=' '.strtoupper($_link).' ';
				$_where .= (is_numeric($_col))
					?$_val											//['id >1', 'stutas =0']
					:"`{$_tab}`.`{$_col}` {$_opt} {$_val}";
			}
			if(!empty($_where)) $this->args['filter'] .=empty($this->args['filter']) ?"({$_where})" :" {$link} ({$_where})";
		}
		return $this;
	}
	private function _buildSELECT($table, $args){
		$get = empty($args['select']) ?"`{$table}`.*" :$args['select'];
		$sort = empty($args['sort']) ?'' :$args['sort'];
		$where = empty($args['filter']) ?'WHERE 1' :' WHERE '.$args['filter'];
		$limit = empty($args['limit']) ?'' :$args['limit'];
		$join = empty($args['join']) ?'' :$args['join'];
		if(is_array($join)){
			$join =[];
			foreach($args['join'] as $_joins){
				list($_join, $_get, $_where) =$_joins;
				$join[] =$_join;
				if(!empty($_get)) $get .=', '.$_get;
				if(!empty($_where)) $where .=' AND '.$_where;
			}
			$join =implode('', $join);
		}
		$group = empty($args['group']) ?'' :$args['group'];
		return "SELECT {$get} FROM `{$table}`{$join}{$where}{$group}{$sort}{$limit}";
	}
	private function _buildUPDATE($table, $args){
		$_where = empty($args['filter']) ?'' :' WHERE '.$args['filter'];
		$_limit = empty($args['limit']) ?'' :$args['limit'];
		return "UPDATE `{$table}` SET {$args['set']}{$_where}{$_limit}";
	}
	private function _buildDELETE($table, $args){
		$_where = empty($args['filter']) ?'' :' WHERE '.$args['filter'];
		$_limit = empty($args['limit']) ?'' :$args['limit'];
		return "DELETE FROM `{$table}`{$_where}{$_limit}";
	}
	/*--------------- ��� ���ݷ��� ----------------------------------------------------------*/
	/**
	 * ���ص�һ����¼
	 * @param string $SQL ֱ�Ӳ�ѯ��SQL��ʡȴʱʹ�����к�������
	 * @param string $Col �ֶ����������ã�ֱ�ӷ����ֶ�����
	 * @return boolean|unknown
	 */
	public function first($Col = null, $SQL = "", $Clear =true){
		return $this->readOne($Col, $SQL, $Clear);
	}
	/**
	 * ѡ����ʾ���ֶ�
	 * @param string $Fields
	 * @return $this
	 */
	public function selectFields($Fields = '*'){
		return $this->select($Fields);
	}
	/**
	 * ������ʹ�� �����ֶ�����
	 * @param array $Fields ����
	 * @return $this
	 */
	public function setFields($field, $value=false){
		return $this->set($field, $value);
	}
	/**
	 * ����
	 * @param array $Sort ʡȴΪ������������
	 *  0:$this->table.primary ASC
	 *  1:string:$this->table.{1} ASC
	 *  2:string,string:$this->table.{1} {2}
	 *  3:string,string,string:{1}.{2} {3}
	 *  1:array:
	 *    [0=>[$k=>$v],1=>[$k=>$v],...]:$this->table.$k $v
	 *    [$t=>[$k=>$v],$t=>[$k=>$v],...]:$t.$k $v
	 * ?2��string,array:[0=>[$k=>$v],1=>[$k=>$v],...]:{1}.$k $v
	 *
	 * @return $this
	 */
	public function orderBy($field =false, $asc =true){
		return $this->sort($field, $asc);
	}
	/**
	 * ���ؼ�¼����
	 * @param string $SQL δ����
	 * @param string $N ͳ���ֶ�
	 * @return Ambigous <boolean, number>    ����false���ֶ�����
	 */
	public function count($N = '*', $SQL = ""){
		return (int)$this->_clone()->fun('COUNT', $N, $SQL, false);
	}
	/**
	 * ��ҳ
	 * @param number $Page �ӵ�1ҳ��ʼ
	 * @param number $Max ÿҳ����
	 * @return $this
	 */
	public function page($Page = 1, $Max = 15){
		$Rows = $Max;
		$Offset = ($Page - 1)*$Max;
		return $this->limit($Rows, $Offset);
	}
	/**
	 * ����
	 * @param array $Conds �������� (1), ('id', 1), ('id', 1, '>'), (['id', 1], ['id', 1, '>'], 'id >1', 'id'=>1, 'id'=>[1, '>'])
	 *                                    1������
	 *                                        ��������������Ϊ��ֵ
	 *                                        ���鰴��keyΪ�ֶ���valueΪֵ���ι���
	 *                                    2������
	 *                                        1Ϊ�ֶ��� 2Ϊ���ֶ�ֵ
	 * @return $this
	 */
	public function filter($Conds){
		return $this->_withWHERE(func_get_args(), func_num_args());
	}
	/**
	 * ֱд��������
	 * @param string $Conditions sql�����ַ���
	 * @return $this
	 */
	public function filterStr($Conditions){
		return $this->where((string)$Conditions);
	}
	/**
	 * @param string $Fields
	 * @return $this
	 */
	public function groupBy($Fields){
		return $this->group($Fields);
	}
}
