# Changelog

All notable changes to the Space Weather Dashboard project are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-01

### Added
- Initial release of Space Weather Dashboard
- Real-time KP index monitoring with color-coded status indicators
- Solar flux (F10.7) measurements and display
- X-ray flux monitoring with NOAA alert levels (A, B, C, M, X class)
- Aurora forecast images from NOAA SWPC
- HF band propagation conditions from HamQSL XML
- Maximum Usable Frequency (MUF) tracking
- D-RAP (D-Region Absorption Prediction) absorption maps
- SDO (Solar Dynamics Observatory) imagery gallery with 5 wavelengths
- Weather satellite imagery from GOES-16, GOES-17
- Placeholders for Meteor-M2 and Himawari-8 satellite integration
- TTL-based caching system (5min real-time, 30min forecast, 60min daily)
- Manual refresh button with loading spinner
- Last update timestamp display
- Responsive mobile-first design with CSS Grid and Flexbox
- Dark mode support via prefers-color-scheme
- Error handling with user-friendly notifications
- Comprehensive API documentation
- Vue.js 3 with Composition API compatibility
- PHP 8.0+ type hints throughout
- Nextcloud 27+ compatibility
- HTTPS-only external API calls
- CSRF token protection on POST endpoints

### Architecture
- Modular service-based design (SpaceWeatherService, WeatherSatelliteService, CacheService)
- RESTful API endpoints with DataResponse wrapper
- Vue.js 3 component-based frontend
- Responsive CSS with mobile-first approach
- Proper separation of concerns (Controller, Service, Template, Component)

### Data Sources
- NOAA Space Weather Prediction Center (SWPC)
- NASA APIs (SDO Imagery)
- HamQSL propagation data
- NOAA GOES satellite imagery
- Roshydromet (placeholder for Meteor-M2)
- Japan Meteorological Agency (placeholder for Himawari-8)

### Components
- SpaceWeatherCard.vue: KP, X-ray, and solar flux display cards
- BandConditions.vue: HF band conditions table with efficiency bars
- SatelliteGallery.vue: Satellite image gallery with provider info

### Documentation
- Comprehensive README.md with feature overview
- Detailed API documentation with endpoint examples
- Development guide (DEVELOPMENT.md) for contributors
- Installation and deployment instructions
- Troubleshooting guide
- Code examples and patterns

### Testing
- Manual API testing support
- Browser DevTools integration
- Vue DevTools compatibility
- Network request inspection
- Component state debugging

## [Unreleased]

### Planned Features
- Background job for automatic cache refresh
- User settings for refresh intervals
- Data export (CSV, JSON)
- Historical data graphs and trends
- Alert notifications for geomagnetic storms
- Integration with amateur radio communities
- Multi-language support (i18n)
- Admin dashboard for configuration
- API rate limiting and throttling
- WebSocket support for real-time updates
- Mobile app wrapper
- Integration with Nextcloud Calendar (aurora alerts)
- Integration with Nextcloud Notifications

### Known Issues
- Meteor-M2 satellite data requires Roshydromet portal integration
- Himawari-8 data requires JMA portal integration
- Some regional D-RAP maps may have limited availability
- Image loading failures gracefully handled but UI shows placeholder

### Future Improvements
- Optimize concurrent API requests with queuing
- Implement incremental data updates
- Add statistical analysis and predictions
- Support for custom band selections
- Theme customization options
- Advanced filtering and search
- Report generation
- Alert threshold configuration
- Data archive and historical analysis

## Version History

- v1.0.0 (Jan 2024): Initial release
  - All core features implemented
  - Production-ready code quality
  - Full API documentation
  - Responsive mobile design

---

## Guidelines for Contributors

When adding new features or fixing bugs:

1. Update this CHANGELOG with your changes
2. Follow the format: `### [Type]` where Type is Added, Changed, Fixed, etc.
3. Use clear, user-focused descriptions
4. Link to related issues or PRs when applicable
5. Update version numbers following Semantic Versioning

### Types of Changes
- **Added**: New features
- **Changed**: Changes to existing functionality
- **Deprecated**: Soon-to-be removed features
- **Removed**: Removed features
- **Fixed**: Bug fixes
- **Security**: Security vulnerability fixes
- **Performance**: Performance improvements

---

**Note**: This changelog is maintained manually. Ensure it stays in sync with actual releases.
