<?php if (!defined('APPLICATION')) exit();

$PluginInfo['Share42'] = array(
	'Name' => 'Share42',
	'Description' => 'Social Sharing Buttons Script.',
	'Version' => '1.1.2',
	'Date' => '12.09.2012 15:24:42',
	'Updated' => '14.09.2012 17:15:17',
	'Author' => 'S',
	'AuthorUrl' => 'http://rv-home.ru',
	'RequiredApplications' => FALSE,
	'SettingsUrl' => '/settings/share42',
	'RequiredTheme' => FALSE, 
	'RequiredPlugins' => FALSE,
	'RegisterPermissions' => FALSE,
	'SettingsPermission' => FALSE,
	'License' => 'X.Net License'
);

class Share42Plugin extends Gdn_Plugin {

	public function Base_Render_Before($Sender) {
		$Share42ScriptPath = $this->_GetShare42ScriptRelative();
		$Sender->AddJsFile("$Share42ScriptPath/share42.js");
		$Sender->AddCssFile('plugins/Share42/design/style.css');
	}

	protected function RenderPanel($Data) {
		$Url = GetValue('Url', $Data);
		if (!$Url) $Url = GetValue('Discussion.Url', $Data);
		$Title = GetValue('Title', $Data);
		$Attributes = array(
			'class' => 'share42init',
			'data-url' => $Url,
			'data-title' => $Title,
		);
		echo Wrap('', 'div', $Attributes);
	}

	public function DiscussionController_AfterBody_Handler($Sender) {
		if (C('Plugins.Share42.VerticalPlace') == 'Discussion') {
			$this->RenderPanel($Sender->Data);
		}
	}
	
	public function Base_AfterBody_Handler($Sender) {
		if ($Sender->MasterView == 'admin') return;
		if (C('Plugins.Share42.VerticalPlace') == 'Every') {
			$this->RenderPanel($Sender->Data);
		}
	}

	public function DiscussionController_AfterComments_Handler($Sender) {
		if (C('Plugins.Share42.HorizontalPlace', 'AfterAllComments') == 'AfterAllComments') {
			$this->RenderPanel($Sender->Data);
		}
	}	

	public function DiscussionController_AfterDiscussionBody_Handler($Sender) {
		if (C('Plugins.Share42.HorizontalPlace') == 'AfterFirstComment') {
			$this->RenderPanel($Sender->Data);
		}
	}

	public function DiscussionController_AfterComment_Handler($Sender) {
	}

	public function SettingsController_Share42_Create($Sender) {
		$Sender->Permission('Garden.Plugins.Manage');
		$Sender->AddSideMenu();
		$Form = Gdn::Factory('Form');
		$Sender->Form = $Form;

		if ($Form->IsPostback()) {
			$Form->SetFormValue('ServiceCollection', $Form->GetValue('Services'));
			$FormValues = $Sender->Form->FormValues();
			$this->_PivotFormValues($FormValues, 'Position');
			$Services = GetValue('Services', $FormValues);
			$Positions = GetValue('Position', $FormValues);
			$this->_SortServices($Services, $Positions);
			$ConfigValues = array();
			$SaveValues = array('Language', 'Size', 'Panel', 'HorizontalPlace', 'VerticalPlace', 'Limit', 'RssLink');
			foreach ($SaveValues as $Value) {
				$ConfigValues['Plugins.Share42.'.$Value] = GetValue($Value, $FormValues);
			}
			$ConfigValues['Plugins.Share42.Services'] = implode(',', $Services);
			Gdn::Config()->RemoveFromConfig('Plugins.Share42');
			Gdn::Config()->SaveToConfig($ConfigValues, '', array('Save' => TRUE));
			$this->InformMessage(T('Saved'));
		} else {
			$Data = C('Plugins.Share42');
			$ServiceCollection = array_map('trim', explode(',', GetValue('Services', $Data)));
			$Form->SetData($Data);
			$Form->SetValue('ServiceCollection', $ServiceCollection);
		}

		$Sender->Title(T('Share42 Settings'));
		$Sender->View = $this->GetView('settings.php');
		$Sender->Plugin = $this;
		$Sender->AddCssFile('plugins/Share42/design/dashboard.css');
		$Sender->AddJsFile('plugins/Share42/js/settings.js');
		$Sender->Render();
	}

