<?php

namespace BareFields\fields;

use BareFields\helpers\AdminHelper;
use BareFields\requests\DocumentFilter;
use Extended\ACF\Fields\Text;

class ComplexFields {

	protected static bool $__hasInjectedAutoIdAssets = false;

	public static function createAutoIdField () {
		$key = "id".DocumentFilter::COMPLEX_ID_MARKER;
		if (!self::$__hasInjectedAutoIdAssets) {
			AdminHelper::injectInlineScript(<<<EOF
				(() => {
					if (!acf) return
					function generateSimpleID() {
						return Math.random().toString(36).slice(2, 12);
					}
					function fillIdField (field) {
						const { \$el } = field
						const input = \$el.find("input").eq( 0 )
						if ( !input.val() )
							input.val( generateSimpleID() )
					}
					acf.addAction('load_field/name=$key', fillIdField);
					acf.addAction('append_field/name=$key', fillIdField);
				})()
			EOF);
			AdminHelper::injectInlineStyle(<<<EOF
				.autoID {
					height: 2px !important;
					overflow: hidden;
					padding: 0 !important;
				}
			EOF);
		}
		return Text::make("ID", $key)
			->wrapper(["class" => "autoID"])
			->readOnly();
	}
}
