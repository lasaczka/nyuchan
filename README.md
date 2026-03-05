# Nyuchan

Private imageboard built on Laravel 12 (no JavaScript on the frontend).

## Features
- Invite-only registration.
- Roles: `user`, `mod`, `admin`.
- Moderation: delete posts/threads, ban author, moderation log.
- Localization: `be` (primary), `ru`, `en`.
- Themes: `sugar`, `makaba`, `re-l`, `nyu`.
- Attachments: images + thumbnails, served via `/media/...`.
- Storage backends: `local` and `s3` (Cloudflare R2).

## Important `.env` settings

### Attachment storage
```env
ATTACHMENTS_DISK=local
ATTACHMENTS_FALLBACK_DISKS=local
```

- `ATTACHMENTS_DISK`: where new uploads are stored (`local` or `s3`).
- `ATTACHMENTS_FALLBACK_DISKS`: fallback read disks if the file is missing on the primary disk (for example `local` after migration to `s3`).

### Attachment limits
```env
ATTACHMENTS_INPUT_MAX_BYTES=20971520
ATTACHMENTS_TARGET_MAX_BYTES=5242880
```

- Input file size limit: `20 MB`.
- Auto-compression for `JPG/PNG/WEBP` above `5 MB` down to `5 MB`.
- `GIF` is not auto-compressed.

### R2 / S3
```env
ATTACHMENTS_DISK=s3
ATTACHMENTS_FALLBACK_DISKS=local

AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_BUCKET=nyuchan
AWS_DEFAULT_REGION=auto
AWS_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

`AWS_URL` is optional because media is served through backend routes (`/media/...`).

### SSL for local R2 testing
```env
AWS_SSL_VERIFY=false
```

For production:
```env
AWS_SSL_VERIFY=true
# AWS_CA_BUNDLE=/path/to/cacert.pem
```

## PHP upload limits

Minimum recommended values for current limits:
```ini
upload_max_filesize = 20M
post_max_size = 24M
```

After changing `php.ini`, restart your server process (`php artisan serve`).

## Cookies and data

The app uses:
- `laravel_session` (session/auth/theme/language state),
- `XSRF-TOKEN` (CSRF protection),
- `remember_*` (if "remember me" is enabled),
- `thread_owner_{id}` (thread owner token).

For anti-abuse, the app uses `abuse_id` in format `u:{user_id}` (no IP-based ban logic).

## Commands
```bash
php artisan migrate --force
php artisan optimize:clear
php artisan serve
```

### Bootstrap first admin
For the first production login, use the interactive command:

```bash
php artisan nyuchan:bootstrap-admin
```

The command:
- asks for `username`,
- asks for password + confirmation,
- if the user already exists, offers to promote to `admin` and reset password,
- if the user does not exist, creates a new admin.

Recommended deployment order:

```bash
php artisan migrate --force
php artisan nyuchan:bootstrap-admin
php artisan optimize:clear
```

After that, log in as admin and generate invites from the profile page.

## Frontend without JS

The UI is fully server-rendered HTML + CSS with no client-side JavaScript.
Default Laravel Vite/NPM artifacts were removed from this repository.
