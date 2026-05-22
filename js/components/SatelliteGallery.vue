<template>
	<div class="satellite-gallery-container">
		<div v-if="loading" class="loading">
			<span class="spinner">⟳</span> Loading satellite images...
		</div>

		<div v-else-if="data && data.satellites && data.satellites.length > 0" class="gallery">
			<div class="gallery-info">
				<p><strong>Data Update Interval:</strong> {{ data.update_interval }}</p>
				<p v-if="data.timestamp">
					<strong>Last Updated:</strong> {{ formatTime(data.timestamp) }}
				</p>
			</div>

			<div class="satellites-grid">
				<div v-for="satellite in data.satellites" :key="satellite.name" class="satellite-card">
					<div class="satellite-header">
						<h3>{{ satellite.name }}</h3>
						<span v-if="satellite.type" class="satellite-type">{{ satellite.type }}</span>
					</div>

					<div class="satellite-body">
						<img 
							:src="satellite.image_url" 
							:alt="satellite.name"
							class="satellite-image"
							@error="handleImageError"
						>
						<div class="satellite-info">
							<p class="description">{{ satellite.description }}</p>
							<p v-if="satellite.provider" class="provider">
								<strong>Provider:</strong> {{ satellite.provider }}
							</p>
							<p v-if="satellite.note" class="note">
								📌 {{ satellite.note }}
							</p>
						</div>
					</div>

					<div class="satellite-footer">
						<a 
							:href="satellite.image_url" 
							target="_blank" 
							rel="noopener noreferrer"
							class="satellite-link"
						>
							View Full Size →
						</a>
					</div>
				</div>
			</div>

			<div class="gallery-note">
				<p><em>Note: Some satellite providers (Meteor-M2, Himawari-8) require direct integration with their respective data portals. Placeholders are shown for demonstration.</em></p>
			</div>
		</div>

		<div v-else class="no-data">
			<p>No satellite image data available</p>
		</div>
	</div>
</template>

<script>
/**
 * Satellite Gallery Component
 * Displays weather satellite imagery from multiple sources
 *
 * @props {Object} data - Satellite imagery data
 * @props {Boolean} loading - Loading state
 */
export default {
	name: 'SatelliteGallery',
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

	methods: {
		/**
		 * Format time string
		 */
		formatTime(timestamp) {
			const date = new Date(timestamp)
			return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
		},

		/**
		 * Handle image loading errors
		 */
		handleImageError(event) {
			event.target.style.display = 'none'
			const card = event.target.closest('.satellite-card')
			if (card) {
				const info = card.querySelector('.satellite-info')
				if (info) {
					info.style.display = 'block'
				}
			}
		},
	},
}
</script>

<style scoped>
.satellite-gallery-container {
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

.gallery-info {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
	margin-bottom: 20px;
	padding: 15px;
	background: #f5f5f5;
	border-radius: 8px;
	font-size: 14px;
	color: #666;
}

.gallery-info p {
	margin: 0;
}

.satellites-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	gap: 20px;
	margin-bottom: 20px;
}

.satellite-card {
	background: white;
	border: 1px solid #eee;
	border-radius: 8px;
	overflow: hidden;
	transition: all 0.3s ease;
	display: flex;
	flex-direction: column;
}

.satellite-card:hover {
	box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
	transform: translateY(-5px);
}

.satellite-header {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	padding: 15px;
	display: flex;
	justify-content: space-between;
	align-items: start;
	gap: 10px;
}

.satellite-header h3 {
	margin: 0;
	font-size: 16px;
	font-weight: 600;
}

.satellite-type {
	display: inline-block;
	background: rgba(255, 255, 255, 0.3);
	padding: 4px 12px;
	border-radius: 12px;
	font-size: 12px;
	font-weight: 600;
	white-space: nowrap;
}

.satellite-body {
	flex: 1;
	padding: 0;
	overflow: hidden;
	position: relative;
	min-height: 200px;
}

.satellite-image {
	width: 100%;
	height: 300px;
	object-fit: cover;
	display: block;
}

.satellite-info {
	padding: 15px;
	background: #f9f9f9;
	font-size: 13px;
	color: #666;
}

.description {
	margin: 0 0 10px 0;
	line-height: 1.5;
	font-style: italic;
}

.provider {
	margin: 8px 0;
}

.note {
	margin: 10px 0 0 0;
	padding: 10px;
	background: #fff3cd;
	border-left: 3px solid #ffc107;
	border-radius: 4px;
	color: #856404;
}

.satellite-footer {
	padding: 12px 15px;
	background: #f5f5f5;
	border-top: 1px solid #eee;
}

.satellite-link {
	display: inline-block;
	color: #667eea;
	text-decoration: none;
	font-weight: 600;
	font-size: 13px;
	transition: color 0.2s ease;
}

.satellite-link:hover {
	color: #764ba2;
}

.gallery-note {
	padding: 15px;
	background: #e3f2fd;
	border-left: 4px solid #2196F3;
	border-radius: 4px;
	font-size: 13px;
	color: #1565c0;
	margin-top: 20px;
}

.gallery-note p {
	margin: 0;
}

.no-data {
	text-align: center;
	padding: 40px 20px;
	color: #999;
}

/* Responsive */
@media (max-width: 768px) {
	.satellite-gallery-container {
		padding: 15px;
	}

	.satellites-grid {
		grid-template-columns: 1fr;
		gap: 15px;
	}

	.satellite-header {
		flex-direction: column;
		gap: 5px;
	}

	.satellite-image {
		height: 200px;
	}

	.satellite-info {
		padding: 10px;
	}

	.gallery-info {
		grid-template-columns: 1fr;
	}
}
</style>
