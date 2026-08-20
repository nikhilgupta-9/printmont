# Printmont API Work — Status Checklist

Same grouping and order as the planned list. Tick a box once an endpoint is
built **and** you have called it successfully.

- **Local:** `http://localhost/printmont/printmont-backend/api`
- **Live:** `https://mediumvioletred-pelican-783174.hostingersite.com/api`
- 🔒 = send `Authorization: Bearer <token>`

> This backend does not use REST paths like `POST /login`. Nearly everything is
> `user-api.php?action=<name>`; the rest are one file per resource. The arrow
> shows what to actually call.

**Audited 21 Aug 2026** — every ticked line was requested against the local
server and returned the expected status.

**41 of 82 built (~50%)**, plus 10 extras not on the original list.

---

## Auth & Account APIs — 10/10 ✅

- [x] POST /register (Signup) → `user-api.php?action=register`
- [x] POST /login → `user-api.php?action=login` *(returns JWT)*
- [x] POST /logout → `user-api.php?action=logout`
- [x] POST /refresh-token → `user-api.php?action=refresh_token`
      ⚠️ built, but **nothing on the frontend calls it** — sessions expire instead of renewing
- [x] POST /forgot-password → `user-api.php?action=forgot_password` *(emails an OTP)*
- [x] POST /reset-password → `user-api.php?action=reset_password`
- [x] PUT /account/update-profile → `user-api.php?action=update_profile` 🔒
- [x] GET /account/profile → `user-api.php?action=profile` 🔒
- [x] DELETE /account/delete (hard delete) → `user-api.php?action=delete_account` 🔒 *(password required)*
- [x] PUT /account/soft-delete → `user-api.php?action=soft_delete_account` 🔒
- [x] *extra* — change password while signed in → `user-api.php?action=change_password` 🔒

## Home / Content APIs — 5/5 ✅

- [x] GET /home → `home-layout-api.php?target=desktop|mobile`
- [x] GET /banners → `banner_api.php` · one section: `?section=<key>`
- [x] GET /search → `search-api.php?q=<term>`
- [x] GET /menu bar → `menu_api.php?type=home|inner`
- [x] GET /category / sub / sub-sub → `category-api.php` *(nested tree)*
- [x] *extra* — homepage product rails → `home-product-api.php?action=<key>` *(18 actions)*

## Category APIs — 2/5

- [x] GET /categories → `category-api.php`
- [x] GET /categories/{id}/subcategories → `category-api.php` *(nested in the tree)*
- [ ] POST /category (create) — admin panel only, no API
- [ ] PUT /category/{id} (update) — admin panel only, no API
- [ ] DELETE /category/{id} — admin panel only, no API

## Product APIs — 4/8

- [x] GET /products → `product-api.php` · `?status=deactive`
- [x] GET /products/{id} → `product-api.php?id=<id>`
- [ ] GET /products/{id}/variants — **no endpoint**
- [ ] GET /products/{id}/reviews — `product_reviews` table + `ReviewController` exist, no API
- [ ] POST /products/{id}/review — same
- [ ] POST /products (create) — admin panel only
- [ ] PUT /products/{id} (update) — admin panel only
- [ ] DELETE /products/{id} — admin panel only
- [x] *extra* — related products → `related_products.php?id=<id>`
- [x] *extra* — product banners → `product-banner-api.php`

## Cart APIs — 5/5 ✅

- [x] GET /cart → `cart-api.php` 🔒
- [x] POST /cart/add → `cart-api.php` 🔒
- [x] PUT /cart/update-quantity → `cart-api.php` 🔒
- [x] DELETE /cart/remove/{itemId} → `cart-api.php?item_id=<id>` 🔒
- [x] DELETE /cart/clear → `cart-api.php` 🔒 *(omit `item_id`)*

## Wishlist APIs — 3/3 ✅

- [x] GET /wishlist → `wishlist-api.php` 🔒
- [x] POST /wishlist/add → `wishlist-api.php` 🔒
- [x] DELETE /wishlist/remove/{itemId} → `wishlist-api.php?product_id=<id>` 🔒

## Address APIs — 5/5 ✅

- [x] GET /address → `user-api.php?action=get_addresses` 🔒
- [x] POST /address/add → `user-api.php?action=add_address` 🔒
- [x] PUT /address/update/{id} → `user-api.php?action=update_address` 🔒 *(id in body)*
- [x] DELETE /address/remove/{id} → `user-api.php?action=delete_address` 🔒 *(id in body)*
- [x] PUT /address/set-default/{id} → `user-api.php?action=set_default_address` 🔒 *(id in body)*

## Order APIs — 4/7

- [x] POST /order/place → `user-api.php?action=create_order` *(guest checkout works too)*
- [x] GET /order/history → `user-api.php?action=get_orders` 🔒
      filters: `status`, `date_from`, `date_to`, `search`, `page`, `limit`
- [x] GET /order/{id} → `user-api.php?action=get_order&id=<id>` 🔒 *(scoped to the token holder)*
- [ ] PUT /order/{id}/cancel — **no endpoint**; admin can cancel, customers cannot
- [x] GET /order/{id}/track → `user-api.php?action=track_order`
      public, but needs the order **or** tracking number **plus** the email or mobile
