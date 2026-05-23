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

        <!-- KP Index -->
        <div class="dashboard-section">
            <h2>Geomagnetic Activity</h2>
            <div class="cards-grid">
                <div class="metric-card kp-<?php p($_['kpStatus'] ?? 'unknown'); ?>">
                    <div class="metric-label">KP Index</div>
                    <div class="metric-value"><?php p(number_format($_['kpIndex'] ?? 0, 1)); ?></div>
                    <div class="metric-status"><?php p(str_replace('_', ' ', $_['kpStatus'] ?? 'Unknown')); ?></div>
                    <?php if (!empty($_['kpTimestamp'])): ?>
                    <div class="metric-time"><?php p(date('H:i', strtotime($_['kpTimestamp']))); ?></div>
                    <?php endif; ?>
                </div>

                <div class="metric-card xray-<?php p($_['xrayAlert'] ?? 'quiet'); ?>">
                    <div class="metric-label">X-Ray Flux</div>
                    <div class="metric-value"><?php p($_['xrayClass'] ?? '--'); ?></div>
                    <?php if (!empty($_['xrayIntensity'])): ?>
                    <div class="metric-sub"><?php p(sprintf('%.1e', $_['xrayIntensity'])); ?> W/m²</div>
                    <?php endif; ?>
                    <div class="metric-status"><?php p(str_replace('_', ' ', $_['xrayAlert'] ?? 'Quiet')); ?></div>
                </div>

                <div class="metric-card flux-<?php p($_['fluxStatus'] ?? 'low'); ?>">
                    <div class="metric-label">Solar Flux (F10.7)</div>
                    <div class="metric-value"><?php p(($_['solarFlux'] ?? 0) . ' sfu'); ?></div>
                    <div class="metric-status"><?php p($_['fluxStatus'] ?? 'low'); ?></div>
                    <?php if (!empty($_['fluxTimestamp'])): ?>
                    <div class="metric-time"><?php p(date('H:i', strtotime($_['fluxTimestamp']))); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Aurora Forecast -->
        <div class="dashboard-section">
            <h2>Aurora Forecast</h2>
            <div class="forecast-container">
                <img src="<?php print_unescaped(\\OCP\\Server::get(\\OCP\\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => 'aurora_north'])); ?>"
                     alt="Aurora Forecast" class="forecast-image" loading="lazy"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
                <p class="forecast-placeholder" style="display:none">Aurora image temporarily unavailable</p>
            </div>
        </div>

        <!-- HF Band Conditions -->
        <div class="dashboard-section">
            <h2>HF Band Propagation</h2>
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
            <h2>D-RAP Absorption Maps</h2>
            <div class="drap-maps-container">
                <div class="drap-map-item">
                    <h3>Global D-RAP</h3>
                    <img src="<?php print_unescaped(\\OCP\\Server::get(\\OCP\\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => 'drap_global'])); ?>"
                         alt="D-RAP Global Map" class="drap-map" loading="lazy">
                </div>
            </div>
        </div>

        <!-- SDO Solar Imagery -->
        <div class="dashboard-section">
            <h2>Solar Dynamics Observatory (SDO)</h2>
            <div class="sdo-gallery">
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
                    <img src="<?php print_unescaped(\\OCP\\Server::get(\\OCP\\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => $img['key']])); ?>"
                         alt="<?php p($img['name']); ?>" class="wavelength-image" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Satellite Images -->
        <div class="dashboard-section">
            <h2>Weather Satellite Imagery</h2>
            <div class="satellite-gallery">
                <?php
                $satImages = [
                    ['key' => 'goes16_fd', 'name' => 'GOES-16 Full Disk'],
                    ['key' => 'goes18_fd', 'name' => 'GOES-18 Full Disk'],
                ];
                foreach ($satImages as $sat):
                ?>
                <div class="satellite-card">
                    <h3><?php p($sat['name']); ?></h3>
                    <img src="<?php print_unescaped(\\OCP\\Server::get(\\OCP\\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => $sat['key']])); ?>"
                         alt="<?php p($sat['name']); ?>" class="satellite-image" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php
style('space_weather', 'style');
?>

<script>
// Simple refresh — reload the page
document.getElementById('refresh-btn').addEventListener('click', function() {
    location.reload();
});
</script>

</body>
</html>