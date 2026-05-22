<?php
/**
 * Space Weather Dashboard - Configuration Examples
 * 
 * This file demonstrates how to configure the Space Weather Dashboard
 * app through environment variables and code modifications.
 * 
 * @copyright Copyright (c) 2024 Nextcloud GmbH
 * @license AGPL-3.0-or-later
 */

/**
 * ============================================================================
 * CACHE CONFIGURATION
 * ============================================================================
 * 
 * Adjust cache TTL values by modifying CacheService.php:
 * 
 * Location: lib/Service/CacheService.php
 */

// Example: Set shorter cache for real-time data during high solar activity
$cacheConfig = [
	'real_time_ttl' => 300,   // 5 minutes for KP, solar flux, X-ray
	'forecast_ttl' => 1800,   // 30 minutes for forecasts
	'daily_ttl' => 3600,      // 60 minutes for static imagery
];

// During high solar activity, reduce TTL:
$activeCacheConfig = [
	'real_time_ttl' => 60,    // 1 minute updates during storms
	'forecast_ttl' => 300,    // 5 minutes
	'daily_ttl' => 1800,      // 30 minutes
];

/**
 * ============================================================================
 * API ENDPOINT CONFIGURATION
 * ============================================================================
 * 
 * All external API endpoints are defined in Service classes:
 * - SpaceWeatherService.php
 * - WeatherSatelliteService.php
 */

// NOAA SWPC Endpoints
$noaaEndpoints = [
	'base' => 'https://services.swpc.noaa.gov/',
	'kp_index' => 'https://services.swpc.noaa.gov/products/noaa-estimated-planetary-k-index.json',
	'solar_flux' => 'https://services.swpc.noaa.gov/products/noaa-solar-fluxes.json',
	'xray_flux' => 'https://services.swpc.noaa.gov/products/noaa-space-weather-exp-xrays-6-hour.json',
	'aurora_forecast' => 'https://services.swpc.noaa.gov/experimental/images/aurora_fc.jpg',
];

// NASA Endpoints
$nasaEndpoints = [
	'sdo_imagery_base' => 'https://sdo.gsfc.nasa.gov/assets/img/latest/',
	'earth_imagery' => 'https://api.nasa.gov/planetary/earth/imagery',
];

// HamQSL Endpoints
$hamqslEndpoints = [
	'band_conditions' => 'http://www.hamqsl.com/solardata/autoham.php',
];

// GOES Satellite Endpoints
$goesEndpoints = [
	'goes16' => 'https://cdn.star.nesdis.noaa.gov/GOES16/ABI/SECTOR/ne/GEOCOLOR/latest.jpg',
	'goes17' => 'https://cdn.star.nesdis.noaa.gov/GOES17/ABI/SECTOR/wh/GEOCOLOR/latest.jpg',
];

/**
 * ============================================================================
 * LOGGING CONFIGURATION
 * ============================================================================
 * 
 * Logging is handled through Nextcloud's LoggerInterface.
 * Configure in Nextcloud config.php
 */

// Example Nextcloud config.php entries:
$logConfig = [
	'log_type' => 'file',                    // or 'syslog'
	'logfile' => '/var/log/nextcloud.log',
	'loglevel' => 2,                         // 0=DEBUG, 1=INFO, 2=WARN, 3=ERROR, 4=FATAL
	'logtimezone' => 'UTC',
];

/**
 * ============================================================================
 * SECURITY CONFIGURATION
 * ============================================================================
 */

// CORS is handled automatically by Nextcloud
// All endpoints require valid session or API token
$securityConfig = [
	'csrf_protection' => true,          // CSRF tokens on POST endpoints
	'https_only' => true,               // External APIs use HTTPS only
	'timeout' => 10,                    // API request timeout in seconds
	'verify_ssl' => true,               // Verify SSL certificates
];

/**
 * ============================================================================
 * PERFORMANCE TUNING
 * ============================================================================
 */

// Database connection pooling (Nextcloud handles)
$perfConfig = [
	'enable_query_logging' => false,
	'enable_redis' => true,             // Use Redis for cache when available
	'max_parallel_requests' => 8,       // Max concurrent API calls
];

/**
 * ============================================================================
 * OPTIONAL: EXTENDED SATELLITE INTEGRATION
 * ============================================================================
 * 
 * To add support for additional satellites, extend WeatherSatelliteService:
 */

