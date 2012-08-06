<?php if (!defined('APPLICATION')) exit();

$PluginInfo['Share42'] = array(
	'Name' => 'Share42',
	'Description' => 'Скрипт кнопок социальных закладок и сетей.',
	'Version' => '1.0.0',
	'Date' => '06.08.2012 21:07:01',
	'Author' => 'S',
	'AuthorUrl' => 'http://rv-home.ru',
	'RequiredApplications' => False,
	'SettingsUrl' => False,
	'RequiredTheme' => False, 
	'RequiredPlugins' => False,
	'RegisterPermissions' => False,
	'SettingsPermission' => False,
	'License' => 'X.Net License'
);

class Share42Plugin extends Gdn_Plugin {
	
	public function Base_Render_Before($Sender) {
		$Sender->AddCssFile('plugins/Share42/style.css');
		$Sender->AddJsFile('plugins/Share42/share42.js');
		$Sender->AddJsFile('plugins/Share42/functions.js');
		$Sender->AddDefinition('Share42Path', Asset('plugins/Share42/'));
	}
	
	public function Setup() {
	}
}