<?php script('space_weather', 'script'); ?>
<?php style('space_weather', 'style'); ?>
<div id="settings-app" class="space-weather-app">
    <div class="settings-container">
        <h1>Space Weather Dashboard Settings</h1>
        
        <div class="settings-section">
            <h2>Data Sources</h2>
            <p>The Space Weather Dashboard aggregates data from multiple reputable sources:</p>
            
            <?php foreach ($_['data_sources'] as $source): ?>
            <div class="data-source-card">
                <h3><?php p($source['name']); ?></h3>
                <p><a href="<?php p($source['url']); ?>" target="_blank" rel="noopener noreferrer"><?php p($source['url']); ?></a></p>
                <p><?php p($source['description']); ?></p>
                <h4>API Endpoints:</h4>
                <ul>
                    <?php foreach ($source['endpoints'] as $name => $endpoint): ?>
                    <li><strong><?php p($name); ?>:</strong> <code><?php p($endpoint); ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="settings-section">
            <h2>Application Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Name:</strong> <span><?php p($_['app_info']['name']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Version:</strong> <span><?php p($_['app_info']['version']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Author:</strong> <span><?php p($_['app_info']['author']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Contact:</strong> <span><a href="mailto:<?php p($_['app_info']['author_email']); ?>"><?php p($_['app_info']['author_email']); ?></a></span>
                </div>
                <div class="info-item">
                    <strong>License:</strong> <span><?php p($_['app_info']['license']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Website:</strong> <span><a href="<?php p($_['app_info']['website']); ?>" target="_blank" rel="noopener noreferrer"><?php p($_['app_info']['website']); ?></a></span>
                </div>
                <div class="info-item">
                    <strong>Issue Tracker:</strong> <span><a href="<?php p($_['app_info']['bugs']); ?>" target="_blank" rel="noopener noreferrer"><?php p($_['app_info']['bugs']); ?></a></span>
                </div>
            </div>
            <p><?php p($_['app_info']['description']); ?></p>
        </div>
        
        <div class="settings-section">
            <h2>Feature Requests & Feedback</h2>
            <p>We welcome your suggestions for improving the Space Weather Dashboard! If you have ideas for new features, improvements, or notice any issues, please let us know.</p>
            
            <form id="feature-request-form" method="POST" action="<?php p($_['requestToken']); ?>">
                <div class="form-group">
                    <label for="feature-title">Feature Request Title:</label>
                    <input type="text" id="feature-title" name="title" required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="feature-description">Description:</label>
                    <textarea id="feature-description" name="description" rows="4" required maxlength="500"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="feature-type">Type:</label>
                    <select id="feature-type" name="type">
                        <option value="new_feature">New Feature</option>
                        <option value="improvement">Improvement</option>
                        <option value="bug_fix">Bug Fix</option>
                        <option value="data_source">New Data Source</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <button type="submit" class="primary-button">Submit Feature Request</button>
            </form>
            
            <div id="form-response" class="form-response" style="display: none;"></div>
        </div>
        
        <div class="settings-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php p($_['app_info']['author']); ?>. All rights reserved.</p>
        </div>
    </div>
</div>