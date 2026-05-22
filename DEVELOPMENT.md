# Development Guide - Space Weather Dashboard

This guide provides information for developers working on the Space Weather Dashboard app.

## Project Structure

```
space_weather/
├── appinfo/
│   ├── info.xml          # App metadata
│   ├── app.php           # App initialization
│   └── routes.php        # API route definitions
├── lib/
│   ├── Controller/
│   │   ├── APIController.php      # REST API endpoints
│   │   └── PageController.php     # Page rendering
│   └── Service/
│       ├── CacheService.php       # Cache wrapper
│       ├── SpaceWeatherService.php # NOAA/NASA data
│       └── WeatherSatelliteService.php # Satellite data
├── templates/
│   └── content/
│       └── index.html    # Main dashboard template
├── js/
│   ├── app.js           # Vue.js application
│   └── components/
│       ├── SpaceWeatherCard.vue    # Data cards
│       ├── BandConditions.vue      # HF conditions
│       └── SatelliteGallery.vue    # Satellite images
├── css/
│   └── style.css        # Responsive styling
├── composer.json        # PHP dependencies
├── package.json         # Node.js dependencies
└── README.md           # User documentation
```

## Local Development Setup

### Prerequisites
- Nextcloud 27+ installation
- PHP 8.0+
- Node.js 16+
- Composer
- Git

### Installation Steps

```bash
# Clone or extract app to Nextcloud apps directory
cd /path/to/nextcloud/apps/
mkdir space_weather
cd space_weather

# Install dependencies
composer install
npm install

# Build JavaScript
npm run build

# Enable app in Nextcloud
php /path/to/nextcloud/occ app:enable space_weather

# Access app at http://localhost/apps/space_weather
```

## Development Workflow

### PHP Development

#### Creating a New API Endpoint

1. Add route in `appinfo/routes.php`:
```php
['name' => 'api#getNewData', 'url' => '/api/v1/new-data', 'verb' => 'GET'],
```

2. Implement method in `lib/Controller/APIController.php`:
```php
public function getNewData(): DataResponse {
	try {
		$data = $this->spaceWeatherService->fetchNewData();
		return new DataResponse(['success' => true, 'data' => $data]);
	} catch (\Exception $e) {
		return new DataResponse(['success' => false, 'error' => $e->getMessage()], 500);
	}
}
```

3. Implement logic in appropriate Service class:
```php
public function fetchNewData(): array {
	$cacheKey = CacheService::getCacheKey('new_data');
	$cached = $this->cacheService->getRealTime($cacheKey);
	
	if ($cached !== null) {
		return json_decode($cached, true) ?? [];
	}
	
	// Fetch data...
	$result = [...];
	$this->cacheService->setRealTime($cacheKey, json_encode($result));
	return $result;
}
```

#### Code Standards

Follow PSR-12 with these conventions:
- Use type hints for all parameters and returns
- Declare strict types: `declare(strict_types=1);`
- Use private properties with type declarations
- Document public methods with PHPDoc blocks
- Use try-catch for exception handling
- Log errors via LoggerInterface

Example:
```php
public function fetchData(string $endpoint): array {
	try {
		$client = $this->clientService->newClient();
		$response = $client->get($endpoint);
		$data = json_decode($response->getBody(), true);
		
		if (!is_array($data)) {
			throw new RuntimeException('Invalid response format');
		}
		
		return $data;
	} catch (\Exception $e) {
		$this->logger->error('API error: ' . $e->getMessage());
		throw $e;
	}
}
```

### Vue.js Development

#### Creating a New Component

1. Create component file `js/components/MyComponent.vue`:
```vue
<template>
	<div class="my-component">
		<h3>{{ title }}</h3>
		<p>{{ content }}</p>
	</div>
</template>

<script>
export default {
	name: 'MyComponent',
	props: {
		title: String,
		content: String,
	},
	data() {
		return {}
	},
	methods: {},
	mounted() {},
}
</script>

<style scoped>
.my-component {
	padding: 20px;
	border-radius: 8px;
	background: white;
}
</style>
```

2. Register component in `js/app.js`:
```javascript
import MyComponent from './components/MyComponent.vue'
app.component('MyComponent', MyComponent)
```

3. Use in template:
```html
<my-component title="Test" content="Hello"></my-component>
```

#### Component Patterns

**Data Fetching**:
```javascript
async fetchData() {
	try {
		const response = await fetch(this.apiBase + '/endpoint')
		const result = await response.json()
		if (result.success) {
			this.data = result.data
		}
	} catch (error) {
		console.error('Fetch error:', error)
		this.addError('Error message')
	}
}
```

**Loading States**:
```javascript
async loadData() {
	this.isLoading = true
	try {
		// Fetch data
	} finally {
		this.isLoading = false
	}
}
```

**Error Handling**:
```javascript
addError(message) {
	this.errors.push(message)
	setTimeout(() => {
		this.errors = this.errors.filter(e => e !== message)
	}, 5000)
}
```

