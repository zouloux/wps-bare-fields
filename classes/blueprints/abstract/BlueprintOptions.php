<?php

namespace BareFields\blueprints\abstract;

trait BlueprintOptions
{
	// --------------------------------------------------------------------------- ACF OPTIONS

	protected array $_options = [];
  public function getOptions () : array { return $this->_options; }

  /**
   * Set ACF registering options
   * @param array $options
   * @return $this
   */
	public function options ( array $options ) : static {
		$this->_options = $options;
		return $this;
	}

}
