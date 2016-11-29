<?php


namespace Eliepse\Cache;


use DateInterval;
use Eliepse\Config\ConfigFactory;
use InvalidArgumentException;

class Cache implements CacheInterface
{
	
	static public $_read_only = 0x1;
	static public $_no_write = 0x2;
	static public $_no_delete = 0x4;
	static public $_force_read = 0x6;
	static public $_return_class = 0x8;

	protected $_config;

	// The type of the cache (for cache config file).
	// Let empty or write 'default' for default type.
	protected $type = '';

	// The cache folder to use.
	// Note that you can configure this with the cache
	// config file (the type is required).
	protected $folder_path = '';

	// The chmod of the cache folder to use if doesn't exist.
	protected $chmod_folder = 0700;
	
	
	/**
	 * Cache constructor.
	 *
	 * @param null|string $url (optionnal) The url to use a different cache folder
	 */
	public function __construct($url = '')
	{
		$this->_config = ConfigFactory::getConfig('cache');
		
		if (!empty($url) && is_string($url))
			$this->folder_path = $url;
	}
	
	
	/**
	 * Read a cache entry. If expired, the entry is deleted
	 *
	 * @param string $name The name of the cache entry
	 * @param null|int|bool (optional) $expire The expire time in seconds. A False value prevent expiration, True force it
	 * @param int|null $flags (optional) The flag to modify the behaviours
	 * @return string|null Return the value of the cache or null if the cache file is not valid
	 */
	public function read($name, $expire = null, $flags = null)
	{
		$cache_file = $this->getFileCache($name);
		
		if ($this->isCacheFileExpired($cache_file, $expire)) {
			
			if (!$flags & self::$_no_delete)
				$this->remove($name);
			
			return null;
		}
		
		return ($flags & self::$_return_class) ? $cache_file : $cache_file->getData();
	}
	
	
	/**
	 * Write a cache entry and return the value (or CacheFile)
	 *
	 * @param string $name The name of the cache entry
	 * @param null|string|int (optional) $value The value to fill the cache
	 * @param int|null $flags (optional) Flags to modify how behave the method
	 * @return CacheFile|mixed Return the value of the cache entry, or a CacheFile with "_return_class" flag
	 */
	public function write($name, $value = null, $flags = null)
	{
		$cache_file = $this->getFileCache($name);
		
		if (!($flags & self::$_read_only || $flags & self::$_no_write))
			$cache_file->setData($value);
		
		return ($flags & self::$_return_class) ? $cache_file : $cache_file->getData();
	}
	
	
	/**
	 * Read a cache entry. If expired the entry is written with the $toWrite parameter.
	 *
	 * @param string $name
	 * @param string|callable $toWrite The value to write or function to execute. The return value of the function is written, except if some flags are returned.
	 * @param int|null $expire (optional) The expire delay
	 * @param int|null $flags (optional)
	 * @return CacheFile|mixed|null|string Return the value of the entry or the value passed through $toWrite. If "_return_class" is given, a CacheFile object is returned.
	 */
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
	 * @param string $name The entry name
	 */
	public function remove($name)
	{
		$this->getFileCache($name)->delete();
		unset($this->files_cache[ $name ]);
	}
	
	
	/**
	 * Check if an entry is expired or not
	 *
	 * @param string $name The entry name
	 * @param int|null $expire (optional)
	 * @return bool Return True if expired, False if not
	 */
	public function isCacheEntryExpired($name, $expire = null)
	{
		$cache_file = $this->getFileCache($name);
		
		if (is_null($expire))
			$expire = $this->_config->default_expired_time;
		
		return $this->isCacheFileExpired($cache_file, $expire);
	}
	
	
	/**
	 * Return the CacheFile class of and entry
	 *
	 * @param string $name The entry name
	 * @return CacheFile
	 */
	public function getFileCache($name)
	{
		return CacheFileFactory::getInstance()->getCacheFile($name,
			$this->type,
			$this->folder_path,
			$this->chmod_folder);
	}
	

	/**
	 * Verify if a CacheFile is expired or not
	 *
	 * @param CacheFile $cache_file The CacheFile to check
	 * @param int|bool $expire The expiration delay (expiration date is computed from the last modified date)
	 * @return bool Return True if the CacheFile is expired, False if not.
	 */
	protected function isCacheFileExpired(CacheFile $cache_file, $expire)
	{

		if (is_bool($expire))
			return $expire;

		if (!is_int($expire)) {
			throw new InvalidArgumentException();
			return false;
		}

		switch ($this->_config->mode) {
			case 'production' :
				return $cache_file->isExpired(new DateInterval('PT' . $expire . 'S'));
				break;
			case 'all_expire' :
				return true;
				break;
			case 'no_expire':
				return false;
				break;
			default:
				return $cache_file->isExpired(new DateInterval('PT' . $expire . 'S'));
		}
		
	}
	
	
	final private function __clone()
	{
	}
}