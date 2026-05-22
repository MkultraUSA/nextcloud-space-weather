/**
 * Space Weather Dashboard - Vue.js 3 Application
 * @copyright Copyright (c) 2024 Nextcloud GmbH
 * @license AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import SpaceWeatherCard from './components/SpaceWeatherCard.vue'
import BandConditions from './components/BandConditions.vue'
import SatelliteGallery from './components/SatelliteGallery.vue'

/**
 * Main Vue Application
 * Handles data fetching, state management, and component coordination
 */
const app = createApp({
	data() {
		return {
			// Data state
			kpIndex: null,
			solarFlux: null,
			xrayFlux: null,
			auroraForecast: null,
			bandConditions: null,
			drapAbsorption: null,
			sdoImagery: null,
			satelliteImages: null,

			// Loading states
			kpLoading: false,
			fluxLoading: false,
			xrayLoading: false,
			bandConditionsLoading: false,
			auroraLoading: false,
			drapLoading: false,
			sdoLoading: false,
			satelliteImagesLoading: false,

			// General state
			isLoading: false,
			lastUpdate: null,
			errors: [],

			// API base path
			apiBase: OC.getRootUrl() + '/apps/space_weather/api/v1',
		}
	},

	computed: {
		allDataLoaded() {
			return this.kpIndex !== null && 
				   this.solarFlux !== null && 
				   this.xrayFlux !== null &&
				   this.bandConditions !== null
		}
	},

	methods: {
		/**
		 * Fetch KP Index
		 */
		async fetchKpIndex() {
			this.kpLoading = true
			try {
				const response = await fetch(`${this.apiBase}/kp-index`)
				const result = await response.json()
				if (result.success) {
					this.kpIndex = result.data
					this.updateTimestamp()
				} else {
					this.addError('Failed to fetch KP index')
				}
			} catch (error) {
				console.error('KP Index fetch error:', error)
				this.addError('Error fetching KP index: ' + error.message)
			} finally {
				this.kpLoading = false
			}
		},

		/**
		 * Fetch Solar Flux
		 */
		async fetchSolarFlux() {
			this.fluxLoading = true
			try {
				const response = await fetch(`${this.apiBase}/solar-flux`)
				const result = await response.json()
				if (result.success) {
					this.solarFlux = result.data
					this.updateTimestamp()
				} else {
					this.addError('Failed to fetch solar flux')
				}
			} catch (error) {
				console.error('Solar Flux fetch error:', error)
				this.addError('Error fetching solar flux: ' + error.message)
			} finally {
				this.fluxLoading = false
			}
		},

		/**
		 * Fetch X-ray Flux
		 */
		async fetchXrayFlux() {
			this.xrayLoading = true
			try {
				const response = await fetch(`${this.apiBase}/xray-flux`)
				const result = await response.json()
				if (result.success) {
					this.xrayFlux = result.data
					this.updateTimestamp()
				} else {
					this.addError('Failed to fetch X-ray flux')
				}
			} catch (error) {
				console.error('X-ray Flux fetch error:', error)
				this.addError('Error fetching X-ray flux: ' + error.message)
			} finally {
				this.xrayLoading = false
			}
		},

		/**
		 * Fetch Aurora Forecast
		 */
		async fetchAuroraForecast() {
			this.auroraLoading = true
			try {
				const response = await fetch(`${this.apiBase}/aurora-forecast`)
				const result = await response.json()
				if (result.success) {
					this.auroraForecast = result.data
					this.updateTimestamp()
				} else {
					this.addError('Failed to fetch aurora forecast')
				}
			} catch (error) {
				console.error('Aurora Forecast fetch error:', error)
				this.addError('Error fetching aurora forecast: ' + error.message)
			} finally {
				this.auroraLoading = false
			}
		},

		/**
		 * Fetch Band Conditions
		 */
		async fetchBandConditions() {
			this.bandConditionsLoading = true
			try {
				const response = await fetch(`${this.apiBase}/band-conditions`)
				const result = await response.json()
				if (result.success) {
					this.bandConditions = result.data
					this.updateTimestamp()
				} else {
					this.addError('Failed to fetch band conditions')
				}
			} catch (error) {
				console.error('Band Conditions fetch error:', error)
				this.addError('Error fetching band conditions: ' + error.message)
			} finally {
				this.bandConditionsLoading = false
			}
		},

		/**
		 * Fetch D-RAP Absorption
		 */
		async fetchDRAPAbsorption() {
			this.drapLoading = true
			try {
				const response = await fetch(`${this.apiBase}/drap-absorption`)
				const result = await response.json()
				if (result.success) {
					this.drapAbsorption = result.data
					this.updateTimestamp()
				} else {
					this.addError('Failed to fetch D-RAP absorption')
				}
			} catch (error) {
				console.error('D-RAP Absorption fetch error:', error)
				this.addError('Error fetching D-RAP absorption: ' + error.message)
			} finally {
				this.drapLoading = false
			}
		},

		/**
		 * Fetch SDO Imagery
		 */
		async fetchSDOImagery() {
			this.sdoLoading = true
			try {
				const response = await fetch(`${this.apiBase}/sdo-imagery`)
				const result = await response.json()
				if (result.success) {
					this.sdoImagery = result.data
					this.updateTimestamp()
				} else {
					this.addError('Failed to fetch SDO imagery')
				}
			} catch (error) {
				console.error('SDO Imagery fetch error:', error)
				this.addError('Error fetching SDO imagery: ' + error.message)
			} finally {
				this.sdoLoading = false
			}
		},

		/**
		 * Fetch Satellite Images
		 */
		async fetchSatelliteImages() {
			this.satelliteImagesLoading = true
			try {
				const response = await fetch(`${this.apiBase}/satellite-images`)
				const result = await response.json()
				if (result.success) {
					this.satelliteImages = result.data
					this.updateTimestamp()
				} else {
					this.addError('Failed to fetch satellite images')
				}
			} catch (error) {
				console.error('Satellite Images fetch error:', error)
				this.addError('Error fetching satellite images: ' + error.message)
			} finally {
				this.satelliteImagesLoading = false
			}
		},

		/**
		 * Fetch all data in parallel
		 */
		async fetchAllData() {
			this.isLoading = true
			const spinner = document.getElementById('loading-spinner')
			if (spinner) spinner.style.display = 'inline-block'

			try {
				await Promise.all([
					this.fetchKpIndex(),
					this.fetchSolarFlux(),
					this.fetchXrayFlux(),
					this.fetchAuroraForecast(),
					this.fetchBandConditions(),
					this.fetchDRAPAbsorption(),
					this.fetchSDOImagery(),
					this.fetchSatelliteImages(),
				])
				this.updateTimestamp()
			} catch (error) {
				console.error('Error fetching all data:', error)
				this.addError('Error fetching data')
			} finally {
				this.isLoading = false
				if (spinner) spinner.style.display = 'none'
			}
		},

		/**
		 * Handle manual refresh button click
		 */
		async handleRefresh() {
			if (this.isLoading) return

			const spinner = document.getElementById('loading-spinner')
			if (spinner) spinner.style.display = 'inline-block'

			try {
				const response = await fetch(`${this.apiBase}/refresh-all`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-Token': OC.requestToken,
					},
				})
				const result = await response.json()

				if (result.success) {
					// Update all data from refresh response
					this.kpIndex = result.data.kp_index
					this.solarFlux = result.data.solar_flux
					this.xrayFlux = result.data.xray_flux
					this.auroraForecast = result.data.aurora_forecast
					this.bandConditions = result.data.band_conditions
					this.drapAbsorption = result.data.drap_absorption
					this.sdoImagery = result.data.sdo_imagery
					this.satelliteImages = result.data.satellite_images

					this.updateTimestamp()
					this.addSuccess('Data refreshed successfully')
				} else {
					this.addError('Failed to refresh data')
				}
			} catch (error) {
				console.error('Refresh error:', error)
				this.addError('Error refreshing data: ' + error.message)
			} finally {
				if (spinner) spinner.style.display = 'none'
			}
		},

		/**
		 * Update last update timestamp
		 */
		updateTimestamp() {
			const now = new Date()
			const hours = String(now.getHours()).padStart(2, '0')
			const minutes = String(now.getMinutes()).padStart(2, '0')
			this.lastUpdate = `${hours}:${minutes}`

			const timeElement = document.querySelector('#last-update time')
			if (timeElement) {
				timeElement.textContent = this.lastUpdate
			}
		},

		/**
		 * Add error message
		 */
		addError(message) {
			this.errors.push(message)
			// Auto-remove after 5 seconds
			setTimeout(() => {
				const index = this.errors.indexOf(message)
				if (index > -1) {
					this.errors.splice(index, 1)
				}
			}, 5000)
		},

		/**
		 * Add success message
		 */
		addSuccess(message) {
			// Create temporary success notification
			const notification = document.createElement('div')
			notification.className = 'success-message'
			notification.textContent = message
			document.body.appendChild(notification)

			setTimeout(() => {
				notification.style.opacity = '0'
				setTimeout(() => notification.remove(), 300)
			}, 3000)
		},
	},

	mounted() {
		// Initial data load
		this.fetchAllData()

		// Attach refresh button handler
		const refreshBtn = document.getElementById('refresh-btn')
		if (refreshBtn) {
			refreshBtn.addEventListener('click', () => this.handleRefresh())
		}

		// Update timestamp on mount
		this.updateTimestamp()
	},
})

// Register components
app.component('SpaceWeatherCard', SpaceWeatherCard)
app.component('BandConditions', BandConditions)
app.component('SatelliteGallery', SatelliteGallery)

// Mount app
app.mount('#app')
