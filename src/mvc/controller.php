<?php
namespace nx\mvc;

class controller{
	static public $instance =null;

	const doExt = 'on';
	const doBefore = 'before';
	const doAfter = 'after';
	/**
	 *
	 * @var \nx\app
	 */
	public $app;
	/**
	 * @var array
	 */
	public $route = [];
	/**
	 * @var \nx\request
	 */
	public $request=null;
	/**
	 * @var \nx\response
	 */
	public $response =null;
	public function __construct($route, $app){
		$this->app = $app;
		$this->route = $route;

		static::$instance =$this;

		//init use trait
		foreach(class_uses($this) as $_trait){
			$_method =str_replace('\\', '_', $_trait);
			if(method_exists($this, $_method)) $this->$_method();
		}
		//load from app
		if(is_null($this->response)) $this->response =$this->app->response;
		if(is_null($this->request)) $this->request =$this->app->request;
		//run
		$r =$this->exec($this->route[1], true, true);
		//back to app
		$this->app->response =$this->response;
		return $r;
	}
	public function __get($name){
		$this->$name =&$this->app->$name;
		return $this->$name;
	}
	public function __call($name, $args){
		switch($name){
			case 'view':
				return $this->app->view($args[0], $args[1]);
				break;
			case 'nofound':
				$this->response->status(404);
				break;
			default:
				return call_user_func_array([$this->app, $name], $args);
		}
	}
	/**
	 * @param $name
	 * @param bool|false $hook
	 * @param bool|false $all
	 * @return bool
	 */
	private function exec($name, $hook = false, $all =false){
		if($hook){
			$found =false;
			$methods =$all
				?[static::doBefore, static::doBefore.$name, $this->request->method().$name, static::doExt.$name, static::doAfter.$name, static::doAfter]
				:[static::doBefore, $this->request->method(), static::doExt, static::doAfter];
			$r =false;
			foreach($methods as $_fun){
				if(method_exists($this, $_fun)){
					$found =true;
					$r =$this->$_fun($this->response, $this->app);
					if($r ===false) break;
				}
			}
			if($found ===false) return $this->nofound($name);
			return $r;
		}else if(method_exists($this, $name)) return $this->$name($this->response, $this->app);
	}
}

