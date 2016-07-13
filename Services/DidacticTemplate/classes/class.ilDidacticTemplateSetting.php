<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Settings for a single didactic template
 *
 * @author Stefan Meyer <meyer@leifos.com>
 * @defgroup ServicesDidacticTemplate
 */
class ilDidacticTemplateSetting
{
	const TYPE_CREATION = 1;


	private $id = 0;
	private $enabled = false;
	private $title = '';
	private $description = '';
	private $info = '';
	private $type = self::TYPE_CREATION;
	private $assignments = array();
	private $effective_from = array();


	/**
	 * Constructor
	 * @param int $a_id
	 */
	public function __construct($a_id = 0)
	{
		$this->setId($a_id);
		$this->read();
	}

	/**
	 * Set id
	 * @param int $a_id 
	 */
	protected function setId($a_id)
	{
		$this->id = $a_id;
	}

	/**
	 * Get id
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set enabled status
	 * @param bool $a_status
	 */
	public function enable($a_status)
	{
		$this->enabled = $a_status;
	}

	/**
	 * Check if template is enabled
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}

	/**
	 * Set title
	 * @param string $a_title
	 */
	public function setTitle($a_title)
	{
		$this->title = $a_title;
	}

	/**
	 * Get title
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * Get description
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set description
	 * @param string $a_description
	 */
	public function setDescription($a_description)
	{
		$this->description = $a_description;
	}

	/**
	 * Set installation info text
	 * @param string $a_info 
	 */
	public function setInfo($a_info)
	{
		$this->info = $a_info;
	}

	/**
	 * Get installation info text
	 * @return string
	 */
	public function getInfo()
	{
		return $this->info;
	}

	/**
	 * Set type
	 * @param int $a_type
	 */
	public function setType($a_type)
	{
		$this->type = $a_type;
	}

	/**
	 * Get type
	 * @return int
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * Set assignments
	 * @param array $a_ass 
	 */
	public function setAssignments(Array $a_ass)
	{
		$this->assignments = (array) $a_ass;
	}

	/**
	 * Get object assignemnts
	 * @return array
	 */
	public function getAssignments()
	{
		return (array) $this->assignments;
	}

	/**
	 * Add one assignment obj type
	 * @param string $a_obj_type 
	 */
	public function addAssignment($a_obj_type)
	{
		$this->assignments[] = $a_obj_type;
	}

	/**
	 * @return int[]
	 */
	public function getEffectiveFrom()
	{
		return $this->effective_from;
	}

	/**
	 * @param int[] $effective_from
	 */
	public function setEffectiveFrom($effective_from)
	{
		$this->effective_from = $effective_from;
	}
	
	/**
	 * get all translations from this object
	 *
	 * @access	public
	 * @return	array
	 */
	function getTranslations()
	{
		$trans = $this->getTranslationObject();
		$lang = $trans->getLanguages();

		foreach($lang as $k => $v)
		{
			if($v['lang_default'])
			{
				$lang[0] = $lang[$k];
			}

		}

		// fallback if translation object is empts
		if(!isset($lang[0]))
		{
			$lang[0]['title'] = $this->getTitle();
			$lang[0]['description'] = $this->getDescription();
		}

		return $lang;
	}

	/**
	 * Delete settings
	 */
	public function delete()
	{
		global $ilDB;

		// Delete settings
		$query = 'DELETE FROM didactic_tpl_settings '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$ilDB->manipulate($query);

		// Delete obj assignments
		$query = 'DELETE FROM didactic_tpl_sa '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$ilDB->manipulate($query);

		include_once './Services/DidacticTemplate/classes/class.ilDidacticTemplateActionFactory.php';
		foreach (ilDidacticTemplateActionFactory::getActionsByTemplateId($this->getId()) as $action)
		{
			$action->delete();
		}

		include_once './Services/DidacticTemplate/classes/class.ilDidacticTemplateObjSettings.php';
		ilDidacticTemplateObjSettings::deleteByTemplateId($this->getId());

		$this->getTranslationObject()->delete();
		$this->deleteEffectiveNodes();
		return true;
	}

	/**
	 * Save settings
	 */
	public function save()
	{
		global $ilDB;

		$this->setId($ilDB->nextId('didactic_tpl_settings'));

		$query = 'INSERT INTO didactic_tpl_settings (id,enabled,title,description,info,type) '.
			'VALUES( '.
			$ilDB->quote($this->getId(),'integer').', '.
			$ilDB->quote($this->isEnabled(),'integer').', '.
			$ilDB->quote($this->getTitle(),'text').', '.
			$ilDB->quote($this->getDescription(),'text').', '.
			$ilDB->quote($this->getInfo(),'text').', '.
			$ilDB->quote($this->getType(),'integer').
			')';

		$ilDB->manipulate($query);

		$this->saveAssignments();
		
		$trans = $this->getTranslationObject();
		$trans->addLanguage($trans->getDefaultLanguage(),$this->getTitle(),$this->getDescription(),true);

		return true;
	}

