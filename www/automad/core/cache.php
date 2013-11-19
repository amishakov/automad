<?php defined('AUTOMAD') or die('Direct access not permitted!');
/*
 *	                  ....
 *	                .:   '':.
 *	                ::::     ':..
 *	                ::.         ''..
 *	     .:'.. ..':.:::'    . :.   '':.
 *	    :.   ''     ''     '. ::::.. ..:
 *	    ::::.        ..':.. .''':::::  .
 *	    :::::::..    '..::::  :. ::::  :
 *	    ::'':::::::.    ':::.'':.::::  :
 *	    :..   ''::::::....':     ''::  :
 *	    :::::.    ':::::   :     .. '' .
 *	 .''::::::::... ':::.''   ..''  :.''''.
 *	 :..:::'':::::  :::::...:''        :..:
 *	 ::::::. '::::  ::::::::  ..::        .
 *	 ::::::::.::::  ::::::::  :'':.::   .''
 *	 ::: '::::::::.' '':::::  :.' '':  :
 *	 :::   :::::::::..' ::::  ::...'   .
 *	 :::  .::::::::::   ::::  ::::  .:'
 *	  '::'  '':::::::   ::::  : ::  :
 *	            '::::   ::::  :''  .:
 *	             ::::   ::::    ..''
 *	             :::: ..:::: .:''
 *	               ''''  '''''
 *	
 *
 *	AUTOMAD CMS
 *
 *	Copyright (c) 2013 by Marc Anton Dahmen
 *	http://marcdahmen.de
 *
 *	Licensed under the MIT license.
 */


/**
 *	The Cache class holds all methods for evaluating, reading and writing the HTML output and the Site object from/to AM_DIR_CACHE.
 *	Basically there are three things which get cached - the latest modification time of all the site's files and directories (site's mTime), the page's HTML and the Site object.
 *
 *	The workflow:
 *	
 *	1. 
 *	A virtual file name of a possibly existing cached version of the visited page gets determined from the PATH_INFO, the QUERY_STRING and the SERVER_NAME.
 *	To keep the whole site portable, the SERVER_NAME within the path is very important, to make sure, that all links/URLs are relative to the correct root directory.
 *	("sub.domain.com/" and "www.domain.com/sub" will return different root relative URLs > "/" and "/sub", but may host the same site > each will get its own /cache/directory)
 *
 *	2. 
 *	The site's mTime gets determined. To keep things fast, the mTime gets only re-calculated after a certain delay and then stored in AM_FILE_SITE_MTIME. 
 *	In between the mTime just gets loaded from that file. That means that not later then AM_CACHE_MONITOR_DELAY seconds, all pages will be up to date again.
 *	To determine the latest changed item, all directories and files under /pages, /shared, /themes and /config get collected in an array.
 *	The filemtime for each item in that array gets stored in a new array ($mTimes[$item]). After sorting, all keys are stored in $mTimesKeys.
 *	The last modified item is then = end($mTimesKeys), and its mtime is $mTimes[$lastItem].
 *	Compared to using max() on the $mTime array, this method is a bit more complicated, but also determines, which of the items was last edited and not only its mtime.
 *	(That gives a bit more control for debugging)
 *
 *	3.
 *	When calling now Cache::pageCacheIsApproved() from outside, true will be returned if the cached file exists and is newer than the site's mTime (and of course caching is active).
 *	If the cache is validated, Cache::readPageFromCache() can return the full HTML to be echoed.
 *	
 *	4. 
 *	In case the page's cached HTML is deprecated, Cache::siteObjectCacheIsApproved() can be called to verify the status of the Site object cache (a file holding the serialized Site object ($S)).
 *	If the Site object cache is approved, Cache::readSiteObjectFromCache() returns the unserialized Site object to be used to create an updated page from a template (outside of Cache).
 *	That step is very helpful, since the current page's cache might be outdated, but other pages might be already up to date again and therefore the Site object cache might be updated also in the mean time.
 *	So when something got changed across the Site, the Site object only has to be created once to be reused to update all pages. 
 *
 *	5.
 *	In case the page and the Site object are deprecated, after creating both, they can be saved to cache using Cache::writePageToCache() and Cache::writeSiteObjectToCache().
 */


class Cache {
	
	
	/**
	 *	The determined matching file of the cached version of the currently visited page.
	 */
	
	private $pageCacheFile;
	

	/**
	 *	The latest modification time of the whole website (any file or directory).
	 */
	
	private $siteMTime;
	
	
	/**
	 *	The constructor just determines $pageCacheFile to make it available within the instance.
	 */
	
	public function __construct() {
		
		if (AM_CACHE_ENABLED) {
			
			Debug::log('Cache: New Instance created!');
			
			$this->pageCacheFile = $this->getPageCacheFilePath();
			$this->siteMTime = $this->getSiteMTime();
		
		} else {
			
			Debug::log('Cache: Caching is disabled!');
			
		}
		
	}
	

