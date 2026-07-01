<?php
/**
 * Tri-state robots codec (Default / permissive / restrictive).
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines\Codec;

/**
 * Canonical 'default' | 'on' | 'off' backed by three configurable native values.
 * "on" is the permissive directive (index/follow/archive), "off" the restrictive
 * one. The SEO Framework uses -1/0/1; Yoast's noindex uses '2'/'0'/'1'.
 */
class TriState implements CodecInterface {

	/**
	 * Native value meaning "on" (permissive).
	 *
	 * @var mixed
	 */
	private $on;

	/**
	 * Native value meaning "off" (restrictive).
	 *
	 * @var mixed
	 */
	private $off;

	/**
	 * Native value meaning "default".
	 *
	 * @var mixed
	 */
	private $fallback;

	/**
	 * Constructor.
	 *
	 * @param mixed $on       Native "on" value.
	 * @param mixed $off      Native "off" value.
	 * @param mixed $fallback Native "default" value.
	 */
	public function __construct( $on, $off, $fallback ) {
		$this->on       = $on;
		$this->off      = $off;
		$this->fallback = $fallback;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $raw Native meta value.
	 * @return string
	 */
	public function decode( $raw ) {
		$value = (string) $raw;

		if ( (string) $this->on === $value ) {
			return 'on';
		}

		if ( (string) $this->off === $value ) {
			return 'off';
		}

		return 'default';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $value   Canonical value.
	 * @param mixed $current Current native value (unused).
	 * @return mixed
	 */
	public function encode( $value, $current ) {
		if ( 'on' === $value ) {
			return $this->on;
		}

		if ( 'off' === $value ) {
			return $this->off;
		}

		return $this->fallback;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function kind(): string {
		return 'robotsTri';
	}
}
