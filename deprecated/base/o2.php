<?php
/**
 * Created by PhpStorm.
 * User: Vea
 * Date: 2019/1/2 002
 * Time: 15:33
 */
namespace nx\base;

/**
 * Trait o2
 * @package nx\base
 * @deprecated 2019-04-17
 */
trait o2{
	protected $data;
	//IteratorAggregate
	public function getIterator(){ return new \ArrayIterator($this->data); } //foreach($this as ..)
	//Countable
	public function count(){ return count($this->data??[]); } //->count($this)
	//ArrayAccess
	public function offsetSet($offset, $value){ $this->data[$offset]=$value; }   //$this['xx'] ='xx'
	public function offsetGet($offset){ return $this->data[$offset]; }           //=$this['zz']
	public function offsetExists($offset){ return isset($this->data[$offset]); }       //isset($this['xx']
	public function offsetUnset($offset){ unset($this->data[$offset]); }                //unset($this['xx']
	////php5.2+?
	//public function __toString(){
	//	//if(isset($this->data['__toString'])) return $this->data['__toString'];
	//	return (null === $this->data) ?'' :json_encode($this->data, JSON_UNESCAPED_UNICODE);
	//} //echo $this
	//php5.3
	public function __invoke(...$args){//php7
		switch(func_num_args()){
			case 0:// =$this()
				return $this->data;
			case 1:// $this($x)
				$this->data=$args[0];
				return $this;
			default:// =$this($x, $y, $z , ...)
				$r=[];
				foreach($args as $arg){
					$r[$arg]=$this->data[$arg] ?? null;
				}
				return $r;
		}
	}
}