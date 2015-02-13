<?php namespace Milkyway\SS\LinkableMenus\Cms;
/**
 * Milkyway Multimedia
 * Admin.php
 *
 * @package milkyway-multimedia/ss-linkable-menus
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class Admin extends \ModelAdmin {
	private static $managed_models = [
		'LinkableMenu',
	];

	private static $url_segment = 'menus';

	private static $menu_title = 'Menus';
} 