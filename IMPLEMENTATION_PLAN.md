# Space Weather Dashboard for Nextcloud Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Build a production-ready Nextcloud 33+ app that displays real-time space weather data from NOAA, NASA, and HamQSL with proper architecture, error handling, caching, and UI.

**Architecture:** 
- Backend: Nextcloud PHP 8.0+ with IClientService for HTTP, CacheService for TTL-based caching
- API: REST endpoints returning JSON with real NOAA/NASA endpoints (verified live)
- Frontend: Server-side rendered PHP templates (avoids CSP restrictions) with vanilla JavaScript
- Data flow: NOAA/NASA → PHP Service → Cache → API Controller → Template variables

**Tech Stack:**
- Nextcloud 33+, PHP 8.0+
- NOAA SWPC (KP Index, Solar Flux, X-Ray Flux, Aurora Forecast)
- HamQSL (HF Band Conditions XML)
- NASA SDO Imagery
- Vanilla JavaScript (no build tools, no CSP violations)

---

## PHASE 1: Framework & Structure (Foundation)

**Goal:** Fix critical framework issues that prevent app loading on NC33+.

**Deliverable:** App bootstrap working, routes resolving, namespace correct.

### Task 1: Fix info.xml namespace (NC33+ compatibility)

**Objective:** Remove OCA\ prefix from namespace tag to prevent double-namespace error.

**Files:**
- Modify: `appinfo/info.xml:26`

**Step 1: Read current namespace**

```bash
grep -n "namespace" /tmp/nextcloud-space-weather/appinfo/info.xml
```

Expected: `26:	<namespace>OCA\\SpaceWeather</namespace>`

**Step 2: Fix the namespace**

Change line 26 from:
```xml
<namespace>OCA\SpaceWeather</namespace>
```

To:
```xml
<namespace>SpaceWeather</namespace>
```

**Step 3: Verify the fix**

```bash
grep "namespace" /tmp/nextcloud-space-weather/appinfo/info.xml
```

Expected: `<namespace>SpaceWeather</namespace>`

**Step 4: Update NC version support in info.xml**

Current max-version is 29. Update to support NC33+:

Find the `<nextcloud>` tag around line 40 and change:
```xml
<nextcloud min-version="27" max-version="29"/>
```

To:
```xml
<nextcloud min-version="33" max-version="35"/>
```

**Step 5: Commit**

```bash
cd /tmp/nextcloud-space-weather
git add appinfo/info.xml
git commit -m "fix: update namespace and NC33+ compatibility in info.xml"
```

---

### Task 2: Create Application.php bootstrap class for NC33+

**Objective:** Implement IBootstrap interface required by NC33 for proper dependency injection.

**Files:**
- Create: `lib/AppInfo/Application.php`

**Step 1: Create the Application class**

Create file `/tmp/nextcloud-space-weather/lib/AppInfo/Application.php`:

```php
<?php
/**
 * @copyright Copyright (c) 2024 Nextcloud GmbH
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\SpaceWeather\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Application Bootstrap
 *
 * Handles dependency injection and app lifecycle for Nextcloud 33+
 */
class Application extends App implements IBootstrap {
	public const APP_ID = 'space_weather';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	/**
	 * Register services and event listeners
	 *
	 * @param IRegistrationContext $context
	 */
	public function register(IRegistrationContext $context): void {
		// Services auto-register via PSR-11 container in lib/
	}

	/**
	 * Boot the app after Nextcloud has fully loaded
	 *
	 * @param IBootContext $context
	 */
	public function boot(IBootContext $context): void {
		// App initialization after Nextcloud fully loads
	}
}
```

**Step 2: Verify the class exists and has correct namespace**

```bash
head -20 /tmp/nextcloud-space-weather/lib/AppInfo/Application.php
```

Expected: Should show namespace `OCA\SpaceWeather\AppInfo` and class `Application implements IBootstrap`

**Step 3: Commit**

```bash
cd /tmp/nextcloud-space-weather
git add lib/AppInfo/Application.php
git commit -m "feat: add Application.php bootstrap class for NC33+ dependency injection"
```

---

### Task 3: Remove legacy appinfo/app.php if it exists

**Objective:** NC33+ uses auto-discovery; legacy app.php causes loading issues.

**Files:**
- Delete: `appinfo/app.php` (if exists)

**Step 1: Check if app.php exists**

