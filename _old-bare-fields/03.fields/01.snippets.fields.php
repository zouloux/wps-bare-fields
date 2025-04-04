<?php

use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\ButtonGroup;
use Extended\ACF\Fields\Email;
use Extended\ACF\Fields\Group;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\Message;
use Extended\ACF\Fields\PageLink;
use Extended\ACF\Fields\Relationship;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\URL;
use Extended\ACF\Fields\WYSIWYGEditor;
use Extended\ACF\Fields\Accordion;


// ----------------------------------------------------------------------------- MIME TYPE

function woolkit_get_mime ( $type, $others = [] ) {
	if ( $type === "image" )
		return ["jpg", "png", "jpeg", ...$others];
	if ( $type === "svg" )
		return ["svg"];
	else if ( $type === "video" )
		return ["mp4", "webm", ...$others];
	else if ( $type === "document" )
		return ["pdf", ...$others];
	else
		throw new \Exception("woolkit_get_mime // Invalid type $type");
}

// ----------------------------------------------------------------------------- TITLE FIELD

function woolkit_create_title_field ( $label = "Title", $key = 'title' ) {
	return Text::make( $label, $key );
}

// ----------------------------------------------------------------------------- IMAGES

function woolkit_create_regular_image_field ( $key = "image", $label = "Image" ) {
	return Image::make($label, $key)
		->wrapper(["class" => "smallImage"]);
}

function woolkit_filter_image_to_href ( &$node, $imageKey ) {
	$node[$imageKey] = (
		( isset($node[$imageKey]) && $node[$imageKey] instanceof WoolkitImage )
		? Nano\core\URL::removeBaseFromHref( $node[$imageKey]->href, woolkit_get_base() )
		: null
	);
}

// ----------------------------------------------------------------------------- WYSIWYG FIELD

function woolkit_create_wysiwyg_field ( $label = "Content", $key = "content", $allowMedia = false, $class = 'clean' ) {
	$editor = WYSIWYGEditor::make( $label, $key )
		->tabs('visual')
		->wrapper(['class' => $class]);
	if ( !$allowMedia )
		$editor->disableMediaUpload();
	return $editor;
}

// ----------------------------------------------------------------------------- ENABLED CONDITIONAL
// Create a boolean with its condition

function woolkit_create_simple_enabled_fields ( $title = "Enabled", $offLabel = "Off", $onLabel = "On", $default = 1, $key = 'enabled' ) {
	return ButtonGroup::make( $title, $key )
		->default( $default )
		->choices([
			0 => $offLabel,
			1 => $onLabel
		]);
}

function woolkit_create_enabled_conditional_fields ( $label = "Enabled", $default = 1, $key = 'enabled', $offLabel = "Off", $onLabel = "On" ) {
	return [
		woolkit_create_simple_enabled_fields( $label, $offLabel, $onLabel, $default, $key ),
		ConditionalLogic::where( $key, "==", 1 )
	];
}

function woolkit_create_locale_enable_field ( $disabled = "Disabled", $enabled = "Enabled" ) {
	$locales = woolkit_locale_get_languages_list();
	$choices = [
		$disabled,
		...array_keys($locales),
		$enabled,
	];
	return ButtonGroup::make("", "enabled")
		->choices( $choices )->default( count($choices) - 1 )
		->format('array')
		->wrapper(["class" => "woolkitEnabledField woolkitEnabledFieldColored"]);
}

// ----------------------------------------------------------------------------- ENABLED FLEXIBLE
// An enabled field which is on top of the flexible block and group fields

function woolkit_create_enable_field ( $choices = [ "Disabled", "Enabled" ], $key = "enabled", $default = "enabled" ) {
	return ButtonGroup::make("", $key)
		->choices( $choices )->default( $default )
		->wrapper(["class" => "woolkitEnabledField woolkitEnabledFieldColored"]);
}


// ----------------------------------------------------------------------------- SIMPLE INLINE ENABLE
function woolkit_create_inline_enable_field ( string $name = "Enable", string $key = "enabled" ) {
	return ButtonGroup::make($name, $key)
		->choices([0 => "Disabled", 1 => "Enabled"])
		->wrapper(["class" => "woolkitEnabledFieldColored"])
		->default("enabled");
}

function woolkit_create_minimalist_enable_field ( string $name = "Enable", string $key = "enabled" ) {
	return ButtonGroup::make($name, $key)
		->choices([0 => "0", 1 => "1"])
		->default("1")
		->wrapper(["class" => "woolkitEnabledFieldColored"])
		->column(1);
}

// ----------------------------------------------------------------------------- CONDITIONAL GROUP

/**
 * IMPORTANT : Use expand when using into fields
 * ->fields([
 * 		...woolkit_create_conditional_group()
 * ])
 * NOTE : Parsed and filtered by WoolkitFilter::recursivePatchFields
 */
