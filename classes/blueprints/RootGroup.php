<?php

namespace BareFields\blueprints;

class RootGroup
{
  // --------------------------------------------------------------------------- CONSTRUCT

  public static function create ( string $title, string $name ) : static {
    return new static( $title, $name );
  }

	public function __construct ( string $title, string $name ) {
		$this->_groupData['title'] = $title;
		$this->_groupData['name'] = $name;
	}

  // --------------------------------------------------------------------------- GROUP DATA

	protected array $_groupData = [
		'fields' => [],
		'options' => [],
		'multiLang' => false,
    'rawFields' => false,
    'seamless' => false,
    'instructions' => '',
    'position' => null,
	];

  public function getName () { return $this->_groupData['name']; }

	public function toArray () { return $this->_groupData; }

	// --------------------------------------------------------------------------- CHAINED CONFIGURATORS

  // NOTE : rawFields is not compatible with singleton yet
	public function rawFields ( bool $value = true ) {
		$this->_groupData['rawFields'] = $value;
		return $this;
	}

	public function seamless ( bool $value = true ) {
		$this->_groupData['seamless'] = $value;
		return $this;
	}

	public function fields ( array $fields ) {
    $this->_groupData['fields'] = array_merge(
      $this->_groupData['fields'],
      array_filter( $fields, fn ($item) => $item !== null ),
    );
		return $this;
	}

	public function options ( array $options ) {
		$this->_groupData['options'] += $options;
		return $this;
	}

	public function multiLang ( bool $multiLang = true ) {
		$this->_groupData['multiLang'] = $multiLang;
		return $this;
	}

	public function helperText ( string $instructions ) {
		$this->_groupData['instructions'] = $instructions;
		return $this;
	}

  public function position ( int $position = null ) {
    $this->_groupData['position'] = $position;
    return $this;
  }

  // --------------------------------------------------------------------------- REQUEST FILTER

  protected mixed $_requestFilter = null;

  public function getRequestFilter () : callable | null { return $this->_requestFilter; }

  public function filter ( callable $handler ) {
    $this->_requestFilter = $handler;
    return $this;
  }
}