	/**
	 * Save assignments in DB
	 * @return bool
	 */
	private function saveAssignments()
	{
		foreach($this->getAssignments() as $ass)
		{
			$this->saveAssignment($ass);
		}
		return true;
	}

	/**
	 * Add one object assignment
	 * @global ilDB $ilDB
	 * @param string $a_obj_type 
	 */
	private function saveAssignment($a_obj_type)
	{
		global $ilDB;

		$query = 'INSERT INTO didactic_tpl_sa (id,obj_type) '.
			'VALUES( '.
			$ilDB->quote($this->getId(),'integer').', '.
			$ilDB->quote($a_obj_type,'text').
			')';
		$ilDB->manipulate($query);
	}

	/**
	 * 
	 */
	protected function saveEffectiveNodes()
	{
		global $ilDB;
		
		if(!count($this->getEffectiveFrom()))
		{
			return;
		}
		
		foreach($this->getEffectiveFrom() as $node)
		{
			$values[] = '( '.
			$ilDB->quote($this->getId(),'integer').', '.
			$ilDB->quote($node,'integer').
			')';
		}
		
		$query = 'INSERT INTO didactic_tpl_en (id,node) '.
			'VALUES ' . implode(', ' , $values);
		
		$ilDB->manipulate($query);
	}
	
	protected function deleteEffectiveNodes()
	{
		global $ilDB;

		$query = 'DELETE FROM didactic_tpl_en '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$ilDB->manipulate($query);
		return true;
	}
	
	protected function readEffectiveNodes()
	{
		global $ilDB;
		$effective_nodes = array();
		
		$query = 'SELECT * FROM didactic_tpl_en '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$effective_nodes[] = $row->node;
		}
		
