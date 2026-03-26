# Charts Refactor & Continuation Plan

## 1. Source Management (CRUD)
- [ ] **DB Logic**: Add `save_source($data)` to `SourceManager.php` for upserting records.
- [ ] **Admin UI**: Refactor `admin/views/sources.php` to include an "Add/Edit Source" form with field validation.
- [ ] **Handlers**: Update `Bootstrap.php` to process `save_source`, `delete_source`, and `toggle_source` actions.
- [ ] **Validation**: Implement client-side and server-side validation for source fields (name, platform, type, url, etc.).

## 2. Ingestion & Scraper Reliability
- [ ] **YouTube Extraction**:
    - [ ] Update `YouTubeParser.php` to support multiple JSON/HTML extraction strategies.
    - [ ] Log specific failure reasons (e.g., `unsupported_public_structure`, `blocked_fetch`) in `wp_charts_import_runs`.
- [ ] **Spotify Pipeline**:
    - [ ] Ensure `SpotifyCsvImporter` correctly maps all 9 standard CSV columns.
    - [ ] Enhance `SpotifyEnrichmentService` to store `is_enriched` status and handle fallback batching.
- [ ] **Import Runs**: Update `admin/views/results.php` to show detailed diagnostics and row counts.

## 3. Frontend & Data Display
- [ ] **Routing**: Standardize URLs like `/charts/track/slug` in `Router.php`.
- [ ] **Index View**: Update `/charts` to show all available charts grouped by country/platform.
- [ ] **Single Chart View**: Implement a premium, cinematic list with artwork, movement indicators, and expandable rows.
- [ ] **Entity Details**: Create artist/track/album/video detail pages with historical chart performance.

## 4. Admin Visibility & Utilities
- [ ] **Entities Explorer**: Implement a data table in `admin/views/entities.php` for browsing the unified database of music items.
- [ ] **Matching Overview**: Ensure the Matching view calculates and displays counts of ambiguous rows.
