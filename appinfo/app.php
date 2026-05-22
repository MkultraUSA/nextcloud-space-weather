<?php
/**
 * @copyright Copyright (c) 2024 Kevin Watkins
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\SpaceWeather\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Space Weather Dashboard Application
 *
 * Main app initialization and bootstrap
 */
class Application extends App implements IBootstrap {
	public const APP_ID = 'space_weather';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		// Register services, events, hooks here if needed
	}

	public function boot(IBootContext $context): void {
		// Bootstrap app after Nextcloud is fully loaded
	}
}
