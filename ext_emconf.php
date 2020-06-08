<?php


$EM_CONF[$_EXTKEY] = array (
	'title' => 'Extbase Hijax add-on',
	'description' => '',
	'category' => 'plugin',
	'author' => 'Nikola Stojiljkovic',
	'author_company' => 'Essential Dots d.o.o. Belgrade',
	'author_email' => 'no-reploy@essentialdots.com',
	'dependencies' => 'cms,extbase,fluid',
	'shy' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/extbase_hijax',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'version' => '4.0.2',
	'constraints' => 
	array (
	  'depends' => 
	  array (
	    'typo3' => '9.5.0-0.0.0',
	    'typo3db_legacy' => '1.0.0-1.0.99',
	    'extbase' => '',
	    'fluid' => '',
	  ),
	  'conflicts' => 
	  array (
	  ),
	  'suggests' => 
	  array (
	    'ed_cache' => '0.2.5',
	  ),
	),
	'suggests' => 
	array (
	),
	'conflicts' => '',
);

?>