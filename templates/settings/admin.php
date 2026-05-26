<?php
/** @var array $_ */
?>
<div class="space-weather-admin-settings">
	<form id="space-weather-admin-form">
		<p class="settings-hint">Configure how the Space Weather Dashboard fetches and caches data from external sources.</p>

		<div class="form-group">
			<label for="cache-ttl">Cache TTL (seconds)</label>
			<input type="number" id="cache-ttl" name="cache_ttl" value="300" min="60" max="3600">
			<p class="settings-hint">How long to cache NOAA API responses before re-fetching.</p>
		</div>

		<div class="form-group">
			<label for="api-timeout">API Timeout (seconds)</label>
			<input type="number" id="api-timeout" name="api_timeout" value="15" min="5" max="60">
			<p class="settings-hint">Maximum time to wait for external API responses.</p>
		</div>

		<h3>Data Sources</h3>
		<div class="form-group">
			<input type="checkbox" id="enable-kp" name="enable_kp" checked>
			<label for="enable-kp">KP Index (NOAA SWPC)</label>
		</div>
		<div class="form-group">
			<input type="checkbox" id="enable-flux" name="enable_flux" checked>
			<label for="enable-flux">Solar Flux F10.7 (NOAA SWPC)</label>
		</div>
		<div class="form-group">
			<input type="checkbox" id="enable-xray" name="enable_xray" checked>
			<label for="enable-xray">X-Ray Flux (NOAA SWPC)</label>
		</div>
		<div class="form-group">
			<input type="checkbox" id="enable-aurora" name="enable_aurora" checked>
			<label for="enable-aurora">Aurora Forecast (NOAA SWPC)</label>
		</div>
		<div class="form-group">
			<input type="checkbox" id="enable-bands" name="enable_bands" checked>
			<label for="enable-bands">HF Band Conditions (HamQSL)</label>
		</div>
		<div class="form-group">
			<input type="checkbox" id="enable-drap" name="enable_drap" checked>
			<label for="enable-drap">D-RAP Absorption (NOAA SWPC)</label>
		</div>
		<div class="form-group">
			<input type="checkbox" id="enable-sdo" name="enable_sdo" checked>
			<label for="enable-sdo">SDO Solar Imagery (NASA)</label>
		</div>
		<div class="form-group">
			<input type="checkbox" id="enable-satellite" name="enable_satellite" checked>
			<label for="enable-satellite">Satellite Images (GOES/NOAA)</label>
		</div>
	</form>
</div>