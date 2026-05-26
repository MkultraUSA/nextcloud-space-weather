<?php
declare(strict_types=1);

namespace OCA\SpaceWeather\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use Psr\Log\LoggerInterface;

/**
 * Controller for admin settings page
 */
class SettingsController extends Controller {
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->logger = $logger;
    }

    /**
     * Admin settings page
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        $data = [];

        // Data sources information
        $data['data_sources'] = [
            [
                'name' => 'NOAA Space Weather Prediction Center (SWPC)',
                'url' => 'https://www.swpc.noaa.gov/',
                'description' => 'Provides real-time monitoring and forecasting of solar and geophysical events.',
                'endpoints' => [
                    'KP Index' => '/api/v1/kp-index',
                    'Solar Flux' => '/api/v1/solar-flux',
                    'X-Ray Flux' => '/api/v1/xray-flux',
                    'Aurora Forecast' => '/api/v1/aurora-forecast',
                    'D-RAP Absorption' => '/api/v1/drap-absorption'
                ]
            ],
            [
                'name' => 'NASA Solar Dynamics Observatory (SDO)',
                'url' => 'https://sdo.gsfc.nasa.gov/',
                'description' => 'Provides continuous solar observations in multiple wavelengths.',
                'endpoints' => [
                    'SDO Imagery' => '/api/v1/sdo-imagery'
                ]
            ],
            [
                'name' => 'HamQSL.com',
                'url' => 'https://www.hamqsl.com/solarxml.php',
                'description' => 'Provides HF band propagation conditions.',
                'endpoints' => [
                    'Band Conditions' => '/api/v1/band-conditions'
                ]
            ],
            [
                'name' => 'GOES Weather Satellite',
                'url' => 'https://www.star.nesdis.noaa.gov/GOES/',
                'description' => 'Provides weather satellite imagery.',
                'endpoints' => [
                    'Satellite Images' => '/api/v1/satellite-images'
                ]
            ]
        ];

        // App information
        $data['app_info'] = [
            'name' => 'Space Weather Dashboard',
            'version' => '1.0.0',
            'author' => 'Kevin Watkins',
            'author_email' => 'k.watkins@me.com',
            'description' => 'Real-time space weather monitoring with NOAA, NASA, and HamQSL data',
            'website' => 'https://github.com/MkultraUSA/nextcloud-space-weather',
            'bugs' => 'https://github.com/MkultraUSA/nextcloud-space-weather/issues',
            'license' => 'AGPL-3.0-or-later'
        ];

        return new TemplateResponse(
            $this->appName,
            'settings/index',
            $data
        );
    }

    /**
     * Handle feature request form submission
     * @NoAdminRequired
     * @CSRFCheckRequired
     */
    public function submitFeatureRequest(): DataResponse {
        // In a real implementation, this would save to database or send email
        // For now, we'll just log and return success
        $this->logger->info('Feature request submitted via Space Weather app settings');

        return new DataResponse([
            'status' => 'success',
            'message' => 'Thank you for your feature request! It has been logged for review.'
        ]);
    }
}