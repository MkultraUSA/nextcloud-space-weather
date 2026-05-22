<template>
	<div class="band-conditions-container">
		<div v-if="loading" class="loading">
			<span class="spinner">⟳</span> Loading band conditions...
		</div>

		<div v-else-if="data && data.bands && Object.keys(data.bands).length > 0" class="bands-table-wrapper">
			<div class="bands-info">
				<div class="info-item">
					<span class="label">Solar Index:</span>
					<span class="value">{{ data.solar_index }}</span>
				</div>
				<div class="info-item">
					<span class="label">Sunspot Number:</span>
					<span class="value">{{ data.sunspot_number }}</span>
				</div>
				<div v-if="data.timestamp" class="info-item">
					<span class="label">Updated:</span>
					<span class="value">{{ formatTime(data.timestamp) }}</span>
				</div>
			</div>

			<table class="bands-table">
				<thead>
					<tr>
						<th>Band</th>
						<th>Condition</th>
						<th>Efficiency</th>
						<th>MUF</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="band in sortedBands" :key="band.name" :class="'condition-' + band.condition.toLowerCase()">
						<td class="band-name">{{ band.name }}</td>
						<td class="condition">
							<span class="condition-badge" :class="'badge-' + band.condition.toLowerCase()">
								{{ band.condition }}
							</span>
						</td>
						<td class="efficiency">
							<div class="efficiency-bar">
								<div class="efficiency-fill" :style="{ width: band.efficiency + '%' }"></div>
								<span class="efficiency-text">{{ band.efficiency }}%</span>
							</div>
						</td>
						<td class="muf">{{ band.muf }} MHz</td>
					</tr>
				</tbody>
			</table>

			<div class="bands-legend">
				<div class="legend-item">
					<span class="legend-badge badge-open">Open</span>
					<span>Excellent propagation</span>
				</div>
				<div class="legend-item">
					<span class="legend-badge badge-fair">Fair</span>
					<span>Good propagation</span>
				</div>
				<div class="legend-item">
					<span class="legend-badge badge-poor">Poor</span>
					<span>Limited propagation</span>
				</div>
				<div class="legend-item">
					<span class="legend-badge badge-closed">Closed</span>
					<span>No propagation</span>
				</div>
			</div>
		</div>

		<div v-else class="no-data">
			<p>No band condition data available</p>
		</div>
	</div>
</template>

<script>
/**
 * Band Conditions Component
 * Displays HF band propagation conditions parsed from HamQSL data
 *
 * @props {Object} data - Band conditions data
 * @props {Boolean} loading - Loading state
 */
export default {
	name: 'BandConditions',
	props: {
		data: {
			type: Object,
			default: () => ({}),
		},
		loading: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		/**
		 * Sort bands by band name (frequency)
		 */
		sortedBands() {
			if (!this.data || !this.data.bands) return []

			const bandOrder = ['80m', '60m', '40m', '30m', '20m', '17m', '15m', '12m', '10m', '6m', '2m']
			const bands = Object.values(this.data.bands)

			return bands.sort((a, b) => {
				const indexA = bandOrder.indexOf(a.name)
				const indexB = bandOrder.indexOf(b.name)
				return (indexA > -1 ? indexA : 999) - (indexB > -1 ? indexB : 999)
			})
		},
	},

	methods: {
		/**
		 * Format time string
		 */
		formatTime(timestamp) {
			const date = new Date(timestamp)
			return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
		},
	},
}
</script>

<style scoped>
.band-conditions-container {
	background: white;
	border-radius: 12px;
	padding: 20px;
	box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.loading {
	text-align: center;
	padding: 40px 20px;
	color: #666;
	font-size: 16px;
}

.spinner {
	display: inline-block;
	animation: spin 2s linear infinite;
	margin-right: 10px;
	font-size: 20px;
}

@keyframes spin {
	from { transform: rotate(0deg); }
	to { transform: rotate(360deg); }
}

.bands-info {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
	gap: 15px;
	margin-bottom: 20px;
	padding: 15px;
	background: #f5f5f5;
	border-radius: 8px;
}

.info-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.info-item .label {
	font-weight: 600;
	color: #333;
}

.info-item .value {
	font-size: 18px;
	font-weight: bold;
	color: #667eea;
}

.bands-table-wrapper {
	overflow-x: auto;
}

.bands-table {
	width: 100%;
	border-collapse: collapse;
	margin-bottom: 20px;
}

.bands-table thead {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
}

.bands-table th {
	padding: 15px;
	text-align: left;
	font-weight: 600;
	font-size: 14px;
}

.bands-table td {
	padding: 15px;
	border-bottom: 1px solid #eee;
	font-size: 14px;
}

.bands-table tbody tr {
	transition: background-color 0.2s ease;
}

.bands-table tbody tr:hover {
	background-color: #f9f9f9;
}

.band-name {
	font-weight: 600;
	color: #333;
	width: 100px;
}

.condition {
	text-align: center;
}

.condition-badge {
	display: inline-block;
	padding: 6px 12px;
	border-radius: 20px;
	font-size: 12px;
	font-weight: 600;
	text-transform: uppercase;
}

.badge-open {
	background-color: #90EE90;
	color: #000;
}

.badge-fair {
	background-color: #FFD700;
	color: #000;
}

.badge-poor {
	background-color: #FFA500;
	color: #fff;
}

.badge-closed {
	background-color: #FFB6C1;
	color: #000;
}

.efficiency {
	min-width: 150px;
}

.efficiency-bar {
	position: relative;
	height: 30px;
	background-color: #eee;
	border-radius: 4px;
	overflow: hidden;
}

.efficiency-fill {
	height: 100%;
	background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
	transition: width 0.3s ease;
}

.efficiency-text {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	font-size: 12px;
	font-weight: 600;
	color: #333;
	z-index: 1;
}

.muf {
	font-weight: 600;
	color: #667eea;
	text-align: right;
}

.bands-legend {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
	gap: 15px;
	padding: 15px;
	background: #f5f5f5;
	border-radius: 8px;
	margin-top: 15px;
}

.legend-item {
	display: flex;
	align-items: center;
	gap: 10px;
	font-size: 14px;
	color: #666;
}

.legend-badge {
	display: inline-block;
	padding: 4px 10px;
	border-radius: 4px;
	font-size: 12px;
	font-weight: 600;
	white-space: nowrap;
}

.no-data {
	text-align: center;
	padding: 40px 20px;
	color: #999;
}

/* Responsive */
@media (max-width: 768px) {
	.band-conditions-container {
		padding: 15px;
	}

	.bands-info {
		grid-template-columns: 1fr;
		gap: 10px;
	}

	.bands-table {
		font-size: 12px;
	}

	.bands-table th,
	.bands-table td {
		padding: 10px 5px;
	}

	.bands-legend {
		grid-template-columns: 1fr;
	}

	.efficiency-text {
		font-size: 10px;
	}
}
</style>
