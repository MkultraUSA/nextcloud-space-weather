<?php
script('space_weather', 'script');
style('space_weather', 'style');
?>
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

        <div class="dashboard-main">

        <div class="dashboard-section">
            <h2>Solar Wind Prediction (WSA-ENLIL)
                <span class="loading-spinner" id="enlil-loading" style="display:none;"></span>
            </h2>
            <div class="wsa-description">
                <p>Shown below is the latest forecast of conditions in the solar wind, as predicted by the WSA-Enlil model. The solar wind is a fast-moving stream of charged particles emanating from the Sun and moving outwards towards the Earth and planets. During &ldquo;fair-weather&rdquo; conditions the solar wind still contains significant variations in density and speed which originate at the solar surface and are imparted with a spiral appearance due to the Sun&rsquo;s roughly 27 day rotation.</p>
                <p>At irregular intervals the &ldquo;fair-weather&rdquo; is interrupted by major solar eruptions known as Coronal Mass Ejections (CMEs) which are propelled outwards into the background wind. Variations in the plasma density and speed within these solar storms can be much more dramatic than during quiet conditions. For both &ldquo;fair-weather&rdquo; and &ldquo;storm&rdquo; conditions, predicting the arrival at Earth of variations in the solar wind is important because these can lead to geomagnetic storms.</p>
            </div>
            <?php if (!empty($_['enlilFrames']) && $_['enlilFrameCount'] > 1): ?>
            <?php
                $enlilFrameUrl0 = \OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.get_enlil_frame', ['index' => 0]);
                $enlilFrameBase = substr($enlilFrameUrl0, 0, -1);
            ?>
            <div class="enlil-animation-container">
                <div class="enlil-animation-player" id="enlil-player">
                    <img id="enlil-anim-img"
                         src="<?php print_unescaped($enlilFrameUrl0); ?>"
                         alt="WSA-ENLIL Solar Wind Animation"
                         class="enlil-anim-image"
                         data-frame-count="<?php p($_['enlilFrameCount']); ?>"
                         data-frame-base="<?php print_unescaped($enlilFrameBase); ?>"
                         loading="lazy">
                    <div class="enlil-anim-controls">
                        <button id="enlil-anim-play" class="enlil-anim-btn" title="Play/Pause">&#9654;</button>
                        <span id="enlil-anim-info" class="enlil-anim-info">Frame 1 / <?php p($_['enlilFrameCount']); ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="enlil-static-container">
                <div class="image-container">
                    <a href="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => 'enlil'])); ?>" class="sw-image-link"><img src="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => 'enlil'])); ?>"
                         alt="WSA-ENLIL Solar Wind Prediction" class="enlil-image" loading="lazy">
                    <div class="image-loading">
                        <div class="loading-spinner"></div>
                        <span>Loading Enlil image...</span>
                    </div>
                    <div class="image-error" style="display:none;">
                        <span>Failed to load Enlil image</span>
                    </div>
                </div>
            </div>
            <p class="forecast-placeholder" style="display:none">Enlil image temporarily unavailable</p>
        </div>
        <!-- KP Index -->
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
            <div class="forecast-container">
                <div class="image-container">
                    <a href="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => 'aurora_north'])); ?>" class="sw-image-link"><img src="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => 'aurora_north'])); ?>"
                         alt="Aurora Forecast" class="forecast-image" loading="lazy">
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
                <span class="band-info-item">Solar Flux: <?php p($bands['solar_flux'] ?? '--'); ?> sfu</span>
                <span class="band-info-item">Sunspots: <?php p($bands['sunspot_number'] ?? '--'); ?></span>
                <span class="band-info-item">MUF: <?php p($bands['muf'] ?? 'N/A'); ?></span>
                <span class="band-info-item">K-Index: <?php p($bands['k_index'] ?? '--'); ?></span>
                <span class="band-info-item">X-Ray: <?php p($bands['xray'] ?? '--'); ?></span>
            </div>
            <table class="band-table">
                <thead>
                    <tr>
                        <th>Band</th><th>Time</th><th>Condition</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $bandData = $bands['bands'] ?? [];
                    $bandOrder = ['80m-40m_day','80m-40m_night','30m-20m_day','30m-20m_night','17m-15m_day','17m-15m_night','12m-10m_day','12m-10m_night'];
                    foreach ($bandOrder as $bk):
                        if (!isset($bandData[$bk])) continue;
                        $b = $bandData[$bk];
                    ?>
                    <tr>
                        <td class="band-name"><?php p($b['name'] ?? $bk); ?></td>
                        <td><?php p($b['time'] ?? '--'); ?></td>
                        <td class="band-cond band-<?php p($b['condition'] ?? 'unknown'); ?>"><?php p($b['condition'] ?? '--'); ?></td>
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
                <!-- We don't have a specific error flag for D-RAP, but we can show a general error if other services failed -->
                <span class="loading-spinner" id="drap-loading" style="display:none;"></span>
                <?php endif; ?>
            </h2>
            <div class="drap-maps-container">
                <div class="drap-map-item">
                    <h3>Global D-RAP</h3>
                    <div class="image-container">
                        <a href="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => 'drap_global'])); ?>" class="sw-image-link"><img src="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => 'drap_global'])); ?>"
                             alt="D-RAP Global Map" class="drap-map" loading="lazy">
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
                <!-- General error indicator if any service failed -->
                <span class="loading-spinner" id="sdo-loading" style="display:none;"></span>
                <?php endif; ?>
            </h2>
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
                    <div class="image-container">
                        <a href="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => $img['key']])); ?>" class="sw-image-link"><img src="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => $img['key']])); ?>"
                             alt="<?php p($img['name']); ?>" class="wavelength-image" loading="lazy">
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
                    <div class="image-container">
                        <a href="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => $sat['key']])); ?>" class="sw-image-link"><img src="<?php print_unescaped(\OCP\Server::get(\OCP\IURLGenerator::class)->linkToRoute('space_weather.image.getimage', ['key' => $sat['key']])); ?>"
                             alt="<?php p($sat['name']); ?>" class="satellite-image" loading="lazy">
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

        </div>  <!-- dashboard-main -->

        <div class="dashboard-sidebar">
            <div class="sidebar-section">
                <h3>Space Weather Facts</h3>
                <table class="fact-table">
                    <tr>
                        <td class="fact-label">KP Index</td>
                        <td class="fact-value"><?php p(number_format($_['kpIndex'] ?? 0, 1)); ?></td>
                        <td class="fact-status fact-<?php p($_['kpStatus'] ?? 'unknown'); ?>"><?php p(str_replace('_', ' ', $_['kpStatus'] ?? 'Unknown')); ?></td>
                    </tr>
                    <tr>
                        <td class="fact-label">Solar Flux</td>
                        <td class="fact-value"><?php p(($_['solarFlux'] ?? 0) . ' sfu'); ?></td>
                        <td class="fact-status fact-<?php p($_['fluxStatus'] ?? 'low'); ?>"><?php p($_['fluxStatus'] ?? 'low'); ?></td>
                    </tr>
                    <tr>
                        <td class="fact-label">X-Ray Flux</td>
                        <td class="fact-value"><?php p($_['xrayClass'] ?? '--'); ?></td>
                        <td class="fact-status fact-<?php p($_['xrayAlert'] ?? 'quiet'); ?>"><?php p(str_replace('_', ' ', $_['xrayAlert'] ?? 'Quiet')); ?></td>
                    </tr>
                    <tr>
                        <td class="fact-label">Sunspots</td>
                        <td class="fact-value"><?php p($_['bandConditions']['sunspot_number'] ?? '--'); ?></td>
                        <td class="fact-empty"></td>
                    </tr>
                </table>
                <div class="fact-footer">
                    Updated: <?php p($_['lastUpdate'] ?? '--:--'); ?> UTC
                </div>
            </div>
        </div>

</div>  <!-- dashboard-container -->
</div>  <!-- app -->
