<?php


namespace Eliepse\Cache;


use DateInterval;
use DateTime;

class CacheFile
{
	
	private $basename;
	private $modifiedAt;
	private $path;
	private $file_exist;
	private $data;
	
	
	/**
	 * CacheFile constructor.
	 *
	 * @param $path The path to the cache file
	 */
	public function __construct($path)
	{
		
		$this->basename = pathinfo($path, PATHINFO_BASENAME);
		$this->dirname = pathinfo($path, PATHINFO_DIRNAME);
		$this->path = $path;
		
	}
	
	
	/**
	 * Get the data of the cache element
	 *
	 * @return null|mixed Return null if the file doesn't exist
	 */
	public function getData()
	{
		if (!$this->isFileExist())
			return null;
		
		if (!isset($this->data))
			$this->data = file_get_contents($this->path);
		
		return $this->data;
	}
	
	
	/**
	 * Set the data of the cache element
	 *
	 * @param $data The data to write
	 */
	public function setData($data)
	{
		file_put_contents($this->path, $data, LOCK_EX);
		
		if ($this->isFileExist())
			chmod($this->path, 0604);
		
		$this->data = $data;
		$this->file_exist = true;
		$this->updateModifiedAt();
	}
	
	
	/**
	 * Check if the file exist or not
	 *
	 * @return boolean Return True if the file exist or False if not.
	 */
	public function isFileExist()
	{
		if (!isset($this->file_exist))
			$this->file_exist = file_exists($this->path);
		
		return $this->file_exist;
	}
	
	
	/**
	 * Return the last "modified at" value of the cache
	 *
	 * @return \DateTime|null A DateTime of the last "modified at" value of the file or null if no file exist.
	 */
	public function getModifiedAt()
	{
		
		if (!$this->isFileExist())
			return null;
		
		if (!isset($this->modifiedAt))
			$this->updateModifiedAt();
		
		return $this->modifiedAt;
		
	}
	
	
	/**
	 * Delete the cache file
	 *
	 * @return bool Return true or false depending the success
	 */
	public function delete()
	{
		if ($this->isFileExist()) {
			return unlink($this->path);
		}
		
		return false;
	}
	
	
	/**
	 * Chech if the cache element is expired or not
	 *
	 * @param DateInterval $interval The delay of expiration
	 * @return bool True if expired or doesn't exist, false if valid
	 */
	public function isExpired(DateInterval $interval)
	{
		if (!$this->isFileExist())
			return true;
		
		$modifiedAt = $this->getModifiedAt();
		
		if (!$modifiedAt instanceof DateTime)
			return true;
		
		$expire_date = clone $modifiedAt;
		$expire_date->add($interval);
		
		return $expire_date <= new DateTime();
	}
	
	
	/**
	 * Return the Absolute parth of the cache file.
	 * @return string A string with the absolute path
	 */
	public function getAbsolutePath()
	{
		return realpath($this->path);
	}
	
	
	/**
	 * Just update the modifiedAt value
	 */
	private function updateModifiedAt()
	{
		if ($this->isFileExist())
			$this->modifiedAt = new DateTime('@' . filemtime($this->path));
	}
	
}