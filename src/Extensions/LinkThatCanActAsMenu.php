<?php namespace Milkyway\SS\LinkableMenus\Extensions;
/**
 * Milkyway Multimedia
 * LinkThatCanActAsMenu.php
 *
 * @package milkyway-multimedia/ss-linkable-menus
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class LinkThatCanActAsMenu extends \DataExtension
{
	const OPEN_IN_NEW__FALSE = 2;
	const OPEN_IN_NEW__TRUE = 1;
	const OPEN_IN_NEW__INHERIT = 0;

	private static $many_many = [
		'MenuLinks' => 'Link',
	];

	private static $belongs_many_many = [
		'MenusLinkedTo' => 'Link',
	];

	private static $many_many_extraFields = [
		'MenuLinks' => [
			'AltTitle' => 'Varchar',
			'AltOpenInNewWindow' => 'Int(1)',
			'Sort' => 'Int',
		],
	];

	public function updateCMSFields(\FieldList $fields)
	{
		$fields->removeByName('MenuLinks');
		$fields->removeByName('MenusLinkedTo');

		if($this->owner->config()->always_use_selection_group)
			$this->updateFields($fields);
	}

	public function updateFrontEndFields(\FieldList $fields) {
		if($this->owner->config()->always_use_selection_group)
			$this->updateFields($fields);
	}

	public function EditableColumnsForMenuLinks($grid = null) {
		$editableColumns = [
			'AltTitle'       => [
				'title'    => _t('Linkable.MENU_LABEL', 'Menu Label'),
				'callback' => function ($record, $col) {
						return \TextField::create($col, _t('Linkable.MENU_LABEL', 'Menu Label'))->setAttribute('placeholder', $record->Title);
					},
			],
			'LinkType' => [
				'title'    => _t('Linkable.TYPE', 'Type'),
				'callback' => function ($record, $col) {
						return \ReadonlyField::create($col);
					},
			],
			'LinkURL'   => [
				'title'    => _t('Linkable.URL', 'URL'),
				'callback' => function ($record, $col) {
						return \ReadonlyField::create($col);
					},
			],
			'AltOpenInNewWindow'       => [
				'title'    => _t('Linkable.OPEN_IN_NEW_WINDOW', 'Open in new window?'),
				'callback' => function ($record, $col) {
						return \DropdownField::create($col, $col, [
							self::OPEN_IN_NEW__INHERIT => '(Inherit: ' . $record->obj('OpenInNewWindow')->Nice() . ')',
							self::OPEN_IN_NEW__FALSE => _t('NO', 'No'),
							self::OPEN_IN_NEW__TRUE => _t('YES', 'Yes'),
						]);
					},
			],
		];

		$this->owner->extend('updateEditableColumnsForMenuLinks', $editableColumns, $grid);

		return $editableColumns;
	}

	public function MenuLinks() {
		return $this->owner->getManyManyComponents('MenuLinks')->sort('Sort', 'ASC');
	}

	public function getRelativeLink() {
		return !$this->owner->Type ? '#' : null;
	}

	public function getLinkingMode() {
		return $this->owner->SiteTree()->exists() ? $this->owner->SiteTree()->LinkingMode() : $this->owner->Type ? strtolower($this->owner->Type) : 'none';
	}

	public function allowLinkToActAsMenu($fields, $form = null, $relation = 'MenuLinks', $parent = null, $controller = null, $item = null)
	{
		if(!$this->owner->config()->always_use_selection_group)
			$this->updateFields($fields);

		if(!$item)
			$item = $this->owner;

		$fields->removeByName($relation);

		if(!($typeField = $fields->fieldByName('Root.Main.Type')) || !($typeField instanceof \TabbedSelectionGroup)) {
			$fields->addFieldsToTab('Root.Main', [
				$fieldsForForm[] = \HeaderField::create('HEADING-LINK_TO', _t('Link.LINK_TO', 'Link to:'), 3),
				$fieldsForForm[] = \FormMessageField::create('MSG-LINK_TO', _t('Link.MSG-LINK_TO', 'When as user clicks on this link in the menu, where does it go?'), 'info')->cms(),
			], 'Type');
		}

		if($item->exists()) {
			$fieldsForForm[] = $links = \GridField::create($relation, _t('Link.LINKS', 'Links'), $item->$relation(),
				$config = \GridFieldConfig_RecordEditor::create()
					->addComponent(
						new \GridFieldDeleteAction(true),
						'GridFieldDeleteAction'
					)
					->addComponent(
						new \Milkyway\SS\GridFieldUtils\AddNewInlineExtended('buttons-before-left', _t('GridFieldExtensions.QUICK_ADD', 'Quick Add')),
						'GridFieldToolbarHeader'
					)
					->addComponent(
						new \GridFieldTitleHeader()
					)
					->removeComponentsByType('GridFieldFilterHeader')
					->removeComponentsByType('GridFieldSortableHeader')
					->removeComponentsByType('GridFieldPageCount')
					->removeComponentsByType('GridFieldPaginator')
					->removeComponentsByType('GridFieldDataColumns')
					->addComponent(
						$ec = new \GridFieldEditableColumns(),
						'GridFieldEditButton'
					)
					->addComponent(
						new \Milkyway\SS\GridFieldUtils\EditableRow(),
						'GridFieldEditableColumns'
					)
					->addComponents(
						new \GridFieldOrderableRows(),
						new \GridFieldAddExistingSearchButton('buttons-before-right')
					)
			);

			$ec->setDisplayFields(singleton($item->$relation()->dataClass())->EditableColumnsForMenuLinks($links));

			if($detailForm = $config->getComponentByType('GridFieldDetailForm')) {
				$detailForm->setItemEditFormCallback(function($form, $controller)use($item){
					if (isset($controller->record))
						$record = $controller->record;
					elseif ($form->Record)
						$record = $form->Record;
					else
						$record = null;

					if($record && $record instanceof \Link)
						$record->allowLinkToActAsMenu($form->Fields(), $form, 'MenuLinks', $item, $controller);
				});
			}
		}
		else {
			$fieldsForForm[] = $links = \FormMessageField::create('ERR-SAVE_FIRST', _t('LinkableMenus.SAVE_FIRST', 'Please save before adding any sub-menu links'), 'warning')->cms();
		}

		$fields->addFieldsToTab('Root.Main', [
			$fieldsForForm[] = \LiteralField::create('HR-SUB_MENU', '<p><br /></p>'),
			$fieldsForForm[] = \HeaderField::create('HEADING-SUB_MENU', _t('Link.SUB_MENU', 'Sub-menu'), 3),
		    $links
		]);

		if($form) {
			foreach($fieldsForForm as $field)
				$field->setForm($form);
		}

		$this->owner->extend('updateLinkThatCanActAsMenuFields', $fields, $form, $relation, $parent, $controller, $item);
	}

	public function setFile_OpenInNewWindow($value) {
		if($this->owner->Type == 'File')
			$this->owner->OpenInNewWindow = $value;
	}

	public function setSiteTree_OpenInNewWindow($value) {
		if($this->owner->Type == 'SiteTree')
			$this->owner->OpenInNewWindow = $value;
	}

	/**
	 * Display Logic Form Fields does not place nice for GridFields, so I have made it use SelectionGroup instead
	 * @param \FieldList $fields
	 */
	protected function updateFields(\FieldList $fields) {
		$this->clearFieldList($fields);

		$types = [
			\SelectionGroup_Item::create('', \CompositeField::create(), _t('Linkable.TYPENONE', 'None'))->setName('Type_None'),
		];

		foreach ((array)$this->owner->config()->types as $type => $label) {
			$types[$type] = \SelectionGroup_Item::create(
				$type,
				$this->getFormFieldsForType($type),
				_t('Linkable.TYPE'.strtoupper($type), $label)
			)->setName('Type_' . $type);
		}

		$type = \ClassInfo::exists('TabbedSelectionGroup') ? 'TabbedSelectionGroup' : 'SelectionGroup';

		if($fields->fieldByName('Root'))
			$fields->addFieldsToTab('Root.Main', $typeField = \Object::create($type, 'Type', $types));
		else
			$fields->push($typeField = \Object::create($type, 'Type', $types));

		if($type == 'TabbedSelectionGroup')
			$typeField->showAsDropdown(true)->setLabelTab(_t('Link.LINK_TO', 'Link to:'));
	}

	protected function clearFieldList(\FieldList $fields) {
		foreach($fields as $field) {
			if($field instanceof \CompositeField)
				$this->clearFieldList($field->FieldList());

			if(!$field->Name || !in_array($field->Name, ['Type', 'URL', 'Email', 'File', 'OpenInNewWindow', 'SiteTreeID', 'Anchor', 'SiteTree_OpenInNewWindow'])) continue;

			$fields->removeByName($field->Name);
			if($field->hasExtension('DisplayLogicFormField'))
				$field->setDisplayLogicCriteria(null);
		}
	}

	protected function getFormFieldsForType($type) {
		switch($type) {
			case 'URL':
				return \CompositeField::create(
					\TextField::create('URL', _t('Linkable.URL', 'URL')),
					\CheckboxField::create('OpenInNewWindow', _t('Linkable.OpenInNewWindow', 'Open link in a new window'))
				);
			case 'Email':
				return \CompositeField::create(
					\TextField::create('Email', _t('Linkable.Email', 'Email'))
				);
			case 'File':
				return \CompositeField::create(
					\UploadField::create('File', _t('Linkable.FILE', 'File'), 'File', 'ID', 'Title')->setConfig('canUpload', false),
					\CheckboxField::create('File_OpenInNewWindow', _t('Linkable.OpenInNewWindow', 'Open link in a new window'))
				);
			case 'SiteTree':
				return \CompositeField::create(
					\TreeDropdownField::create('SiteTreeID', _t('Linkable.PAGE', 'Page'), 'SiteTree'),
					\TextField::create('Anchor', _t('Linkable.ANCHOR', 'Anchor'))
						->setDescription(_t('Linkable.DESC-ANCHOR', 'Include # at the start of your anchor name')),
					\CheckboxField::create('SiteTree_OpenInNewWindow', _t('Linkable.OpenInNewWindow', 'Open link in a new window'))
				);
			default:
				return \CompositeField::create();
		}
	}
} 