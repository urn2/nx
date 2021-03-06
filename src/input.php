<?php
/**
 * Created by PhpStorm.
 * User: Vea
 * Date: 2018/08/23 023
 * Time: 00:39
 */
namespace nx;

use nx\parts\o2;

/**
 * Class request
 * @package nx
 * @method null|mixed header(string $key=null) 返回指定 header 或全部
 * @method null|mixed body(string $key=null) 返回指定 body 或全部
 * @method null|mixed query(string $key=null) 返回指定 query 或全部
 * @method null|mixed uri(string $key=null) 返回指定 uri 或全部
 * @method null|mixed cookie(string $key=null) 返回指定 cookie 或全部
 * @method string|bool method(string $method=null) 返回当前请求的method或验证method是否正确
 */
class input implements \ArrayAccess, \Countable, \IteratorAggregate{
	use o2;
	public function __construct($data=[]){
		if(PHP_SAPI == 'cli'){
			$argv=$_SERVER['argv'];
			array_shift($argv);
			$this->data['params']=$argv;
			$this->data['method']='cli';
			$this->data['uri']=implode(' ', $_SERVER['argv']);
		}else{
			$this->data['params']=$data;//构建数据
			$this->data['method']=strtolower($_SERVER['REQUEST_METHOD']);
			$this->data['uri']=$_SERVER['REQUEST_URI'];
			$this->data['query']=&$_GET;
			$this->data['post']=&$_POST;
			$this->data['cookie']=&$_COOKIE;
			$this->data['file']=&$_FILES;
		}
	}
	public function &offsetGet($offset){
		if(!array_key_exists($offset, $this->data)){
			switch($offset){
				case 'header':
					if(!function_exists('getallheaders')){
						$this->data['header']=[];
						foreach($_SERVER as $name=>$value){
							if('HTTP_' === substr($name, 0, 5)) $this->data['header'][str_replace(' ', '-', strtolower(str_replace('_', ' ', substr($name, 5))))]=$value;
						}
					}else{
						foreach(getallheaders() as $key=>$value){
							$this->data['header'][strtolower($key)]=$value;
						}
					}
					break;
				case 'input':
					$this->data['input']=file_get_contents('php://input');
					break;
				case 'body':
					$content_type =$this->header('content-type');
					if($content_type){
						$content_type = strtolower(trim(false !== strpos($content_type, ';')
							?explode(';', $content_type)[0]
							:$content_type
						));
						switch($content_type){//触发header更新
							case 'multipart/form-data':
								$this->data['body'] = $_POST;
								break;
							case 'application/x-www-form-urlencoded':
								parse_str($this['input'], $vars);
								$this->data['body'] = $vars;
								break;
							case 'application/json':
								$this->data['body'] = json_decode($this['input'], true);
								break;
							case 'application/xml':
								$xml = simplexml_load_string($this['input']);
								$this->data['body'] = json_decode(json_encode($xml), true);
								break;
							case 'text/plain':
							case 'text/html':
							default:
								$this->data['body'] = $this['input'];
								break;
						}
					} else $this->data['body'] =null;
					break;
				default:
					$this->data[$offset] =null;
			}
		}
		return $this->data[$offset];
	}
	/**
	 * 返回当前上传的文件，并验证是否可用
	 * @param $arg
	 * @return array|null
	 */
	public function file($arg){
		$f=&$this->data['file'][$arg];
		return (isset($f['name']) && isset($f['type']) && isset($f['size']) && isset($f['tmp_name']) && isset($f['error']) && ($f['error'] == UPLOAD_ERR_OK) && is_file($f['tmp_name']) && is_uploaded_file($f['tmp_name']) && is_readable($f['tmp_name']))
			?$f :null;
	}
	/**
	 * 返回请求ip
	 * @return mixed
	 */
	public function ip(){
		if(!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
		return $_SERVER['REMOTE_ADDR'];
	}
	/**
	 * 魔术方法
	 * @param string $from
	 * @param array  $arguments
	 * @return mixed|null
	 */
	public function __call(string $from, array $arguments){
		$key =$arguments[0]??null;
		switch($from){
			case 'query':
			case 'body':
			case 'header':
			case 'file':
			case 'cookie':
				$data =&$this[$from];
				if(null !==$key){
					if(\nx\app::$instance) \nx\app::$instance->log("       ->{$from}[{$key}]");
					//$app->log('  : {value}', ['value'=>json_encode($data[$key] ?? null)]);
				}
				return null ===$key ?$data :$data[$key]??null;
			case 'uri':
				return null ===$key ?$this->data['uri'] :($this->data['params'][$key]??null);
			case 'method':
				return null ===$key ?$this->data['method'] :$this->data['method'] == strtolower($key);
			default:
				return null;
		}
	}
}