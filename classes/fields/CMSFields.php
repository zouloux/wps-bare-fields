<?php

namespace BareFields\fields;

use BareFields\blueprints\RootGroup;
use BareFields\multilang\TranslatedFields;
use BareFields\requests\DocumentRequest;
use Extended\ACF\Fields\ButtonGroup;
use Extended\ACF\Fields\ColorPicker;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;


class CMSFields
{
  public static function filterShareImage ( $fields ) {
    if ( $fields["shareImage"] )
      $fields["shareImage"] = $fields["shareImage"]->href;
    return $fields;
  }

  public static function createDefaultsMetaGroup ( string $title = "Default meta", string $name = "meta", bool $showKeywords = false ) {
    return RootGroup::create( $title, $name )
      ->multiLang()
      ->filter( fn ($f) => self::filterShareImage($f) )
      ->fields([
        ...TranslatedFields::many( fn () => [
          Textarea::make("Default meta description", 'description')
            ->rows(3),
          Text::make("Default share title", 'shareTitle')
            ->helperText("Will be page title if empty"),
          Textarea::make("Default share description", 'shareDescription')
            ->helperText("Will be page description if empty")
            ->rows(3),
        ]),
        // Keywords
        $showKeywords
        ? TranslatedFields::one( fn () =>
          Textarea::make("Keywords", "keywords")->helperText("Comma separated.")->rows(2)
        )
        : null,
        // Share image
        BasicFields::createImage("Share image", 'shareImage')
          ->helperText("Image for open graph"),
      ]);
  }

  public static function createPageMetaGroup ( string $title = "Page meta", string $name = "meta", bool $titleOverride = true , bool $canonical = true ) {
    /** @noinspection PhpUndefinedConstantInspection */
    $home = defined("WP_HOME") ? WP_HOME : "";
    return RootGroup::create( $title, $name )
      ->multiLang()->position(0)
      ->filter( fn ($f) => self::filterShareImage($f) )
      ->fields([
        TranslatedFields::one( fn () =>
          Textarea::make("Description override", 'description')
            ->rows(2),
        ),
        $titleOverride
        ? TranslatedFields::one( fn () =>
          Text::make("Title override", 'title')
            ->helperText("Override head title tag."),
        )
        : null,
        // Canonical
        $canonical
        ? Text::make("Canonical URL", "canonicalUrl")
          ->placeholder("/other-page")
          ->helperText("Starting with **/**<br>Relative to **".$home."**<br>Without locale")
        : null,
        // Sitemap
        ButtonGroup::make("Sitemap presence", "sitemap")
          ->default("visible")
          ->choices([
            "hidden" => "Hidden",
            "visible" => "Visible"
          ]),
        // Share overrides
        AdminFields::createAccordion("Share override", "shareOverride"),
        BasicFields::createImage("Share image override", "shareImage"),
        ...TranslatedFields::many( fn () => [
          Text::make("Share title override", 'shareTitle')
            ->helperText("Will be page title if empty"),
          Textarea::make("Share description override", 'shareDescription')
            ->helperText("Will be page description if empty")
            ->rows(3),
        ]),
      ]);
  }

  public static function createThemeGroup ( string $title = "Theme", string $name = "theme", bool $titleTemplate = true ) {
    $fields = [];
    if ( $titleTemplate ) {
      $fields[] = Text::make("Page title template", 'pageTitleTemplate')
        ->placeholder("{{site}} - {{page}}")
        ->helperText("<strong>{{site}}</strong> for site name<br><strong>{{page}}</strong> for page name.");
    }
    $fields = [
      ...$fields,
      TranslatedFields::one( fn () =>
        Text::make("App title", "title")
          ->helperText("Mobile device application title. Will default to site name if empty."),
      ),
      BasicFields::createImage("Favicon 32", "favicon32", "microImage")
        ->helperText("Icon for browsers.<br>PNG - 32px x 32px"),
      BasicFields::createImage("Favicon 1024", "favicon1024")
        ->helperText("Icon for mobile devices.<br>PNG - 1024px x 1024px"),
      ColorPicker::make("Theme color", "color"),
      ButtonGroup::make("iOS title bar style", "iosTitleBarStyle")
        ->default("default")
        ->choices([
          "default" => "Default",
          "black" => "Black",
          "black-translucent" => "Translucent",
        ])
    ];
    return RootGroup::create( $title, $name )
      ->fields( $fields )
      ->filter(function ($data) {
        if ( empty($data["pageTemplateTitle"]) )
          $data["pageTemplateTitle"] = "{{site}} - {{page}}";
        if ( empty($data["title"]) )
          $data["title"] = DocumentRequest::getSiteName();
        if ( $data["favicon32"] )
          $data["favicon32"] = $data["favicon32"]->href;
        if ( $data["favicon1024"] )
          $data["favicon1024"] = $data["favicon1024"]->href;
        return $data;
      });
  }


}
