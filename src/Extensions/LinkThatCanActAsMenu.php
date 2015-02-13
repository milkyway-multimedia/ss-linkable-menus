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
	private static $many_many = [
		'MenuLinks' => 'Link',
	];

	private static $belongs_many_many = [
		'MenusLinkedTo' => 'Link',
	];

	private static $many_many_extraFields = [
		'MenuLinks' => [
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

	public function allowLinkToActAsMenu($fields, $form = null, $relation = 'MenuLinks', $parent = null, $controller = null, $item = null)
	{
		if(!$this->owner->config()->always_use_selection_group)
			$this->updateFields($fields);

		if(!$item)
			$item = $this->owner;

		$fields->removeByName($relation);

		if(($typeField = $fields->fieldByName('Root.Main.Type')) && ($typeField instanceof \TabbedSelectionGroup)) {
			$typeField->setLabelTab(_t('Link.LINK_TO', 'Link to:'));
		}
		else {
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
			$fieldsForForm[] = \HorizontalRuleField::create('HR-SUB_MENU')->invisible(),
			$fieldsForForm[] = \HeaderField::create('HEADING-SUB_MENU', _t('Link.SUB_MENU', 'Sub-menu'), 3),
		    $links
		]);

		if($form) {
			foreach($fieldsForForm as $field)
				$field->setForm($form);
		}
	}

	public function setFile_OpenInNewWindow($value) {
		$this->owner->OpenInNewWindow = $value;
	}

	public function setSiteTree_OpenInNewWindow($value) {
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
			$fields->addFieldsToTab('Root.Main', \Object::create($type, 'Type', $types));
		else
			$fields->push(\Object::create($type, 'Type', $types));
	}

	protected function clearFieldList(\FieldList $fields) {
		foreach($fields as $field) {
			if($field instanceof \CompositeField)
				$this->clearFieldList($field->FieldList());

			if(in_array($field->Name, ['Root', 'Main', 'Title'])) continue;

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