		$this->setEffectiveFrom($effective_nodes);
	}

	/**
	 * Delete assignments
	 * @global ilDB $ilDB
	 * @return bool
	 */
	private function deleteAssignments()
	{
		global $ilDB;

		$query = 'DELETE FROM didactic_tpl_sa '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$ilDB->manipulate($query);
		return true;
	}

	/**
	 * Update settings
	 * @global ilDB $ilDB
	 */
	public function update()
	{
		global $ilDB;

		$query = 'UPDATE didactic_tpl_settings '.
			'SET '.
			'enabled = '.$ilDB->quote($this->isEnabled(),'integer').', '.
			'title = '.$ilDB->quote($this->getTitle(),'text').', '.
			'description = '.$ilDB->quote($this->getDescription(),'text').', '.
			'info = '.$ilDB->quote($this->getInfo(),'text').', '.
			'type = '.$ilDB->quote($this->getType(),'integer').' '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$ilDB->manipulate($query);
		$this->deleteAssignments();
		$this->saveAssignments();
		
		$this->deleteEffectiveNodes();
		$this->saveEffectiveNodes();
		
		$trans = $this->getTranslationObject();
		
		$trans->addLanguage($trans->getDefaultLanguage(),$this->getTitle(),$this->getDescription(),true, true);
		$trans->save();

		return true;
	}

	/**
	 * read settings from db
	 * @return bool
	 */
	protected function read()
	{
		global $ilDB;

		if(!$this->getId())
		{
			return false;
		}

		/**
		 * Read settings
		 */
		$query = 'SELECT * FROM didactic_tpl_settings dtpl '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$this->setType($row->type);
			$this->enable($row->enabled);
			$this->setTitle($row->title);
			$this->setDescription($row->description);
			$this->setInfo($row->info);

		}

		/**
		 * Read assigned objects
		 */
		$query = 'SELECT * FROM didactic_tpl_sa '.
			'WHERE id = '.$ilDB->quote($this->getId(),'integer');
		$res = $ilDB->query($query);
		while($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT))
		{
			$this->addAssignment($row->obj_type);
		}
		
		$this->readEffectiveNodes();
		
		$trans = $this->getTranslationObject();
		$lang = $trans->getLanguages();
		$lang_key = $trans->getDefaultLanguage();

		if($lang[$lang_key]['title'])
		{
			$this->setTitle($lang[$lang_key]['title']);
			$this->setDescription($lang[$lang_key]['description']);
		}

		return true;
	}

	/**
	 * Export
	 * @param ilXmlWriter $writer
	 * @return ilXmlWriter
	 */
	public function toXml(ilXmlWriter $writer)
	{
		global $ilSetting;
		switch($this->getType())
		{
			case self::TYPE_CREATION:
				$type = 'creation';
				break;
		}
		
		$writer->xmlStartTag('didacticTemplate',array('type' => $type));
		$writer->xmlElement('title',array(),$this->getTitle());
		$writer->xmlElement('description', array(), $this->getDescription());

		$writer = $this->getTranslationObject()->toXml($writer);

		// info text with p-tags
		if(strlen($this->getInfo()))
		{
			$writer->xmlStartTag('info');

			$info_lines = (array) explode("\n",$this->getInfo());
			foreach($info_lines as $info)
			{
				$trimmed_info = trim($info);
				if(strlen($trimmed_info))
				{
					$writer->xmlElement('p', array(), $trimmed_info);
				}
			}

			$writer->xmlEndTag('info');
		}

		if(count($this->getEffectiveFrom()) > 0)
		{
			$writer->xmlStartTag('effectiveFrom', array('nic_id'=> $ilSetting->get('inst_id')));

			foreach($this->getEffectiveFrom() as $node)
			{
				$writer->xmlElement('node', array(), $node);
			}
			$writer->xmlEndTag('effectiveFrom');
		}

		// Assignments
		$writer->xmlStartTag('assignments');
		foreach($this->getAssignments() as $assignment)
		{
			$writer->xmlElement('assignment', array(), $assignment);
		}
		$writer->xmlEndTag('assignments');


		$writer->xmlStartTag('actions');
		include_once './Services/DidacticTemplate/classes/class.ilDidacticTemplateActionFactory.php';
		foreach(ilDidacticTemplateActionFactory::getActionsByTemplateId($this->getId()) as $action)
		{
			$action->toXml($writer);
		}
		$writer->xmlEndTag('actions');
		$writer->xmlEndTag('didacticTemplate');

		return $writer;
	}

	/**
	 * Implemented clone method
	 */
	public function  __clone()
	{
		$this->setId(0);
		include_once './Services/DidacticTemplate/classes/class.ilDidacticTemplateCopier.php';
		$this->setTitle(ilDidacticTemplateCopier::appendCopyInfo($this->getTitle()));
		$this->enable(false);
	}

	/**
	 * @return ilMultilingualism
	 */
	public function getTranslationObject()
	{
		include_once("./Services/Multilingualism/classes/class.ilMultilingualism.php");
		return ilMultilingualism::getInstance($this->getId(), "dtpl");
	}

	/**
	 * @param int $a_node_id
	 * @return bool
	 */
	public function isEffective($a_node_id)
	{
		global $tree;

		if(!count($this->getEffectiveFrom()) ||  in_array($a_node_id, $this->getEffectiveFrom()))
		{
			return true;
		}
		
		foreach ($this->getEffectiveFrom() as $node)
		{
			if($tree->isGrandChild($node, $a_node_id))
			{
				return true;
			}
		}
		
		return false;
	}

	/**
	 * @param ilDidacticTemplateSetting $a_settings
	 */
	function applyOtherSettingsObject($a_settings)
	{

		//delete all obsolete data
		include_once './Services/DidacticTemplate/classes/class.ilDidacticTemplateActionFactory.php';
		foreach (ilDidacticTemplateActionFactory::getActionsByTemplateId($this->getId()) as $action)
		{
			$action->delete();
		}

		include_once './Services/DidacticTemplate/classes/class.ilDidacticTemplateObjSettings.php';
		ilDidacticTemplateObjSettings::deleteByTemplateId($this->getId());
		$this->getTranslationObject()->delete();

		//copy data from temp object
		$this->setEffectiveFrom($a_settings->getEffectiveFrom());
		$this->setTitle($a_settings->getTitle());
		$this->setDescription($a_settings->getDescription());
		$this->setInfo($a_settings->getInfo());
		$this->setType($a_settings->getType());
		$this->setAssignments($a_settings->getAssignments());

		foreach (ilDidacticTemplateActionFactory::getActionsByTemplateId($a_settings->getId()) as $action)
		{
			$action->setTemplateId($this->getId());
			$action->delete();
			$action->save();
		}
		$this->update();

		//copy translations
		$trans = $a_settings->getTranslationObject();
		$trans->setObjId($this->getId());
		$trans->save();
		$trans->setObjId($a_settings->getId()); //switch back to old ID to prevent deletions
	}
}

?>