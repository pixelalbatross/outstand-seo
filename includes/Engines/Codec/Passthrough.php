<?php
/**
 * Passthrough codec: store the canonical value verbatim.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines\Codec;

/**
 * Stores strings (or integers) unchanged. Used for titles, URLs, ids, etc.
 */
class Passthrough implements CodecInterface {

	/**
	 * Canonical kind ('string' or 'int').
	 *
	 * @var string
	 */
	private string $kind;

	/**
	 * Constructor.
	 *
	 * @param string $kind 'string' (default) or 'int'.
	 */
	public function __construct( string $kind = 'string' ) {
		$this->kind = 'int' === $kind ? 'int' : 'string';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $raw Native meta value.
	 * @return mixed Canonical value.
	 */
	public function decode( $raw ) {
		return 'int' === $this->kind ? (int) $raw : (string) ( $raw ?? '' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $value   Canonical value.
	 * @param mixed $current Current native value (unused).
	 * @return mixed Native value.
	 */
	public function encode( $value, $current ) {
		return 'int' === $this->kind ? (int) $value : $value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function kind(): string {
		return $this->kind;
	}
}
