<template>
	<div class="space-weather-card">
		<div class="card-header">
			<h3>{{ cardTitle }}</h3>
			<span v-if="loading" class="card-spinner">⟳</span>
		</div>

		<div v-if="data && !data.error" class="card-content">
			<!-- KP Index Display -->
			<div v-if="dataType === 'kp'" class="kp-display">
				<div class="kp-value" :class="getKpClass(data.kp)">
					{{ data.kp.toFixed(1) }}
				</div>
				<div class="kp-status">{{ formatStatus(data.status) }}</div>
				<div class="kp-info">
					<small>Geomagnetic Index</small>
				</div>
				<div v-if="data.timestamp" class="timestamp">
					{{ formatTime(data.timestamp) }}
				</div>
			</div>

			<!-- X-Ray Flux Display -->
			<div v-else-if="dataType === 'xray'" class="xray-display">
				<div class="xray-alert" :class="'alert-' + data.alert_level">
					{{ data.alert_level.toUpperCase() }}
				</div>
				<div class="xray-values">
					<div class="xray-value">
						<span class="label">Long:</span>
						<span class="value">{{ formatScientific(data.long) }}</span>
					</div>
					<div class="xray-value">
						<span class="label">Short:</span>
						<span class="value">{{ formatScientific(data.short) }}</span>
					</div>
				</div>
				<div v-if="data.timestamp" class="timestamp">
					{{ formatTime(data.timestamp) }}
				</div>
			</div>

			<!-- Solar Flux Display -->
			<div v-else-if="dataType === 'flux'" class="flux-display">
				<div class="flux-value">
					{{ data.current.toFixed(0) }}
				</div>
				<div class="flux-unit">sfu</div>
				<div class="flux-info">
					<small>Solar Flux Unit (10^-22 W/m²/Hz)</small>
				</div>
				<div v-if="data.timestamp" class="timestamp">
					{{ formatTime(data.timestamp) }}
				</div>
			</div>

			<!-- Generic Display -->
			<div v-else class="generic-display">
				<pre>{{ JSON.stringify(data, null, 2) }}</pre>
			</div>
		</div>

		<div v-else-if="data && data.error" class="card-error">
			<p>{{ data.message || 'Error loading data' }}</p>
		</div>

		<div v-else class="card-loading">
			<p>Loading...</p>
		</div>
	</div>
</template>

<script>
/**
 * Space Weather Card Component
 * Displays individual space weather metrics with appropriate formatting
 *
 * @props {String} cardTitle - Card title
 * @props {Object} data - Data object to display
 * @props {String} dataType - Type of data: 'kp', 'xray', 'flux'
 * @props {Boolean} loading - Loading state
 */
export default {
	name: 'SpaceWeatherCard',
	props: {
		cardTitle: {
			type: String,
			required: true,
		},
		data: {
			type: Object,
			default: () => ({}),
		},
		dataType: {
			type: String,
			default: 'generic',
		},
		loading: {
			type: Boolean,
			default: false,
		},
	},

	methods: {
		/**
		 * Get CSS class for KP index based on value
		 */
		getKpClass(kp) {
			if (kp < 2) return 'kp-quiet'
			if (kp < 4) return 'kp-unsettled'
			if (kp < 6) return 'kp-active'
			if (kp < 8) return 'kp-minor-storm'
			if (kp < 9) return 'kp-major-storm'
			return 'kp-severe-storm'
		},

		/**
		 * Format status string for display
		 */
		formatStatus(status) {
			return status
				.replace(/_/g, ' ')
				.split(' ')
				.map(word => word.charAt(0).toUpperCase() + word.slice(1))
				.join(' ')
		},

		/**
		 * Format time string
		 */
		formatTime(timestamp) {
			const date = new Date(timestamp)
			return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
		},

		/**
		 * Format scientific notation numbers
		 */
		formatScientific(value) {
			if (!value) return '0'
			if (value === 0) return '0'
			return value.toExponential(2)
		},
	},
}
</script>

