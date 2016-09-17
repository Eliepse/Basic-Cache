<?php


namespace Eliepse\Cache;


use Eliepse\Config\ConfigFactory;

class CacheFileFactory
{
	
	static $_instance;
	
	private $path_base;
	private $cache_config;
	private $cache_extension;
	private $_cache_files;
	
	private $cache_config_paths;
	
	
	/**
	 * CacheFileFactory constructor.
	 */
	private function __construct()
	{
		$this->cache_config = ConfigFactory::getConfig('cache');

		$this->path_base = __DIR__ . '/../../../../';
		$this->_cache_files = [];
		
		$extension = $this->cache_config->cache_extension;
		$this->cache_extension = !is_string($extension) || empty($extension) ? '' : '.' . $extension;
		
		$this->cache_config_paths = $this->cache_config->paths;
	}
	
	
	/**
	 * Return the instance of CacheFileFactory (singleton)
	 * @return CacheFileFactory The singleton instance of CacheFileFactory
	 */
	static public function getInstance()
	{
		if (empty(self::$_instance))
			self::$_instance = new CacheFileFactory();
		
		return self::$_instance;
	}
	
	
	/**
	 * Return a valid CacheFile.
	 *
	 * @param string $name The name of the entry
	 * @param null|string $cache_type The type of cache (permit to link to the configurations)
	 * @param null|string $force_url (optional) The path to a specific cache directory
	 * @param null|string $chmod (optional) The chmod for any automatically created folder
	 * @return CacheFile Return a CacheFile object. If the cache file doesn't exist yet, a new CacheFile object is returned.
	 */
	public function getCacheFile($name, $cache_type = '', $force_url = '', $chmod = null)
	{

		// On vérifie si le type est spécifié et on le met de côté
		$type = empty($cache_type) ? 'default' : $cache_type;
		
		
		/* ----- Si le fichier est déjà enregistré, on le renvoie directement ----- */
		
		
		if (array_key_exists($type, $this->_cache_files)
			&& array_key_exists($name, $this->_cache_files[ $type ])
		) {
			return $this->_cache_files[ $type ][ $name ];
		}
		
		
		/* ----- Sinon, on créé une nouvelle entrée ----- */
		
		
		$folder_url = $this->path_base;
		
		// On vérifie que l'url n'a pas été donnée au préalable
		if (empty($force_url)) {
			
			// On récupère le chemin vers le dossier de cache.
			// S'il n'est pas enregistré par défault,
			// On récupère le chemin par défault
			
			$key = array_key_exists($type, $this->cache_config_paths) ? $type : 'default';
			$folder_url .= $this->cache_config_paths[ $key ];
			
		} else {
			
			$folder_url .= $force_url;
			
		}
		
		// On vérifie si le dossier de cache existe
		// sinon on le créé avec le chmod renseigné ou par défaut
		
		if (!file_exists($folder_url))
			mkdir($folder_url, is_int($chmod) ? $chmod : $this->cache_config->default_chmod, true);
		
		// On finit l'url par le nom du fichier et l'extension
		// déjà calculée en fonction de la configuration
		$file_url = $folder_url . $name . $this->cache_extension;
		
		
		/* ----- On enregistre la nouvelle entrée ----- */
		
		
		// on vérifie que le type est déjà enregistré
		if (!array_key_exists($type, $this->_cache_files)) {
			$this->_cache_files[ $type ] = [];
		}
		
		// On enregistre une nouvelle entrée
		$this->_cache_files[ $type ][ $name ] = new CacheFile($file_url);
		
		// On retourne l'entrée nouvellement créée
		return $this->_cache_files[ $type ][ $name ];
	}
	
}