# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-07-02

### Changed

- Breadcrumbs are now driven through the core Breadcrumbs block
  (`core/breadcrumbs`, WordPress 7.0+) via a `render_block` override: the stock
  block renders the active engine's trail (The SEO Framework or Yoast SEO) and
  its matching JSON-LD, instead of shipping a separate block.
- The engine breadcrumb trail now honors the core block's controls where the
  active engine supports them — separator, show/hide the home and current
  crumbs, front-page visibility, and a custom home label — resolved per block
  instance without leaking into other breadcrumb output.

### Added

- Engine breadcrumb capability map: the editor hides or annotates the core
  Breadcrumbs controls an active engine cannot honor, and adds an engine "Home
  label" control.
- Breadcrumbs now render in the editor preview under the REST block-renderer by
  rebuilding the trail from the block's post context.

### Removed

- The standalone `outstand-seo/breadcrumbs` block, replaced by the
  `core/breadcrumbs` override above.

## [1.1.0] - 2026-07-02

### Changed

- Moved the primary-term selector out of the SEO sidebar and into the core
  taxonomy panel (Categories, etc.), matching where Yoast and The SEO Framework
  place theirs, via the `editor.PostTaxonomyType` filter.
- The primary-term control now prefills the engine's resolved primary term (for
  The SEO Framework, its own fallback resolver) instead of showing an empty
  selection, and no longer offers a "none" option — one term is always primary
  once two or more are assigned, mirroring Yoast and TSF.

### Fixed

- Primary-term label now uses the taxonomy singular name (e.g. "Primary
  Category" instead of "Primary Categories").

## [1.0.0] - 2026-07-01

### Added

- Engine-agnostic block-editor SEO sidebar that drives the active SEO engine
  (The SEO Framework or Yoast SEO) in the background.
- Engine adapter layer (`includes/Engines/`) that disables each engine's native
  editor UI and maps canonical fields to the engine's native meta.
- Engine-aware breadcrumb block.
- Lightweight on-page content analysis (focus keyphrase + checks).