function woolkit_create_conditional_group ( $label, $key, $choiceFields, $layout = "row", $tabMode = false ) {
	// Key for button group
	$groupKey = "\$_".$key.'_group';
	$enabledKey = $groupKey.'_selected';
	// Convert choices to "my-choice" => "My Choice"
	$choices = [];
	// Allow keys to be like "disabled/Désactivé" to convert to ["disabled" => "Désactivé"]
	foreach ( $choiceFields as $choice => $fields ) {
		$split = explode("/", $choice, 2);
		if ( count($split) === 2 )
			$choices[ acf_slugify($split[0]) ] = $split[1];
		else
			$choices[ acf_slugify($choice) ] = $choice;
	}
	// Generate button group
	$output = [
		ButtonGroup::make( $label, $enabledKey )
			->wrapper(['class' => 'noLabel'])
			->choices( $choices )
	];
	// Browse choices and map to correct field
	$c = array_keys( $choices );
	$v = array_values( $choiceFields );
	foreach ( $c as $index => $choiceSlug ) {
		// Target fields from choice index
		$fields = $v[ $index ];
		// Do not create empty groups
		if ( empty($fields) ) continue;
		// Create group and connect it to correct choice
		$output[] = Group::make(' ', $groupKey.'_'.$choiceSlug)
			->layout( $layout )
			->wrapper(['class' => 'conditionalGroup'.($tabMode ? ' tabMode' : '')])
			->fields( $fields )
			->conditionalLogic([
				ConditionalLogic::where( $enabledKey, "==", $choiceSlug )
			]);
	}
	return $output;
}

// ----------------------------------------------------------------------------- COLUMNS GROUP
// Create a clean group field

function woolkit_create_columns_group_field ( $fields = [], $name = "columns", $layout = 'row') {
	return Group::make(' ', $name)
		->layout( $layout )
		->wrapper(['class' => 'columns-group clean'])
		->fields( $fields );
}

/**
 *
 * woolkit_create_responsive_columns_field("label", "key", true, [
 *   Group::make("Column A", "a")->fields( ... ),
 *   Group::make("Column B", "b")->fields( ... ),
 *   Group::make("Column C", "x")->fields( ... ),
 *   ...
 * ]
 *
 * @param string $label
 * @param string $key
 * @param bool $showLabel
 * @param array $fields
 * @return Group
 */
function woolkit_create_responsive_columns_field ( string $label, string $key, bool $showLabel, array $fields ) {
	return Group::make( $label, $key )
		->wrapper([ "class" => "columns".($showLabel ? "" : " noLabel") ])
		->fields( $fields );
}

// ----------------------------------------------------------------------------- PAGE LINK FIELD

function woolkit_create_page_link_field ( $title = "Link to page", $key = 'link', $postTypes = ['page'], $allowArchives = false ) {
	$field = PageLink::make( $title, $key )
		->nullable()
		->required()
		->postTypes( $postTypes );
	if ( !$allowArchives )
		$field->disableArchives();
	return $field;
}

// ----------------------------------------------------------------------------- IMAGE FIELD

function woolkit_create_image_field ( $label = "Image", $key = 'image', $class = 'smallImage' ) {
	return Image::make($label, $key)
		->wrapper(['class' => $class]);
}

// ----------------------------------------------------------------------------- FLEXIBLE LAYOUT

function woolkit_create_flexible_layout ( $title, $id, $layout, $fields ) {
	return Layout::make( $title, $id )
		->layout( $layout )
		->fields( $fields );
}

// ----------------------------------------------------------------------------- LAYOUT FLEXIBLE SEPARATOR
// Create a separator layout for flexibles

$_woolkitLayoutSeparatorIndex = 0;
function woolkit_create_separator_layout () {
	global $_woolkitLayoutSeparatorIndex;
	return Layout::make('', '--'.(++$_woolkitLayoutSeparatorIndex));
}

// ----------------------------------------------------------------------------- INSTRUCTIONS

function woolkit_create_instruction_group_fields ( $fields, $richContent, $fontSize = "1.3em" ) {
	global $woolkit_create_instruction_group_fields_counter;
	if ( !isset($woolkit_create_instruction_group_fields_counter) )
		$woolkit_create_instruction_group_fields_counter = 0;
	$woolkit_create_instruction_group_fields_counter ++;
	$fields->addGroup("instructions_".$woolkit_create_instruction_group_fields_counter, " ")
		->seamless()
		->helperText("<p style='font-size: $fontSize'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$richContent</p>");
}

// ----------------------------------------------------------------------------- RELATIONSHIP

function woolkit_create_relationship_field ( array $postTypes, string $title, string $key ) {
	return Relationship::make($title, $key)
       ->wrapper(["class" => "noLabel relationshipNoPostStatus relationshipNoPostType"])
       ->taxonomies([])
       ->postStatus(["publish"])
       ->postTypes( $postTypes )
       ->format('id');
}

