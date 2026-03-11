# PWA Operations Guide

This guide explains how to validate and operate the Akaunting PWA setup in on-premise deployments.

## What Is Implemented

- Service worker registration is versioned with `?v={{ version('short') }}`.
- Cache names are scoped per installation path to avoid collisions.
- Offline fallback page exists at `offline.html`.
- Desktop install UI warning is handled with a `wide` screenshot in `manifest.json`.

## Release Behavior

1. Akaunting version changes in release.
2. `version('short')` output changes.
3. Browser fetches a new service worker URL because query string changes.
4. New cache buckets are created for the new version.
5. Old cache buckets for the same scope are cleaned during `activate`.

## Deploy Checklist (Required)

1. Open app URL.
2. Chrome DevTools -> Application -> Service Workers.
3. Click `Update` and `Skip waiting`.
4. Hard refresh (`Ctrl+F5`).
5. Check Cache Storage:
- Cache names should include scope + current app version.
6. Offline test:
- DevTools Network -> `Offline`
- Refresh page
- `offline.html` should be shown.
7. Manifest test:
- Application -> Manifest
- No installability errors.

## Lighthouse Acceptance Targets

Run Lighthouse against a logged-out page when possible.

- PWA category: >= 90
- Performance: >= 70
- Best Practices: >= 90
- Accessibility: >= 85
- SEO: >= 85

If PWA is below target, first check:

1. Service worker registration and active status.
2. Offline fallback availability.
3. Broken icon/screenshot paths in `manifest.json`.
4. Console errors related to service worker or fetch handlers.

## Troubleshooting Quick Notes

- Error `Failed to convert value to 'Response'`:
  Offline fallback response is missing. Validate `offline.html` is cached.
- New release but stale assets:
  Ensure service worker URL query version changed and run `Update` + `Skip waiting`.
- Multiple on-premise installs on same host:
  Scope-based cache naming prevents cross-project cache pollution.
