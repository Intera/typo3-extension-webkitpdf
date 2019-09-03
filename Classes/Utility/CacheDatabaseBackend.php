<?php
namespace Tx\Webkitpdf\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Dev-Team Typoheads <dev@typoheads.at>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend;
use TYPO3\CMS\Core\Cache;

/**
 * Cache handling for generated PDF documents.
 */
class CacheDatabaseBackend extends AbstractFrontend
 {

        /**
         * Sets a reference to the cache frontend which uses this backend
         *
         * @param Cache\Frontend\FrontendInterface $cache The frontend for this backend
         */
        public function setCache(Cache\Frontend\FrontendInterface $cache){
            
        }
        /**
         * Checks if a cache entry with the specified identifier exists.
         *
         * @param string $entryIdentifier An identifier specifying the cache entry
         * @return bool TRUE if such an entry exists, FALSE if not
         */
        public function has($entryIdentifier){
            
        }
        /**
         * Removes all cache entries matching the specified identifier.
         * Usually this only affects one entry but if - for what reason ever -
         * old entries for the identifier still exist, they are removed as well.
         *
         * @param string $entryIdentifier Specifies the cache entry to remove
         * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
         */
        public function remove($entryIdentifier){
            
        }
        /**
         * Removes all cache entries of this cache.
         */
        public function flush(){
            
        }
         /**
         * Loads data from the cache.
         *
         * @param string $entryIdentifier An identifier which describes the cache entry to load
         * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
         */
        public function get($entryIdentifier){
            
        }
    
	/**
	 * If this is larger 0 and the number of entries in the cache exeeds the
	 * number of allowed entries, old entries will be deleted.
	 *
	 * @var int
	 */
	protected $maximumNumberOfEntries = 0;

	/**
	 * Does the default garbage collection of the Typo3DatabaseBackend and removes
	 * all files from the PDF cache directory that have no valid cache entries.
	 *
	 * @return void
	 */
	public function collectGarbage() {
		parent::collectGarbage();
		$cacheManager = GeneralUtility::makeInstance(CacheManager::class);
		$cacheManager->collectGarbage();
	}

	/**
     * Saves data in a cache file.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
     * @throws \TYPO3\CMS\Core\Cache\Exception if no cache frontend has been set.
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if the data is not a string
     */
    public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
        if ($this->maximumNumberOfEntries > 0 && !$this->has($entryIdentifier)) {
            $this->removeOldEntriesIfRequired();
        }
        try {
            if (!$this->isValidEntryIdentifier($entryIdentifier)) {
                throw new \InvalidArgumentException('"' . $entryIdentifier . '" is not a valid cache entry identifier.', 1264023823);
            }
            if (!is_string($data)) {
                throw new \TYPO3\CMS\Core\Cache\Exception\InvalidDataException('The given source code is not a valid string.', 1264023824);
            }
            foreach ($tags as $tag) {
                if (!$this->isValidTag($tag)) {
                    throw new \InvalidArgumentException('"' . $tag . '" is not a valid tag for a cache entry.', 1264023825);
                }
            }
            $data = '<?php' . LF . $data . LF . '#';
            parent::set($entryIdentifier, $data, $tags, $lifetime);
        } catch (\TYPO3\CMS\Core\Cache\Exception\InvalidDataException $e) {
        } catch (\TYPO3\CMS\Core\Cache\Exception $e) {
        }
    }

	/**
	 * Sets the maximum number of allowed cache entries.
	 *
	 * @param int $maximumNumberOfEntries
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function setMaximumNumberOfEntries($maximumNumberOfEntries) {

		if (!is_int($maximumNumberOfEntries) || $maximumNumberOfEntries < 0) {
			throw new \InvalidArgumentException('The maxiumum number of entries must be given as a positive integer.', 1233072774);
		}

		$this->maximumNumberOfEntries = $maximumNumberOfEntries;
	}

	/**
	 * Checks if there are more valid entries in the database than allowed and
	 * removes old entries if required. After this call one more entry can be added
	 * whithout exceeding the $maximumNumberOfEntries limit.
	 *
	 * @return void
	 */
	protected function removeOldEntriesIfRequired() {

		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->cacheTable);
                $cacheEntryCount = $queryBuilder
                        ->count('uid')
                        ->from($this->cacheTable)
                        ->where($this->notExpiredStatement)
                        ->execute()
                        ->fetchColumn(0);
		
		if ($cacheEntryCount < $this->maximumNumberOfEntries) {
			return;
		}

		$tooManyItems = ($cacheEntryCount + 1) - $this->maximumNumberOfEntries;
                
                $rows = $queryBuilder
                        ->select($this->identifierField)
                        ->from($this->cacheTable)
                        ->where($this->notExpiredStatement)
                        ->orderBy($this->expiresField, "ASC")
                        ->setMaxResults($tooManyItems)
                        ->execute();
		while($row = $rows->fetch()){
			$this->remove(array_shift($row));
		}
	}
}