	/**
	 *	Verify if the cached version of the visited page is existing and still up to date.
	 *
	 *	@return boolean - true, if the cached version is valid.
	 */

	public function pageCacheIsApproved() {
		
		if (AM_CACHE_ENABLED) {
	
			if (file_exists($this->pageCacheFile)) {	
					
				$cacheMTime = filemtime($this->pageCacheFile);
			
				if ($cacheMTime < $this->siteMTime) {
					
					// If the cached page is older than the site's mTime,
					// the cache gets no approval.
					Debug::log('Cache: Page cache is deprecated!'); 
					Debug::log('       Page cache mTime: ' . date('d. M Y, H:i:s', $cacheMTime));
					return false;
					
				} else {
					
					// If the cached page is newer, it gets approved.
					Debug::log('Cache: Page cache got approved!');
					Debug::log('       Page cache mTime: ' . date('d. M Y, H:i:s', $cacheMTime));
					return true;
					
				}
	
			} else {
		
				Debug::log('Cache: Page cache does not exist!');
				return false;
		
			}
	
		} else {
			
			Debug::log('Cache: Caching is disabled! Not checking page cache!');
			return false;
			
		}
		
	}


	/**
	 *	Verify if the cached version of the Site object is existingand  still up to date.
	 *
	 *	@return boolean 
	 */

	public function siteObjectCacheIsApproved() {
		
		if (AM_CACHE_ENABLED) {
		
			if (file_exists(AM_FILE_SITE_OBJECT_CACHE)) {
		
				$siteObjectMTime = filemtime(AM_FILE_SITE_OBJECT_CACHE);
			
				if ($siteObjectMTime < $this->siteMTime) {
				
					Debug::log('Cache: Site object cache is deprecated!');
					Debug::log('       Site object mTime: ' . date('d. M Y, H:i:s', $siteObjectMTime));
					return false;
				
				} else {
					
					Debug::log('Cache: Site object cache got approved!');
					Debug::log('       Site object mTime: ' . date('d. M Y, H:i:s', $siteObjectMTime));
					return true;
					
				}
			
			} else {
				
				Debug::log('Cache: Site object cache does not exist!');
				return false;
				
			}
			
			
		} else {
			
			Debug::log('Cache: Caching is disabled! Not checking site object!');
			return false;
			
		}
		
		
	}


	/**
	 *	Determine the corresponding file in the cache for the visited page in consideration of a possible query string.
	 *	A page gets for each possible query string (to handle sort/filter) an unique cache file.
	 *
	 *	@return The determined file name of the matching cached version of the visited page.
	 */
	
	private function getPageCacheFilePath() {
	
		if (isset($_SERVER['PATH_INFO'])) {
			// Make sure that $currentPath is never just '/', by wrapping the string in an extra rtrim().
			$currentPath = rtrim('/' . trim($_SERVER['PATH_INFO'], '/'), '/');
		} else {
			$currentPath = '';
		}
		
		if ($_SERVER['QUERY_STRING']) {
			$queryString = '_' . Parse::sanitize($_SERVER['QUERY_STRING']);
		} else {
			$queryString = '';
		}
		
		$pageCacheFile = AM_BASE_DIR . AM_DIR_CACHE . '/' . $_SERVER['SERVER_NAME'] . $currentPath . '/' . AM_FILE_PREFIX_CACHE . $queryString . '.' . AM_FILE_EXT_PAGE_CACHE;
		
		return $pageCacheFile;
		
	}
	
	
	/**
	 *	Get an array of all subdirectories and all files under /pages, /shared, /themes and /config (and the version.php) 
	 *	and determine the latest mtime among all these items.
	 *	That time basically represents the site's modification time, to find out the lastes edit/removal/add of a page.
	 *	To be efficient under heavy traffic, the Site-mTime only gets re-determined after a certain delay.
	 *
	 *	@return The latest found mtime, which equal basically the site's modification time.
	 */
	