	protected static function _SortServices(&$Services, $Positions = FALSE) {
		if (!is_array($Positions)) $Positions = array_keys($Services);
		$Positions = array_filter($Positions);
		asort($Positions);
		$NewResult = array_merge(array_keys($Positions), $Services);
		$NewResult = array_intersect($NewResult, $Services);
		$NewResult = array_unique($NewResult);
		$Services = $NewResult;
		return $Services;
	}

	protected static function _GetCrc($Values) {
		$FieldsForCrc = $Values;
		unset($FieldsForCrc['HorizontalPlace']);
		unset($FieldsForCrc['VerticalPlace']);
		$Crc = sprintf('%u', crc32(serialize($FieldsForCrc)));
		return $Crc;
	}

	protected $_Share42Script;

	protected function _GetShare42ScriptRelative() {
		static $Result;
		if ($Result === NULL) {
			$Directory = $this->_GetShare42Script();
			$Result = substr($Directory, strlen(PATH_ROOT) + 1);
		}
		
		return $Result;
	}

	protected function _GetShare42Script() {
		if ($this->_Share42Script !== NULL) {
			return $this->_Share42Script;
		}
		$Default = PATH_ROOT . '/plugins/Share42/default/share42';
		// 1. Check for script in cache.
		$Values = C('Plugins.Share42');
		if (!$Values) {
			$this->_Share42Script = $Default;
			return $this->_Share42Script;
		}
		$Icons = array_map('trim', explode(',', $Values['Services']));
		$Crc = self::_GetCrc($Values);
		$DirectoryPath = PATH_CACHE . DS . 'share42' . DS . $Crc;
		$Directory = $DirectoryPath . DS . 'share42';
		if (file_exists($Directory) && is_dir($Directory)) {
			$this->_Share42Script = $Directory;
			return $this->_Share42Script;
		}

		// 2. Get new.
		$PostFields = array(
			'browser' => '',
			'charset' => 'utf8',
			'jquery' => 1,
			'lang' => GetValue('Language', $Values),
			'ontop' => (ArrayHasValue($Icons, 'ontop')) ? 1 : '',
			'panel' => $Values['Panel'],
			'share42icon' => GetValue('Share42Icon', $Values) ? 'share42' : '',
			'size' => GetValue('Size', $Values),
			'limit' => GetValue('Limit', $Values),
			'rssLink' => $Values['RssLink'],
			'icons' => $Icons
		);
		if (GetValue('Limit', $Values)) $PostFields['useLimit'] = 1;

		$ServerResponse = self::ClientRequest(array(
			'GetInfo' => TRUE,
			'Url' => 'http://share42.com/',
			'Referer' => 'http://share42.com/',
			'Post' => TRUE,
			'PostFields' => http_build_query($PostFields),
		));
		list($FileContents, $Info) = $ServerResponse;

		if ($Info['http_code'] == 200 && $Info['content_type'] == 'application/octet-stream') {
			$TmpFile = $DirectoryPath . DS . 'tmp.zip';
			if (!is_dir($DirectoryPath)) mkdir($DirectoryPath, 0777, TRUE);
			file_put_contents($TmpFile, $FileContents);
			self::ExtractZip($TmpFile, $DirectoryPath);

			$Directory = $DirectoryPath . DS . 'share42';
			if (file_exists($Directory) && is_dir($Directory)) {
				$this->_Share42Script = $Directory;
				return $this->_Share42Script;
			}
		}

		// 3. Default.
		$this->_Share42Script = $Default;

		return $this->_Share42Script;
	}

