# Space Weather Dashboard - Nextcloud App

A comprehensive real-time space weather monitoring dashboard for Nextcloud, providing live data from NOAA, NASA, and other authoritative sources.

## Features

### Real-Time Data
- **KP Index**: Geomagnetic activity index with color-coded status
- **Solar Flux (F10.7)**: Solar radio flux measurements in solar flux units (sfu)
- **X-Ray Flux**: Solar X-ray monitoring with alert levels (A, B, C, M, X class flares)
- **Aurora Forecast**: Real-time aurora prediction images from NOAA SWPC

### Propagation Data
- **HF Band Conditions**: Parsed from HamQSL XML data with band efficiency indicators
- **D-RAP Absorption Maps**: D-region absorption prediction for radio propagation
- **MUF Tracking**: Maximum Usable Frequency predictions for amateur radio

### Solar Imagery
- **SDO Solar Imagery**: Multiple wavelength observations from NASA's Solar Dynamics Observatory
  - AIA 94Å, 193Å, 211Å (extreme ultraviolet)
  - HMI Magnetogram (magnetic field)
  - Continuum white light imagery

### Satellite Data
- **GOES-16/17**: NOAA geostationary satellite imagery
- **Meteor-M2**: Placeholder for Russian meteorological satellite integration
- **Himawari-8**: Placeholder for Japanese satellite data integration

## System Requirements

- **Nextcloud**: 27.0 or higher (tested through 35.0)
- **PHP**: 8.0 — 8.4
- **Browser**: Modern browser with ES2020+ support
- **Network**: Internet connectivity for external API calls (NOAA, NASA, HamQSL)

## Installation

### 1. Install Dependencies
```bash
cd /path/to/nextcloud/apps/space_weather
composer install --no-dev
```

### 2. Enable the App
```bash
sudo -u www-data php occ app:enable space_weather
```

### 3. Access the Dashboard
Navigate to your Nextcloud instance and click "Space Weather" in the sidebar, or visit:
```
https://your-nextcloud.example.com/apps/space_weather/
```

**No build step required** — all JavaScript is vanilla ES5/ES2020+ and CSP-compliant.

## Architecture

### Backend (PHP)
- **Controllers**: APIController (8 REST endpoints), PageController (dashboard rendering), ImageController (CSP-safe image proxy)
- **Services**: SpaceWeatherService (NOAA KP/flux/X-ray/aurora), WeatherSatelliteService (HamQSL bands, D-RAP, SDO, satellites), CacheService (TTL-based caching)
- **Settings**: Admin.php (ISettings panel for cache TTL, API timeout, data source toggles), AdminSection.php (IIconSection for settings navigation)
- **Framework**: Nextcloud AppFramework with IBootstrap, dependency injection, PSR-4 autoloading

### Frontend (Vanilla JS)
- Single `js/app.js` — no build step, no framework dependencies
- All DOM manipulation via `createElement` / `textContent` (CSP-compliant)
- All event binding via `addEventListener` (no inline handlers)
- Parallel API fetching with `Promise.all`
- Responsive CSS with dark mode, print styles, and reduced-motion support

### Image Proxy
External NOAA/NASA images are fetched server-side and served through same-origin endpoints (`/api/v1/image/{key}`) to comply with Nextcloud's Content Security Policy.

## API Documentation

### Endpoints

All endpoints support CORS and require no authentication.

#### KP Index
```
GET /apps/space_weather/api/v1/kp-index
Response: {
  "success": true,
  "data": {
    "kp": 4.3,
    "status": "active",
    "timestamp": "2024-01-01T12:00:00Z",
    "raw": [...]
  }
}
```

#### Solar Flux
```
GET /apps/space_weather/api/v1/solar-flux
Response: {
  "success": true,
  "data": {
    "current": 145.2,
    "timestamp": "2024-01-01T12:00:00Z",
    "raw": [...]
  }
}
```

#### X-Ray Flux
```
GET /apps/space_weather/api/v1/xray-flux
Response: {
  "success": true,
  "data": {
    "short": 1.2e-5,
    "long": 8.5e-6,
    "alert_level": "b_class",
    "timestamp": "2024-01-01T12:00:00Z",
    "raw": [...]
  }
}
```

#### Aurora Forecast
```
GET /apps/space_weather/api/v1/aurora-forecast
Response: {
  "success": true,
  "data": {
    "image_url": "...",
    "image_3hour_url": "...",
    "timestamp": "2024-01-01T12:00:00Z"
  }
}
```

#### HF Band Conditions
```
GET /apps/space_weather/api/v1/band-conditions
Response: {
  "success": true,
  "data": {
    "bands": {
      "80m": {
        "name": "80m",
        "condition": "Open",
        "efficiency": 85,
        "muf": 3800
      },
      ...
    },
    "solar_index": 87,
    "sunspot_number": 42
  }
}
```

#### D-RAP Absorption Maps
```
GET /apps/space_weather/api/v1/drap-absorption
Response: {
  "success": true,
  "data": {
    "current_map": "...",
    "forecast_12h": "...",
    "forecast_24h": "...",
    "timestamp": "2024-01-01T12:00:00Z"
  }
}
```