<style scoped>
.space-weather-card {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	border-radius: 12px;
	padding: 20px;
	color: white;
	box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
	transition: all 0.3s ease;
	min-height: 200px;
	display: flex;
	flex-direction: column;
}

.space-weather-card:hover {
	transform: translateY(-5px);
	box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.card-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
	border-bottom: 2px solid rgba(255, 255, 255, 0.2);
	padding-bottom: 10px;
}

.card-header h3 {
	margin: 0;
	font-size: 18px;
	font-weight: 600;
}

.card-spinner {
	display: inline-block;
	animation: spin 2s linear infinite;
	font-size: 20px;
}

@keyframes spin {
	from { transform: rotate(0deg); }
	to { transform: rotate(360deg); }
}

.card-content {
	flex: 1;
	display: flex;
	flex-direction: column;
	justify-content: center;
}

/* KP Index Styles */
.kp-display {
	text-align: center;
}

.kp-value {
	font-size: 48px;
	font-weight: bold;
	margin-bottom: 10px;
	padding: 20px;
	border-radius: 8px;
	background: rgba(255, 255, 255, 0.1);
}

.kp-quiet { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.kp-unsettled { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.kp-active { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.kp-minor-storm { background: linear-gradient(135deg, #ff9a56 0%, #ff6a88 100%); }
.kp-major-storm { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); }
.kp-severe-storm { background: linear-gradient(135deg, #8b0000 0%, #ff0000 100%); }

.kp-status {
	font-size: 16px;
	font-weight: 600;
	margin-bottom: 5px;
}

.kp-info small {
	font-size: 12px;
	opacity: 0.8;
}

/* X-Ray Flux Styles */
.xray-display {
	text-align: center;
}

.xray-alert {
	font-size: 32px;
	font-weight: bold;
	padding: 15px;
	border-radius: 8px;
	margin-bottom: 15px;
	background: rgba(255, 255, 255, 0.1);
}

.alert-normal { color: #90EE90; }
.alert-a_class { color: #FFD700; }
.alert-b_class { color: #FFA500; }
.alert-c_class { color: #FF6347; }
.alert-m_class { color: #FF0000; }
.alert-x_class { color: #8B0000; background: rgba(139, 0, 0, 0.3) !important; }

.xray-values {
	font-size: 14px;
	line-height: 1.8;
}

.xray-value {
	display: flex;
	justify-content: center;
	gap: 10px;
}

.xray-value .label {
	font-weight: 600;
	min-width: 60px;
	text-align: right;
}

/* Solar Flux Styles */
.flux-display {
	text-align: center;
}

.flux-value {
	font-size: 48px;
	font-weight: bold;
	margin-bottom: 5px;
}

.flux-unit {
	font-size: 16px;
	font-weight: 600;
	margin-bottom: 10px;
}

.flux-info small {
	font-size: 11px;
	opacity: 0.8;
}

/* Common Styles */
.timestamp {
	font-size: 12px;
	opacity: 0.7;
	margin-top: 15px;
	padding-top: 10px;
	border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.card-error,
.card-loading {
	text-align: center;
	padding: 20px;
	color: white;
}

.card-error p {
	color: #ffcccc;
	font-size: 14px;
}

.card-loading p {
	opacity: 0.8;
}

.generic-display pre {
	background: rgba(0, 0, 0, 0.2);
	padding: 10px;
	border-radius: 4px;
	font-size: 11px;
	overflow-x: auto;
	max-height: 300px;
}

/* Responsive */
@media (max-width: 768px) {
	.space-weather-card {
		min-height: 160px;
		padding: 15px;
	}

	.kp-value,
	.flux-value {
		font-size: 36px;
	}

	.xray-alert {
		font-size: 24px;
	}

	.card-header h3 {
		font-size: 16px;
	}
}
</style>