// Example: Adding Meteor-M2 integration
$meteorM2Config = [
	'enabled' => false,                 // Set to true after portal integration
	'base_url' => 'https://meteor.obninsk.com/',
	'image_url_pattern' => 'https://meteor.obninsk.com/archive/{date}/imgs/msu_gs_20{date}T{time}Z_1km.jpg',
	'update_interval' => 3600,          // 1 hour
	'cache_ttl' => 3600,
];

// Example: Adding Himawari-8 integration
$himawari8Config = [
	'enabled' => false,                 // Set to true after JMA portal integration
	'base_url' => 'https://himawari8.nict.go.jp/',
	'image_url_pattern' => 'https://himawari8.nict.go.jp/img/D531106/1d/550/ALBS550',
	'update_interval' => 600,           // 10 minutes
	'cache_ttl' => 600,
];

/**
 * ============================================================================
 * CUSTOM STYLING
 * ============================================================================
 * 
 * Override CSS variables in custom style.css or theme:
 */

$cssVariables = [
	'--primary-gradient' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
	'--secondary-gradient' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
	'--border-radius' => '12px',
	'--shadow-sm' => '0 2px 10px rgba(0, 0, 0, 0.1)',
	'--color-text-primary' => '#333',
	'--color-bg-light' => '#f5f5f5',
];

/**
 * ============================================================================
 * DEVELOPMENT MODE CONFIGURATION
 * ============================================================================
 * 
 * For development, enable verbose logging and error reporting
 */

if (getenv('NEXTCLOUD_DEBUG')) {
	$devConfig = [
		'debug' => true,
		'loglevel' => 0,                // DEBUG level
		'errorlog' => '/var/log/nextcloud-debug.log',
		'log_query' => true,
		'profiler' => true,
	];
	
	// Enable Vue DevTools in browser
	// Set in templates/content/index.html
}

/**
 * ============================================================================
 * MONITORING & ALERTS CONFIGURATION
 * ============================================================================
 * 
 * For future alert system implementation:
 */

$monitoringConfig = [
	'kp_index_alert_threshold' => 7,          // Alert when KP >= 7
	'xray_alert_threshold' => 'M_CLASS',      // Alert on M or X class
	'enable_email_alerts' => false,            // Not yet implemented
	'enable_push_notifications' => false,     // Not yet implemented
	'alert_cooldown' => 3600,                  // 1 hour between same alerts
];

/**
 * ============================================================================
 * NEXTCLOUD INTEGRATION CONFIGURATION
 * ============================================================================
 */

// Register app in Nextcloud config.php to enable space_weather app
$nextcloudConfig = [
	'installed_apps' => [
		'space_weather' => true,
		// ... other apps
	],
	// App is accessible at: /apps/space_weather/
];

// User permissions are automatically handled by Nextcloud
// All authenticated users can access the dashboard
// No special permissions required (configurable in info.xml if needed)

/**
 * ============================================================================
 * TESTING CONFIGURATION
 * ============================================================================
 * 
 * For development and testing, mock external APIs:
 */

if (getenv('TESTING')) {
	// Example: Mock NOAA responses for testing
	$testConfig = [
		'mock_external_apis' => true,
		'mock_response_delay' => 100,  // ms
		'mock_data_dir' => __DIR__ . '/tests/mock-data/',
	];
}

/**
 * ============================================================================
 * NOTES
 * ============================================================================
 * 
 * 1. All configuration in this file is for documentation purposes.
 *    Actual configuration should be implemented in:
 *    - lib/Service/*.php for business logic configuration
 *    - appinfo/info.xml for app-level settings
 *    - Nextcloud config.php for system-level settings
 * 
 * 2. To modify cache TTL values, edit lib/Service/CacheService.php
 * 
 * 3. To add new API endpoints, modify the appropriate Service class
 *    and register the route in appinfo/routes.php
 * 
 * 4. All external API calls use HTTP Client Service from Nextcloud
 *    for proper error handling and timeout management
 * 
 * 5. Logging uses Nextcloud's LoggerInterface for consistency
 *
 * 6. User authentication is automatically handled by Nextcloud
 *    @NoAdminRequired means any authenticated user can access
 *
 * 7. CSRF protection is automatic for POST/PUT/DELETE via AppFramework
 */