	private static function ExtractZip($ZipFile, $Directory = NULL) {
		if ($Directory === NULL) {
			$Directory = dirname($ZipFile);
		}
		if (class_exists('ZipArchive')) {
			$Zip = new ZipArchive();
			$Zip->Open($ZipFile);
			$Result = $Zip->ExtractTo($Directory);
			$Zip->Close();

			return $Result;
		}
		$Command = '/usr/local/bin/unzip';
		if (PHP_OS == 'WINNT') $Command = 'unzip';
		$Exec = "$Command -oX $ZipFile -d $Directory";
		$Result = exec($Exec, $Out, $ReturnCode);
		if ($ReturnCode === 0) {
			return TRUE;
		}
		return FALSE;
	}

	private function _PivotFormValues(&$FormValues, $Name, $Remove = TRUE) {
		foreach ($FormValues as $Key => $Value) {
			$Length = strlen($Name) + 1;
			if (substr($Key, 0, $Length) == "{$Name}_") {
				$ArrayKey = substr($Key, $Length);
				$FormValues[$Name][$ArrayKey] = $Value;
				if ($Remove) {
					unset($FormValues[$Key]);
				}
			}
		}
		return $FormValues;
	}
	
	public function GetServiceIconUrl($ServiceID) {
		$IconBasename = self::GetServiceValue("$ServiceID.IconBasename");
		return 'http://share42.com/icons/24x24/' . $IconBasename;
	}

	public static function GetServiceValue($Path) {
		$Services = self::GetServices();
		return GetValueR($Path, $Services);
	}

	public function Setup() {
		$Error = '';
		if (!extension_loaded('curl')) $Error = ConcatSep("\n", $Error, 'This plugin requires curl.');
		if ($Error) throw new Gdn_UserException($Error, 400);
	}

	public function CleanUp() {
		RemoveFromConfig('Plugins.Share42');
	}

