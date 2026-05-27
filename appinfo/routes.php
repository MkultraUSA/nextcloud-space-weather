<?php
/**
 * @copyright Copyright (c) 2024 Kevin Watkins
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

return [
    'routes' => [
        // Page routes
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        // API routes
        ['name' => 'api#getKpIndex',         'url' => '/api/v1/kp-index',          'verb' => 'GET'],
        ['name' => 'api#getSolarFlux',       'url' => '/api/v1/solar-flux',        'verb' => 'GET'],
        ['name' => 'api#getXrayFlux',        'url' => '/api/v1/xray-flux',         'verb' => 'GET'],
        ['name' => 'api#getAuroraForecast',  'url' => '/api/v1/aurora-forecast',   'verb' => 'GET'],
        ['name' => 'api#getBandConditions',  'url' => '/api/v1/band-conditions',   'verb' => 'GET'],
        ['name' => 'api#getDRAPAbsorption',  'url' => '/api/v1/drap-absorption',   'verb' => 'GET'],
        ['name' => 'api#getSDOImagery',      'url' => '/api/v1/sdo-imagery',       'verb' => 'GET'],
        ['name' => 'api#getSatelliteImages', 'url' => '/api/v1/satellite-images',  'verb' => 'GET'],
        ['name' => 'api#refreshAll',         'url' => '/api/v1/refresh-all',       'verb' => 'POST'],

        // Image proxy route
        ['name' => 'image#getImage', 'url' => '/api/v1/image/{key}', 'verb' => 'GET'],
        ['name' => 'image#getEnlilFrame', 'url' => '/api/v1/image/enlil-frame/{index}', 'verb' => 'GET'],
    ],
];
