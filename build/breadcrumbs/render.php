<?php
/**
 * Server render for the SEO Breadcrumbs block.
 *
 * Delegates to the active SEO engine's breadcrumb output (TSF's
 * `tsf_breadcrumb()` or Yoast's `yoast_breadcrumb()`). The matching
 * BreadcrumbList JSON-LD is emitted by the engine, so we do not add schema.
 *
 * @package OutstandSEO
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block default content.
 * @var WP_Block $block      Block instance.
 */

use Outstand\WP\SEO\Engines\EngineManager;

$engine = EngineManager::get_active();

if ( null === $engine ) {
	return;
}

$breadcrumbs = $engine->get_breadcrumb_html( $attributes );

if ( '' === trim( (string) $breadcrumbs ) ) {
	return;
}

printf(
	'<div %s>%s</div>',
	get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are escaped by core.
	$breadcrumbs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Engine returns escaped, self-contained markup.
);
