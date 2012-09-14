<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data('Title');?></h1>

<?php echo $this->Form->Open(); ?>
<?php echo $this->Form->Errors(); ?>

<ul>

<?php 
	$SizeData = array('32' => '32x32', '24' => '24x24', '16' => '16x16');
	$Label = $this->Form->Label('Choose size of icon', 'Size');
	$Input = $this->Form->RadioList('Size', $SizeData, array('Default' => '16'));
	echo Wrap($Label . $Input, 'li');
	
	$LanguageData = array('en' => 'English', 'ru' => 'Russian');
	$Label = $this->Form->Label('Language', 'Language');
	$Input = $this->Form->DropDown('Language', $LanguageData);
	echo Wrap($Label . $Input, 'li');

	$Panels = array('' => 'Horizontal', 'floating' => 'Vertical "sticky"');
	$Label = $this->Form->Label('Type of panel with icons', 'Panel');
	$Input = $this->Form->RadioList('Panel', $Panels);
	echo Wrap($Label . $Input, 'li');

	$HorizontalPlaces = array(
		'AfterAllComments' => 'After comments on discussion page',
		'AfterFirstComment' => 'After first comment on first discussion page'
	);
	$Label = $this->Form->Label('Where to display (horizontal panel)', 'HorizontalPlace');
	$Input = $this->Form->DropDown('HorizontalPlace', $HorizontalPlaces);
	echo Wrap($Label . $Input, 'li');

	$VerticalPlaces = array(
		'Discussion' => 'Discussion page',
		'Every' => 'Every page'
	);
	$Label = $this->Form->Label('Where to display (vertical panel)', 'VerticalPlace');
	$Input = $this->Form->DropDown('VerticalPlace', $VerticalPlaces);
	echo Wrap($Label . $Input, 'li');

	$Label = $this->Form->Label('Limit the number of visible icons (vertical panel)', 'Limit');
	$Input = $this->Form->TextBox('Limit');
	echo Wrap($Label . $Input, 'li');


	// $Label = $this->Form->Label('Add Share42.com icon', 'Share42Icon');
	// $Input = $this->Form->CheckBox('Share42Icon');
	// echo Wrap($Label . $Input, 'li');

?>


<li>
	<p>Select icons which you want to use on your website:</p>
	<table id="Services">
		<thead>
		<tr>
			<td>Icon</td>
			<td>Position</td>
		</tr>
		</thead>
		<tbody>
<?php
foreach ($this->Plugin->GetServices() as $ServiceID => $Service) {
	$IconUrl = $this->Plugin->GetServiceIconUrl($ServiceID);
	$IconStyle = "background: url('{$IconUrl}') left center no-repeat";
	$LabelAttributes = array('style' => $IconStyle, 'class' => 'ServiceLabel');
	echo "\n<tr>";
	$CheckBoxOptions = array('value' => $ServiceID, 'id' => $this->Form->IDPrefix.$ServiceID);
	$CheckedServices = $this->Form->GetValue('ServiceCollection');
	if (in_array($ServiceID, $CheckedServices)) {
		$CheckBoxOptions['checked'] = 'checked';
	}
	$CheckBox = $this->Form->CheckBox('Services[]', '', $CheckBoxOptions);
	$Label = $this->Form->Label($Service['Name'], $ServiceID, $LabelAttributes);
	echo Wrap($CheckBox . $Label, 'td');
	$PositionValue = $this->Form->GetValue("Position_{$ServiceID}", GetValue('Position', $Service));
	$TextBoxOptions = array('value' => $PositionValue,  'class' => 'InputBox PositionTextBox');
	echo Wrap($this->Form->TextBox("Position_{$ServiceID}", $TextBoxOptions), 'td');
	echo "</tr>";
}
?>
		</tbody>
	</table>
</li>

<?php 
	$Label = $this->Form->Label('RSS link of your website', 'RssLink');
	$Input = $this->Form->TextBox('RssLink');
	echo Wrap($Label . $Input, 'li');
?>

</ul>

<?php echo $this->Form->Button('Save'); ?>
<?php echo $this->Form->Close(); ?>