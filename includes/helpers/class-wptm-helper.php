<?php



class WPTM_Helper {

	/**
	 * WPTM_Helper constructor.
	 */
	public function __construct() {

		$this->tournament       = new WPTM_Tournament_Helper( $this );

	}

	public function __set( $key, $value ) {
		$this->items[ $key ] = $value;
	}

	public function __get( $key ) {
		if ( isset( $this->items[ $key ] ) ) {
			return $this->items[ $key ];
		}

		return null;
	}

	public function __isset( $key ) {
		return isset( $this->items[ $key ] );
	}

	public function __unset( $key ) {
		if ( isset( $this->items[ $key ] ) ) {
			unset( $this->items[ $key ], $this->raws[ $key ], $this->frozen[ $key ] );
		}
	}
}


function WPTM() {

	static $object = null;

	if ( is_null( $object ) ) {

		$object = new WPTM_Helper();

	}

	return $object;

}
