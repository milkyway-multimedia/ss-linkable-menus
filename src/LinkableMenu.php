<?php
/**
 * Milkyway Multimedia
 * LinkableMenu.php
 *
 * @package milkyway-multimedia/ss-linkable-menus
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class LinkableMenu extends \DataObject implements \PermissionProvider, \TemplateGlobalProvider {
	private static $singular_name = 'Menu';

	private static $db = [
		'Title' => 'Varchar',
		'Slug' => 'Varchar',
	];

	private static $many_many = [
		'Links' => 'Link',
	];

	private static $many_many_extraFields = [
		'Links' => [
			'AltTitle' => 'Varchar',
			'AltOpenInNewWindow' => 'Boolean',
			'Sort' => 'Int',
		],
	];

	public function getCMSFields($params = []) {
		$this->beforeExtending('updateCMSFields', function(\FieldList $fields) {
			if($slug = $fields->dataFieldByName('Slug'))
				$slug->setDescription(_t(__CLASS__.'.SLUG-DESC', 'The slug of the menu must be unique. If none provided, will be automatically generated from Title.'));

			$link = singleton($this->Links()->dataClass());
			$link->allowLinkToActAsMenu($fields, null, 'Links', $this, null, $this);
			$fields->removeByName('HR-SUB_MENU');
			$fields->removeByName('HEADING-SUB_MENU');
		});

		return parent::getCMSFields($params);
	}

	protected function validate() {
		$this->beforeExtending('validate', function($result) {
			$this->validSlug($result);
		});

		return parent::validate();
	}

	protected function onBeforeWrite() {
		parent::onBeforeWrite();
		// If there is no Slug set, generate one from Title
		if(!$this->Slug && $this->Title) {
			$this->Slug = $this->generateSlug($this->Title);
		} else if($this->isChanged('Slug', 2)) {
			$this->Slug = $this->generateSlug($this->Title, false);
		}

		// Ensure that this object has a non-conflicting Slug value.
		$count = 2;
		while(!$this->validSlug()) {
			$this->Slug = preg_replace('/-[0-9]+$/', null, $this->Slug) . '-' . $count;
			$count++;
		}
	}

	public function generateSlug($title, $allowExtension = true){
		$t = \URLSegmentFilter::create()->filter($title);

		// Fallback to generic page name if path is empty (= no valid, convertable characters)
		if(!$t || $t == '-' || $t == '-1') $t = "menu-$this->ID";

		// Hook for extensions
		if($allowExtension)
			$this->extend('updateSlug', $t, $title);

		return $t;
	}

	public function validSlug($result = null) {
		$errors = [];

		if($result && $this->Slug) {
			$filters = ['Slug' => $this->Slug];
			$excludes = [];

			if($this->ID)
				$excludes['ID'] = $this->ID;

			if($this->get()->filter($filters)->exclude($excludes)->exists())
				$errors[] = _t(__CLASS__ . '.ERROR-NON_UNIQUE_SLUG', 'Slug must be unique');
		}

		if($result) {
			array_map(function($error) use($result) {
				$result->error($error);
			}, $errors);
		}

		return !count($errors);
	}

	public function canCreate($member = null)
	{
		$this->beforeExtending(__METHOD__, function($member) {
			if(!$this->checkIfHasGlobalMenuPermission($member))
				return false;
		});

		return parent::canCreate($member);
	}

	public function canEdit($member = null)
	{
		$this->beforeExtending(__METHOD__, function($member) {
			if(!$this->checkIfHasGlobalMenuPermission($member))
				return false;
		});

		return parent::canEdit($member);
	}

	public function canView($member = null)
	{
		$this->beforeExtending(__METHOD__, function($member) {
			if(!$this->checkIfHasGlobalMenuPermission($member))
				return false;
		});

		return parent::canView($member);
	}

	public function canDelete($member = null)
	{
		$this->beforeExtending(__METHOD__, function($member) {
			if(!$this->checkIfHasGlobalMenuPermission($member))
				return false;
		});

		return parent::canDelete($member);
	}

	public function providePermissions()
	{
		return [
			'MANAGE_MENU_SETS' => _t(__CLASS__.'.PERMISSION', 'Manage Menus'),
		];
	}

	public static function get_template_global_variables() {
		return [
			'MenuSet' => 'get_menu_by_slug',
		];
	}

	public static function get_menu_by_slug($slug)
	{
		return \DataList::create(__CLASS__)->filter(['Slug' => $slug])->first();
	}

	protected function checkIfHasGlobalMenuPermission($member = null) {
		return \Permission::checkMember($member, 'MANAGE_MENU_SETS');
	}
} 