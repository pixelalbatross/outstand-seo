# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- The SEO sidebar shows, as each empty field's placeholder, the title or
  description the active engine renders for the post's unsaved state. The
  engine computes it with its own code (The SEO Framework's generators, Yoast
  SEO's post builder and presenters) through the new
  `POST outstand-seo/v1/defaults/{id}` REST route, shortly after each edit and
  after each save.

### Fixed

- Open Graph and Twitter titles and descriptions show the engine's fallbacks:
  the edited meta title and description, Yoast SEO social templates, and the
  excerpt.
- Yoast SEO: new posts show defaults before their first save.
- The SEO Framework: the Twitter description renders when Open Graph output is
  off.

## [1.3.1] - 2026-10-03

### Fixed

- The default meta title in the SEO sidebar now matches the title the active
  engine renders. Text that filters add around the post title
  (`the_seo_framework_title_from_generation`, `wpseo_title`), The SEO
  Framework's protection status, and the site name are kept while the title is
  edited.
- Yoast SEO: the live title keeps the space between the post title and the
  rest of the title format, so it no longer renders as `Title- Site`.
- Clearing the post title shows the title the engine renders for an untitled
  post, in place of the title from when the editor loaded.
- The SEO Framework: the static default title includes the site name.

## [1.3.0] - 2026-09-25

### Added

- Structured data: the `outstand_seo_schema_nodes` filter adds nodes to the
  active engine's JSON-LD graph (The SEO Framework or Yoast SEO), with `@id`
  references to the engine's Organization, Person, WebSite and WebPage entities.
  Untyped nodes and nodes restating an `@id` already in the graph are dropped.
- `EngineInterface::get_schema_graph_filter()`, naming the filter an engine's
  graph passes through.

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
