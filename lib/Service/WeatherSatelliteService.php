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
use SimpleXMLElement;

/**
 * Weather Satellite Service
 *
 * Handles fetching satellite data and imagery including:
 * - HF band propagation conditions (HamQSL XML)
 * - D-RAP absorption maps (NOAA)
 * - SDO solar imagery (NASA)
 * - GOES satellite images
 */
class WeatherSatelliteService {

	private const HAMQSL_BASE    = 'http://www.hamqsl.com';
	private const NOAA_DRAP_BASE = 'https://services.swpc.noaa.gov';
	private const NASA_SDO_BASE  = 'https://sdo.gsfc.nasa.gov';
	private const GOES_BASE      = 'https://cdn.star.nesdis.noaa.gov';

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
	 * Fetch HF band propagation conditions from HamQSL XML.
	 *
	 * @return array<string, mixed>
	 */
	public function getBandConditions(): array {
		$cacheKey = CacheService::getCacheKey('band_conditions');
		$cached = $this->cacheService->getForecast($cacheKey);

		if ($cached !== null) {
			return json_decode($cached, true) ?? [];
		}

		try {
			$client = $this->clientService->newClient();
			$response = $client->get(self::HAMQSL_BASE . '/solardata/autoham.php', [
				'timeout' => 10,
			]);

			$xmlString = $response->getBody();

			try {
				$xml = new SimpleXMLElement($xmlString);
			} catch (\Exception $e) {
				$this->logger->warning('HamQSL XML parse failed, using fallback: ' . $e->getMessage());
				return $this->bandConditionsFallback();
			}

			$bands = [];
			if (isset($xml->propagation->band)) {
				foreach ($xml->propagation->band as $band) {
					$bandName = (string) $band['name'];
					$condition = (string) ($band->condition ?? $band ?? '--');

					// Handle both element and attribute based condition
					$condStr = $condition;
					if (empty($condStr)) {
						$condStr = (string) $band;
					}

					$bands[$bandName] = [
						'name'       => $bandName,
						'condition'  => $condStr,
						'efficiency' => isset($band->efficiency) ? (int) $band->efficiency : 0,
						'legend'     => isset($band->legend) ? (string) $band->legend : '',
						'muf'        => isset($band->muf) ? (float) $band->muf : 0,
					];
				}
			}

			$bandData = [
				'bands'          => $bands,
				'timestamp'      => date('c'),
				'solar_index'    => isset($xml->solar->solarindex) ? (int) $xml->solar->solarindex : 0,
				'sunspot_number' => isset($xml->solar->sunspots) ? (int) $xml->solar->sunspots : 0,
			];

			$this->cacheService->setForecast($cacheKey, json_encode($bandData));
			return $bandData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch band conditions: ' . $e->getMessage());
			return $this->bandConditionsFallback();
		}
	}

	/**
	 * Fallback when HamQSL is unreachable.
	 */
	private function bandConditionsFallback(): array {
		return [
			'bands'          => [],
			'timestamp'      => date('c'),
			'solar_index'    => 0,
			'sunspot_number' => 0,
			'error'          => true,
			'message'        => 'HamQSL propagation data unavailable',
		];
	}

	/**
	 * Fetch D-RAP absorption maps from NOAA SWPC.
	 *
	 * @return array<string, mixed>
	 */
	public function getDRAPAbsorption(): array {
		$cacheKey = CacheService::getCacheKey('drap_absorption');
		$cached = $this->cacheService->getForecast($cacheKey);

		if ($cached !== null) {
			return json_decode($cached, true) ?? [];
		}

		try {
			$drapData = [
				'current_map'   => self::NOAA_DRAP_BASE . '/images/animations/d-rap/global/latest.png',
				'forecast_map'  => self::NOAA_DRAP_BASE . '/images/animations/d-rap/global/latest.png',
				'timestamp'     => date('c'),
				'description'   => 'D-region absorption prediction maps for HF radio propagation',
			];

			$this->cacheService->setForecast($cacheKey, json_encode($drapData));
			return $drapData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to construct D-RAP data: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch D-RAP data'];
		}
	}

	/**
	 * Fetch SDO solar imagery metadata from NASA.
	 *
	 * @return array<string, mixed>
	 */
	public function getSDOImagery(): array {
		$cacheKey = CacheService::getCacheKey('sdo_imagery');
		$cached = $this->cacheService->getDaily($cacheKey);

		if ($cached !== null) {
			return json_decode($cached, true) ?? [];
		}

		try {
			// SDO imagery served via image proxy; these are metadata entries
			$sdoData = [
				'wavelengths' => [
					[
						'name'        => 'AIA 193Å',
						'description' => 'Corona — 1.2 million K plasma',
						'wavelength'  => '193Å',
						'proxy_key'   => 'sdo_193',
					],
					[
						'name'        => 'AIA 304Å',
						'description' => 'Chromosphere — 50,000 K',
						'wavelength'  => '304Å',
						'proxy_key'   => 'sdo_304',
					],
					[
						'name'        => 'AIA 171Å',
						'description' => 'Quiet corona — 600,000 K',
						'wavelength'  => '171Å',
						'proxy_key'   => 'sdo_171',
					],
					[
						'name'        => 'AIA 211Å',
						'description' => 'Active regions — 2 million K',
						'wavelength'  => '211Å',
						'proxy_key'   => 'sdo_211',
					],
					[
						'name'        => 'HMI Magnetogram',
						'description' => 'Magnetic field — photosphere',
						'wavelength'  => '6173Å',
						'proxy_key'   => 'sdo_magnetogram',
					],
				],
				'timestamp'       => date('c'),
				'update_interval' => 'Every 15 minutes',
			];

			$this->cacheService->setDaily($cacheKey, json_encode($sdoData));
			return $sdoData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to construct SDO imagery: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch SDO imagery'];
		}
	}

	/**
	 * Fetch satellite imagery metadata.
	 *
	 * @return array<string, mixed>
	 */
	public function getSatelliteImages(): array {
		$cacheKey = CacheService::getCacheKey('satellite_images');
		$cached = $this->cacheService->getDaily($cacheKey);

		if ($cached !== null) {
			return json_decode($cached, true) ?? [];
		}

		try {
			$satellites = [
				[
					'name'        => 'GOES-16 (East)',
					'description' => 'Geostationary — Eastern US / Atlantic',
					'provider'    => 'NOAA',
					'proxy_key'   => 'goes16_fd',
					'type'        => 'Full Disk GeoColor',
				],
				[
					'name'        => 'GOES-18 (West)',
					'description' => 'Geostationary — Western US / Pacific',
					'provider'    => 'NOAA',
					'proxy_key'   => 'goes18_fd',
					'type'        => 'Full Disk GeoColor',
				],
			];

			$satelliteData = [
				'satellites'       => $satellites,
				'timestamp'        => date('c'),
				'update_interval'  => 'Every 10 minutes',
			];

			$this->cacheService->setDaily($cacheKey, json_encode($satelliteData));
			return $satelliteData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to construct satellite data: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch satellite images'];
		}
	}
}