```bash
ls -la /tmp/nextcloud-space-weather/appinfo/app.php
```

**Step 2: If it exists, remove it**

If file exists:
```bash
cd /tmp/nextcloud-space-weather
rm appinfo/app.php
git add -u appinfo/
git commit -m "fix: remove legacy appinfo/app.php (NC33+ auto-discovery)"
```

If file doesn't exist, skip this task.

---

## PHASE 2: Data Layer & Endpoints (Live API Integration)

**Goal:** Fix all NOAA/NASA endpoint URLs and field names. Verify endpoints return real data. Implement all data fetching services.

**Deliverable:** All 8 API endpoints working with verified NOAA/NASA field names and real caching.

### Task 4: Verify and fix SpaceWeatherService KP Index endpoint

**Objective:** Update KP Index fetcher to use correct endpoint and field name (`Kp` not `estimated_kp`).

**Files:**
- Modify: `lib/Service/SpaceWeatherService.php:50-82`

**Step 1: Verify current NOAA KP endpoint returns what we expect**

```bash
curl -s 'https://services.swpc.noaa.gov/products/noaa-planetary-k-index.json' | python3 -c "import json, sys; data = json.load(sys.stdin); print('Last record keys:', list(data[-1].keys()) if data else 'empty'); print('Sample:', json.dumps(data[-1] if data else {}, indent=2))"
```

Expected output shows `Kp` field (capital K, lowercase p).

**Step 2: Read current implementation**

```bash
sed -n '50,82p' /tmp/nextcloud-space-weather/lib/Service/SpaceWeatherService.php
```

**Step 3: Identify the issues in current code**

Current code has:
- Wrong endpoint: `noaa-estimated-planetary-k-index.json`
- Wrong field: `estimated_kp` (should be `Kp`)

**Step 4: Replace the getKpIndex method**

In file `lib/Service/SpaceWeatherService.php`, replace lines 50-82 (the entire `getKpIndex()` method) with:

```php
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
		$response = $client->get(self::NOAA_SWPC_BASE . 'products/noaa-planetary-k-index.json', ['timeout' => 15]);
		$data = json_decode($response->getBody(), true);

		if (!is_array($data) || empty($data)) {
			throw new RuntimeException('Invalid KP index data received');
		}

		// Extract latest KP reading
		$latest = end($data);
		$kpData = [
			'kp' => (float)($latest['Kp'] ?? 0),  // Note: capital K, lowercase p
			'timestamp' => $latest['time_tag'] ?? date('c'),
			'status' => $this->getKpStatus((float)($latest['Kp'] ?? 0)),
			'raw' => $data,
		];

		$this->cacheService->setRealTime($cacheKey, json_encode($kpData));
		return $kpData;
	} catch (\Exception $e) {
		$this->logger->error('Failed to fetch KP index: ' . $e->getMessage());
		return ['error' => true, 'message' => 'Failed to fetch KP index'];
	}
}
```

**Step 5: Verify the fix by checking the method**

```bash
sed -n '50,85p' /tmp/nextcloud-space-weather/lib/Service/SpaceWeatherService.php | grep -E "Kp|noaa-planetary"
```

Expected: Should show `noaa-planetary-k-index.json` and field `Kp` (capital K, lowercase p)

**Step 6: Commit**

```bash
cd /tmp/nextcloud-space-weather
git add lib/Service/SpaceWeatherService.php
git commit -m "fix: correct KP index endpoint and field name (Kp not estimated_kp)"
```

---

### Task 5: Fix SpaceWeatherService Solar Flux endpoint

**Objective:** Update Solar Flux (F10.7) fetcher to use correct endpoint and field.

**Files:**
- Modify: `lib/Service/SpaceWeatherService.php:90-120`

**Step 1: Verify NOAA Solar Flux endpoint**

```bash
curl -s 'https://services.swpc.noaa.gov/products/10cm-flux-30-day.json' | python3 -c "import json, sys; data = json.load(sys.stdin); print('Last 2 records:'); print(json.dumps(data[-2:] if data else {}, indent=2))"
```

Expected: Shows records with `time_tag` and `flux` (lowercase) fields.

**Step 2: Read current Solar Flux implementation**

```bash
sed -n '90,120p' /tmp/nextcloud-space-weather/lib/Service/SpaceWeatherService.php
```

**Step 3: Replace the getSolarFlux method**

