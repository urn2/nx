<?php
namespace nx\parts\config;

/**
 * Trait files
 * @trait   app
 * @package nx\config
 * @deprecated 2020-06-23 Vea 使用config对象替换
 */
trait files{
	/**
	 * @var array 直接缓存结果 config key
	 */
	protected $_files_config=[];
	protected function nx_parts_config_files(){
		$it=is_a($this, 'nx\app') ?$this :$this->app;
		$it->buffer['config/files']=$it->setup['config/files'] ?? [];
		$it->buffer['config/files']['path']=$it->buffer['config/files']['path'] ?? $it->getPath('./config/');
		$it->buffer['config/files']['cache']=$it->setup['config'] ?? [];
	}
	/**
	 * 读取配置内容
	 * @param      $word   "ns.key"
	 * @param null $params 默认值
	 * @return null
	 */
	public function config($word, $params=null){
		$it=is_a($this, 'nx\app') ?$this :$this->app;
		if(array_key_exists($word, $it->_files_config)) return $it->_files_config[$word];
		$_ns=$word;
		$_key=null;
		if(false !== strpos($word, '.')) [$_ns, $_key]=explode('.', $word, 2);
		$buffer=&$it->buffer['config/files']['cache'];
		if(!array_key_exists($_ns, $buffer)){
			$config=[];
			if(is_file($file=$it->buffer['config/files']['path'].$_ns.'.php')){
				$config=@include($file);
			}
			$buffer[$_ns]=$config;
		}
		$it->_files_config[$word]=is_null($_key) ?$buffer[$_ns] :(isset($buffer[$_ns][$_key]) ?$buffer[$_ns][$_key] :$params);
		return $it->_files_config[$word];
	}
}