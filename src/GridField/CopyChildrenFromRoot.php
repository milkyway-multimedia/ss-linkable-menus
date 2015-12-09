<?php namespace Milkyway\SS\LinkableMenus\GridField;

/**
 * Milkyway Multimedia
 * CopyChildrenFromRoot.php
 *
 * @package milkyway-multimedia/ss-linkable-menus
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use ArrayData;
use Controller;

class CopyChildrenFromRoot implements \GridField_HTMLProvider, \GridField_ActionProvider
{
	protected $template;

	protected $urlSegment = 'copy-children-from-root';

	public function __construct($targetFragment = 'before', $buttonName = '')
	{
		$this->targetFragment = $targetFragment;
		$this->buttonName = $buttonName;
	}

	public function getHTMLFragments($gridField)
	{
		$singleton = singleton($gridField->getModelClass());

		if (!$singleton->canCreate() || !singleton('SiteTree')->canCreate()) {
			return [];
		}

		if (!$this->buttonName) {
			// provide a default button name, can be changed by calling {@link setButtonName()} on this component
			$this->buttonName = _t('Link.COPY_CHILDREN_FROM_ROOT', 'Copy children from root');
		}

		return [
			$this->targetFragment =>
				\GridField_FormAction::create($gridField, 'CopyChildrenFromRoot-' . \Convert::raw2att($this->urlSegment), $this->buttonName, $this->urlSegment, [])
					->addExtraClass('gridfield-button-copyChildrenFromRoot')
					->setAttribute('title', $this->buttonName)
					->setDescription($this->buttonName)
					->Field(),
		];
	}

	public function getActions($gridField)
	{
		return [$this->urlSegment];
	}

	public function handleAction(\GridField $gridField, $actionName, $arguments, $data)
	{
		if ($actionName != $this->urlSegment) {
			return;
		}

		$root = \SiteTree::get()->filter('ParentID', 0);

		if (!$root->exists()) {
			throw new \ValidationException(_t('Link.NO_PAGES', 'No pages available'), 0);
		}

		$item = singleton($gridField->getModelClass());

		if (!$item->canCreate()) {
			throw new \ValidationException(_t('Link.CANNOT_CREATE', 'You cannot create a Link'), 0);
		}

		foreach ($root as $page) {
			$link = $item->create();
			$link->Type = 'SiteTree';
			$link->SiteTreeID = $page->ID;
			$link->write();
			$gridField->getList()->add($link);
		}
	}
}