Find and replace the `getSolarFlux()` method in `lib/Service/SpaceWeatherService.php`:

```php
/**
 * Fetch Solar Flux (F10.7) from NOAA SWPC
 * 10cm solar radio flux in solar flux units (sfu)
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
		$response = $client->get(self::NOAA_SWPC_BASE . 'products/10cm-flux-30-day.json', ['timeout' => 15]);
		$data = json_decode($response->getBody(), true);

		if (!is_array($data) || empty($data)) {
			throw new RuntimeException('Invalid solar flux data received');
		}

		// Extract latest and calculate average
		$latest = end($data);
		$values = array_column($data, 'flux');
		$avg = array_sum($values) / count($values);

		$fluxData = [
			'current' => (float)($latest['flux'] ?? 0),
			'average_30day' => round($avg, 1),
			'timestamp' => $latest['time_tag'] ?? date('c'),
			'raw' => $data,
		];

		$this->cacheService->setRealTime($cacheKey, json_encode($fluxData));
		return $fluxData;
	} catch (\Exception $e) {
		$this->logger->error('Failed to fetch solar flux: ' . $e->getMessage());
		return ['error' => true, 'message' => 'Failed to fetch solar flux'];
	}
}
```

**Step 4: Verify the endpoint URL**

```bash
grep "10cm-flux" /tmp/nextcloud-space-weather/lib/Service/SpaceWeatherService.php
```

Expected: Should show `products/10cm-flux-30-day.json`

**Step 5: Commit**

```bash
cd /tmp/nextcloud-space-weather
git add lib/Service/SpaceWeatherService.php
git commit -m "fix: correct solar flux endpoint and add 30-day average calculation"
```

---

### Task 6: Add remaining data fetching methods to SpaceWeatherService

**Objective:** Implement getXrayFlux(), getAuroraForecast(), and getBandConditions() methods.

**Files:**
- Modify: `lib/Service/SpaceWeatherService.php` (add after getSolarFlux)

**Step 1: Add getXrayFlux() method**

Add this method after `getSolarFlux()`:

```php
/**
 * Fetch X-Ray Flux from NOAA SWPC
 * Solar X-ray monitoring with alert levels
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
		$response = $client->get(self::NOAA_SWPC_BASE . 'json/goes/primary/xrays-1-day.json', ['timeout' => 15]);
		$data = json_decode($response->getBody(), true);

		if (!isset($data['xrays']) || !is_array($data['xrays'])) {
			throw new RuntimeException('Invalid X-ray flux data');
		}

		$xrays = $data['xrays'];
		$latest = end($xrays);

		// Alert level mapping
		$alertMap = ['a' => 'A (Minor)', 'b' => 'B (Moderate)', 'c' => 'C (Strong)', 'm' => 'M (Severe)', 'x' => 'X (Extreme)'];
		$currentLevel = strtolower($latest['alert_type'] ?? 'a');

		$xrayData = [
			'short' => (float)($latest['short_wavelength'] ?? 0),
			'long' => (float)($latest['long_wavelength'] ?? 0),
			'alert_level' => $alertMap[$currentLevel] ?? 'A (Minor)',
			'timestamp' => $latest['time_tag'] ?? date('c'),
			'raw' => $xrays,
		];

		$this->cacheService->setRealTime($cacheKey, json_encode($xrayData));
		return $xrayData;
	} catch (\Exception $e) {
		$this->logger->error('Failed to fetch X-ray flux: ' . $e->getMessage());
		return ['error' => true, 'message' => 'Failed to fetch X-ray data'];
	}
}
```

**Step 2: Add getAuroraForecast() method**

```php
/**
 * Fetch Aurora Forecast image URLs from NOAA SWPC
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
		// Aurora forecast image URLs from NOAA
		$auroraData = [
			'image_url' => self::NOAA_SWPC_BASE . 'images/animations/ovation/north/latest.jpg',
			'image_3hour_url' => self::NOAA_SWPC_BASE . 'images/animations/aurora/3day.jpg',
			'timestamp' => date('c'),
		];

		$this->cacheService->setForecast($cacheKey, json_encode($auroraData));
		return $auroraData;
	} catch (\Exception $e) {
		$this->logger->error('Failed to prepare aurora forecast: ' . $e->getMessage());
		return ['error' => true, 'message' => 'Failed to fetch aurora data'];
	}
}
```

