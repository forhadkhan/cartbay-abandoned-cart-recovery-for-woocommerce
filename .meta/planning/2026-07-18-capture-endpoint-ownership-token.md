# Plan: Token-Based Ownership for the Public `/capture` Endpoint

- **Date:** 2026-07-18
- **Status:** Implemented and verified 2026-07-18 (phpcs/phpstan/phpunit green; 5/5 behavioral REST tests pass incl. the money test). `wp plugin check` still to be run in a www-data-writable environment.
- **Scope decision:** Token-gate update + delete (keep both on the public endpoint; authorize by a per-session secret token). The leaner "create-only" alternative was considered and rejected because it degrades UX (duplicate sessions on re-capture) and consent responsiveness (unchecking consent at checkout would no longer delete immediately).
- **Trigger:** WordPress.org plugin review, round 3 / final (review email 2026-07-17, Review ID `R cartbay-abandoned-cart-recovery-for-woocommerce/forhadkhan/6Jul26/T3 17Jul26`).

## Context

CartBay's WordPress.org submission is in its **third and final review round**. Every earlier issue (trialware/locked features, `wp_enqueue` for inline scripts, output escaping, the Logger writing a `.php` file into `uploads/`, and public source availability) is resolved and no longer flagged — verified: `app/Utils/Logger.php` now performs no file I/O.

One objection remains, with a hard warning: *"If more issues of the same nature are found in the following review, this plugin will not be reviewed again."*

The objection (verbatim from the 2026-07-17 email):
> Public access is allowed for create/update/delete session actions, but the handler only relies on a generic REST nonce plus **matching a supplied email**, which is not sufficient authorization or proof of ownership for **mutating another shopper's session**.

The reviewer's principle is correct and non-negotiable: **an email address is not a secret**, so "the request supplied an email that matches the stored email" is not proof of ownership for a public mutating endpoint. The endpoint (`POST /cartbay/v1/capture`, `permission_callback => '__return_true'`) today authorizes UPDATE/DELETE by email-match (`CaptureService::session_email_matches`), and DELETE even falls back to `find_active_by_email($email)` — so knowing a shopper's (non-secret) email alone lets an attacker delete/suppress their session. UPDATE has a parallel hole via `find_active_by_email_and_cart_fingerprint`.

The review also explicitly instructs authors to find *other* instances of the same defect (*"the Plugins Team may not share all cases of the same issue"* … *"search for any other similar occurrences"*). There are three email-as-ownership paths in `CaptureService.php`; all three must close, or the "same nature" warning triggers a permanent rejection.

**Goal:** authorize every *mutation of an existing session* by possession of a per-session **secret bearer token** — the same model as the WooCommerce Store API cart token, and the same primitive the plugin already uses for restore/unsubscribe links (`TokenHelper`). CREATE stays public and bound to the shopper's genuine server-side WooCommerce cart — it has no prior owner, so it needs no ownership proof; this mirrors core WooCommerce guest checkout (a guest types any billing email, and abandonment triggers store emails, each carrying a secure unsubscribe token). The endpoint stays `__return_true`; the security moves from a claimed email to a real secret.

## Approach

Issue a capture token on CREATE, return the plaintext to the client, store only its SHA-256 hash server-side, and require a `hash_equals` match for UPDATE and DELETE. Remove **all** email-as-authorization paths.

### 1. `app/Utils/TokenHelper.php` — add capture-token helpers (reuse `generate()` / `hash()`)

Two static methods mirroring `create_restore_token()` / `validate_restore_token()`, using a new distinct meta key `_cartbay_capture_token_hash` (no collision with existing `_cartbay_token_hash(es)` / `_cartbay_restore_token_hash` / `_cartbay_unsub_token_hash`):

- `create_capture_token( \WC_Order $session ): string` — `generate()`, store `hash()` in `_cartbay_capture_token_hash`, `save()`, return the plaintext.
- `validate_capture_token( \WC_Order $session, string $token ): bool` — **guard first: return `false` if `'' === $token` OR the stored hash is empty**, then `hash_equals( $stored, self::hash( $token ) )`. The empty-guard is mandatory: `hash_equals('', '')` is `true`, which would otherwise let any request with an empty token authorize against any legacy pre-token session — a universal bypass worse than the original hole. (Model: `validate_restore_token()` line 104.)

### 2. `app/Recovery/CaptureService.php` — token authz; drop every email/fingerprint authorization path