// ----------------------------------------------------------------------------- EXTERNAL / INTERNAL LINK

function _woolkit_create_link_text_field ( string $key, string $label = "Link text" ) {
	return Text::make( $label, "{$key}_text" )->required();
}

function woolkit_create_internal_link_fields ( string $label = "Page", string $key = "link" ) {
	return [
		_woolkit_create_link_text_field( $key ),
		PageLink::make($label, "{$key}_internal")
			->disableArchives()
			->postTypes(["page"])
			->postStatus(["publish"])
			->nullable(),
	];
}

function woolkit_create_external_link_fields ( string $label = "Link URL", string $key = "link" ) {
	return [
		_woolkit_create_link_text_field( $key ),
		URL::make($label, "{$key}_external")
			->placeholder("https:// ...")
			->required(),
	];
}

// none
// anchor
// text
// internal
// external


function woolkit_create_link_group ( string $groupLabel = "Link", string $key = "link", array $types = ["none", "internal", "external"] ) {
	$fields = [];
	if ( in_array("none", $types) )
		$fields["None"] = [];
	if ( in_array('anchor', $types) )
		$fields["Anchor"] = [
			_woolkit_create_link_text_field( $key ),
			Text::make("Anchor", "{$key}_anchor")
				->prefix("#")
				->required(),
		];
	if ( in_array("text", $types) )
		$fields["Text"] = [
			_woolkit_create_link_text_field( $key ),
		];
	if ( in_array("internal", $types) )
		$fields["Internal"] = woolkit_create_internal_link_fields( key: $key );
	if ( in_array("external", $types) )
		$fields["External"] = woolkit_create_external_link_fields( key: $key );
	if ( in_array("email", $types) )
		$fields["Email"] = [
			_woolkit_create_link_text_field( $key ),
			Email::make($key, "{$key}_email")->required()
		];
	return Group::make( $groupLabel, $key )
		->layout("row")
		->fields(
			woolkit_create_conditional_group(" ", "$key-type", $fields, "table", true)
		);
}

function woolkit_filter_link_group ( $key, $node, $locale = null ) {
	$k = "$key-type";
	if ( empty($node[$k]) )
		return $node;
	$linkNode = $node[$k];
	return woolkit_filter_link_fields( $k, $linkNode, $locale );
}
function woolkit_filter_link_fields ( $key, $node, $locale = null ) {
	$selected = $node['selected'];
	if ( $selected === 'none' )
		return [ 'selected' => $selected ];
	$r = [
		'selected' => $selected,
		'text' => $node["{$key}_text"] ?? ''
	];
	if ( $selected === 'text' )
		return $r;
	if ( $selected === "internal" ) {
		$href = $node["{$key}_internal"];
		if ( is_null($locale) ) {
			$locale = woolkit_locale_get();
			if ( !empty($locale) )
				$href = '/'.$locale.$href;
		}
		if ( !empty($href) && defined('WP_HOME') )
			$href = Nano\core\URL::removeBaseFromHref( $href, WP_HOME );
		$r['href'] = $href;
	}
	else if ( $selected === "anchor" )
		$r['href'] = "#".$node["{$key}_anchor"];
	else if ( $selected === "external" ) {
		$r['href'] = $node["{$key}_external"];
		$r['target'] = "_blank";
	}
	else if ( $selected === "email" )
		$r['href'] = "mailto:".$node["{$key}_email"];
	return $r;
}

// ----------------------------------------------------------------------------- ACCORDIONS
$__woolkitGlobalHiddenFieldsID = 0;
global $__woolkitGlobalHiddenFieldsID;

function woolkit_create_accordion_field ( string $title = "More" ) {
	global $__woolkitGlobalHiddenFieldsID;
	$__woolkitGlobalHiddenFieldsID++;
	return Accordion::make($title, "@hidden_$__woolkitGlobalHiddenFieldsID");
}

// ----------------------------------------------------------------------------- ADMIN INFO

function woolkit_create_admin_info ( string $message ) {
	global $__woolkitGlobalHiddenFieldsID;
	$__woolkitGlobalHiddenFieldsID++;
	return Message::make("Message", "@hidden_$__woolkitGlobalHiddenFieldsID")
		->body( $message )
		->withSettings([
			'wrapper' => [ 'class' => 'clean' ]
		]);
}

function woolkit_create_admin_sub_title ( string $title ) {
	global $__woolkitGlobalHiddenFieldsID;
	$__woolkitGlobalHiddenFieldsID++;
	return Message::make("Message", "@hidden_$__woolkitGlobalHiddenFieldsID")
		->body( $title )
		->withSettings([
			'wrapper' => [ 'class' => 'clean adminTitle' ]
		]);
}
