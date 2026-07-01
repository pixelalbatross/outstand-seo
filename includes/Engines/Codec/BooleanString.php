<?php
/**
 * Boolean codec backed by two literal string native values.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines\Codec;

/**
 * Canonical boolean stored as configurable strings (e.g. Yoast cornerstone:
 * '1' / 'false').
 */
class BooleanString implements CodecInterface {

	/**
	 * Native value for true.
	 *
	 * @var string
	 */
	private string $true_value;

	/**
	 * Native value for false.
	 *
	 * @var string
	 */
	private string $false_value;

	/**
	 * Constructor.
	 *
	 * @param string $true_value  Native value meaning true.
	 * @param string $false_value Native value meaning false.
	 */
	public function __construct( string $true_value, string $false_value ) {
		$this->true_value  = $true_value;
		$this->false_value = $false_value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $raw Native meta value.
	 * @return bool
	 */
	public function decode( $raw ) {
		$value = (string) $raw;

		return $this->true_value === $value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $value   Canonical value.
	 * @param mixed $current Current native value (unused).
	 * @return string
	 */
	public function encode( $value, $current ) {
		return $value ? $this->true_value : $this->false_value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function kind(): string {
		return 'bool';
	}
}
