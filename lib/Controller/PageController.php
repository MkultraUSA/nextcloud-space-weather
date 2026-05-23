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
    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        $data = [];
        $data['hasError'] = false; // Global error flag if needed

        // KP Index
        try {
            $kp = $this->weatherService->getKpIndex();
            $data['kpIndex'] = $kp['kp'] ?? 0;
            $data['kpStatus'] = $kp['status'] ?? 'unknown';
            $data['kpTimestamp'] = $kp['timestamp'] ?? '';
            $data['kpHistory'] = $kp['raw'] ?? [];
            $data['kpError'] = false;
        } catch (\Exception $e) {
            $this->logger->error('KP Index failed: ' . $e->getMessage());
            $data['kpIndex'] = 0;
            $data['kpStatus'] = 'error';
            $data['kpTimestamp'] = '';
            $data['kpHistory'] = [];
            $data['kpError'] = true;
            $data['hasError'] = true;
        }

        // Solar Flux
        try {
            $flux = $this->weatherService->getSolarFlux();
            $data['solarFlux'] = $flux['current'] ?? 0;
            $data['fluxStatus'] = $flux['status'] ?? 'low';
            $data['fluxTimestamp'] = $flux['timestamp'] ?? '';
            $data['fluxError'] = false;
        } catch (\Exception $e) {
            $this->logger->error('Solar flux failed: ' . $e->getMessage());
            $data['solarFlux'] = 0;
            $data['fluxStatus'] = 'error';
            $data['fluxTimestamp'] = '';
            $data['fluxError'] = true;
            $data['hasError'] = true;
        }

        // X-Ray Flux
        try {
            $xray = $this->weatherService->getXrayFlux();
            $data['xrayClass'] = $xray['class'] ?? '--';
            $data['xrayAlert'] = $xray['alert_level'] ?? 'quiet';
            $data['xrayIntensity'] = $xray['intensity'] ?? 0;
            $data['xrayTimestamp'] = $xray['timestamp'] ?? '';
            $data['xrayError'] = false;
        } catch (\Exception $e) {
            $this->logger->error('X-ray flux failed: ' . $e->getMessage());
            $data['xrayClass'] = '--';
            $data['xrayAlert'] = 'error';
            $data['xrayIntensity'] = 0;
            $data['xrayTimestamp'] = '';
            $data['xrayError'] = true;
            $data['hasError'] = true;
        }

        // Aurora Forecast
        try {
            $aurora = $this->weatherService->getAuroraForecast();
            $data['auroraUrl'] = $aurora['image_url'] ?? '';
            $data['auroraError'] = false;
        } catch (\Exception $e) {
            $this->logger->error('Aurora forecast failed: ' . $e->getMessage());
            $data['auroraUrl'] = '';
            $data['auroraError'] = true;
            $data['hasError'] = true;
        }

        // Band Conditions
        try {
            $bands = $this->satelliteService->getBandConditions();
            $data['bandConditions'] = $bands;
            $data['bandError'] = false;
            // Check if the service returned an error
            if (!empty($bands) && isset($bands['error'])) {
                $data['bandError'] = true;
                $data['hasError'] = true;
            }
        } catch (\Exception $e) {
            $this->logger->error('Band conditions failed: ' . $e->getMessage());
            $data['bandConditions'] = [];
            $data['bandError'] = true;
            $data['hasError'] = true;
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