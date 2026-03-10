# Changelog

## 0.2.1 - 2026-03-10

### Added
- Reusable invite links for admins with optional use limit and expiration.
- Admin controls for reusable invites in `/mod` tools: create + revoke.
- Post-style formatting support for homepage announcements.
- Automatic opening of reply form when `?quote=<post_id>` is present.
- New tests for reusable invites, attachment upload limits, pagination window, and quote-open behavior.

### Changed
- Refactoring of posting macro handling into dedicated service.
- Refactoring of attachment upload limits into value object and unified validation wiring.
- Recovery key flow aligned with `RecoveryKey` value object API.
- Mod tools invite UI compacted for better mobile/desktop layout.

### Fixed
- Compatibility fallback for databases where invite reusable columns are not migrated yet.
- Frontend layout issues in reusable invite form (input/button overflow and spacing).
- Regression protection for reply form open state via dedicated feature test.