	public static function GetServices() {
		$Services['blogger'] = array('Id' => 'blogger', 'Name' => 'blogger', 'IconBasename' => 'blogger.png');
		$Services['bobrdobr'] = array('Id' => 'bobrdobr', 'Name' => 'bobrdobr', 'IconBasename' => 'bobrdobr.png');
		$Services['delicious'] = array('Id' => 'delicious', 'Name' => 'delicious', 'IconBasename' => 'delicious.png');
		$Services['design-bump'] = array('Id' => 'design-bump', 'Name' => 'design bump', 'IconBasename' => 'design-bump.png');
		$Services['design-float'] = array('Id' => 'design-float', 'Name' => 'design float', 'IconBasename' => 'design-float.png');
		$Services['digg'] = array('Id' => 'digg', 'Name' => 'digg', 'IconBasename' => 'digg.png');
		$Services['evernote'] = array('Id' => 'evernote', 'Name' => 'evernote', 'IconBasename' => 'evernote.png');
		$Services['facebook'] = array('Id' => 'facebook', 'Name' => 'facebook', 'IconBasename' => 'facebook.png');
		$Services['friendfeed'] = array('Id' => 'friendfeed', 'Name' => 'FriendFeed', 'IconBasename' => 'friendfeed.png');
		$Services['google-bookmarks'] = array('Id' => 'google-bookmarks', 'Name' => 'google bookmarks', 'IconBasename' => 'google-bookmarks.png');
		$Services['google-plus'] = array('Id' => 'google-plus', 'Name' => 'google plus', 'IconBasename' => 'google-plus.png');
		$Services['identi'] = array('Id' => 'identi', 'Name' => 'identi', 'IconBasename' => 'identi.png');
		$Services['juick'] = array('Id' => 'juick', 'Name' => 'juick', 'IconBasename' => 'juick.png');
		$Services['linkedin'] = array('Id' => 'linkedin', 'Name' => 'LinkedIn', 'IconBasename' => 'linkedin.png');
		$Services['liveinternet'] = array('Id' => 'liveinternet', 'Name' => 'LiveInternet', 'IconBasename' => 'liveinternet.png');
		$Services['livejournal'] = array('Id' => 'livejournal', 'Name' => 'LiveJournal', 'IconBasename' => 'livejournal.png');
		$Services['mail-ru'] = array('Id' => 'mail-ru', 'Name' => 'Mail.Ru', 'IconBasename' => 'mail-ru.png');
		$Services['memori'] = array('Id' => 'memori', 'Name' => 'memori', 'IconBasename' => 'memori.png');
		$Services['mister-wong'] = array('Id' => 'mister-wong', 'Name' => 'mister wong', 'IconBasename' => 'mister-wong.png');
		$Services['mixx'] = array('Id' => 'mixx', 'Name' => 'mixx', 'IconBasename' => 'mixx.png');
		$Services['moi-krug'] = array('Id' => 'moi-krug', 'Name' => 'moi krug', 'IconBasename' => 'moi-krug.png');
		$Services['myspace'] = array('Id' => 'myspace', 'Name' => 'MySpace', 'IconBasename' => 'myspace.png');
		$Services['netvibes'] = array('Id' => 'netvibes', 'Name' => 'netvibes', 'IconBasename' => 'netvibes.png');
		$Services['newsvine'] = array('Id' => 'newsvine', 'Name' => 'newsvine', 'IconBasename' => 'newsvine.png');
		$Services['odnoklassniki'] = array('Id' => 'odnoklassniki', 'Name' => 'odnoklassniki', 'IconBasename' => 'odnoklassniki.png');
		$Services['pikabu'] = array('Id' => 'pikabu', 'Name' => 'pikabu', 'IconBasename' => 'pikabu.png');
		$Services['pinterest'] = array('Id' => 'pinterest', 'Name' => 'pinterest', 'IconBasename' => 'pinterest.png');
		$Services['posterous'] = array('Id' => 'posterous', 'Name' => 'posterous', 'IconBasename' => 'posterous.png');
		$Services['reddit'] = array('Id' => 'reddit', 'Name' => 'reddit', 'IconBasename' => 'reddit.png');
		$Services['rutvit'] = array('Id' => 'rutvit', 'Name' => 'RuTwit', 'IconBasename' => 'rutvit.png');
		$Services['stumbleupon'] = array('Id' => 'stumbleupon', 'Name' => 'StumbleUpon', 'IconBasename' => 'stumbleupon.png');
		$Services['surfingbird'] = array('Id' => 'surfingbird', 'Name' => 'surfingbird', 'IconBasename' => 'surfingbird.png');
		$Services['technorati'] = array('Id' => 'technorati', 'Name' => 'technorati', 'IconBasename' => 'technorati.png');
		$Services['tumblr'] = array('Id' => 'tumblr', 'Name' => 'tumblr', 'IconBasename' => 'tumblr.png');
		$Services['twitter'] = array('Id' => 'twitter', 'Name' => 'twitter', 'IconBasename' => 'twitter.png');
		$Services['vkontakte'] = array('Id' => 'vkontakte', 'Name' => 'VKontakte', 'IconBasename' => 'vkontakte.png');
		$Services['webdiscover'] = array('Id' => 'webdiscover', 'Name' => 'WebDiscover', 'IconBasename' => 'webdiscover.png');
		$Services['yahoo-bookmarks'] = array('Id' => 'yahoo-bookmarks', 'Name' => 'yahoo bookmarks', 'IconBasename' => 'yahoo-bookmarks.png');
		$Services['yandex'] = array('Id' => 'yandex', 'Name' => 'Yandex Bookmarks', 'IconBasename' => 'yandex.png');
		$Services['yaru'] = array('Id' => 'yaru', 'Name' => 'Ya.ru', 'IconBasename' => 'yaru.png');
		$Services['yosmi'] = array('Id' => 'yosmi', 'Name' => 'yoSMI', 'IconBasename' => 'yosmi.png');
		$Services['browser'] = array('Id' => 'browser', 'Name' => 'Browser Favorites', 'IconBasename' => 'browser.png');
		$Services['email'] = array('Id' => 'email', 'Name' => 'E-mail', 'IconBasename' => 'email.png');
		$Services['ontop'] = array('Id' => 'ontop', 'Name' => 'back on top', 'IconBasename' => 'ontop.png');
		$Services['print'] = array('Id' => 'print', 'Name' => 'print', 'IconBasename' => 'print.png');
		$Services['rss'] = array('Id' => 'rss', 'Name' => 'RSS', 'IconBasename' => 'rss.png');

		$ServicesString = C('Plugins.Share42.Services');
		if ($ServicesString) {
			$ServicesArray = array_map('trim', explode(',', $ServicesString));
			foreach ($ServicesArray as $Index => $ServiceID) {
				$Services[$ServiceID]['Position'] = $Index + 1;
			}
			// Move selected to top.
			uasort($Services, array(__CLASS__, 'SortCallback'));
		}

		return $Services;
	}

