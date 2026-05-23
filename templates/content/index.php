<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Space Weather Dashboard</title>
</head>
<body>
<div id="app" class="space-weather-app">

    <div class="app-header">
        <h1>Space Weather Dashboard</h1>
        <div class="header-controls">
            <button id="refresh-btn" class="refresh-button" title="Refresh all data">
                <span class="refresh-icon">⟳</span>
                <span class="refresh-text">Refresh</span>
            </button>
            <div class="loading-spinner" id="loading-spinner" style="display:none">
                <span class="spinner"></span>
            </div>
            <div class="last-update">
                Updated: <time id="last-update-time">--:--</time>
            </div>
        </div>
    </div>

    <div class="dashboard-container">

        <!-- Real-time Monitoring Cards -->
        <div class="dashboard-section">
            <h2>Geomagnetic Activity</h2>
            <div class="cards-grid">
                <div id="kp-card"></div>
                <div id="xray-card"></div>
                <div id="flux-card"></div>
            </div>
        </div>

        <!-- Aurora Forecast -->
        <div class="dashboard-section">
            <h2>Aurora Forecast</h2>
            <div id="aurora-forecast" class="forecast-container"></div>
        </div>

        <!-- HF Band Conditions -->
        <div class="dashboard-section">
            <h2>HF Band Propagation</h2>
            <div id="band-conditions"></div>
        </div>

        <!-- D-RAP Absorption Maps -->
        <div class="dashboard-section">
            <h2>D-RAP Absorption Maps</h2>
            <div id="drap-maps" class="drap-maps-container"></div>
        </div>

        <!-- SDO Solar Imagery -->
        <div class="dashboard-section">
            <h2>Solar Dynamics Observatory (SDO)</h2>
            <div id="sdo-gallery" class="sdo-gallery"></div>
        </div>

        <!-- Weather Satellite Gallery -->
        <div class="dashboard-section">
            <h2>Weather Satellite Imagery</h2>
            <div id="satellite-gallery" class="satellite-gallery"></div>
        </div>

        <!-- Error Messages -->
        <div id="errors-container" class="errors-container"></div>

    </div>
</div>

<?php
// CSP-safe asset loading via Nextcloud helpers
style('space_weather', 'style');
script('space_weather', 'app');
?>

</body>
</html>
