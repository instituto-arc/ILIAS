<#1>
<?php
//create il translation table to store translations for title and descriptions
if(!$ilDB->tableExists('il_translations'))
{
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'id_type' => array(
			'type' => 'text',
			'length' => 50,
			'notnull' => true
			),
		'lang_code' => array(
			'type' => 'text',
			'length' => 2,
			'notnull' => true
		),
		'title' => array(
			'type' => 'text',
			'length' => 256,
			'fixed' => false,
		),
		'description' => array(
			'type' => 'text',
			'length' => 512,
		),
		'lang_default' => array(
			'type' => 'integer',
			'length' => 1,
			'notnull' => true
		)
	);
	$ilDB->createTable('il_translations', $fields);
	$ilDB->addPrimaryKey("il_translations", array("id", "id_type", "lang_code"));
}
?>
<#2>
<?php
//data migration didactic templates to il_translation
if($ilDB->tableExists('didactic_tpl_settings') && $ilDB->tableExists('il_translations'))
{

	$ini = new ilIniFile(ILIAS_ABSOLUTE_PATH."/ilias.ini.php");

	$lang_default = $ini->readVariable("language","default");

	$ilSetting = new ilSetting();

	if ($ilSetting->get("language") != "")
	{
		$lang_default = $ilSetting->get("language");
	}

	$set = $ilDB->query("SELECT id, title, description".
		" FROM didactic_tpl_settings");

	while($row = $ilDB->fetchAssoc($set))
	{
		$fields = array("id" => array("integer", $row['id']),
			"id_type" => array("text", "dtpl"),
			"lang_code" => array("text", $lang_default),
			"title" => array("text", $row['title']),
			"description" => array("text", $row['description']),
			"lang_default" => array("integer", 1));

		$ilDB->insert("il_translations", $fields);
	}
}

?>
<#3>
<?php
//table to store "effective from" nodes for didactic templates
if(!$ilDB->tableExists('didactic_tpl_en'))
{		
	$fields = array(
		'id' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			),
		'node' => array(
			'type' => 'integer',
			'length' => 4,
			'notnull' => true
			)
	);
	$ilDB->createTable('didactic_tpl_en', $fields);
	$ilDB->addPrimaryKey("didactic_tpl_en", array("id", "node"));
}

?>
<#4>
<?php
$ilCtrlStructureReader->getStructure();
?>