	public static function SortCallback($A, $B) {
		$MaxInteger32 = pow(2, 32);
		$PositionA = GetValue('Position', $A, $MaxInteger32);
		$PositionB = GetValue('Position', $B, $MaxInteger32);
		if ($PositionA < $PositionB) {
			return -1;
		} elseif ($PositionA > $PositionB) {
			return 1;
		} else {
			return 0;
		}
	}

	/**
	* Perform client request to server.
	* Options: see here http://www.php.net/manual/en/function.curl-setopt.php
	* Bool options: 
	* 	ReturnTransfer, Post, FollowLocation, Header
	* Integer options: 
	* 	ConnectTimeout, Timeout, Timeout_Ms
	* Other options: 
	* 	Url, Cookie, CookieFile, CustomRequest, PostFields, Referer, UserAgent, UserPwd
	* 
	* @param mixed $Url or array $Options.
	* @return mixed $Result.
	*/
	protected function ClientRequest($Url, $Options = FALSE) {
		static $Connections = array();
		if (func_num_args() == 1) {
			$Options = $Url;
		}
		if (is_string($Options)) {
			$Options['Url'] = $Options;
		}

		$Url = GetValue('Url', $Options, FALSE, TRUE);
		$GetInfo = GetValue('GetInfo', $Options, FALSE, TRUE);
		TouchValue('ReturnTransfer', $Options, TRUE);
		//TouchValue('ConnectTimeout', $Options, 5);
		//TouchValue('Timeout', $Options, 5);

		if (!array_key_exists($Url, $Connections)) $Connections[$Url] = curl_init($Url); 
		$Connection =& $Connections[$Url];
		
		foreach ($Options as $Option => $Value) {
			$Constant = 'CURLOPT_' . strtoupper($Option);
			if (!defined($Constant)) {
				$InfoConstant = 'CURLINFO_' . strtoupper($Option);
				if (!defined($InfoConstant)) {
					trigger_error("cURL. Unknown option: $Constant ($InfoConstant)");
				} else {
					$Constant = $InfoConstant;
				}

			}
			curl_setopt($Connection, constant($Constant), $Value);
		}
		$Result = curl_exec($Connection);
		$Return[0] = $Result;
		if ($Result === FALSE) {
			$ErrorMessage = curl_error($Connection);
			trigger_error($ErrorMessage);
			return FALSE;
		}
		if ($GetInfo) {
			$Return[1] = curl_getinfo($Connection);
		}

		return $Return;
	}

	private static function InformMessage($Message, $Sprite = 'Check') {
		$Controller = Gdn::Controller();
		if ($Controller) {
			$Options = array('Sprite' => $Sprite, 'CssClass' => 'Dismissable AutoDismiss');
			$Controller->InformMessage($Message, $Options);
		}
	}
	
}