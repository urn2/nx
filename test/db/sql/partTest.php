<?php
/**
 * Created by PhpStorm.
 * User: Vea
 * Date: 2019/04/19 019
 * Time: 10:44
 */
use nx\helpers\db\sql\part;

class partTest extends PHPUnit\Framework\TestCase{
	/**
	 * @var \nx\helpers\db\sql
	 */
	public $from =null;
	public function setUp(){
		parent::setUp(); // TODO: Change the autogenerated stub
		$this->from =new \nx\helpers\db\sql\table('user');
	}
	public function testValue(){
		$part =new \nx\helpers\db\sql\part(1);
		$this->assertEquals('1', (string)$part);

		$part =new \nx\helpers\db\sql\part('1');
		$this->assertEquals('"1"', (string)$part);

		$part =new \nx\helpers\db\sql\part('abc');
		$this->assertEquals('"abc"', (string)$part);

		$part =new \nx\helpers\db\sql\part('abc');
		$this->assertEquals('"abc" `name`', (string)$part->as('name'));
	}
	public function testValueFromTable(){
		$part =($this->from)('123');
		$this->assertEquals('"123"', (string)$part);
	}
	public function testField(){
		$part=new \nx\helpers\db\sql\part('id', 'field', $this->from);
		$this->assertEquals('`user`.`id`', (string)$part);

		$part=new \nx\helpers\db\sql\part(123, 'field', $this->from);
		$this->assertEquals('`user`.`123`', (string)$part);
	}
	public function testFieldFromTable(){
		$part =$this->from['id'];
		$this->assertEquals('`user`.`id`', (string)$part);
	}
	public function testFunction(){
		$name ="sum";
		$part=new \nx\helpers\db\sql\part($name, 'function');
		$this->assertEquals(strtoupper($name).'()', (string)$part);

		$part=new \nx\helpers\db\sql\part($name, 'function');
		$part->arguments('*');
		$this->assertEquals(strtoupper($name).'(*)', (string)$part);

		$part=new \nx\helpers\db\sql\part($name, 'function');
		$part->arguments('*', 'abc', 123);
		$this->assertEquals(strtoupper($name).'(*, "abc", 123)', (string)$part);

		$user =$this->from;
		$part=new \nx\helpers\db\sql\part($name, 'function');
		$part->arguments($user['id'], $user['score']);
		$this->assertEquals(strtoupper($name).'(`user`.`id`, `user`.`score`)', (string)$part);
	}
	public function testFunctionFromSQL(){
		$user =$this->from;

		$part =\nx\helpers\db\sql::YEAR($user('2019-04-19'));
		$this->assertEquals('YEAR("2019-04-19")', (string)$part);

		$part =\nx\helpers\db\sql::YEAR($user['createdAt']);
		$this->assertEquals('YEAR(`user`.`createdAt`)', (string)$part);

		$part =\nx\helpers\db\sql::SUM($user['*']);
		$this->assertEquals('SUM(`user`.*)', (string)$part);
	}
	public function testFunctionLink(){
		$user =$this->from;

		$part =$user['createdAt']->UNIX_TIMESTAMP()->YEAR('Y');
		$this->assertEquals('YEAR(UNIX_TIMESTAMP(`user`.`createdAt`), "Y")', (string)$part);

		$part =$user['createdAt']->UNIX_TIMESTAMP()->YEAR('Y')->and($user['updatedAt']->MONTH()->equal(4));
		$this->assertEquals('YEAR(UNIX_TIMESTAMP(`user`.`createdAt`), "Y") AND MONTH(`user`.`updatedAt`) = 4', (string)$part);
	}
}
