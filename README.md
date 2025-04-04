# WPS - Bare Fields

ACF Pro with blueprints. Handle ACF field management in PHP files with a heavily typed structure.

> This plugin is still WIP so not documented yet. 

**What it does :**
- Use git with your ACF fields, no JSON, no DB fields
- Type your field structure, no mistakes
- Multi-lang is included, no need for a multi-lang plugin
- Better for headless Wordpress
- Add drag and drop in the admin on post list to set orders
- Use blueprints to create complex structure
- Fill admin fields with custom data ( query the db, request AirTable, etc ... )
- Manage upload sizes and formats ( with webp and color extraction )
- Query documents from blueprints easily 

**What it does not :**
- No multi-lang management for native Wordpress theming or Timber 



### Dependencies

Dependencies to install in `composer.json` at root of your Wordpress project with [Bedrock](https://roots.io/bedrock/).
-  `"vinkla/extended-acf": "^14.3"`
- `"wpengine/advanced-custom-fields-pro": "^6.3"`

### Documentation

WIP

### TODO

beta :
- Décider si on renomme :
  - Attachment en AttachementVO,
  - ImageAttachement en ImageAttachementVO
  - etc
- Document
  - Finir fetchContent / fetchExcerpt / fetchAuthor / fetchTags / fetchCategories
- TranslationHelpers::getDateFormat
- Faire système pour gérer entièrement le menu Admin

v1
- Faire une doc minimaliste correct
- Faire project example
- Publication GitHub

