<?php
/**
 * Space Weather Dashboard - Application Bootstrap
 *
 * NC33+ requires an Application class implementing IBootstrap
 * for proper app registration and lifecycle management.
 *
 * @copyright Copyright (c) 2024 Kevin Watkins
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\SpaceWeather\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'space_weather';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		// Register admin settings
		$context->registerService('OCA\SpaceWeather\Settings\Admin', function($c) {
			return new \OCA\SpaceWeather\Settings\Admin();
		});
	}

	public function boot(IBootContext $context): void {
		// Application bootstrapping complete
	}
}
