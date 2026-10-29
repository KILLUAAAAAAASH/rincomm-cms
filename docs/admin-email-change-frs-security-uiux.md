# Admin Login Email Change — FRS → Security → UI/UX Checkpoint

## FRS
Module 1: User Management (profiles, role-based access and activity logs). This is an FRS-aligned extension to manage an administrator's own login email; the FRS does not explicitly prescribe this exact change mechanism. Do not conflate this with Module 9 Feature 5 customer profile update requests.

## Security
- Administrator-only own-account change, behind `auth`, `active`, and `role:admin` middleware.
- Existing password required on request and confirmation; submission throttling; CSRF.
- New inbox must receive verification link; current login email unchanged before confirmation.
- Random 256-bit one-use token stored only as SHA-256 hash in database; 30-minute expiry.
- New email uniqueness checked before requesting and again at confirmation; stale links rejected.
- Activity logs contain no password or verification token.
- After confirmation: update email, mark verified, revoke database-backed sessions, rotate remember token, sign out current session.
- SMTP delivery required for real confirmation; never use a fake inbox or treat a Mail::fake test as delivered.

## UI/UX
- Administrator opens **administrator account pop-up -> My Profile** (no hidden URL).
- Shows current email and optional pending request, with new email and password form.
- Verification link opens a confirmation page in the same Rincomm CMS layout; reconfirm password.
- Validation errors, success state, and theme-responsive design provided.

## Acceptance status
- Source implementation script prepared; syntax checks performed on generated PHP.
- Isolated SQLite focused/full tests must pass on user's Windows project.
- Local MySQL migration, actual SMTP inbox verification and browser acceptance pending.
- Do NOT mark complete or commit/push before verification.


## Integrated profile UI correction
- Administrators open administrator account pop-up -> My Profile, the same shared profile design used by customer accounts.
- Email input is editable within Profile Information; a separate verified-email action is associated with the account profile form (no nested forms).
- Existing name Save Changes behavior is preserved. Email changes only complete after the separate verification link and password reconfirmation.
- The old admin.email-change.edit endpoint now redirects to My Profile; no duplicate standalone settings screen is used.
