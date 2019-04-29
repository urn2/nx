<?php
/**
 * Created by PhpStorm.
 * User: Vea
 * Date: 2019/03/26 026
 * Time: 09:33
 */

use PHPUnit\Framework\TestCase;

class throwTest extends TestCase{
	use \nx\parts\validator\filterThrow;
	protected $in;
	protected $source=[
		'string'=>"123456789",
		'string2'=>"12345678x",
		'email'=>'vea.urn2@gmail.com',
		'mobile'=>17090084418,
		'id'=>'000000198103230000',
		'hex'=>'0xFFFF',
		'int1'=>1,
		'int2'=>10,
		'cid'=>11,
		'did'=>12,
	];
	protected function setUp(){
		parent::setUp(); // TODO: Change the autogenerated stub
		$this->in =new \nx\input();
		$this->in['query']=[
			'cid'=>1,
			'did'=>2,
		];
		$this->in['body']=[
			'string'=>"123456789",
			'string2'=>"12345678x",
			'email'=>'vea.urn2@gmail.com',
			'mobile'=>17090084418,
			'id'=>'000000198103230000',
			'date1'=>'2019-03-07 15:17',
			'date2'=>'2019-03-07 15:17:05',
			'date3'=>'2019/03/07',
			'date4'=>'- xx d 2019x/03/07',
			'hex'=>'0xFFFF',
			'int1'=>1,
			'int2'=>10,
			'cid'=>3,
			'did'=>4,
			'json1'=>json_encode('12345', JSON_UNESCAPED_UNICODE),
			'json2'=>json_encode(true, JSON_UNESCAPED_UNICODE),
			'json3'=>json_encode(null, JSON_UNESCAPED_UNICODE),
			'json4'=>json_encode([1,2,3], JSON_UNESCAPED_UNICODE),
			'json5'=>json_encode(['id'=>1, 'xx'=>2], JSON_UNESCAPED_UNICODE),
			'json6'=>json_encode(['id'=>1, 'xx'=>'x'], JSON_UNESCAPED_UNICODE),
			'json7'=>json_encode([
				'id'=>1,
				'xx'=>[
					'a'=>2,
					'b'=>3,
				]
			], JSON_UNESCAPED_UNICODE),
			'array1'=>[1,2,3],
			'array2'=>['id'=>1, 'xx'=>2],
			'array3'=>['id'=>1, 'xx'=>'x'],
			'array4'=>'1,2,3,4,5',
			'array5'=>'1,2,3,4,5,xx',
			'array6'=>json_encode([
				'id'=>1,
				'xx'=>[
					'a'=>[
						['n'=>1],
						['n'=>2],
						['n'=>3],
					],
					'b'=>3,
				]
			], JSON_UNESCAPED_UNICODE),
			'base64'=>base64_encode('12345'),
			'email1'=>'x9@c.com',
			'email2'=>'x.a@c.cn',
			'email3'=>'172847081@11.com',
			'email4'=>'17284708111.com',
			'email5'=>'1728470@8111',
			'email6'=>'1728470中@8111.com',
			'mobile1'=>'13620801000',//ok
			'mobile2'=>'14820801000',
			'mobile3'=>'15820801000',
			'mobile4'=>'17820801000',
			'mobile5'=>'18820801000',
			'mobile6'=>'19820',//error
			'mobile7'=>'19820801000',
			'mobile8'=>'29820801000122',
			'url1'=>'http://b.com/',//ok
			'url2'=>'https://baidu.com/?a=xxaaa',
			'url3'=>'xx.com',
			'url4'=>'xxcom',//error
			'url5'=>'http://xx.com/a=xx#aaa',
			'id1'=>'110112198001010001',//ok
			'id2'=>'11011219800101000X',
			'id3'=>'110112800101000',
			'id4'=>'11011280010',//error
			'ip1'=>'127.0.0.1',
			'ip2'=>'192.168.1.88',
			'ip3'=>'116.62.188.207',
			'ip4'=>'116.62.188'
		];
		$this->in['params']=[
			'cid'=>5,
			'did'=>6,
		];
		$this->in['header']=[
			'cid'=>7,
			'did'=>8,
		];
		$this->in['cookie']=[
			'cid'=>9,
			'did'=>10,
		];
	}
	/**
	 * 规则降级(简化)
	 */
	public function testRules(){
		$data =$this->filter([
			'dxx-id'=>[
				'type'=>['value'=>'integer'],
				'from'=>'query',
				'key'=>['value'=>'did'],
			],
			'cx-id'=>[
				'type'=>'integer',//=> ['value'=>'integer']
				'from'=>'body',
				'key'=>'cid',
			],
			'dx-id'=>['integer','body','key'=>'did',],
			'cid'=>['int','uri'],
			'did'=>['int', 'uri'],
			'int2'=>'int',
		]);
		$this->assertEquals(2, $data['dxx-id']);
		$this->assertEquals(3, $data['cx-id']);
		$this->assertEquals(4, $data['dx-id']);
		$this->assertEquals(5, $data['cid']);
		$this->assertEquals(6, $data['did']);
		$this->assertEquals(10, $data['int2']);
	}
	/**
	 * 不同来源
	 */
	public function testFrom(){
		//todo header & cookie
		$data =$this->filter([
			'cid1'=>['int', 'query', 'key'=>'cid'],
			'cid2'=>['int', 'body', 'key'=>'cid'],
			'cid3'=>['int', 'uri', 'key'=>'cid'],
			'cid4'=>['int', 'header', 'key'=>'cid'],
			'cid5'=>['int', 'cookie', 'key'=>'cid'],
			'cid6'=>['int', 'from'=>$this->source, 'key'=>'cid'],
			'cid7'=>['int', 'key'=>'cid'],//<----------default 'body'
		]);
		$this->assertEquals(1, $data['cid1']);
		$this->assertEquals(3, $data['cid2']);
		$this->assertEquals(5, $data['cid3']);
		$this->assertEquals(7, $data['cid4']);
		$this->assertEquals(9, $data['cid5']);
		$this->assertEquals(11, $data['cid6']);
		$this->assertEquals(3, $data['cid7']);
	}
	/**
	 * 调整默认来源
	 */
	public function testFromDefault(){
		//todo header & cookie
		$data =$this->filter([
			'cid1'=>['int', 'query', 'key'=>'cid'],
			'cid2'=>['int', 'key'=>'cid'],//<----------default 'query'
			'cid3'=>['int', 'uri', 'key'=>'cid'],
			'cid4'=>['int', 'header', 'key'=>'cid'],
			'cid5'=>['int', 'cookie', 'key'=>'cid'],
			'cid6'=>['int', 'from'=>$this->source, 'key'=>'cid'],
			'cid7'=>['int', 'key'=>'cidx'],//<----------no exist
			'cid8'=>['int', 'key'=>'cidx', 'null'=>1],//<----------no exist set default
		], ['query', 'error'=>404]);
		$this->assertEquals(1, $data['cid1']);
		$this->assertEquals(1, $data['cid2']);
		$this->assertEquals(5, $data['cid3']);
		$this->assertEquals(7, $data['cid4']);
		$this->assertEquals(9, $data['cid5']);
		$this->assertEquals(11, $data['cid6']);
		$this->assertEquals(null, $data['cid7']);
		$this->assertEquals(1, $data['cid8']);//上层throw不会影响到当前默认值设置
	}
	/**
	 * 默认值设置
	 */
	public function testNull(){
		//数据返回 全局数据来源配置
		$data =$this->filter('xid', ['null'=>2, 'query']);
		$this->assertEquals(2, $data);
	}
	/**
	 * 必填
	 * @expectedException \Exception
	 * @expectedExceptionCode 400
	 */
	public function testNullError(){
		//数据返回 全局数据来源配置
		$this->filter('xid', ['null', 'query']);
	}
	/**
	 * 必填
	 * @expectedException \Exception
	 * @expectedExceptionCode 401
	 */
	public function testNullErrorAndCustomError(){
		//数据返回 全局数据来源配置
		$this->filter('xid', ['null', 'query', 'error'=>401]);
	}
	/**
	 * @expectedException \Exception
	 * @expectedExceptionCode 401
	 */
	public function testIntGT10Error(){
		//数据返回 全局数据来源配置
		$this->filter('cid', ['int', 'digit'=>['>'=>10], 'query', 'error'=>401]);
	}
	public function testINT1(){
		//标准数组返回
		$data=$this->filter(['int1'=>['int']]);
		$this->assertEquals(1, $data['int1']);
		$this->assertInternalType('integer', $data['int1']);
	}
	public function testINT1Alias(){
		//标准数组返回
		$data=$this->filter(['int1'=>['integer']]);
		$this->assertEquals(1, $data['int1']);
		$this->assertInternalType('integer', $data['int1']);
	}
	public function testINT1NameAs(){
		//数组返回 别名
		$data=$this->filter(['int'=>['int', 'key'=>'int1', 'from'=>$this->source]]);
		$this->assertEquals(1, $data['int']);
	}
	public function testINT1Data(){
		//数据返回
		$data=$this->filter('int1', ['int', 'source'=>$this->source]);
		$this->assertEquals(1, $data);
	}
	public function testINT1DataOptions(){
		//数据返回 全局数据来源配置
		$data =$this->filter('int1', ['int'], ['source'=>$this->source]);
		$this->assertEquals(1, $data);
	}
	public function testString(){
		//标准数组返回
		$data=$this->filter(['string'=>['str', 'body']]);
		$this->assertInternalType('string', $data['string']);
	}
	public function testStringAlias(){
		//标准数组返回
		$data=$this->filter(['string'=>['string', 'body']]);
		$this->assertInternalType('string', $data['string']);
	}
	public function testJson(){
		//标准数组返回
		$data=$this->filter([
			'json1'=>['json'=>'int'], //=>{type:json, children:{int}}
			'json2'=>'json',
			'json3'=>'json',
			'json4'=>['json'=>['arr'=>'int']],
			'json5'=>'json',//todo [key=>value] ?
			'json6'=>['json'=>['arr'=>['int', 'error'=>401]]],
		], ['body']);
		//$this->assertInternalType('string', $data['json1']);
		$this->assertInternalType('integer', $data['json1']);
		$this->assertEquals('12345', $data['json1']);
		$this->assertInternalType('boolean', $data['json2']);
		$this->assertEquals(true, $data['json2']);
		$this->assertEquals(null, $data['json3']);
		$this->assertInternalType('array', $data['json4']);
		$this->assertEquals([1,2,3], $data['json4']);
		$this->assertInternalType('array', $data['json5']);
		$this->assertEquals(['id'=>1, 'xx'=>2], $data['json5']);
		//$this->assertInternalType('array', $data['json6']);
		//$this->assertEquals(['id'=>1, 'xx'=>'x'], $data['json6']);
	}
	/**
	 * @expectedException \Exception
	 * @expectedExceptionCode 401
	 */
	public function testDate(){
		$data=$this->filter([
			'date1'=>'date',
			'date2'=>'date',
			'date3'=>'date',
			'date4'=>['date', 'null'=>'throw', 'error'=>401],
		], ['body']);
		$this->assertInternalType('integer', $data['date1']);
		$this->assertEquals(strtotime('2019-03-07 15:17'), $data['date1']);
		$this->assertInternalType('integer', $data['date2']);
		$this->assertEquals(strtotime('2019-03-07 15:17:05'), $data['date2']);
		$this->assertInternalType('integer', $data['date3']);
		$this->assertEquals(strtotime('2019-03-07 00:00:00'), $data['date3']);
		$this->assertInternalType('integer', $data['date4']);
		$this->assertEquals(0, $data['date4']);
	}
	public function testArray(){
		$data=$this->filter([
			//'array1'=>'arr',
			//'array2'=>['array'=>['key-exists'=>'id']],
			//'array3'=>['type'=>['value'=>'array', 'error'=>403], 'error'=>404],//no exists idx
			'array4'=>['array'=>['int', 'error'=>402], 'error'=>401],
		], ['body']);
		//$this->assertInternalType('array', $data['array1']);
		//$this->assertEquals([1,2,3], $data['array1']);
		//$this->assertInternalType('array', $data['array2']);
		//$this->assertEquals(['id'=>1, 'xx'=>2], $data['array2']);
		//$this->assertInternalType('array', $data['array3']);
		//$this->assertEquals(['id'=>1, 'xx'=>'x'], $data['array3']);
		$this->assertInternalType('array', $data['array4']);
		$this->assertEquals([1,2,3,4,5], $data['array4']);
	}
	/**
	 * @expectedException \Exception
	 * @expectedExceptionCode 402
	 */
	public function testArrayInt(){
		$data=$this->filter([
			'array5'=>['array'=>['int', 'throw', 'error'=>402], 'error'=>401],
		], ['body']);
	}
	public function testHex(){
		//标准数组返回
		$data=$this->filter([
			'hex'=>'hex',
		], ['body']);
		//$this->assertInternalType('string', $data['json1']);
		$this->assertInternalType('integer', $data['hex']);
		$this->assertEquals(65535, $data['hex']);
	}
	public function testBase64(){
		//标准数组返回
		$data=$this->filter([
			'base64'=>'base64',
		], ['body']);
		$this->assertInternalType('string', $data['base64']);
		$this->assertEquals('12345', $data['base64']);
	}
	public function testRemove(){
		//标准数组返回
		$data=$this->filter([
			'cid'=>['int'],
			'did'=>['int'],
			'xid'=>['int', 'remove'],
		], ['body']);
		$this->assertInternalType('array', $data);
		$this->assertEquals(['cid'=>3, 'did'=>4], $data);
	}
	public function testRemoveOne(){
		//标准数组返回
		$data=$this->filter('xid', ['int', 'remove']);
		$this->assertInternalType('null', $data);
		$this->assertEquals(null, $data);
	}
	public function testRemoveAndEmpty(){
		//标准数组返回
		$data=$this->filter([
			'xid'=>['int', 'remove'],
		], ['body']);
		$this->assertInternalType('array', $data);
		$this->assertEquals([], $data);
	}
	public function testEmail(){
		$data=$this->filter([
			'email1'=>['match'=>'email'],
			'email2'=>['email'],
			'email3'=>['email'],
		], ['body']);
		$this->assertEquals($this->in['body']['email1'], $data['email1']);
		$this->assertEquals($this->in['body']['email2'], $data['email2']);
		$this->assertEquals($this->in['body']['email3'], $data['email3']);
	}
	/**
	 * @expectedException \Exception
	 * @expectedExceptionCode 401
	 */
	public function testEmailError(){
		$data=$this->filter([
			'email4'=>['email'],
			'email5'=>['email'],
			'email6'=>['email'],
		], ['body', 'error'=>401]);
		var_dump($data);
	}
	public function testMobile(){
		$data=$this->filter([
			'mobile1'=>['china-mobile'],
			'mobile2'=>['china-mobile'],
			'mobile3'=>['china-mobile'],
			'mobile4'=>['china-mobile'],
			'mobile5'=>['china-mobile'],
		], ['body']);
		$this->assertEquals($this->in['body']['mobile1'], $data['mobile1']);
		$this->assertEquals($this->in['body']['mobile2'], $data['mobile2']);
		$this->assertEquals($this->in['body']['mobile3'], $data['mobile3']);
		$this->assertEquals($this->in['body']['mobile4'], $data['mobile4']);
		$this->assertEquals($this->in['body']['mobile5'], $data['mobile5']);
	}
	/**
	 * @expectedException \Exception
	 * @expectedExceptionCode 401
	 */
	public function testMobileError(){
		//$this->in['body']['mobile8']=18620806200;
		$data=$this->filter([
			'mobile6'=>['china-mobile'],
			'mobile7'=>['china-mobile'],
			'mobile8'=>['china-mobile'],
		], ['body', 'error'=>401]);
	}
	public function testUrl(){
		$data=$this->filter([
			'url1'=>['url'],
			'url2'=>['url'],
		], ['body']);
		$this->assertEquals($this->in['body']['url1'], $data['url1']);
		$this->assertEquals($this->in['body']['url2'], $data['url2']);
	}
	/**
	 * @expectedException \Exception
	 * @expectedExceptionCode 401
	 */
	public function testUrlError(){
		$data=$this->filter([
			'url3'=>['url'],
			'url4'=>['url'],
			'url5'=>['url'],
		], ['body', 'error'=>401]);
	}
	public function testID(){
		$data=$this->filter([
			'id1'=>['china-id'],
			'id2'=>['china-id'],
		], ['body']);
		$this->assertEquals($this->in['body']['id1'], $data['id1']);
		$this->assertEquals($this->in['body']['id2'], $data['id2']);
	}
	/**
	 * @expectedException \Exception
	 * @expectedExceptionCode 401
	 */
	public function testIDError(){
		$data=$this->filter([
			'id3'=>['china-id'],
			'id4'=>['china-id'],
		], ['body', 'error'=>401]);
	}
	public function testIP(){
		$data=$this->filter([
			'ip1'=>['ip-v4'],
			'ip2'=>['ip-v4'],
			'ip3'=>['ip-v4'],
		], ['body']);
		$this->assertEquals($this->in['body']['ip1'], $data['ip1']);
		$this->assertEquals($this->in['body']['ip2'], $data['ip2']);
		$this->assertEquals($this->in['body']['ip3'], $data['ip3']);
	}
	/**
	 * @expectedException \Exception
	 * @expectedExceptionCode 401
	 */
	public function testIPError(){
		$data=$this->filter([
			'ip4'=>['ip-v4'],
		], ['body', 'error'=>401]);
	}
}