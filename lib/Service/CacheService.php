<?php
/**
 * @copyright Copyright (c) 2024 Nextcloud GmbH
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\SpaceWeather\Service;

use Nextcloud\Log\Log;
use OCP\ICache;
use OCP\ICacheFactory;

/**
 * Cache Service wrapper for Nextcloud ICache
 *
 * Provides TTL-based caching with different intervals for:
 * - Real-time data (5 minutes)
 * - Forecast data (30 minutes)
 * - Historical/daily data (60 minutes)
 */
class CacheService {
	private const CACHE_REAL_TIME_TTL = 300;      // 5 minutes
	private const CACHE_FORECAST_TTL = 1800;      // 30 minutes
	private const CACHE_DAILY_TTL = 3600;         // 60 minutes

	private ICache $cache;

	public function __construct(ICacheFactory $cacheFactory) {
		$this->cache = $cacheFactory->createLocal();
	}

	/**
	 * Get real-time cached data (5 minute TTL)
	 */
	public function getRealTime(string $key): ?string {
		return $this->cache->get($key);
	}

	/**
	 * Set real-time cached data (5 minute TTL)
	 */
	public function setRealTime(string $key, string $value): void {
		$this->cache->set($key, $value, self::CACHE_REAL_TIME_TTL);
	}

	/**
	 * Get forecast cached data (30 minute TTL)
	 */
	public function getForecast(string $key): ?string {
		return $this->cache->get($key);
	}

	/**
	 * Set forecast cached data (30 minute TTL)
	 */
	public function setForecast(string $key, string $value): void {
		$this->cache->set($key, $value, self::CACHE_FORECAST_TTL);
	}

	/**
	 * Get daily cached data (60 minute TTL)
	 */
	public function getDaily(string $key): ?string {
		return $this->cache->get($key);
	}

	/**
	 * Set daily cached data (60 minute TTL)
	 */
	public function setDaily(string $key, string $value): void {
		$this->cache->set($key, $value, self::CACHE_DAILY_TTL);
	}

	/**
	 * Remove cached data
	 */
	public function remove(string $key): void {
		$this->cache->remove($key);
	}

	/**
	 * Clear all app cache
	 */
	public function clear(): void {
		$this->cache->clear();
	}

	/**
	 * Get cache key prefix for the app
	 */
	public static function getCacheKey(string $type, string $id = ''): string {
		$base = 'space_weather_' . $type;
		return $id ? $base . '_' . $id : $base;
	}
}