- **`capture()` return contract** changes from `int|WP_Error` to `array{session_id:int, capture_token:string}|WP_Error`. `capture_token` is the new plaintext only on CREATE; `''` on UPDATE (the client keeps the token it already holds — plaintext is never recoverable server-side). Exactly one caller to update (see §3; verified: no other references in free or `cartbay-pro`).
- **New signature:** `capture( string $email, array $cart_data, string $source, int $session_id = 0, string $capture_token = '' )`.
- **Resolve an existing session ONLY by `session_id` + valid token.** Keep the `session_id > 0` branch but replace the ownership check with the token check. **Delete the `find_active_by_email_and_cart_fingerprint()` fallback (≈ lines 99-101) entirely** — it is a second email/reproducible-cart authorization path. Rule: never write to a record whose token you didn't verify; when there is no valid `session_id`+token, always CREATE a fresh session.
- **`can_update_session()`:** drop the `session_email_matches` check and the cart-fingerprint check; **keep the status allowlist** (`cartbay-captured` / `cartbay-abandoned` only) and add `TokenHelper::validate_capture_token()`. The status gate must stay because `SessionRepository::update()` (line 92) also writes to `recovered` / `expired` / `suppressed`, and a valid owner-token must not resurrect a recovered or suppressed/unsubscribed record.
- **On CREATE success**, call `TokenHelper::create_capture_token( $session )` and return the plaintext in the result array. On UPDATE, return `capture_token => ''`.
- **`delete_after_consent_withdrawal()`:** change signature to `( int $session_id, string $capture_token )` (drop `$email`). Resolve strictly by `session_id`; require status ∈ {captured, abandoned} **and** `validate_capture_token()`. **Remove the `find_active_by_email()` fallback (≈ lines 553-555).** No email is used at all.
- Delete `session_email_matches()` if it has no remaining callers after these edits (grep to confirm).

### 3. `app/Api/Routes/CaptureRoute.php` — plumb the token; drop the email-on-delete requirement

- Add a `capture_token` arg to the `args` schema: `type => 'string'`, `sanitize_callback => 'sanitize_text_field'` (**not** `absint`), `required => false`, `default => ''`. (`wp_generate_password(64, false)` is `[A-Za-z0-9]`, so sanitize is a no-op and hashing stays consistent on write and compare.)
- Read `$capture_token = sanitize_text_field( $request->get_param( 'capture_token' ) )`.
- **DELETE branch (`! $consent`):** require `$session_id > 0 && '' !== $capture_token`; otherwise return the existing "Invalid or expired request. Please reload the checkout page…" error (reuse the `cartbay_invalid_origin`-style string — **no new translation string**). Call `delete_after_consent_withdrawal( $session_id, $capture_token )`. Remove the email-required 422 and the email argument.
- **CREATE/UPDATE branch:** pass `$capture_token` to `capture()`, unpack the array, and include `capture_token` in the 201 response body **only when non-empty**. Keep the `is_email` check for CREATE (a valid email is still required to enroll).
- **Update the now-inaccurate comments** (registration block ≈ lines 62-74, `has_valid_origin` block ≈ lines 108-116, delete-branch comment ≈ lines 176-178) to describe *per-session capability token* authorization instead of "email-ownership." A final-round reviewer reads these; leaving "proof of email ownership" in the comments while the code uses tokens reads as contradictory.
- Keep the `wp_rest` nonce (CSRF defense-in-depth, not authorization) and the rate limiter unchanged. Do **not** add the token to any `Logger::*` call (keep logging `session_id` only).

### 4. Client JS — `src/capture/index.js` and `src/block/index.js` (same pattern in both)

- Add `getStoredToken()` / `storeToken()` / `clearToken()` for sessionStorage key `cartbay_capture_token`, parallel to the existing `cartbay_session_id` helpers. Make the existing "clear session id" also clear the token.
- On a successful **create** response, store both: `storeSessionId(json.session_id)` and, when present, `storeToken(json.capture_token)`. (Update responses omit `capture_token` — keep the existing one.)
- `sendCapture` payload: add `capture_token: getStoredToken()`.
- `deleteCapture`: **gate on `sessionId && token`** (drop the email gate); send `{ consent:false, source, session_id, capture_token }` (email no longer needed); clear both session_id and token afterward.

### 5. Rebuild assets

`npm run build` (`wp-scripts`, per `webpack.config.js`) regenerates `assets/js/cartbay-capture.js`, `assets/js/cartbay-block.js`, and their `*.asset.php`. Confirm the source-banner still prepends and the compiled bundles now contain `capture_token`.

### 6. i18n

The plan reuses existing strings, so ideally no `.pot` delta. If any user-facing string changed, run `npm run i18n:make-pot` to refresh `languages/…pot`.

## What is intentionally NOT changed

- **CREATE remains public and email-typed**, bound to the real server-side WC cart (`maybe_load_wc_cart()` → `no_active_cart` when absent). It creates a new record with no prior owner — consistent with core WooCommerce guest checkout. No double-opt-in (out of scope; adding scope in a final round invites new objections).
- The email-link unsubscribe flow (`Plugin.php::handle_unsubscribe_request`, `?cartbay_unsubscribe=TOKEN`) is already token-secure and untouched.
- **Legacy in-flight sessions** (created minutes before deploy, tab still open, no token): their capture-time consent-withdrawal will cleanly no-op (ownership unprovable). Blast radius is tiny; privacy is still covered by the email unsubscribe link and retention pruning. **Do not** add an email fallback "for compatibility" — that reintroduces the exact hole.

