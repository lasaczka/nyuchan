# Nyuchan ZBT Manual Checklist

## 1) Auth + Locale + Theme
- Register via valid invite link.
- Login/logout.
- Switch `be/ru/en`, reload page, ensure texts change.
- Switch theme, reload page, ensure theme persists.

## 2) Boards / Threads / Posts
- Open each board (`/a/`, `/b/`, `/rf/`, `/nsfw/`) and hidden board (`/bb/`) by direct link.
- Create thread with and without `Name posting`.
- Reply with and without `Name posting`.
- Check post formatting: `>>123`, greentext, bold/italic/strike/underline/spoiler.

## 3) Attachments
- Post with 1/2/3/4 images and verify thumbnail layout.
- Open image by direct link.
- Test JPG/PNG/GIF/WEBP.
- Test upload limits and readable validation errors.

## 4) Moderation
- As mod/admin: delete post, delete thread.
- As mod/admin: ban post author, ban thread author.
- Verify ban blocks further posting.
- Verify self-ban is blocked.
- Toggle mod tools in `/mod` and confirm UI blocks hide/show.

## 5) Invites
- User: second invite within 60 min must be blocked.
- Mod: second invite within 10 min must be blocked.
- Admin: generate multiple invites without cooldown.
- Profile shows last unused invite.
- Admin profile shows all active invites.

## 6) Error Pages
- 404 page shows random phrase and random fallback board link.
- 500 page shows random phrase.
- In local env: open `/_debug/500`.

## 7) Persistence / Storage
- Restart app and verify posts/media still available.
- Verify media storage disk config (`local` / `s3`) works in current environment.

## 8) Security / Access
- Unauthenticated access to boards/media should be denied.
- Hidden board is not listed in menu but opens by direct URL.
- Mod cannot change user roles.
- Admin can change roles.
