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
	static protected $_instances;

	protected $path_base = __DIR__ . '/../../../../';
	protected $files_cache;
	protected $cache_path;
	protected $cache_config;
	protected $cache_extension;


	/**
	 * CacheManager constructor.
	 *
	 * @param bool $url To instanciate with a different cache url
	 */
	public function __construct($url = false)
	{
		$this->initConfigs();
		$this->ensureCacheFolder();
	}


	/**
	 * @param string $name
	 * @param null|int|bool $expire The expire time in seconds
	 * @param null|int $flags
	 * @return mixed|null
	 */
	public function read($name, $expire = null, $flags = null)
	{
		$cache_file = $this->getFileCache($name . $this->cache_extension);

		if (!$this->isCacheFileValid($cache_file, $expire, $flags)) {

			if (!$flags & self::$_no_delete)
				$this->remove($name);

			return null;
		}

		return $cache_file->getData();
	}


	public function write($name, $value = null, $flags = null)
	{
		$cache_file = $this->getFileCache($name . $this->cache_extension);

		if (!($flags & self::$_read_only || $flags & self::$_no_write))
			$cache_file->setData($value);

		return $cache_file->getData();
	}


	public function readOrWrite($name, $toWrite, $expire = null, $flags = null)
	{

		$data = $this->read($name, $expire, $flags);

		if (is_null($data) && !($flags & self::$_read_only || $flags & self::$_no_write)) {

			if (is_callable($toWrite)) {

				$foo_data = $toWrite($data);

				if($foo_data & self::$_force_read)
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
	 * @param string $cache
	 */
	public function remove($cache)
	{
		$this->getFileCache($cache)->delete();
		unset($this->files_cache[ $cache ]);
	}


	public function isCacheEntryExpired($name, $expire = null)
	{
		$cache_file = $this->getFileCache($name);

		if (is_null($expire))
			$expire = $this->cache_config->default_expired_time;

		return $this->isCacheFileExpired($cache_file, $expire);
	}


	protected function initConfigs($url = false)
	{
		$this->files_cache = [];
		$this->cache_config = ConfigFactory::getConfig('cache');

		if (is_string($url) && !empty($url))
			$this->cache_path = $this->path_base . $url;
		else
			$this->cache_path = $this->path_base . $this->cache_config->paths['default'];

		$extension = $this->cache_config->cache_extension;

		if (!is_string($extension) || empty($extension))
			$this->cache_extension = '';
		else
			$this->cache_extension = '.' . $extension;
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


	protected function ensureCacheFolder()
	{
		if (!file_exists($this->cache_path))
			mkdir($this->cache_path, 0600, true);
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


	/**
	 * @param $filename string
	 * @return CacheFile
	 */
	public function getFileCache($filename)
	{
		if (array_key_exists($filename, $this->files_cache))
			return $this->files_cache[ $filename ];

		$this->files_cache[ $filename ] = new CacheFile($this->cache_path . $filename);

		return $this->files_cache[ $filename ];
	}
	
	final private function __clone()
	{
	}
}