<?php

namespace BareFields\fields;

use BareFields\requests\DocumentFilter;
use Extended\ACF\Fields\Accordion;
use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\Message;

class AdminFields
{
  protected static int $__flexibleSeparatorIndex = 0;
  public static function createFlexibleSeparatorLayout () {
    return Layout::make('', '--'.(++self::$__flexibleSeparatorIndex));
  }

  protected static int $__hiddenFieldID = 0;
  protected static function getNextHiddenID () {
    return "hidden-".(++self::$__hiddenFieldID).DocumentFilter::HIDDEN_MARKER;
  }

  public static function createAccordion ( string $title = "More", string $scope = "" ) {
    return Accordion::make($title, self::getNextHiddenID().$scope);
  }

  public static function createInfo ( string $message ) {
    return Message::make("Message", self::getNextHiddenID())
      ->body( $message )
      ->withSettings([
        'wrapper' => [ 'class' => 'clean' ]
      ]);
  }

  public static function createSubtitle ( string $title ) {
    return Message::make("Message", self::getNextHiddenID())
      ->body( $title )
      ->withSettings([
        'wrapper' => [ 'class' => 'clean adminTitle' ]
      ]);
  }
}
