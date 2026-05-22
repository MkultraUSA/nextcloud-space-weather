<?php
/**
 * @copyright Copyright (c) 2024 Kevin Watkins
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
 * Fetches and parses real-time space weather data from NOAA SWPC.
 */
class SpaceWeatherService {

	private const NOAA_BASE = 'https://services.swpc.noaa.gov/';

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
	 * Fetch KP Index from NOAA SWPC (planetary K-index, NOT estimated).
	 *
	 * @return array<string, mixed>
	 */
	public function getKpIndex(): array {
		$cacheKey = CacheService::getCacheKey('kp_index');
		$cached = $this->cacheService->getRealTime($cacheKey);

		if ($cached !== null) {
			return json_decode($cached, true) ?? [];
		}

		try {
			$client = $this->clientService->newClient();
			$response = $client->get(self::NOAA_BASE . 'products/noaa-planetary-k-index.json');
			$data = json_decode($response->getBody(), true);

			if (!is_array($data) || empty($data)) {
				throw new RuntimeException('Invalid KP index data received');
			}

			// The last entry is the most recent observation
			$latest = end($data);
			$kpValue = (float) ($latest['Kp'] ?? 0);

			$kpData = [
				'kp'        => $kpValue,
				'timestamp' => $latest['time_tag'] ?? date('c'),
				'status'    => $this->getKpStatus($kpValue),
				'a_running' => (int) ($latest['a_running'] ?? 0),
				'stations'  => (int) ($latest['station_count'] ?? 0),
				'raw'       => array_slice($data, -12), // last 12 readings
			];

			$this->cacheService->setRealTime($cacheKey, json_encode($kpData));
			return $kpData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch KP index: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch KP index'];
		}
	}

	/**
	 * Fetch Solar Flux (F10.7) from NOAA SWPC.
	 *
	 * @return array<string, mixed>
	 */
	public function getSolarFlux(): array {
		$cacheKey = CacheService::getCacheKey('solar_flux');
		$cached = $this->cacheService->getRealTime($cacheKey);

		if ($cached !== null) {
			return json_decode($cached, true) ?? [];
		}

		try {
			$client = $this->clientService->newClient();
			$response = $client->get(self::NOAA_BASE . 'products/10cm-flux-30-day.json');
			$data = json_decode($response->getBody(), true);

			if (!is_array($data) || empty($data)) {
				throw new RuntimeException('Invalid solar flux data received');
			}

			$latest = end($data);
			$fluxValue = (float) ($latest['flux'] ?? 0);

			$fluxData = [
				'current'    => $fluxValue,
				'timestamp'  => $latest['time_tag'] ?? date('c'),
				'status'     => $fluxValue > 180 ? 'high' : ($fluxValue > 120 ? 'moderate' : 'low'),
				'raw'        => array_slice($data, -7), // last 7 days
			];

			$this->cacheService->setRealTime($cacheKey, json_encode($fluxData));
			return $fluxData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch solar flux: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch solar flux'];
		}
	}

	/**
	 * Fetch X-ray Flux / Solar Flare data from NOAA SWPC.
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
			$response = $client->get(self::NOAA_BASE . 'json/goes/primary/xray-flares-latest.json');
			$data = json_decode($response->getBody(), true);

			if (!is_array($data) || empty($data)) {
				throw new RuntimeException('Invalid X-ray flux data received');
			}

			$latest = $data[0]; // first entry is the most recent flare/event
			$currentClass = $latest['current_class'] ?? '--';
			$currentIntensity = (float) ($latest['current_int_xrlong'] ?? 0);

			$xrayData = [
				'class'       => $currentClass,
				'intensity'   => $currentIntensity,
				'alert_level' => $this->getXrayAlertLevel($currentIntensity),
				'max_class'   => $latest['max_class'] ?? null,
				'max_time'    => $latest['max_time'] ?? null,
				'begin_time'  => $latest['begin_time'] ?? null,
				'timestamp'   => $latest['time_tag'] ?? date('c'),
				'raw'         => array_slice($data, 0, 5), // last 5 events
			];

			$this->cacheService->setRealTime($cacheKey, json_encode($xrayData));
			return $xrayData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch X-ray flux: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch X-ray flux'];
		}
	}

	/**
	 * Fetch Aurora Forecast image metadata from NOAA SWPC.
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
			$auroraData = [
				'image_url'        => self::NOAA_BASE . 'images/animations/ovation/north/latest.jpg',
				'image_south_url'  => self::NOAA_BASE . 'images/animations/ovation/south/latest.jpg',
				'timestamp'        => date('c'),
				'description'      => 'Aurora forecast (30-minute prediction) — NOAA OVATION model',
			];

			$this->cacheService->setForecast($cacheKey, json_encode($auroraData));
			return $auroraData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to construct aurora forecast: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch aurora forecast'];
		}
	}

	/**
	 * Get human-readable KP status.
	 */
	private function getKpStatus(float $kp): string {
		if ($kp < 2) {
			return 'quiet';
		}
		if ($kp < 4) {
			return 'unsettled';
		}
		if ($kp < 6) {
			return 'active';
		}
		if ($kp < 7) {
			return 'minor_storm';
		}
		if ($kp < 8) {
			return 'major_storm';
		}
		return 'severe_storm';
	}

	/**
	 * Get X-ray alert level from flux intensity.
	 */
	private function getXrayAlertLevel(float $flux): string {
		if ($flux < 1e-8) {
			return 'quiet';
		}
		if ($flux < 1e-7) {
			return 'a_class';
		}
		if ($flux < 1e-6) {
			return 'b_class';
		}
		if ($flux < 1e-5) {
			return 'c_class';
		}
		if ($flux < 1e-4) {
			return 'm_class';
		}
		return 'x_class';
	}
}