<?php
declare(strict_types=1);
namespace FluidTYPO3\Flux\Service;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\SingletonInterface;

class CacheService implements SingletonInterface
{
    private FrontendInterface $persistentCache;
    private FrontendInterface $transientCache;

    public function __construct(FrontendInterface $persistentCache, FrontendInterface $transientCache)
    {
        $this->persistentCache = $persistentCache;
        $this->transientCache = $transientCache;
    }

    /**
     * @return mixed|false
     */
    public function getFromCaches(string ...$identifyingValues)
    {
        $cacheKey = $this->createCacheIdFromValues($identifyingValues);
        $fromTransient = $this->transientCache->get($cacheKey);
        if ($fromTransient) {
            return $fromTransient;
        }

        $fromPersistent = $this->persistentCache->get($cacheKey);
        if ($fromPersistent) {
            $this->transientCache->set($cacheKey, $fromPersistent);
            return $fromPersistent;
        }

        return false;
    }

    /**
     * @param mixed $value
     */
    public function setInCaches($value, bool $persistent, string ...$identifyingValues): void
    {
        $cacheKey = $this->createCacheIdFromValues($identifyingValues);
        $this->transientCache->set($cacheKey, $value);
        if ($persistent) {
            $this->persistentCache->set($cacheKey, $value);
        }
    }

    public function remove(string ...$identifyingValues): void
    {
        $cacheKey = $this->createCacheIdFromValues($identifyingValues);
        $this->transientCache->remove($cacheKey);
        $this->persistentCache->remove($cacheKey);
    }

    protected function createCacheIdFromValues(array $identifyingValues): string
    {
        return 'flux-' . md5(serialize($identifyingValues));
    }
}
