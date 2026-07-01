<?php
/**
 * Tri-state robots codec backed by a token in a shared CSV meta value.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines\Codec;

/**
 * Canonical 'default' | 'on' | 'off' where "off" means the token is present in a
 * comma-separated list shared with other fields (e.g. Yoast robots-adv
 * `noarchive`). The engine has no "force on", so 'on' and 'default' both mean the
 * token is absent.
 */
class CsvTriState implements CodecInterface {

	use HandlesTokens;

	/**
	 * The token this field owns within the shared CSV value.
	 *
	 * @var string
	 */
	private string $token;

	/**
	 * Constructor.
	 *
	 * @param string $token Token owned by this field.
	 */
	public function __construct( string $token ) {
		$this->token = $token;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $raw Native meta value.
	 * @return string
	 */
	public function decode( $raw ) {
		return in_array( $this->token, $this->split_tokens( $raw ), true ) ? 'off' : 'default';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $value   Canonical value.
	 * @param mixed $current Current native CSV value (preserves other tokens).
	 * @return string
	 */
	public function encode( $value, $current ) {
		return $this->with_token( $current, $this->token, 'off' === $value );
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
