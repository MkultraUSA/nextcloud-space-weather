<?php
/**
 * @copyright Copyright (c) 2024 Kevin Watkins
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\SpaceWeather\Settings;

use OCP\Settings\IIconSection;

/**
 * Admin settings section for Space Weather Dashboard
 *
 * Adds a "Space Weather Dashboard" section to Nextcloud's admin settings navigation.
 */
class AdminSection implements IIconSection {

	/**
	 * @return string Unique section ID
	 */
	public function getID(): string {
		return 'space_weather';
	}

	/**
	 * @return string Display name in the admin settings sidebar
	 */
	public function getName(): string {
		return 'Space Weather Dashboard';
	}

	/**
	 * @return int Sort order (lower values appear higher in the sidebar)
	 */
	public function getPriority(): int {
		return 75;
	}

	/**
	 * @return string Path to the icon relative to the app directory
	 */
	public function getIcon(): string {
		return '/apps/space_weather/img/app-dark.svg';
	}
}