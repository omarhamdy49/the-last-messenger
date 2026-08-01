# Public Google Login / Registration Tasks

Scope: public visitors only. Do not change or reuse any administrative authentication route, controller, guard, middleware, page, or test.

## 1. Public application fixes

- [x] Fix the homepage guest CTA to use the existing `public.register` route instead of the missing `register` route.
- [x] Require Google's verified-email claim before creating a public account, linking Google to an existing public account, or marking an email verified.
- [x] Add a localized error for a Google account whose email is missing or unverified in Arabic, English, French, Turkish, and Urdu.
- [x] Replace the Lucide Chrome icon with the official multicolour Google “G” on the public login page, registration page, and public login modal.
- [x] Keep email/password authentication available as the fallback.
- [x] Keep Google OAuth stateful, session-based, guest-only, and throttled.
- [x] Keep the public-page return URL restricted to same-site relative paths.
- [x] Do not store Google access tokens or refresh tokens.

## 2. Google Auth Platform

- [ ] Use a dedicated Google Cloud project for `rasoolallah.com`.
- [ ] Configure application branding with the public website name, support email, developer contact, homepage, privacy-policy URL, and terms URL.
- [ ] Configure an External audience for public Google users.
- [ ] Request only `openid`, `profile`, and `email`.
- [ ] Create a Web application OAuth client.
- [ ] Register the exact production callback: `https://rasoolallah.com/auth/google/callback`.
- [ ] Keep the OAuth client secret outside Git and application artifacts.

## 3. Production activation

- [ ] Add `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT=https://rasoolallah.com/auth/google/callback` to the server's shared production `.env`.
- [ ] Confirm the existing social-login migration is applied.
- [ ] Rebuild Laravel's configuration, route, and view caches.
- [ ] Confirm the Google button becomes visible on `/login`, `/register`, and the public login modal.

## 4. Automated verification

- [x] Cover new Google-user registration.
- [x] Cover returning Google-user login without duplication.
- [x] Cover linking a verified Google email to an existing public account.
- [x] Reject missing and unverified Google emails without creating or linking an account.
- [x] Cover cancellation and provider failures.
- [x] Cover the intended public-page redirect from the login modal.
- [x] Cover conditional Google-button visibility.
- [x] Cover the homepage registration CTA.

## 5. Browser acceptance test

- [ ] Register a new public account with Google on production.
- [ ] Log out and sign in again with the same Google account.
- [ ] Confirm no duplicate user is created.
- [ ] Start from a protected public feature and confirm return to the originating page.
- [ ] Cancel Google consent and confirm a localized recoverable error.
- [ ] Verify Arabic RTL and English LTR layouts on desktop and mobile.
- [ ] Confirm email/password login and registration still work.

## 6. Rollback

- [ ] Remove the three Google environment values and rebuild Laravel's config cache; the conditional buttons will disappear while email/password authentication remains available.
