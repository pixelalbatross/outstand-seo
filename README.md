# Outstand SEO

> One block-editor SEO UI, powered by your engine in the background.

## Description

Outstand SEO provides one custom Gutenberg sidebar that drives whichever powerful
SEO plugin is active in the background — **[The SEO Framework](https://wordpress.org/plugins/autodescription/)**
or **[Yoast SEO](https://wordpress.org/plugins/wordpress-seo/)** — while
suppressing that plugin's own editor UI. The background engine keeps
producing all frontend SEO output (`wp_head`, schema, sitemaps); Outstand SEO
only replaces the editing UX.

## How it works

- **Engine adapters** (`includes/Engines/`) detect the active SEO plugin, disable
  its native editor UI, expose its post meta to REST, and describe a canonical
  field map (canonical field → native meta key + value codec). `EngineManager`
  resolves the active engine in priority order (TSF, then Yoast).
  - **TSF:** `the_seo_framework_seobox_output` filter removes the metabox;
    fields map to `_genesis_*` / `_social_image_*` / `_primary_term_*`.
  - **Yoast:** `wpseo_enable_editor_features_{post_type}` → `false` removes the
    metabox **and** the block sidebar / pre-publish / document panels; fields
    map to `_yoast_wpseo_*` with value codecs for Yoast's encodings (3-state
    noindex, comma-separated robots-adv, `is_cornerstone`, etc.).
- **SEO sidebar** — a dedicated **SEO** editor sidebar (`PluginSidebar`, opened
  from the icon in the editor header or the more menu) with collapsible
  General / Social / Visibility sections that mirror The SEO Framework's
  metabox. Title/description and OG/Twitter title/description fields show a
  character counter colored against a recommended range. Built from core
  `@wordpress/components`; each control renders only when the active engine
  supports that field (config localized as `window.outstandSeo`). Fields write
  the active engine's **native** post meta directly, so the engine renders the
  frontend unchanged — no duplicated data, no sync.
- **SEO Analysis panel** — a document panel in the default Settings sidebar with
  the focus keyphrase and lightweight on-page checks (keyphrase placement,
  content / title / description length, links), scored with the WordPress admin
  status colors. Editor-only; the keyphrase persists on the engine's native key.
- **Breadcrumbs block** (`outstand-seo/breadcrumbs`) — engine-aware: renders the
  active engine's breadcrumb trail (`tsf_breadcrumb()` / `yoast_breadcrumb()`).
  The matching `BreadcrumbList` schema is emitted by the engine, not duplicated
  here.

## Supported fields

Fourteen canonical fields (title, description, canonical, robots
noindex/nofollow/noarchive, Open Graph + Twitter title/description/image, focus
keyphrase, redirect, primary terms) work on **both** engines. A handful are
engine-specific — TSF-only (remove site name, exclude search/archive, Twitter
card type) and Yoast-only (noimageindex, nosnippet, cornerstone, separate Twitter
image). Unsupported fields are hidden for the active engine.

See **[Engine field support](docs/engine-field-support.md)** for the full matrix
(native meta keys, codecs, and the TSF Focus-extension interop).

## Requirements

- WordPress 6.7+
- PHP 8.2+
- One supported SEO engine active: **[The SEO Framework](https://wordpress.org/plugins/autodescription/)**
  or **[Yoast SEO](https://wordpress.org/plugins/wordpress-seo/)**. With
  neither active the plugin is dormant and shows an admin notice (no panels, no
  fatals).

## Installation

### Manual Installation

1. Download the latest release ZIP from the [Releases page](https://github.com/pixelalbatross/outstand-seo/releases/latest).
2. Go to Plugins > Add New > Upload Plugin in your WordPress admin area.
3. Upload the ZIP file and click Install Now.
4. Activate the plugin.

### Install with Composer

To include this plugin as a dependency in your Composer-managed WordPress project:

1. Add the plugin to your project using the following command:

```bash
composer require outstand/seo
```

2. Run `composer install`.
3. Activate the plugin from your WordPress admin area or using WP-CLI.

## Switching SEO engines

Each engine owns its own post meta, so switching engines is a deliberate act, not
something this plugin migrates automatically. To move existing SEO data between
engines, use the destination engine's importer (e.g. The SEO Framework's
**Transport** extension imports Yoast / Rank Math / SEOPress data into TSF's
keys). Once data is in the target engine's keys, Outstand SEO's sidebar edits it
transparently.

## Adding an engine

Adapters implement `Outstand\WP\SEO\Engines\EngineInterface` (see
`includes/Engines/AbstractEngine.php` for shared REST-meta registration).
Register a new adapter in `EngineManager::candidates()`. An adapter declares:
`is_active()`, `disable_native_editor_ui()`, `register_rest_meta()`,
`get_js_config()` (field map + codecs + primary-term key pattern + focus-keyphrase
key), and `get_breadcrumb_html()`.

## Changelog

All notable changes to this project are documented in [CHANGELOG.md](https://github.com/pixelalbatross/outstand-seo/blob/main/CHANGELOG.md).

## Credits

The SEO sidebar icon is from the [Industrial Sharp UI Icons](https://www.svgrepo.com/svg/486525/anomaly) collection by Siemens AG, licensed under the [MIT License](https://opensource.org/licenses/MIT). See [CREDITS.md](CREDITS.md).

## License

This project is licensed under the [GPL-3.0-or-later](https://spdx.org/licenses/GPL-3.0-or-later.html).