#### SDO Solar Imagery
```
GET /apps/space_weather/api/v1/sdo-imagery
Response: {
  "success": true,
  "data": {
    "wavelengths": [
      {
        "name": "AIA 94Å",
        "description": "Extreme ultraviolet - Hot corona",
        "url": "...",
        "wavelength": "94Å"
      },
      ...
    ]
  }
}
```

#### Satellite Images
```
GET /apps/space_weather/api/v1/satellite-images
Response: {
  "success": true,
  "data": {
    "satellites": [
      {
        "name": "GOES-16",
        "description": "...",
        "provider": "NOAA",
        "image_url": "...",
        "type": "Full Disk"
      },
      ...
    ]
  }
}
```

#### Refresh All Data
```
POST /apps/space_weather/api/v1/refresh-all
Headers: {
  "X-CSRF-Token": "<token>",
  "Content-Type": "application/json"
}
Response: {
  "success": true,
  "message": "All data refreshed successfully",
  "data": {
    "kp_index": {...},
    "solar_flux": {...},
    "xray_flux": {...},
    "aurora_forecast": {...},
    "band_conditions": {...},
    "drap_absorption": {...},
    "sdo_imagery": {...},
    "satellite_images": {...}
  }
}
```

## Caching Strategy

The app implements TTL-based caching for optimal performance:

- **Real-Time Data** (5 minutes): KP index, Solar flux, X-ray flux
- **Forecast Data** (30 minutes): Aurora forecast, Band conditions, D-RAP maps
- **Daily Data** (60 minutes): SDO imagery, Satellite images

### Manual Refresh
Users can manually trigger a cache refresh via the "Refresh" button in the dashboard UI, which:
1. Clears all cached data
2. Fetches fresh data from all sources
3. Updates the UI with new values
4. Updates the last-update timestamp

## Code Style & Quality

### PHP Standards
- Follows PSR-12 code style guidelines
- Type hints required for all parameters and returns
- Comprehensive error handling with proper logging
- Nextcloud 27+ API compatibility

### JavaScript
- ES2020+ syntax (vanilla, no framework)
- CSP-compliant — no `eval()`, no inline handlers
- Proper error boundaries
- Accessibility (a11y) compliant

### CSS
- Mobile-first responsive design
- CSS Grid and Flexbox layouts
- CSS variables for theming
- Dark mode support via prefers-color-scheme
- Print stylesheet support

## Error Handling

The application implements comprehensive error handling:

### API Level
- Network error recovery with user feedback
- Invalid data validation
- Graceful degradation when services are unavailable
- Proper HTTP status codes

### Frontend Level
- User-friendly error notifications
- Auto-dismissing error messages (5 seconds)
- Retry capability for failed requests
- Fallback UI states

## Performance Optimization

- TTL-based caching reduces API calls
- Parallel data fetching via Promise.all()
- Lazy loading of images
- CSS animations use transform and opacity
- Minified CSS and JavaScript
- Responsive image sizing

## Development

### Local Development Setup
```bash
# Clone the repository
git clone https://github.com/MkultraUSA/nextcloud-space-weather.git
cd nextcloud-space-weather

# Install PHP dependencies
composer install

# No build step required — all JavaScript is vanilla ES2020+ and CSP-compliant
```

### Testing API Calls
```bash
# Test KP index endpoint
curl http://localhost/apps/space_weather/api/v1/kp-index

# Test with refresh
curl -X POST http://localhost/apps/space_weather/api/v1/refresh-all \
  -H "X-CSRF-Token: <token>" \
  -H "Content-Type: application/json"
```

## Configuration

The app requires no special configuration beyond standard Nextcloud setup. However, you can customize:

### Cache TTL Values (edit CacheService.php)
```php
private const CACHE_REAL_TIME_TTL = 300;      // 5 minutes
private const CACHE_FORECAST_TTL = 1800;      // 30 minutes
private const CACHE_DAILY_TTL = 3600;         // 60 minutes
```

## Troubleshooting

### Data Not Loading
1. Check Nextcloud error logs: `tail -f data/nextcloud.log`
2. Verify internet connectivity
3. Test API endpoints directly via curl
4. Ensure cache service is functioning

### Images Not Displaying
1. Check browser console for CORS errors
2. Verify external URLs are accessible
3. Check image URLs in API responses
4. Enable JavaScript in browser

### Performance Issues
1. Clear app cache: Navigate to Settings > Space Weather
2. Reduce dashboard refresh frequency
3. Check server network bandwidth
4. Monitor PHP memory usage

## Security

- All external API calls use HTTPS
- User input is validated and sanitized
- No sensitive data stored locally
- API endpoints protected with CSRF tokens
- Follows Nextcloud security guidelines

## License

AGPL-3.0-or-later

## Contributing

Contributions welcome! Please ensure:
- Code follows PSR-12 standards
- Tests are included
- Documentation is updated
- Commits are well-described

## Support

For issues, feature requests, or questions:
- GitHub Issues: https://github.com/MkultraUSA/nextcloud-space-weather/issues
- Documentation: https://github.com/MkultraUSA/nextcloud-space-weather/blob/main/README.md
- Nextcloud Community: https://help.nextcloud.com/

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full release history.
