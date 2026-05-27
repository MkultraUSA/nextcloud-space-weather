<?php
/** @var array $_ */
?><style>
.space-weather-admin-settings {
	padding-left: 24px;
}
.space-weather-admin-settings h3 {
	margin-top: 20px;
	margin-bottom: 8px;
}
.space-weather-admin-settings .settings-hint {
	margin-block-start: 8px;
	margin-block-end: 12px;
	font-size: 0.85em;
	color: var(--color-text-maxcontrast, #999);
}
</style>
<div class="space-weather-admin-settings">
	<h3>Data Sources</h3>
	<p>The Space Weather Dashboard aggregates data from these sources:</p>
	<ul>
		<li><a href="https://www.swpc.noaa.gov/" target="_blank">NOAA Space Weather Prediction Center (SWPC)</a> — KP Index, Solar Flux, X-Ray Flux, Aurora Forecast, D-RAP Absorption</li>
		<li><a href="https://sdo.gsfc.nasa.gov/" target="_blank">NASA Solar Dynamics Observatory (SDO)</a> — Solar imagery at 6 wavelengths</li>
		<li><a href="https://www.hamqsl.com/solarxml.php" target="_blank">HamQSL.com</a> — HF band propagation conditions</li>
		<li><a href="https://www.star.nesdis.noaa.gov/GOES/" target="_blank">NOAA GOES Satellites</a> — Weather satellite imagery</li>
	</ul>

	<h3>Cache Configuration</h3>
	<form id="space-weather-admin-form">
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

		<div class="form-group">
			<label for="enable-kp">
				<input type="checkbox" id="enable-kp" name="enable_kp" checked>
				KP Index (NOAA SWPC)
			</label>
		</div>
		<div class="form-group">
			<label for="enable-flux">
				<input type="checkbox" id="enable-flux" name="enable_flux" checked>
				Solar Flux F10.7 (NOAA SWPC)
			</label>
		</div>
		<div class="form-group">
			<label for="enable-xray">
				<input type="checkbox" id="enable-xray" name="enable_xray" checked>
				X-Ray Flux (NOAA SWPC)
			</label>
		</div>
		<div class="form-group">
			<label for="enable-aurora">
				<input type="checkbox" id="enable-aurora" name="enable_aurora" checked>
				Aurora Forecast (NOAA SWPC)
			</label>
		</div>
		<div class="form-group">
			<label for="enable-bands">
				<input type="checkbox" id="enable-bands" name="enable_bands" checked>
				HF Band Conditions (HamQSL)
			</label>
		</div>
		<div class="form-group">
			<label for="enable-drap">
				<input type="checkbox" id="enable-drap" name="enable_drap" checked>
				D-RAP Absorption (NOAA SWPC)
			</label>
		</div>
		<div class="form-group">
			<label for="enable-sdo">
				<input type="checkbox" id="enable-sdo" name="enable_sdo" checked>
				SDO Solar Imagery (NASA)
			</label>
		</div>
		<div class="form-group">
			<label for="enable-satellite">
				<input type="checkbox" id="enable-satellite" name="enable_satellite" checked>
				Satellite Images (GOES/NOAA)
			</label>
		</div>
	</form>

	<h3>About</h3>
	<p>Version 1.0.0 &mdash; by Kevin Watkins &mdash; <a href="https://github.com/MkultraUSA/nextcloud-space-weather" target="_blank">GitHub</a></p>
	<p>Real-time space weather monitoring with NOAA, NASA, and HamQSL data.</p>
</div>
