<?php
namespace nx\db;

/**
 * Class table
 * @trait model
 * @package nx\db
 */
trait table{

	/*
	protected function nx_db_table(){
		if(isset($this->buffer)){
			if(!isset($this->buffer['table'])) $this->buffer['table'] = [];
		}
	}*/

	/**
	 * @param $name
	 * @param null $primary
	 * @param string $config
	 * @return \nx\helpers\sql
	 */
	public function table($name, $primary = 'id', $config = 'default'){
		return \nx\helpers\sql::factory($name, $primary, $config, $this);
		/*
		if(!isset($this->buffer['table'][$name]))
			$this->buffer['table'][$name] =\nx\db\sql::factory($name, $primary, $config, $this);
		return $this->buffer['table'][$name];*/
	}

}