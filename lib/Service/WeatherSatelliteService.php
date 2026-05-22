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
use SimpleXMLElement;

/**
 * Weather Satellite Service
 *
 * Handles fetching satellite data and imagery including:
 * - HF band propagation conditions (HamQSL XML)
 * - D-RAP absorption maps (NOAA)
 * - SDO solar imagery (NASA)
 * - GOES/Meteor-M2 satellite images
 */
class WeatherSatelliteService {
	private const HAMQSL_BASE = 'http://www.hamqsl.com';
	private const NOAA_DRAP_BASE = 'https://services.swpc.noaa.gov';
	private const NASA_IMAGERY_BASE = 'https://api.nasa.gov';
	private const GOES_BASE = 'https://noaa-goes16.s3.amazonaws.com';

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
	 * Fetch HF band propagation conditions from HamQSL
	 * Parses XML response to extract band conditions
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
			$xml = new SimpleXMLElement($xmlString);

			$bands = [];
			foreach ($xml->propagation->band ?? [] as $band) {
				$bandName = (string)$band['name'];
				$bands[$bandName] = [
					'name' => $bandName,
					'condition' => (string)$band->condition,
					'efficiency' => (int)$band->efficiency,
					'legend' => (string)$band->legend,
					'muf' => (int)$band->muf,
				];
			}

			$bandData = [
				'bands' => $bands,
				'timestamp' => date('c'),
				'solar_index' => (int)($xml->solar->solarindex ?? 0),
				'sunspot_number' => (int)($xml->solar->sunspots ?? 0),
			];

			$this->cacheService->setForecast($cacheKey, json_encode($bandData));
			return $bandData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch band conditions: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch band conditions'];
		}
	}

	/**
	 * Fetch D-RAP absorption maps from NOAA SWPC
	 * D-region absorption prediction maps
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
			// NOAA DRAP maps are typically accessed via direct URLs
			$drapData = [
				'current_map' => self::NOAA_DRAP_BASE . '/experimental/images/drap_current_us.gif',
				'forecast_12h' => self::NOAA_DRAP_BASE . '/experimental/images/drap_forecast_12h_us.gif',
				'forecast_24h' => self::NOAA_DRAP_BASE . '/experimental/images/drap_forecast_24h_us.gif',
				'timestamp' => date('c'),
				'description' => 'D-region absorption prediction maps for HF radio propagation',
			];

			$this->cacheService->setForecast($cacheKey, json_encode($drapData));
			return $drapData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch DRAP absorption: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch DRAP data'];
		}
	}

	/**
	 * Fetch SDO (Solar Dynamics Observatory) imagery from NASA
	 * Latest solar images in multiple wavelengths
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
			// SDO provides real-time imagery via public URLs
			$sdoData = [
				'wavelengths' => [
					[
						'name' => 'AIA 94Å',
						'description' => 'Extreme ultraviolet - Hot corona',
						'url' => 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_94_512x512.jpg',
						'wavelength' => '94Å',
					],
					[
						'name' => 'AIA 193Å',
						'description' => 'Extreme ultraviolet - Corona',
						'url' => 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_193_512x512.jpg',
						'wavelength' => '193Å',
					],
					[
						'name' => 'AIA 211Å',
						'description' => 'Extreme ultraviolet - Hot corona',
						'url' => 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_211_512x512.jpg',
						'wavelength' => '211Å',
					],
					[
						'name' => 'HMI Magnetogram',
						'description' => 'Photosphere - Magnetic field',
						'url' => 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_magnetogram_512x512.jpg',
						'wavelength' => 'Visible',
					],
					[
						'name' => 'Continuum',
						'description' => 'Photosphere - White light',
						'url' => 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_512x512.jpg',
						'wavelength' => 'Visible',
					],
				],
				'timestamp' => date('c'),
				'update_interval' => '12 hours',
			];

			$this->cacheService->setDaily($cacheKey, json_encode($sdoData));
			return $sdoData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch SDO imagery: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch SDO imagery'];
		}
	}

	/**
	 * Fetch satellite imagery from multiple sources
	 * Including GOES-16, GOES-17, and Meteor-M2 placeholders
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
					'name' => 'GOES-16 (NOAA)',
					'description' => 'Geostationary Operational Environmental Satellite - Eastern Hemisphere',
					'provider' => 'NOAA',
					'image_url' => 'https://cdn.star.nesdis.noaa.gov/GOES16/ABI/SECTOR/ne/GEOCOLOR/latest.jpg',
					'type' => 'Full Disk',
				],
				[
					'name' => 'GOES-17 (NOAA)',
					'description' => 'Geostationary Operational Environmental Satellite - Western Hemisphere',
					'provider' => 'NOAA',
					'image_url' => 'https://cdn.star.nesdis.noaa.gov/GOES17/ABI/SECTOR/wh/GEOCOLOR/latest.jpg',
					'type' => 'Full Disk',
				],
				[
					'name' => 'Meteor-M2 (Roshydromet)',
					'description' => 'Russian meteorological satellite - Polar orbiting',
					'provider' => 'Roshydromet',
					'image_url' => 'https://example.com/meteor-m2-placeholder.jpg',
					'type' => 'IR',
					'note' => 'Placeholder - requires integration with Roshydromet data portal',
				],
				[
					'name' => 'Himawari-8 (NOAA)',
					'description' => 'Japanese geostationary meteorological satellite',
					'provider' => 'JMA',
					'image_url' => 'https://example.com/himawari-placeholder.jpg',
					'type' => 'Full Disk',
					'note' => 'Placeholder - requires JMA data portal integration',
				],
			];

			$satelliteData = [
				'satellites' => $satellites,
				'timestamp' => date('c'),
				'update_interval' => '30 minutes',
			];

			$this->cacheService->setDaily($cacheKey, json_encode($satelliteData));
			return $satelliteData;
		} catch (\Exception $e) {
			$this->logger->error('Failed to fetch satellite images: ' . $e->getMessage());
			return ['error' => true, 'message' => 'Failed to fetch satellite images'];
		}
	}
}