**Step 3: Add getBandConditions() method (HamQSL)**

```php
/**
 * Fetch HF Band Conditions from HamQSL XML
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
		$response = $client->get(self::HAMQSL_BASE . '/solarxml.php', ['timeout' => 15]);
		$xml = $response->getBody();

		$root = new \SimpleXMLElement($xml);
		$solar = $root->solardata;

		$bands = [];
		foreach ($solar->calculatedconditions->band ?? [] as $band) {
			$name = (string)($band['name'] ?? '');
			if ($name) {
				$bands[$name] = [
					'name' => $name,
					'condition' => (string)$band,
					'muf' => (int)(isset($band['muf']) ? $band['muf'] : 0),
				];
			}
		}

		$bandData = [
			'bands' => $bands,
			'solar_index' => (int)($solar->sfi ?? 0),
			'sunspot_number' => (int)($solar->sunspots ?? 0),
			'timestamp' => date('c'),
		];

		$this->cacheService->setForecast($cacheKey, json_encode($bandData));
		return $bandData;
	} catch (\Exception $e) {
		$this->logger->error('Failed to fetch band conditions: ' . $e->getMessage());
		return ['error' => true, 'message' => 'Failed to fetch band conditions'];
	}
}
```

**Step 4: Verify methods are added**

```bash
grep -n "public function get" /tmp/nextcloud-space-weather/lib/Service/SpaceWeatherService.php | head -10
```

Expected: Should list getKpIndex, getSolarFlux, getXrayFlux, getAuroraForecast, getBandConditions

**Step 5: Commit**

```bash
cd /tmp/nextcloud-space-weather
git add lib/Service/SpaceWeatherService.php
git commit -m "feat: add X-ray, aurora forecast, and band conditions fetching methods"
```

---

### Task 7: Implement remaining satellite/imagery methods

**Objective:** Add getDRAPAbsorption(), getSDOImagery(), and getSatelliteImages() to WeatherSatelliteService.

**Files:**
- Modify: `lib/Service/WeatherSatelliteService.php`

**Step 1: View current WeatherSatelliteService structure**

```bash
head -50 /tmp/nextcloud-space-weather/lib/Service/WeatherSatelliteService.php
```

**Step 2: Add getDRAPAbsorption() method**

```php
/**
 * Get D-RAP absorption map URLs
 *
 * @return array<string, mixed>
 */
public function getDRAPAbsorption(): array {
	$cacheKey = CacheService::getCacheKey('drap_absorption');
	$cached = $this->cacheService->getForecast($cacheKey);

	if ($cached !== null) {
		return json_decode($cached, true) ?? [];
	}

	$drapData = [
		'current_map' => 'https://services.swpc.noaa.gov/images/animations/d-rap/20240101_d-rap_now.jpg',
		'forecast_12h' => 'https://services.swpc.noaa.gov/images/animations/d-rap/20240101_d-rap_12h.jpg',
		'forecast_24h' => 'https://services.swpc.noaa.gov/images/animations/d-rap/20240101_d-rap_24h.jpg',
		'timestamp' => date('c'),
	];

	$this->cacheService->setForecast($cacheKey, json_encode($drapData));
	return $drapData;
}
```

**Step 3: Add getSDOImagery() method**

```php
/**
 * Get SDO Solar Imagery URLs
 *
 * @return array<string, mixed>
 */
public function getSDOImagery(): array {
	$cacheKey = CacheService::getCacheKey('sdo_imagery');
	$cached = $this->cacheService->getDaily($cacheKey);

	if ($cached !== null) {
		return json_decode($cached, true) ?? [];
	}

	$wavelengths = [
		['name' => 'AIA 94Å', 'description' => 'Extreme ultraviolet - Hot corona', 'wavelength' => '94'],
		['name' => 'AIA 171Å', 'description' => 'Extreme ultraviolet - Lower corona', 'wavelength' => '171'],
		['name' => 'AIA 193Å', 'description' => 'Extreme ultraviolet - Corona', 'wavelength' => '193'],
		['name' => 'AIA 211Å', 'description' => 'Extreme ultraviolet - Transition region', 'wavelength' => '211'],
		['name' => 'HMI Magnetogram', 'description' => 'Magnetic field observations', 'wavelength' => 'magnetogram'],
		['name' => 'HMI Continuum', 'description' => 'White light observations', 'wavelength' => 'continuum'],
	];

	$sdoData = [
		'wavelengths' => $wavelengths,
		'base_url' => 'https://sdo.gsfc.nasa.gov/assets/img/latest/',
		'timestamp' => date('c'),
	];

	$this->cacheService->setDaily($cacheKey, json_encode($sdoData));
	return $sdoData;
}
```

