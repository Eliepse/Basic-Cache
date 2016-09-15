<?php
return [

	/*
	 * Select the cache mode
	 *
	 * production   - Works normaly
	 * all_expire   - Data always expire (usefull for testing)
	 * no_expire    - Data never expire (usefull to lighten internet connections)
	 *
	 * */

	'mode' => 'production',


	/*
	 * Write down the folder to store
	 * the cache files
	 *
	 * */

	'paths' => [
		'default' => 'cache/',
	],

	/*
	 * The default value for the access
	 * right of directory automatically created
	 *
	 * */

	'default_chmod' => 0700,


	/*
	 * Write down the extension you want
	 * to use for the cache system.
	 * Don't forget to start with a "."
	 * if needed
	 *
	 * */

	'cache_extension'      => '.cache',

	
	/*
	 * Default expiration time of
	 * cache files
	 *
	 * */
	'default_expired_time' => 3600,

];
