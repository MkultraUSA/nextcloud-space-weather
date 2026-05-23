<?php
/**
 * @copyright Copyright (c) 2024 Kevin Watkins
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\SpaceWeather\Controller;

use OCA\SpaceWeather\Service\SpaceWeatherService;
use OCA\SpaceWeather\Service\WeatherSatelliteService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class PageController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private SpaceWeatherService $weatherService,
		private WeatherSatelliteService $satelliteService,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index(): TemplateResponse {
		$data = [];

		// KP Index
		try {
			$kp = $this->weatherService->getKpIndex();
			$data['kpIndex'] = $kp['kp'] ?? 0;
			$data['kpStatus'] = $kp['status'] ?? 'unknown';
			$data['kpTimestamp'] = $kp['timestamp'] ?? '';
			$data['kpHistory'] = $kp['raw'] ?? [];
		} catch (\Exception $e) {
			$this->logger->error('KP Index failed: ' . $e->getMessage());
			$data['kpIndex'] = 0;
			$data['kpStatus'] = 'error';
		}

		// Solar Flux
		try {
			$flux = $this->weatherService->getSolarFlux();
			$data['solarFlux'] = $flux['current'] ?? 0;
			$data['fluxStatus'] = $flux['status'] ?? 'low';
			$data['fluxTimestamp'] = $flux['timestamp'] ?? '';
		} catch (\Exception $e) {
			$this->logger->error('Solar flux failed: ' . $e->getMessage());
			$data['solarFlux'] = 0;
			$data['fluxStatus'] = 'error';
		}

		// X-Ray Flux
		try {
			$xray = $this->weatherService->getXrayFlux();
			$data['xrayClass'] = $xray['class'] ?? '--';
			$data['xrayAlert'] = $xray['alert_level'] ?? 'quiet';
			$data['xrayIntensity'] = $xray['intensity'] ?? 0;
			$data['xrayTimestamp'] = $xray['timestamp'] ?? '';
		} catch (\Exception $e) {
			$this->logger->error('X-ray flux failed: ' . $e->getMessage());
			$data['xrayClass'] = '--';
			$data['xrayAlert'] = 'error';
		}

		// Aurora Forecast
		try {
			$aurora = $this->weatherService->getAuroraForecast();
			$data['auroraUrl'] = $aurora['image_url'] ?? '';
		} catch (\Exception $e) {
			$this->logger->error('Aurora forecast failed: ' . $e->getMessage());
		}

		// Band Conditions
		try {
			$bands = $this->satelliteService->getBandConditions();
			$data['bandConditions'] = $bands;
		} catch (\Exception $e) {
			$this->logger->error('Band conditions failed: ' . $e->getMessage());
		}

		$data['lastUpdate'] = date('H:i');

		return new TemplateResponse(
			$this->appName,
			'content/index',
			$data,
			'blank'
		);
	}
}