- [ ] PUT /order/{id}/status (admin update) — admin panel only; `index.php` declares the
      action but every route form returns 404
- [ ] GET /order/{id}/invoice — **no endpoint**

## Payment APIs — 0/5 ❌

- [ ] POST /payment/create-link — no gateway integration
- [ ] POST /payment/verify
- [ ] GET /payment/status/{orderId} — `orders.payment_status` exists, no endpoint
- [ ] POST /payment/webhook
- [ ] GET /payment/methods — `payment_settings` table exists, no API

> Checkout records a `payment_method` string on the order. **No money moves.**

## Shipping / Delivery APIs — 1/4

- [ ] GET /delivery-partners
- [ ] POST /shipping/assign-partner — courier name is typed by hand in the admin
- [x] GET /shipping/track/{orderId} → `user-api.php?action=track_order` *(same as order tracking)*
- [ ] POST /shipping/calculate-charges — flat rule in `createOrder`: free over ₹1000, else ₹160

## Coupon & Discount APIs — 0/6 ❌

- [ ] GET /coupons — `coupons` table + `CouponController` exist, **no API**
- [ ] POST /coupon/apply — checkout discount is currently hardcoded
- [ ] DELETE /coupon/remove
- [ ] POST /coupon (create) — admin panel only
- [ ] PUT /coupon/{id} (update) — admin panel only
- [ ] DELETE /coupon/{id} — admin panel only

## Coin / Rewards System APIs — 0/7 ❌

- [ ] GET /coins/balance — frontend shows a **hardcoded balance of 36**
- [ ] GET /coins/history
- [ ] POST /coins/earn
- [ ] POST /coins/redeem — checkout "cash coins" discount is a fixed ₹20
- [ ] GET /coins/rules
- [ ] POST /coins/adjust (admin manual credit/debit)
- [ ] GET /coins/expiry-notification

> No coin tables exist. This whole area is presentational for now.

## Referral / Loyalty APIs — 0/3 ❌

- [ ] GET /referral/code — no table, no API
- [ ] POST /referral/apply
- [ ] GET /referral/history

## Reviews & Ratings APIs — 0/3 ❌

- [ ] POST /reviews/add — `product_reviews` table + `ReviewController` exist, **no API**
- [ ] GET /reviews/{productId} — same
- [ ] DELETE /reviews/{id} — same

> The cheapest of the missing areas: model and controller are already written.

## Notification APIs — 0/4 ❌

- [ ] POST /notify/email — `MailService` sends mail directly; no generic endpoint
- [ ] POST /notify/sms — no SMS provider
- [ ] POST /notify/push
- [ ] GET /notification/list — `notifications` + `notification_reads` tables exist, no API

Transactional email that already works (not via an endpoint):
order confirmation · contact acknowledgement · password-reset OTP · registration welcome

## Contact APIs — 2/2 ✅

- [x] GET → `contact-api.php` *(phone, emails, addresses, hours)*
- [x] POST → `contact-api.php` *(stores the enquiry and emails an acknowledgement)*

---

## Extra APIs (not on the original list) — 10

- [x] `about-api.php` — About sections + team members
- [x] `faq-api.php` — FAQs with category names
- [x] `policies-api.php` — all policies
- [x] `security-api.php` — security page sections
- [x] `help-center-api.php` — help centre content
- [x] `blog-api.php/posts|categories|recent|popular` — blog
- [x] `career-get-api.php` · `?id=<id>` — vacancies list / detail
- [x] `career-post-api.php` — job application incl. CV upload
- [x] `page-sections-api.php?page=<key>` — affiliate · business-solutions · become-a-seller
- [x] `logo-api.php` — site logo

---

## Security — fix before launch

Verified by request on 21 Aug 2026.

**1. Nine admin scripts delete or modify data with no authentication.**
The login gate lives inside `includes/side-navbar.php`, so pages that include
it redirect to the login screen. These action-only scripts never include it:

```
delete-banner.php          delete-order.php     delete-social-link.php
delete-coupon.php          delete-product.php   edit-email-template.php
delete-footer-section.php  delete-logo.php      delete-review.php
```

`delete-order.php?id=13` checks only that the id is numeric, then deletes and
redirects. **Most urgent item here.** One line per file to fix.

**2. `.env` was tracked in git until commit `9466fe4`.** Untracked now, but the
old values remain in history — **rotate the database credentials.**

**3. Checked and fine:** `api/index.php` and `api/router.php` declare admin
actions, but every route form 404s — unreachable. `user-api.php` scopes orders
and addresses to the bearer token, and `track_order` needs a second factor, so
sequential order numbers alone reveal nothing.

---

## Notes

- Any path added here must name a real file in `printmont-backend/api/`. The
  `.htaccess` rewrites unknown paths to `index.php`, so a wrong path returns the
  admin login page as **HTTP 200 text/html**, which then fails in `res.json()`
  as `Unexpected token '<'` rather than surfacing as a 404.
- Frontend endpoint constants live in `printmont/src/config/apiEndpoints.js`.
- An older audit exists at `printmont/WORKING_APIS.md` (July 2026), listing only
  endpoints confirmed `200 OK` at that time. This file supersedes it.