## Verification (must prove the hole is closed, live at `/var/www/html`)

**Static:** `npm run build` succeeds; `composer phpcs`, `composer phpstan`, and `composer test` (phpunit) pass; `npm run plugin-check` (`wp plugin check`) is clean; grep compiled assets for `capture_token`; confirm no plaintext token in any Logger output.

**Behavioral (Playwright / real checkout against the running site):**
1. **Setup victim** — real cart → checkout → type `victim@x.com` + consent → capture. Confirm order N is `cartbay-captured`, meta `_cartbay_capture_token_hash` set, plaintext stored nowhere server-side.
2. **THE MONEY TEST — email-only DELETE with a guessed order ID, no token:** POST `{consent:false, email:victim@x.com, session_id:N}` with a valid `wp_rest` nonce. **Must** return 4xx, `deleted:false`, order N intact. (Succeeds today via `find_active_by_email`; must now fail.)
3. DELETE with `capture_token:''` and with `capture_token:'garbage'` → both fail (proves the empty-hash guard).
4. **Email+fingerprint UPDATE, no token:** attacker adds the same product to their own cart, POSTs `{consent:true, email:victim@x.com, session_id:0}` → must CREATE a new attacker session and leave order N's snapshot / `last_activity_at` / source unchanged (proves the fingerprint path is gone).
5. Guessed `session_id:N` + victim email + invalid token → order N unchanged.
6. **Owner UPDATE** (same tab, holds token) → order N updated in place, no new order.
7. **Owner DELETE** (same tab, holds token) → 200 `deleted:true`, Action Scheduler jobs cancelled.
8. Legacy session (manually clear its token-hash meta) → owner delete with empty token → clean failure, no fatal.
9. `?cartbay_unsubscribe=` email link still works.

## Alternatives considered and rejected

Two earlier drafts (`.meta/planning/draft.md`, `draft-updated.md`) proposed authorizing at the **permission_callback / session layer** instead of the record layer. Both are rejected:

- **Session-bound `wp_create_nonce('cartbay_capture_' . $customer_id)` (draft.md):** cryptographically forgeable for guests — `wp_create_nonce()` uses `uid=0` / `session_token=''` for logged-out users, and `customer_id` is printed into the page, so any guest can reproduce it.
- **HMAC of WC `customer_id` in `permission_callback` (draft-updated.md):** proves only that the caller holds *a* valid WooCommerce session — it never references the target record. That draft explicitly keeps `session_email_matches()` as the record-level check, so an attacker with their own valid session passes the permission gate and still mutates a victim's record via `{email:victim, session_id:N}` in the body. It fixes the wrong layer and leaves the reviewer's exact hole open. It also loads the WC session inside `permission_callback` (`wc_load_cart()`), which the draft itself calls "fragile and not standard practice."

The reviewer's own email endorses `__return_true` for genuinely public endpoints; the objection is the weak *authorization* (email), not the literal `__return_true`. Our per-session **bearer token is scoped to the specific record** (session N, issued at create), so it authorizes "mutating *this* shopper's session" — the exact thing the reviewer demands. We keep `__return_true` (same as the Store API) and enforce the token in the handler/service.

One useful, correct idea salvaged from `draft-updated.md`: the explanation of *why* the `wp_rest` nonce is meaningless for guests (`uid=0`, `session_token=''` → all guests share one nonce within a tick). This is good material for the reviewer reply.

## Follow-up (separate, after the fix is verified)

Draft the short, direct reviewer reply: mutations of an existing session are now authorized by a per-session secret capability token (issued to the shopper's browser on create, stored server-side only as a hash, verified with `hash_equals`) — not by the supplied email; CREATE remains bound to the server-side cart, same model as the Store API cart token. Optionally note that the `wp_rest` nonce alone is meaningless for guests (shared `uid=0`/empty session), which is why real per-session authorization was needed. Keep it brief (the team explicitly asks for concise replies).

## Files touched

- `app/Utils/TokenHelper.php` — add `create_capture_token()` / `validate_capture_token()` (with empty-guard)
- `app/Recovery/CaptureService.php` — token authz; remove email + fingerprint authorization paths; array return
- `app/Api/Routes/CaptureRoute.php` — `capture_token` arg; token-gate update/delete; drop email-on-delete; fix comments
- `src/capture/index.js`, `src/block/index.js` — store/send/clear `capture_token`
- `assets/js/cartbay-capture.js`, `assets/js/cartbay-block.js`, `*.asset.php` — rebuilt output
- `languages/…pot` — only if strings changed
