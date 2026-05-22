<?php
/**
 * @copyright Copyright (c) 2024 Kevin Watkins
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\SpaceWeather\Controller;

use OCA\SpaceWeather\Service\CacheService;
use OCA\SpaceWeather\Service\SpaceWeatherService;
use OCA\SpaceWeather\Service\WeatherSatelliteService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * API Controller
 *
 * Handles HTTP requests to fetch space weather data via REST API endpoints
 * All endpoints support manual refresh via query parameters
 */
class APIController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private SpaceWeatherService $spaceWeatherService,
		private WeatherSatelliteService $weatherSatelliteService,
		private CacheService $cacheService,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Get KP Index data
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getKpIndex(): DataResponse {
		try {
			$data = $this->spaceWeatherService->getKpIndex();
			return new DataResponse([
				'success' => true,
				'data' => $data,
				'timestamp' => date('c'),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Error in getKpIndex: ' . $e->getMessage());
			return new DataResponse([
				'success' => false,
				'error' => 'Failed to fetch KP index',
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Get Solar Flux (F10.7) data
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getSolarFlux(): DataResponse {
		try {
			$data = $this->spaceWeatherService->getSolarFlux();
			return new DataResponse([
				'success' => true,
				'data' => $data,
				'timestamp' => date('c'),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Error in getSolarFlux: ' . $e->getMessage());
			return new DataResponse([
				'success' => false,
				'error' => 'Failed to fetch solar flux',
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Get X-ray Flux data with alert levels
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getXrayFlux(): DataResponse {
		try {
			$data = $this->spaceWeatherService->getXrayFlux();
			return new DataResponse([
				'success' => true,
				'data' => $data,
				'timestamp' => date('c'),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Error in getXrayFlux: ' . $e->getMessage());
			return new DataResponse([
				'success' => false,
				'error' => 'Failed to fetch X-ray flux',
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Get Aurora Forecast
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getAuroraForecast(): DataResponse {
		try {
			$data = $this->spaceWeatherService->getAuroraForecast();
			return new DataResponse([
				'success' => true,
				'data' => $data,
				'timestamp' => date('c'),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Error in getAuroraForecast: ' . $e->getMessage());
			return new DataResponse([
				'success' => false,
				'error' => 'Failed to fetch aurora forecast',
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Get HF Band Conditions
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getBandConditions(): DataResponse {
		try {
			$data = $this->weatherSatelliteService->getBandConditions();
			return new DataResponse([
				'success' => true,
				'data' => $data,
				'timestamp' => date('c'),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Error in getBandConditions: ' . $e->getMessage());
			return new DataResponse([
				'success' => false,
				'error' => 'Failed to fetch band conditions',
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Get D-RAP Absorption Maps
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getDRAPAbsorption(): DataResponse {
		try {
			$data = $this->weatherSatelliteService->getDRAPAbsorption();
			return new DataResponse([
				'success' => true,
				'data' => $data,
				'timestamp' => date('c'),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Error in getDRAPAbsorption: ' . $e->getMessage());
			return new DataResponse([
				'success' => false,
				'error' => 'Failed to fetch D-RAP absorption',
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Get SDO Solar Imagery
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getSDOImagery(): DataResponse {
		try {
			$data = $this->weatherSatelliteService->getSDOImagery();
			return new DataResponse([
				'success' => true,
				'data' => $data,
				'timestamp' => date('c'),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Error in getSDOImagery: ' . $e->getMessage());
			return new DataResponse([
				'success' => false,
				'error' => 'Failed to fetch SDO imagery',
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Get Satellite Images
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getSatelliteImages(): DataResponse {
		try {
			$data = $this->weatherSatelliteService->getSatelliteImages();
			return new DataResponse([
				'success' => true,
				'data' => $data,
				'timestamp' => date('c'),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Error in getSatelliteImages: ' . $e->getMessage());
			return new DataResponse([
				'success' => false,
				'error' => 'Failed to fetch satellite images',
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Refresh all data and clear cache
	 * Manual refresh endpoint for user-initiated updates
	 *
	 * @return DataResponse
	 *
	 * @NoAdminRequired
	 */
	public function refreshAll(): DataResponse {
		try {
			// Clear all cached data
			$this->cacheService->clear();

			// Fetch all data fresh
			$kpIndex = $this->spaceWeatherService->getKpIndex();
			$solarFlux = $this->spaceWeatherService->getSolarFlux();
			$xrayFlux = $this->spaceWeatherService->getXrayFlux();
			$auroraForecast = $this->spaceWeatherService->getAuroraForecast();
			$bandConditions = $this->weatherSatelliteService->getBandConditions();
			$drapAbsorption = $this->weatherSatelliteService->getDRAPAbsorption();
			$sdoImagery = $this->weatherSatelliteService->getSDOImagery();
			$satelliteImages = $this->weatherSatelliteService->getSatelliteImages();

			return new DataResponse([
				'success' => true,
				'message' => 'All data refreshed successfully',
				'data' => [
					'kp_index' => $kpIndex,
					'solar_flux' => $solarFlux,
					'xray_flux' => $xrayFlux,
					'aurora_forecast' => $auroraForecast,
					'band_conditions' => $bandConditions,
					'drap_absorption' => $drapAbsorption,
					'sdo_imagery' => $sdoImagery,
					'satellite_images' => $satelliteImages,
				],
				'timestamp' => date('c'),
			]);
		} catch (\Exception $e) {
			$this->logger->error('Error in refreshAll: ' . $e->getMessage());
			return new DataResponse([
				'success' => false,
				'error' => 'Failed to refresh data',
				'message' => $e->getMessage(),
			], 500);
		}
	}
}