	private function getSiteMTime() {
		
		if ((@filemtime(AM_FILE_SITE_MTIME) + AM_CACHE_MONITOR_DELAY) < time()) {
		
			// The modification times get only checked every AM_CACHE_MONITOR_DELAY seconds, since
			// the process of collecting all mtimes itself takes some time too.
			// After scanning, the mTime gets written to a file. 
		
			// $arrayDirsAndFiles will collect all relevant files and dirs to be monitored for changes.
			// At first, since it it just a single file, it will hold version.php. 
			// (This file always exists and there is no can needed to add it to the array)
			// The version file represents all changes to the core files, since it will always be increased with a changeset,
			// so the core itself doesn't need to be scanned.
			$arrayDirsAndFiles = array(AM_BASE_DIR . '/automad/version.php');
		
			// The following directories are monitored for any changes.
			$monitoredDirs = array(AM_DIR_PAGES, AM_DIR_THEMES, AM_DIR_SHARED, '/config');
		
			foreach($monitoredDirs as $monitoredDir) {
		
				// Get all directories below the monitored directory (including the monitored directory).
			
				// Add base dir to string.
				$dir = AM_BASE_DIR . $monitoredDir;
			
				// Also add the directory itself, to monitor the top level.	
				$arrayDirs = array($dir);
	
				while ($dirs = glob($dir . '/*', GLOB_ONLYDIR)) {
					$dir .= '/*';
					$arrayDirs = array_merge($arrayDirs, $dirs);
				}

				// Get all files
				$arrayFiles = array();
	
				foreach ($arrayDirs as $d) {
					$arrayFiles = array_merge($arrayFiles, array_filter(glob($d . '/*'), 'is_file'));
				}
		
				// Merge all files and dirs into the full collection.
				$arrayDirsAndFiles = array_merge($arrayDirsAndFiles, $arrayDirs, $arrayFiles);

			}
		
			// Collect all modification times and find last modified item
			$mTimes = array();
	
			foreach ($arrayDirsAndFiles as $item) {
				$mTimes[$item] = filemtime($item);
			}
	
			// Needs to be that complicated to get the key and the mtime for debugging.
			// Can't use max() for that.
			asort($mTimes);
			$mTimesKeys = array_keys($mTimes);
			$lastModifiedItem = end($mTimesKeys);
			$siteMTime = $mTimes[$lastModifiedItem];
			
			// Save mTime
			$old = umask(0);
			Debug::log('Cache: Changed umask: ' . umask());
			file_put_contents(AM_FILE_SITE_MTIME, serialize($siteMTime));
			umask($old);
			
			Debug::log('Cache: Scanned directories and saved Site-mTime.');
			Debug::log('       Last modified item: ' . $lastModifiedItem); 
			Debug::log('       Site-mTime:  ' . date('d. M Y, H:i:s', $siteMTime));
			Debug::log('Cache: Restored umask: ' . umask());
		
		} else {
			
			// In between this delay, it just gets loaded from a file.
			$siteMTime = unserialize(file_get_contents(AM_FILE_SITE_MTIME));
			Debug::log('Cache: Load Site-mTime from file.');
			Debug::log('       Site-mTime:  ' . date('d. M Y, H:i:s', $siteMTime));
			
		}
		
		return $siteMTime;
		
	}
	
	
	/**
	 *	Read the rendered page from the cached version.
	 *
	 *	@return The full cached HTML of the page. 
	 */
	
	public function readPageFromCache() {
		
		Debug::log('Cache: Read page: ' . $this->pageCacheFile);
		return file_get_contents($this->pageCacheFile);
		
	}
	
	
	/**
	 *	 Read (unserialize) the Site object from AM_FILE_SITE_OBJECT_CACHE.
	 *
	 *	@return Site object
	 */
	
	public function readSiteObjectFromCache() {
		
		$site = unserialize(file_get_contents(AM_FILE_SITE_OBJECT_CACHE));
		
		Debug::log('Cache: Read site object: ' . AM_FILE_SITE_OBJECT_CACHE);
		Debug::log($site->getCollection());
		
		return $site;
		
	}
	
	
	/**
	 *	Write the rendered HTML output to the cache file.
	 */
	
	public function writePageToCache($output) {
		
		if (AM_CACHE_ENABLED) {
			
			$old = umask(0);
			Debug::log('Cache: Changed umask: ' . umask());
		
			if(!file_exists(dirname($this->pageCacheFile))) {
				mkdir(dirname($this->pageCacheFile), 0777, true);
		    	}
		
			file_put_contents($this->pageCacheFile, $output);
			umask($old);
			Debug::log('Cache: Write page: ' . $this->pageCacheFile);
			Debug::log('Cache: Restored umask: ' . umask());
		
		} else {
			
			Debug::log('Cache: Caching is disabled! Not writing page to cache!');
			
		}
		
	}
	
	
	/**
	 *	Write (serialize) the Site object to AM_FILE_SITE_OBJECT_CACHE.
	 */
	
	public function writeSiteObjectToCache($site) {
		
		if (AM_CACHE_ENABLED) {
			
			$old = umask(0);
			Debug::log('Cache: Changed umask: ' . umask());
			
			if(!file_exists(dirname(AM_FILE_SITE_OBJECT_CACHE))) {
				mkdir(dirname(AM_FILE_SITE_OBJECT_CACHE), 0777, true);
		    	}
		
			file_put_contents(AM_FILE_SITE_OBJECT_CACHE, serialize($site));
			umask($old);
			Debug::log('Cache: Write site object: ' . AM_FILE_SITE_OBJECT_CACHE);
			Debug::log('Cache: Restored umask: ' . umask());
		
		} else {
			
			Debug::log('Cache: Caching is disabled! Not writing site object to cache!');
			
		}	
		
	}
	
	
}


?>