### CSS Development

Follow mobile-first responsive design:

```css
/* Mobile first */
.component {
	display: grid;
	grid-template-columns: 1fr;
	gap: 10px;
}

/* Tablet and up */
@media (min-width: 768px) {
	.component {
		grid-template-columns: repeat(2, 1fr);
	}
}

/* Desktop and up */
@media (min-width: 1024px) {
	.component {
		grid-template-columns: repeat(3, 1fr);
	}
}
```

## Testing

### Manual Testing

```bash
# Test API endpoints
curl http://localhost/apps/space_weather/api/v1/kp-index

# Test with Firefox DevTools
# 1. Open browser DevTools (F12)
# 2. Network tab
# 3. Refresh dashboard
# 4. Inspect API responses
```

### Browser Console Testing

```javascript
// In browser console
// Test data fetching
fetch(OC.getRootUrl() + '/apps/space_weather/api/v1/kp-index')
	.then(r => r.json())
	.then(d => console.log(d))

// Test Vue app state
console.log(app._instance.data())

// Test errors
app._instance.addError('Test error')
```

## Debugging

### Enable Debug Logging

In `lib/Service/SpaceWeatherService.php`:
```php
private function debug(string $message): void {
	if (getenv('DEBUG_SPACE_WEATHER')) {
		$this->logger->debug('SpaceWeather: ' . $message);
	}
}
```

Run with debug:
```bash
DEBUG_SPACE_WEATHER=1 php -S localhost:8000
```

### Browser DevTools

1. **Network Tab**: Monitor API calls
2. **Console Tab**: Check for JavaScript errors
3. **Vue DevTools**: Inspect component state
4. **Application Tab**: Check localStorage/sessionStorage

## Performance Optimization

### Caching Strategy

Current TTL values (configurable in CacheService):
- Real-time: 5 minutes
- Forecast: 30 minutes
- Daily: 60 minutes

To adjust:
```php
private const CACHE_REAL_TIME_TTL = 300;  // seconds
```

### API Response Optimization

Keep response payloads small:
```php
// Good - only essential data
return ['kp' => 4.3, 'timestamp' => date('c')];

// Avoid - excess data
return ['kp' => 4.3, 'detailed' => [...], 'history' => [...], 'metadata' => [...]];
```

### Frontend Optimization

Use computed properties for derived state:
```javascript
computed: {
	filteredBands() {
		// Cache this expensive calculation
		return this.bands.filter(b => b.efficiency > 50)
	}
}
```

## Building and Deployment

### Build for Production

```bash
# Build Vue components
npm run build

# Minify CSS (optional)
npm run build:css

# Create distribution package
cd /path/to/nextcloud/apps/space_weather
tar czf space_weather.tar.gz \
	--exclude=node_modules \
	--exclude=.git \
	--exclude=.gitignore \
	--exclude=package-lock.json \
	.
```

### Deploy to Nextcloud App Store

1. Update version in `appinfo/info.xml`
2. Update CHANGELOG.md
3. Create git tag: `git tag v1.0.0`
4. Push to GitHub
5. Submit to Nextcloud App Store

## External API Integration

### Adding New Data Source

1. Create new method in appropriate Service:
```php
public function fetchExternalData(): array {
	$client = $this->clientService->newClient();
	$response = $client->get('https://api.example.com/data');
	return json_decode($response->getBody(), true);
}
```

2. Add error handling:
```php
try {
	// API call
} catch (ConnectException $e) {
	$this->logger->error('Connection error: ' . $e->getMessage());
	return ['error' => true, 'message' => 'Service unavailable'];
} catch (ClientException $e) {
	$this->logger->error('Client error: ' . $e->getMessage());
	return ['error' => true, 'message' => 'Invalid request'];
}
```

3. Add to API endpoint in controller
4. Update frontend component to display data

## Common Issues and Solutions

### Issue: Cache not clearing
**Solution**: Check cache backend configuration in Nextcloud config.php

### Issue: Images not loading
**Solution**: Add proper error handlers and fallback URLs

### Issue: Slow API responses
**Solution**: Implement parallel fetching with Promise.all(), optimize cache TTL

### Issue: CORS errors
**Solution**: Ensure API endpoints support CORS or use server-side proxy

## Resources

- [Nextcloud App Development](https://docs.nextcloud.com/server/latest/developer_manual/)
- [Vue.js 3 Guide](https://vuejs.org/)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
- [NOAA SWPC API](https://services.swpc.noaa.gov/)
- [NASA APIs](https://api.nasa.gov/)

## Contributing

1. Fork the repository
2. Create feature branch: `git checkout -b feature/my-feature`
3. Make changes following code standards
4. Test thoroughly
5. Commit with descriptive messages
6. Push and create pull request
7. Update documentation

## License

AGPL-3.0-or-later
