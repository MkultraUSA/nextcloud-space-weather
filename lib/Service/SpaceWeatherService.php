<?php
/**
 * @copyright Copyright (c) 2024 Nextcloud GmbH
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\SpaceWeather\Service;

use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Space Weather Service
 *
 * Handles fetching and parsing space weather data from:
 * - NOAA Space Weather Prediction Center
 * - NASA APIs
 * - HamQSL propagation data
 */
class SpaceWeatherService {
	private const NOAA_SWPC_BASE = 'https://services.swpc.noaa.gov/';
	private const NOAA_ALERTS = 'https://api.weather.gov/alerts/active';
	private const NASA_IMAGERY_BASE = 'https://api.nasa.gov';
	private const HAMQSL_BASE = 'http://www.hamqsl.com';

	private IClientService $clientService;
	private CacheService $cacheService;
	private LoggerInterface $logger;

	public function __construct(
		IClientService $clientService,
		CacheService $cacheService,
		LoggerInterface $logger,
	) {
		$this->clientService = $clientService;
		$this->cacheService = $cacheService;
		$this->logger = $logger;
	}

	/**
	 * Fetch KP Index from NOAA SWPC
	 * Real-time geomagnetic activity index
	 *
	 * @return array<string, mixed>
	 * @throws RuntimeException
	 */
	public function getKpIndex(): array {
		$cacheKey = CacheService::getCacheKey('kp_index');
		$cached = $this->cacheService->getRealTime($cacheKey);

		if ($cached !== null) {
			return json_decode($cached, true) ?? [];
		}

		try {
			$client = $this->clientService->newClient();
			$response = $client->get(self::NOAA_SWPC_BASE . 'products/noaa-estimated-planetary-k-index.json');
			$data = json_decode($response->getBody(), true);

			if (!is_array($data) || empty($data)) {
				throw new RuntimeException('Invalid KP index data received');
			}

			// Extract latest KP reading
			$latest = end($data);
			$kpData = [
				'kp' => (float)($latest['estimated_kp'] ?? 0),
				'timestamp' => $latest['time_tag'] ?? date('c'),
				'status' => $this->getKpStatus((float)($latest['estimated_kp'] ?? 0)),
				'raw' => $data,
			];

			$this->cacheService->setRealTime($cacheKey, json_encode($kpData));
			return $kpData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch KP index: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch KP index'];
		}
	}

	/**
	 * Fetch Solar Flux (F10.7) from NOAA SWPC
	 *
	 * @return array<string, mixed>
	 * @throws RuntimeException
	 */
	public function getSolarFlux(): array {
		$cacheKey = CacheService::getCacheKey('solar_flux');
		$cached = $this->cacheService->getRealTime($cacheKey);

		if ($cached !== null) {
			return json_decode($cached, true) ?? [];
		}

		try {
			$client = $this->clientService->newClient();
			$response = $client->get(self::NOAA_SWPC_BASE . 'products/noaa-solar-fluxes.json');
			$data = json_decode($response->getBody(), true);

			if (!is_array($data) || empty($data)) {
				throw new RuntimeException('Invalid solar flux data received');
			}

			// Extract F10.7 values
			$fluxData = [
				'current' => (float)($data[0]['f107'] ?? 0),
				'timestamp' => $data[0]['time_tag'] ?? date('c'),
				'raw' => $data,
			];

			$this->cacheService->setRealTime($cacheKey, json_encode($fluxData));
			return $fluxData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch solar flux: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch solar flux'];
		}
	}

	/**
	 * Fetch X-ray Flux from NOAA SWPC
	 * Monitor solar flares with alert levels
	 *
	 * @return array<string, mixed>
	 */
	public function getXrayFlux(): array {
		$cacheKey = CacheService::getCacheKey('xray_flux');
		$cached = $this->cacheService->getRealTime($cacheKey);

		if ($cached !== null) {
			return json_decode($cached, true) ?? [];
		}

		try {
			$client = $this->clientService->newClient();
			$response = $client->get(self::NOAA_SWPC_BASE . 'products/noaa-space-weather-exp-xrays-6-hour.json');
			$data = json_decode($response->getBody(), true);

			if (!is_array($data) || empty($data)) {
				throw new RuntimeException('Invalid X-ray flux data received');
			}

			// Extract latest readings and determine alert level
			$latest = end($data);
			$xrayData = [
				'short' => (float)($latest['A_lg_short'] ?? 0),
				'long' => (float)($latest['A_lg_long'] ?? 0),
				'timestamp' => $latest['time_tag'] ?? date('c'),
				'alert_level' => $this->getXrayAlertLevel((float)($latest['A_lg_long'] ?? 0)),
				'raw' => array_slice($data, -24), // Last 24 hours
			];

			$this->cacheService->setRealTime($cacheKey, json_encode($xrayData));
			return $xrayData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch X-ray flux: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch X-ray flux'];
		}
	}

	/**
	 * Fetch Aurora Forecast Image from NOAA SWPC
	 *
	 * @return array<string, mixed>
	 */
	public function getAuroraForecast(): array {
		$cacheKey = CacheService::getCacheKey('aurora_forecast');
		$cached = $this->cacheService->getForecast($cacheKey);

		if ($cached !== null) {
			return json_decode($cached, true) ?? [];
		}

		try {
			// NOAA provides aurora forecast images via direct URL
			$auroraData = [
				'image_url' => self::NOAA_SWPC_BASE . 'experimental/images/aurora_fc.jpg',
				'image_3hour_url' => self::NOAA_SWPC_BASE . 'experimental/images/aurora_fc_120min.jpg',
				'timestamp' => date('c'),
				'description' => 'Aurora forecast for next 30 minutes to 2 hours',
			];

			$this->cacheService->setForecast($cacheKey, json_encode($auroraData));
			return $auroraData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch aurora forecast: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch aurora forecast'];
		}
	}

	/**
	 * Get KP Index status/interpretation
	 */
	private function getKpStatus(float $kp): string {
		if ($kp < 2) return 'quiet';
		if ($kp < 4) return 'unsettled';
		if ($kp < 6) return 'active';
		if ($kp < 8) return 'minor_storm';
		if ($kp < 9) return 'major_storm';
		return 'severe_storm';
	}

	/**
	 * Get X-ray Alert Level
	 */
	private function getXrayAlertLevel(float $flux): string {
		if ($flux < 1e-6) return 'normal';
		if ($flux < 1e-5) return 'a_class';
		if ($flux < 1e-4) return 'b_class';
		if ($flux < 1e-3) return 'c_class';
		if ($flux < 1e-2) return 'm_class';
		return 'x_class';
	}
}
