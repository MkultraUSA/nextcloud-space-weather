<?php
/**
 * @copyright Copyright (c) 2024 Kevin Watkins
 * @license AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\SpaceWeather\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

/**
 * Admin settings for Space Weather Dashboard
 *
 * Provides configurable cache TTL, API timeout, and data source toggles
 * visible in Nextcloud's admin settings panel.
 */
class Admin implements ISettings {

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		return new TemplateResponse('space_weather', 'settings/admin');
	}

	/**
	 * @return string Section ID this settings form appears in
	 */
	public function getSection(): string {
		return 'space_weather';
	}

	/**
	 * @return int Priority within the section (lower = higher in list)
	 */
	public function getPriority(): int {
		return 10;
	}
}