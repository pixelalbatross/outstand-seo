<?php
/**
 * Tri-state robots codec with no explicit "on" native value.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines\Codec;

/**
 * Canonical 'default' | 'on' | 'off' for engines that store only the restrictive
 * state (e.g. Yoast nofollow: '1' = nofollow, absent otherwise). "on" and
 * "default" both collapse to the default native value; only "off" persists.
 */
class TwoStateTriState implements CodecInterface {

	/**
	 * Native value meaning "off" (restrictive).
	 *
	 * @var string
	 */
	private string $off;

	/**
	 * Native value meaning "default".
	 *
	 * @var string
	 */
	private string $fallback;

	/**
	 * Constructor.
	 *
	 * @param string $off      Native "off" value.
	 * @param string $fallback Native "default" value.
	 */
	public function __construct( string $off, string $fallback = '0' ) {
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

		return $this->off === $value ? 'off' : 'default';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $value   Canonical value.
	 * @param mixed $current Current native value (unused).
	 * @return string
	 */
	public function encode( $value, $current ) {
		return 'off' === $value ? $this->off : $this->fallback;
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
