<?php
declare(strict_types=1);

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

use InvalidArgumentException;
use PDO;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Cache handling for generated PDF documents.
 */
class CacheDatabaseBackend extends Typo3DatabaseBackend
{
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
    public function collectGarbage()
    {
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
     * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is
     *     used. "0" means unlimited liftime.
     * @return void
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        if ($this->maximumNumberOfEntries > 0 && !$this->has($entryIdentifier)) {
            $this->removeOldEntriesIfRequired();
        }
        parent::set($entryIdentifier, $data, $tags, $lifetime);
    }

    /**
     * Sets the maximum number of allowed cache entries.
     *
     * @param int $maximumNumberOfEntries
     * @return void
     * @throws InvalidArgumentException
     */
    public function setMaximumNumberOfEntries($maximumNumberOfEntries)
    {
        if (!is_int($maximumNumberOfEntries) || $maximumNumberOfEntries < 0) {
            throw new InvalidArgumentException(
                'The maxiumum number of entries must be given as a positive integer.',
                1233072774
            );
        }

        $this->maximumNumberOfEntries = $maximumNumberOfEntries;
    }

    /**
     * @param QueryBuilder $countQueryBuilder
     * @return string
     */
    protected function buildNonExpiredContraint(QueryBuilder $countQueryBuilder): string
    {
        return $countQueryBuilder->expr()->gte(
            'expires',
            $countQueryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], PDO::PARAM_INT)
        );
    }

    protected function getQueryBuilder()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->cacheTable);
    }

    /**
     * Checks if there are more valid entries in the database than allowed and
     * removes old entries if required. After this call one more entry can be added
     * whithout exceeding the $maximumNumberOfEntries limit.
     *
     * @return void
     */
    protected function removeOldEntriesIfRequired()
    {
        $countQuery = $this->getQueryBuilder();
        $cacheEntryCount = $countQuery->count('id')
            ->from($this->cacheTable)
            ->where($this->buildNonExpiredContraint($countQuery))
            ->execute()
            ->fetchColumn(0);

        if ($cacheEntryCount < $this->maximumNumberOfEntries) {
            return;
        }

        $tooManyItems = ($cacheEntryCount + 1) - $this->maximumNumberOfEntries;

        $deleteSelect = $this->getQueryBuilder();
        $deleteResult = $deleteSelect
            ->select('identifier')
            ->from($this->cacheTable)
            ->where($this->buildNonExpiredContraint($deleteSelect))
            ->setMaxResults($tooManyItems)
            ->orderBy('expires', 'ASC')
            ->execute();

        foreach ($deleteResult->fetchColumn(0) as $identifier) {
            $this->remove($identifier);
        }
    }
}