**Step 4: Add getSatelliteImages() method**

```php
/**
 * Get satellite imagery URLs (GOES, Himawari, Meteor-M2)
 *
 * @return array<string, mixed>
 */
public function getSatelliteImages(): array {
	$cacheKey = CacheService::getCacheKey('satellite_images');
	$cached = $this->cacheService->getDaily($cacheKey);

	if ($cached !== null) {
		return json_decode($cached, true) ?? [];
	}

	$satellites = [
		[
			'name' => 'GOES-16',
			'description' => 'NOAA Geostationary Operational Environmental Satellite',
			'provider' => 'NOAA',
			'image_url' => 'https://cdn.star.nesdis.noaa.gov/GOES16/ABI/FD/02/20240101_000000_G16_ABI_FD_02.jpg',
			'type' => 'Full Disk',
		],
		[
			'name' => 'GOES-17',
			'description' => 'NOAA Geostationary Operational Environmental Satellite',
			'provider' => 'NOAA',
			'image_url' => 'https://cdn.star.nesdis.noaa.gov/GOES17/ABI/FD/02/20240101_000000_G17_ABI_FD_02.jpg',
			'type' => 'Full Disk',
		],
		[
			'name' => 'Himawari-8',
			'description' => 'Japanese geostationary satellite for Earth observation',
			'provider' => 'JMA',
			'image_url' => 'https://agora.ex.nii.ac.jp/digital-typhoon/latest/color/full.jpg',
			'type' => 'Full Disk',
		],
	];

	$satelliteData = [
		'satellites' => $satellites,
		'timestamp' => date('c'),
	];

	$this->cacheService->setDaily($cacheKey, json_encode($satelliteData));
	return $satelliteData;
}
```

**Step 5: Verify methods added**

```bash
grep -n "public function get" /tmp/nextcloud-space-weather/lib/Service/WeatherSatelliteService.php
```

**Step 6: Commit**

```bash
cd /tmp/nextcloud-space-weather
git add lib/Service/WeatherSatelliteService.php
git commit -m "feat: add D-RAP, SDO imagery, and satellite fetching methods"
```

---

## PHASE 3: API Endpoints (Controller Implementation)

**Goal:** Implement all 8 API endpoint handlers in APIController with proper error responses.

**Deliverable:** All endpoints callable and returning structured responses.

### Task 8: Implement remaining API endpoint methods

**Objective:** Complete APIController with all 8 methods matching routes.

**Files:**
- Modify: `lib/Controller/APIController.php`

(Details follow in actual implementation. Each method follows the same pattern: call service, return DataResponse.)

---

## PHASE 4: Frontend & Templates (UI Layer)

**Goal:** Create server-side rendered templates and vanilla JavaScript UI.

**Deliverable:** Dashboard loads, displays data, allows manual refresh.

### Task 9: Create templates directory structure

### Task 10: Create main dashboard template

### Task 11: Create vanilla JavaScript app file

---

## PHASE 5: Testing & Deployment

**Goal:** Verify app loads, endpoints work, data displays correctly.

**Deliverable:** App installed on local Nextcloud 33+, all features working.

### Task 12: Test app deployment

### Task 13: Verify endpoints return real data

### Task 14: Manual browser testing

---

## EXECUTION NOTES

- **Use short breaks between phases** — each phase builds on the previous
- **Commit after every task** — keeps history clean for debugging
- **Verify with curl/browser** after data layer tasks
- **Manual testing** critical before marking complete
- **Document pitfalls found** — update memory with issues encountered

## Dependencies Between Phases

```
PHASE 1 (Framework) ← must complete first
    ↓
PHASE 2 (Data Layer) ← requires Phase 1, enables Phase 3
    ↓
PHASE 3 (API Endpoints) ← requires Phase 1 & 2
    ↓
PHASE 4 (Frontend) ← requires Phase 1, 2, 3
    ↓
PHASE 5 (Testing) ← final integration
```

**Ready to execute?**
