<?php


namespace App;


interface CacheInterface
{
	
	public function read($name, $expire = null, $flags = null);

	public function write($name, $value = null, $flags = null);

	public function remove($name);
	
}