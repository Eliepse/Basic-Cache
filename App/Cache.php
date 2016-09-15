<?php


namespace Eliepse\Cache;


use DateInterval;
use Eliepse\Config\ConfigFactory;

class Cache implements CacheInterface
{
	
	static public $_read_only = 0x1;
	static public $_no_write = 0x2;
	static public $_no_delete = 0x4;
	static public $_force_read = 0x6;
	static public $_return_class = 0x8;
	
	protected $cache_config;
	protected $cache_path = '';
	protected $type = '';
	protected $chmod_cache_folder = 0700;
	
	
	/**
	 * Cache constructor.
	 *
	 * @param null|string $url The url to use a different cache folder
	 */
	public function __construct($url = '')
	{
		$this->cache_config = ConfigFactory::getConfig('cache');
		
		if (!empty($url) && is_string($url))
			$this->cache_path = $url;
		else
			$this->cache_path = '';
	}
	
	
	/**
	 * @param string $name
	 * @param null|int|bool $expire The expire time in seconds
	 * @param null|int $flags
	 * @return mixed|null
	 */
	public function read($name, $expire = null, $flags = null)
	{
		$cache_file = $this->getFileCache($name);
		
		if (!$this->isCacheFileValid($cache_file, $expire, $flags)) {
			
			if (!$flags & self::$_no_delete)
				$this->remove($name);
			
			return null;
		}
		
		return ($flags & self::$_return_class) ? $cache_file : $cache_file->getData();
	}
	
	
	public function write($name, $value = null, $flags = null)
	{
		$cache_file = $this->getFileCache($name);
		
		if (!($flags & self::$_read_only || $flags & self::$_no_write))
			$cache_file->setData($value);
		
		return ($flags & self::$_return_class) ? $cache_file : $cache_file->getData();
	}
	
	
	public function readOrWrite($name, $toWrite, $expire = null, $flags = null)
	{
		
		$data = $this->read($name, $expire, $flags);
		
		if (is_null($data) && !($flags & self::$_read_only || $flags & self::$_no_write)) {
			
			if (is_callable($toWrite)) {
				
				$foo_data = $toWrite($this->getFileCache($name));
				
				if ($foo_data & self::$_force_read)
					$data = $this->getFileCache($name)->getData();
				
				if (!($foo_data & self::$_no_write)) {
					$data = $this->write($name, $foo_data);
				}
				
			} else {
				$data = $this->write($name, $toWrite);
			}
			
		}
		
		return $data;
	}
	
	
	/**
	 * Remove a cache element
	 *
	 * @param string $name
	 */
	public function remove($name)
	{
		$this->getFileCache($name)->delete();
		unset($this->files_cache[ $name ]);
	}
	
	
	public function isCacheEntryExpired($name, $expire = null)
	{
		$cache_file = $this->getFileCache($name);
		
		if (is_null($expire))
			$expire = $this->cache_config->default_expired_time;
		
		return $this->isCacheFileExpired($cache_file, $expire);
	}
	
	
	/**
	 * @param $filename string
	 * @return CacheFile
	 */
	public function getFileCache($filename)
	{
		return CacheFileFactory::getInstance()->getCacheFile($filename,
			$this->type,
			$this->cache_path,
			$this->chmod_cache_folder);
	}
	
	
	protected function isCacheFileValid(CacheFile $cache_file, $expire, $flags = null)
	{
		if (is_null($expire))
			$expire = $this->cache_config->default_expired_time;
		
		if ($expire !== false && ($expire === true || $this->isCacheFileExpired($cache_file, $expire)))
			return false;
		else
			return true;
	}
	
	
	/**
	 * @param CacheFile $cache_file
	 * @param int $sec_interval
	 * @return bool
	 */
	protected function isCacheFileExpired(CacheFile $cache_file, $sec_interval)
	{
		
		if (!is_int($sec_interval))
			return false;
		
		switch ($this->cache_config->mode) {
			case 'production' :
				return $cache_file->isExpired(new DateInterval('PT' . $sec_interval . 'S'));
				break;
			case 'all_expire' :
				return true;
				break;
			case 'no_expire':
				return false;
				break;
			default:
				return $cache_file->isExpired(new DateInterval('PT' . $sec_interval . 'S'));
		}
		
	}
	
	
	final private function __clone()
	{
	}
}