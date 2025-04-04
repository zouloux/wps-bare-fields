<?php

namespace BareFields\blueprints\abstract;

trait BlueprintEditor
{
	// --------------------------------------------------------------------------- EDITOR

	protected bool $_editor = false;
  public function getEditor () : bool { return $this->_editor; }

	public function editor ( bool $editor = true ) : static {
		$this->_editor = $editor;
		return $this;
	}

	// --------------------------------------------------------------------------- EXCERPT

	protected bool $_excerpt = false;
  public function getExcerpt () : bool { return $this->_excerpt; }

	public function excerpt ( bool $excerpt = true ) : static {
		$this->_excerpt = $excerpt;
		return $this;
	}

}
