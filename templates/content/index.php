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
                <span class="refresh-icon">&#x27F3;</span>
                <span class="refresh-text">Refresh</span>
            </button>
            <div class="last-update">
                Updated: <time id="last-update-time"><?php p($_['lastUpdate'] ?? '--:--'); ?></time>
            </div>
        </div>
    </div>

    <div class="dashboard-container">

        <!-- KP Index + Chart -->
        <div class="dashboard-section">
            <h2>Geomagnetic Activity
                <?php if (!empty($_['kpError'])): ?>
                <span class="loading-spinner" id="kp-loading" style="display:none;"></span>
                <?php endif; ?>
                <?php if (!empty($_['hasError']) && !empty($_['kpError'])): ?>
                <span style="color: #dc3545; font-size: 0.8em; margin-left: 10px;">(Error loading data)</span>
                <?php endif; ?>
            </h2>
            <div class="cards-grid">
                <div class="metric-card kp-<?php p($_['kpStatus'] ?? 'unknown'); ?> <?php if (!empty($_['kpError'])): ?>error<?php endif; ?>">
                    <div class="metric-label">KP Index</div>
                    <div class="metric-value"><?php p(number_format($_['kpIndex'] ?? 0, 1)); ?></div>
                    <div class="metric-status"><?php p(str_replace('_', ' ', $_['kpStatus'] ?? 'Unknown')); ?></div>
                    <?php if (!empty($_['kpTimestamp'])): ?>
                    <div class="metric-time"><?php p(date('H:i', strtotime($_['kpTimestamp']))); ?></div>
                    <?php endif; ?>
                </div>

                <div class="metric-card xray-<?php p($_['xrayAlert'] ?? 'quiet'); ?> <?php if (!empty($_['xrayError'])): ?>error<?php endif; ?>">
                    <div class="metric-label">X-Ray Flux</div>
                    <div class="metric-value"><?php p($_['xrayClass'] ?? '--'); ?></div>
                    <?php if (!empty($_['xrayIntensity'])): ?>
                    <div class="metric-sub"><?php p(sprintf('%.1e', $_['xrayIntensity'])); ?> W/m²</div>
                    <?php endif; ?>
                    <div class="metric-status"><?php p(str_replace('_', ' ', $_['xrayAlert'] ?? 'Quiet')); ?></div>
                </div>

                <div class="metric-card flux-<?php p($_['fluxStatus'] ?? 'low'); ?> <?php if (!empty($_['fluxError'])): ?>error<?php endif; ?>">
                    <div class="metric-label">Solar Flux (F10.7)</div>
                    <div class="metric-value"><?php p(($_['solarFlux'] ?? 0) . ' sfu'); ?></div>
                    <div class="metric-status"><?php p($_['fluxStatus'] ?? 'low'); ?></div>
                    <?php if (!empty($_['fluxTimestamp'])): ?>
                    <div class="metric-time"><?php p(date('H:i', strtotime($_['fluxTimestamp']))); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="chart-grid">
                <div class="chart-container" id="kp-chart-container">
                    <h3>KP Index — Last 12 Readings</h3>
                    <div class="chart-loading" id="kp-chart-loading">Loading chart...</div>
                    <canvas id="kp-chart" width="600" height="250"></canvas>
                </div>
                <div class="chart-container" id="flux-chart-container">
                    <h3>Solar Flux (F10.7) — Last 7 Days</h3>
                    <div class="chart-loading" id="flux-chart-loading">Loading chart...</div>
                    <canvas id="flux-chart" width="600" height="250"></canvas>
                </div>
                <div class="chart-container" id="xray-chart-container">
                    <h3>X-Ray Flare Events — Recent</h3>
                    <div class="chart-loading" id="xray-chart-loading">Loading chart...</div>
                    <canvas id="xray-chart" width="600" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Aurora Forecast -->
        <div class="dashboard-section">
            <h2>Aurora Forecast
                <?php if (!empty($_['auroraError'])): ?>
                <span class="loading-spinner" id="aurora-loading" style="display:none;"></span>
                <?php endif; ?>
                <?php if (!empty($_['hasError']) && !empty($_['auroraError'])): ?>
                <span style="color: #dc3545; font-size: 0.8em; margin-left: 10px;">(Error loading data)</span>
                <?php endif; ?>
            </h2>
            <div class="forecast-container center-image">
                <div class="image-container">
                    <img src="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => 'aurora_north'])); ?>"
                         alt="Aurora Forecast" class="forecast-image" loading="lazy"
                         onerror="this.parentNode.querySelector('.image-error').style.display='flex'; this.parentNode.querySelector('.image-loading').style.display='none'; this.classList.add('image-loaded');"
                         onload="this.parentNode.querySelector('.image-loading').style.display='none'; this.parentNode.querySelector('.image-error').style.display='none'; this.classList.add('image-loaded');">
                    <div class="image-loading">
                        <div class="loading-spinner"></div>
                        <span>Loading aurora forecast...</span>
                    </div>
                    <div class="image-error" style="display:none;">
                        <span>Failed to load aurora image</span>
                    </div>
                </div>
                <p class="forecast-placeholder" style="display:none">Aurora image temporarily unavailable</p>
            </div>
        </div>

        <!-- HF Band Conditions -->
        <div class="dashboard-section">
            <h2>HF Band Propagation
                <?php if (!empty($_['bandError'])): ?>
                <span class="loading-spinner" id="band-loading" style="display:none;"></span>
                <?php endif; ?>
                <?php if (!empty($_['hasError']) && !empty($_['bandError'])): ?>
                <span style="color: #dc3545; font-size: 0.8em; margin-left: 10px;">(Error loading data)</span>
                <?php endif; ?>
            </h2>
            <?php if (!empty($_['bandConditions']) && !isset($_['bandConditions']['error'])): ?>
            <?php $bands = $_['bandConditions']; ?>
            <div class="band-info">
                <span class="band-info-item">Solar Index: <?php p($bands['solar_index'] ?? '--'); ?></span>
                <span class="band-info-item">Sunspots: <?php p($bands['sunspot_number'] ?? '--'); ?></span>
            </div>
            <table class="band-table">
                <thead>
                    <tr>
                        <th>Band</th><th>Condition</th><th>Day</th><th>Night</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $bandData = $bands['bands'] ?? [];
                    $bandOrder = ['80m','60m','40m','30m','20m','17m','15m','12m','10m','6m','2m'];
                    foreach ($bandOrder as $bn):
                        if (!isset($bandData[$bn])) continue;
                        $b = $bandData[$bn];
                    ?>
                    <tr>
                        <td class="band-name"><?php p($b['name'] ?? $bn); ?></td>
                        <td class="band-cond band-<?php p($b['condition'] ?? 'unknown'); ?>"><?php p($b['condition'] ?? '--'); ?></td>
                        <td>
                            <div class="eff-bar" style="width:<?php p(($b['efficiency'] ?? 0) . '%'); ?>"><?php p($b['legend'] ?? '-'); ?></div>
                        </td>
                        <td><?php p(($b['efficiency'] ?? '--') . '%'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="placeholder">Band conditions temporarily unavailable</p>
            <?php endif; ?>
        </div>

        <!-- D-RAP -->
        <div class="dashboard-section">
            <h2>D-RAP Absorption Maps
                <?php if (!empty($_['hasError']) && !empty($_['kpError']) || !empty($_['hasError']) && !empty($_['fluxError'])): ?>
                <span class="loading-spinner" id="drap-loading" style="display:none;"></span>
                <?php endif; ?>
            </h2>
            <div class="drap-maps-container center-image">
                <div class="drap-map-item">
                    <h3>Global D-RAP</h3>
                    <div class="image-container">
                        <img src="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => 'drap_global'])); ?>"
                             alt="D-RAP Global Map" class="drap-map" loading="lazy"
                             onerror="this.parentNode.querySelector('.image-error').style.display='flex'; this.parentNode.querySelector('.image-loading').style.display='none'; this.classList.add('image-loaded');"
                             onload="this.parentNode.querySelector('.image-loading').style.display='none'; this.parentNode.querySelector('.image-error').style.display='none'; this.classList.add('image-loaded');">
                        <div class="image-loading">
                            <div class="loading-spinner"></div>
                            <span>Loading D-RAP map...</span>
                        </div>
                        <div class="image-error" style="display:none;">
                            <span>Failed to load D-RAP map</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SDO Solar Imagery -->
        <div class="dashboard-section">
            <h2>Solar Dynamics Observatory (SDO)
                <?php if (!empty($_['hasError'])): ?>
                <span class="loading-spinner" id="sdo-loading" style="display:none;"></span>
                <?php endif; ?>
            </h2>
            <div class="sdo-gallery center-wrapper">
                <?php
                $sdoImages = [
                    ['key' => 'sdo_193', 'name' => 'AIA 193Å (Corona)', 'desc' => 'Hot corona — 1.2 MK'],
                    ['key' => 'sdo_304', 'name' => 'AIA 304Å (Chromosphere)', 'desc' => 'Upper chromosphere — 50,000 K'],
                    ['key' => 'sdo_171', 'name' => 'AIA 171Å (Quiet Corona)', 'desc' => 'Quiet corona — 600,000 K'],
                    ['key' => 'sdo_211', 'name' => 'AIA 211Å (Active Regions)', 'desc' => 'Active regions — 2 MK'],
                    ['key' => 'sdo_magnetogram', 'name' => 'HMI Magnetogram', 'desc' => 'Magnetic field — photosphere'],
                ];
                foreach ($sdoImages as $img):
                ?>
                <div class="wavelength-card">
                    <h3><?php p($img['name']); ?></h3>
                    <p class="wavelength-desc"><?php p($img['desc']); ?></p>
                    <div class="image-container">
                        <img src="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => $img['key']])); ?>"
                             alt="<?php p($img['name']); ?>" class="wavelength-image" loading="lazy"
                             onerror="this.parentNode.querySelector('.image-error').style.display='flex'; this.parentNode.querySelector('.image-loading').style.display='none'; this.classList.add('image-loaded');"
                             onload="this.parentNode.querySelector('.image-loading').style.display='none'; this.parentNode.querySelector('.image-error').style.display='none'; this.classList.add('image-loaded');">
                        <div class="image-loading">
                            <div class="loading-spinner"></div>
                            <span>Loading...</span>
                        </div>
                        <div class="image-error" style="display:none;">
                            <span>Failed to load image</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Satellite Images -->
        <div class="dashboard-section">
            <h2>Weather Satellite Imagery
                <?php if (!empty($_['hasError'])): ?>
                <span class="loading-spinner" id="satellite-loading" style="display:none;"></span>
                <?php endif; ?>
            </h2>
            <div class="satellite-gallery center-wrapper">
                <?php
                $satImages = [
                    ['key' => 'goes16_fd', 'name' => 'GOES-16 Full Disk'],
                    ['key' => 'goes18_fd', 'name' => 'GOES-18 Full Disk'],
                ];
                foreach ($satImages as $sat):
                ?>
                <div class="satellite-card">
                    <h3><?php p($sat['name']); ?></h3>
                    <div class="image-container">
                        <img src="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => $sat['key']])); ?>"
                             alt="<?php p($sat['name']); ?>" class="satellite-image" loading="lazy"
                             onerror="this.parentNode.querySelector('.image-error').style.display='flex'; this.parentNode.querySelector('.image-loading').style.display='none'; this.classList.add('image-loaded');"
                             onload="this.parentNode.querySelector('.image-loading').style.display='none'; this.parentNode.querySelector('.image-error').style.display='none'; this.classList.add('image-loaded');">
                        <div class="image-loading">
                            <div class="loading-spinner"></div>
                            <span>Loading...</span>
                        </div>
                        <div class="image-error" style="display:none;">
                            <span>Failed to load image</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Solar Wind Prediction (WSA-ENLIL) -->
        <div class="dashboard-section">
            <h2>Solar Wind Prediction (WSA-ENLIL)
                <span class="loading-spinner" id="enlil-loading" style="display:none;"></span>
            </h2>
            <div class="center-image">
                <div class="image-container">
                    <img src="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => 'enlil'])); ?>"
                         alt="WSA-ENLIL Solar Wind Prediction" class="enlil-image" loading="lazy"
                         onerror="this.parentNode.querySelector('.image-error').style.display='flex'; this.parentNode.querySelector('.image-loading').style.display='none'; this.classList.add('image-loaded');"
                         onload="this.parentNode.querySelector('.image-loading').style.display='none'; this.parentNode.querySelector('.image-error').style.display='none'; this.classList.add('image-loaded');">
                    <div class="image-loading">
                        <div class="loading-spinner"></div>
                        <span>Loading Enlil image...</span>
                    </div>
                    <div class="image-error" style="display:none;">
                        <span>Failed to load Enlil image</span>
                    </div>
                </div>
                <p class="forecast-placeholder" style="display:none">Enlil image temporarily unavailable</p>
            </div>
        </div>

    </div>
</div>

<?php
style('space_weather', 'style');
?>

<!-- CSP-safe chart data injection -->
<script>
window.SW_CHART_DATA = {
    kp: <?php print_unescaped(json_encode($_['kpHistory'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)); ?>,
    flux: <?php print_unescaped(json_encode($_['fluxHistory'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)); ?>,
    xray: <?php print_unescaped(json_encode($_['xrayHistory'] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)); ?>
};
</script>

<script>
// Simple refresh — reload the page
document.getElementById('refresh-btn').addEventListener('click', function() {
    location.reload();
});

// Hide loading spinners after a timeout in case of stale loading states
setTimeout(function() {
    document.querySelectorAll('.loading-spinner').forEach(function(spinner) {
        spinner.style.display = 'none';
    });
}, 10000);

// ============================================================
// Canvas Chart Drawing (vanilla JS, CSP-compliant)
// ============================================================
(function() {
    'use strict';

    var data = window.SW_CHART_DATA;
    if (!data) return;

    var CHART_COLORS = {
        kp: '#667eea',
        kpBar: function(v) {
            if (v < 2) return '#4caf50';
            if (v < 4) return '#8bc34a';
            if (v < 6) return '#ffc107';
            if (v < 7) return '#ff9800';
            if (v < 8) return '#f44336';
            return '#9c27b0';
        },
        flux: '#e91e63',
        xray: '#00bcd4',
        grid: '#e0e0e0',
        text: '#666666',
        bg: '#ffffff'
    };

    function clearChart(canvasId, loadingId) {
        var loading = document.getElementById(loadingId);
        if (loading) loading.style.display = 'none';
        var canvas = document.getElementById(canvasId);
        if (!canvas) return null;
        var ctx = canvas.getContext('2d');
        var dpr = window.devicePixelRatio || 1;
        var rect = canvas.parentNode.getBoundingClientRect();
        var w = rect.width - 32; // padding
        var h = canvas.height || 250;
        canvas.width = w * dpr;
        canvas.height = h * dpr;
        canvas.style.width = w + 'px';
        canvas.style.height = h + 'px';
        ctx.scale(dpr, dpr);
        ctx.clearRect(0, 0, w, h);
        return { ctx: ctx, w: w, h: h };
    }

    function drawGrid(ctx, w, h, xLabels, yMin, yMax, yLabel) {
        var pad = { top: 20, right: 20, bottom: 50, left: 55 };
        var pw = w - pad.left - pad.right;
        var ph = h - pad.top - pad.bottom;

        // Background
        ctx.fillStyle = CHART_COLORS.bg;
        ctx.fillRect(0, 0, w, h);

        // Y-axis grid lines and labels
        var ySteps = 5;
        var yRange = yMax - yMin || 1;
        ctx.strokeStyle = CHART_COLORS.grid;
        ctx.lineWidth = 1;
        ctx.fillStyle = CHART_COLORS.text;
        ctx.font = '11px -apple-system, sans-serif';
        ctx.textAlign = 'right';
        for (var i = 0; i <= ySteps; i++) {
            var y = pad.top + (ph / ySteps) * i;
            var val = yMax - ((yRange / ySteps) * i);
            ctx.beginPath();
            ctx.moveTo(pad.left, y);
            ctx.lineTo(w - pad.right, y);
            ctx.stroke();
            ctx.fillText(val.toFixed(1), pad.left - 8, y + 4);
        }

        // Y-axis label
        if (yLabel) {
            ctx.save();
            ctx.translate(12, pad.top + ph / 2);
            ctx.rotate(-Math.PI / 2);
            ctx.textAlign = 'center';
            ctx.font = '10px -apple-system, sans-serif';
            ctx.fillText(yLabel, 0, 0);
            ctx.restore();
        }

        // X-axis labels
        ctx.textAlign = 'center';
        ctx.font = '10px -apple-system, sans-serif';
        var step = Math.max(1, Math.floor(xLabels.length / 8));
        for (var j = 0; j < xLabels.length; j++) {
            if (j % step !== 0 && j !== xLabels.length - 1) continue;
            var x = pad.left + (pw / (xLabels.length - 1 || 1)) * j;
            ctx.fillText(xLabels[j], x, h - pad.bottom + 16);
            // tick mark
            ctx.beginPath();
            ctx.moveTo(x, h - pad.bottom);
            ctx.lineTo(x, h - pad.bottom + 5);
            ctx.stroke();
        }

        return { pad: pad, pw: pw, ph: ph };
    }

    // ---- KP Index Bar Chart ----
    function drawKpChart() {
        var info = clearChart('kp-chart', 'kp-chart-loading');
        if (!info) return;
        var ctx = info.ctx, w = info.w, h = info.h;

        var raw = data.kp || [];
        if (raw.length === 0) {
            ctx.fillStyle = CHART_COLORS.text;
            ctx.font = '14px -apple-system, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('No KP data available', w / 2, h / 2);
            return;
        }

        var kpValues = raw.map(function(r) { return parseFloat(r.Kp) || 0; });
        var labels = raw.map(function(r) {
            try {
                var d = new Date(r.time_tag);
                return d.getHours() + ':00';
            } catch(e) { return ''; }
        });

        var yMax = Math.max(9, Math.ceil(Math.max.apply(null, kpValues)) + 1);
        var grid = drawGrid(ctx, w, h, labels, 0, yMax, 'KP Index');

        // Bars
        var barW = Math.max(8, (grid.pw / raw.length) * 0.7);
        for (var i = 0; i < raw.length; i++) {
            var x = grid.pad.left + (grid.pw / (raw.length - 1 || 1)) * i - barW / 2;
            var barH = (kpValues[i] / yMax) * grid.ph;
            var y = h - grid.pad.bottom - barH;
            ctx.fillStyle = CHART_COLORS.kpBar(kpValues[i]);
            ctx.fillRect(Math.round(x), Math.round(y), Math.round(barW), Math.round(barH));

            // Value label on bar
            if (barH > 18) {
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 10px -apple-system, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(kpValues[i].toFixed(1), x + barW / 2, y + barH / 2 + 4);
            }
        }
    }

    // ---- Solar Flux Line Chart ----
    function drawFluxChart() {
        var info = clearChart('flux-chart', 'flux-chart-loading');
        if (!info) return;
        var ctx = info.ctx, w = info.w, h = info.h;

        var raw = data.flux || [];
        if (raw.length === 0) {
            ctx.fillStyle = CHART_COLORS.text;
            ctx.font = '14px -apple-system, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('No flux data available', w / 2, h / 2);
            return;
        }

        var fluxValues = raw.map(function(r) { return parseFloat(r.flux) || 0; });
        var labels = raw.map(function(r) {
            try {
                var d = new Date(r.time_tag);
                return (d.getMonth() + 1) + '/' + d.getDate();
            } catch(e) { return ''; }
        });

        var fMin = Math.floor(Math.min.apply(null, fluxValues) - 5);
        var fMax = Math.ceil(Math.max.apply(null, fluxValues) + 5);
        var grid = drawGrid(ctx, w, h, labels, fMin, fMax, 's.f.u.');

        // Line
        ctx.strokeStyle = CHART_COLORS.flux;
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.beginPath();
        for (var i = 0; i < raw.length; i++) {
            var x = grid.pad.left + (grid.pw / (raw.length - 1 || 1)) * i;
            var y = h - grid.pad.bottom - ((fluxValues[i] - fMin) / (fMax - fMin)) * grid.ph;
            if (i === 0) ctx.moveTo(x, y);
            else ctx.lineTo(x, y);
        }
        ctx.stroke();

        // Area fill
        ctx.lineTo(grid.pad.left + grid.pw, h - grid.pad.bottom);
        ctx.lineTo(grid.pad.left, h - grid.pad.bottom);
        ctx.closePath();
        ctx.fillStyle = 'rgba(233, 30, 99, 0.08)';
        ctx.fill();

        // Dots
        for (var j = 0; j < raw.length; j++) {
            var dx = grid.pad.left + (grid.pw / (raw.length - 1 || 1)) * j;
            var dy = h - grid.pad.bottom - ((fluxValues[j] - fMin) / (fMax - fMin)) * grid.ph;
            ctx.beginPath();
            ctx.arc(dx, dy, 4, 0, Math.PI * 2);
            ctx.fillStyle = CHART_COLORS.flux;
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 1.5;
            ctx.stroke();
        }
    }

    // ---- X-Ray Flux Line Chart ----
    function drawXrayChart() {
        var info = clearChart('xray-chart', 'xray-chart-loading');
        if (!info) return;
        var ctx = info.ctx, w = info.w, h = info.h;

        var raw = data.xray || [];
        if (raw.length === 0) {
            ctx.fillStyle = CHART_COLORS.text;
            ctx.font = '14px -apple-system, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('No X-ray data available', w / 2, h / 2);
            return;
        }

        var xrayValues = raw.map(function(r) { return parseFloat(r.current_int_xrlong) || 0; });
        var labels = raw.map(function(r) {
            try {
                var d = new Date(r.time_tag);
                return (d.getMonth() + 1) + '/' + d.getDate() + ' ' + d.getHours() + ':00';
            } catch(e) { return '?'; }
        });

        // Use log scale for xray since values span many orders of magnitude
        var logValues = xrayValues.map(function(v) { return v > 0 ? Math.log10(v) : -9; });
        var xMin = Math.floor(Math.min.apply(null, logValues) - 0.5);
        var xMax = Math.ceil(Math.max.apply(null, logValues) + 0.5);

        // Y-axis labels in scientific notation
        var pad = { top: 20, right: 20, bottom: 55, left: 70 };
        var pw = w - pad.left - pad.right;
        var ph = h - pad.top - pad.bottom;

        ctx.fillStyle = CHART_COLORS.bg;
        ctx.fillRect(0, 0, w, h);

        // Y-axis scale labels (log)
        var ySteps = 5;
        ctx.strokeStyle = CHART_COLORS.grid;
        ctx.lineWidth = 1;
        ctx.fillStyle = CHART_COLORS.text;
        ctx.font = '11px -apple-system, sans-serif';
        ctx.textAlign = 'right';
        for (var i = 0; i <= ySteps; i++) {
            var y = pad.top + (ph / ySteps) * i;
            var logVal = xMax - ((xMax - xMin) / ySteps) * i;
            var realVal = Math.pow(10, logVal);
            ctx.beginPath();
            ctx.moveTo(pad.left, y);
            ctx.lineTo(w - pad.right, y);
            ctx.stroke();
            ctx.fillText(realVal.toExponential(1), pad.left - 8, y + 4);
        }

        // Y-axis label
        ctx.save();
        ctx.translate(16, pad.top + ph / 2);
        ctx.rotate(-Math.PI / 2);
        ctx.textAlign = 'center';
        ctx.font = '10px -apple-system, sans-serif';
        ctx.fillText('W/m²', 0, 0);
        ctx.restore();

        // X-axis labels
        ctx.textAlign = 'center';
        ctx.font = '9px -apple-system, sans-serif';
        var step = Math.max(1, Math.floor(labels.length / 5));
        for (var j = 0; j < labels.length; j++) {
            if (j % step !== 0 && j !== labels.length - 1) continue;
            var x = pad.left + (pw / (labels.length - 1 || 1)) * j;
            ctx.fillText(labels[j], x, h - pad.bottom + 14);
            ctx.beginPath();
            ctx.moveTo(x, h - pad.bottom);
            ctx.lineTo(x, h - pad.bottom + 5);
            ctx.stroke();
        }

        // Line
        ctx.strokeStyle = CHART_COLORS.xray;
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.beginPath();
        for (var k = 0; k < logValues.length; k++) {
            var lx = pad.left + (pw / (logValues.length - 1 || 1)) * k;
            var ly = h - pad.bottom - ((logValues[k] - xMin) / (xMax - xMin)) * ph;
            if (k === 0) ctx.moveTo(lx, ly);
            else ctx.lineTo(lx, ly);
        }
        ctx.stroke();

        // Dots
        for (var m = 0; m < logValues.length; m++) {
            var dx = pad.left + (pw / (logValues.length - 1 || 1)) * m;
            var dy = h - pad.bottom - ((logValues[m] - xMin) / (xMax - xMin)) * ph;
            ctx.beginPath();
            ctx.arc(dx, dy, 4, 0, Math.PI * 2);
            ctx.fillStyle = CHART_COLORS.xray;
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 1.5;
            ctx.stroke();
        }
    }

    // ---- Draw all charts ----
    function drawAllCharts() {
        drawKpChart();
        drawFluxChart();
        drawXrayChart();
    }

    // Draw on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', drawAllCharts);
    } else {
        drawAllCharts();
    }

    // Redraw on resize (debounced)
    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(drawAllCharts, 250);
    });
})();
</script>

</body>
</html>