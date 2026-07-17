# Handoff: Apple-Dex Redesign — "Collector's Game" direction (1b)

## Overview
A visual refresh of the Apple-Dex app (a Pokédex-style logger for apple varieties tried, with photo capture). This package covers the "Collector's Game" direction: whimsical but not childish, dark orchard palette, rounded playful type, tactile "3D button" shadows. This is the direction the user selected after reviewing three explorations (1a Field Guide / 1b Collector's Game / 1c Orchard Journal) — only 1b was carried forward and polished.

## About the Design Files
The bundled file `Apple-Dex Explorations.dc.html` is a **design reference built in HTML** — a prototype showing intended look, layout, and micro-interactions, not production code to copy directly. The task is to **recreate these screens in the app's existing codebase** (whatever its current framework/component library is), following its established patterns — not to ship this HTML as-is.

Note: this file contains ALL explored directions (1a, 1b, 1c, plus the earlier "Turn 1" grid layouts). **Only the "1b — Collector's Game" screens matter for implementation** — the main dex grid (id="1b" in Turn 1) plus the three Turn 2 screens: 2a (caught detail), 2b (uncaught detail / "Catch it!"), 2c (log-a-catch form). Ignore 1a and 1c.

## Fidelity
**High-fidelity.** Final colors, typography, spacing, and shadow treatments are intended as final. Recreate pixel-perfectly using the codebase's existing component library where possible (buttons, inputs, cards); apply these exact tokens/styles rather than the app's current (pre-redesign) styling.

## Design Tokens

**Colors**
- Background (app shell): `#1e2419` (deep olive/charcoal)
- Header/surface panel: `#232b1c`
- Card / input surface: `#3a4530`
- Dimmed/uncaught card surface: `#262e1f` (rendered at ~75% opacity via `fadeInDim` animation end-state)
- Primary text (light): `#f4ede1`
- Secondary/label text: `#dde2c9`
- Tertiary/metadata text: `#b7c19c`
- Uncaught card label text (warm, lighter): `#f0d9a8`
- Accent red (icon chip, "Catch it!" button, delete-adjacent link): `#e8543f` / button fill `#f2634c`, shadow `#a5392b`
- Accent yellow/gold (primary CTA "Save catch", active sort pill, count number): `#e0c14c`, shadow `#a8891f`
- Delete button: bg `#4a2b21`, text `#ff8f7d`
- Uncaught silhouette shape fill: `#454f38`

**Typography**
- Display/headings: `Baloo 2`, weight 600–700 (Google Font)
- Body/UI text: `Nunito Sans`, weight 400–700 (Google Font)
- Sizes: app title 17–20px, screen titles 20–24px, card labels 12–13px, metadata 11–13px, buttons 13–16px

**Shape & shadow language ("tactile/3D button")**
- Buttons and chips use a solid flat-color fill + a solid (non-blurred) offset shadow in a darker shade of the same hue, e.g. `box-shadow: 0 3px 0 #a5392b` for a red chip, `0 5px 0 #a8891f` for the yellow CTA — this reads as a pressable, slightly toy-like button (never blurred/soft shadows on buttons).
- Border radius: large and consistent — 8–9px on small icon chips, 12–16px on cards/inputs/buttons, 20px on pills, 24px on the outer app card frame.
- Caught grid cards have a very slight per-card rotation (`-2deg`, `1.5deg`, `-1deg`) for a scattered, playful trading-card feel. Uncaught cards do NOT tilt (kept flat/dimmed — decided in review).

**Grain texture**
- A subtle noise/grain overlay sits over each dark card surface: `position:absolute; inset:0; opacity:0.5; mix-blend-mode:overlay;` with a `feTurbulence` SVG data-URI as `background-image`. This kills the flat/solid-color look. Recreate as a shared noise PNG/texture asset or an SVG turbulence filter, applied at low opacity with `mix-blend-mode: overlay` over dark surfaces only.

## Screens

### Main Dex grid (Turn 1, `id="1b"`)
- App header: icon chip (see Icon Mark below) + "Apple-Dex" wordmark, small settings-square placeholder, top-right.
- Below: large count readout — big yellow number + "varieties caught" label (Baloo 2 bold number, Nunito Sans label).
- Search input: full-width, pill-ish rounded rect, `#3a4530` fill, no border, placeholder text light.
- Sort control: two pills — "Recent" (active, yellow fill + shadow) and "A–Z" (inactive, `#3a4530` fill).
- Grid: 3 columns, 14px gap. Caught cards: photo (currently a placeholder gradient — swap for the user's real photo), name label below, slight rotation, solid offset shadow. Uncaught cards: dimmed flat card, no rotation, simple silhouette placeholder shape (rounded-square rotated to suggest an apple outline) + warm-toned name label — no rarity badges, no progress bar, no extra gamification chrome (explicitly decided against).
- Load animation: header block and search block fade+rise in (translateY 14px→0, opacity 0→1, ~0.5s ease, staggered ~0.02s/0.1s delay); grid cards fade-in-opacity-only staggered ~0.18s→0.36s (no translateY on cards, since it would conflict with their rotation transform). One-time on load, not on every re-render.

### Caught detail (Turn 2, `id="2a"`)
- Header (same app icon/wordmark) + "← Back to Dex" link (yellow).
- Large photo (full width, ~220px tall, rounded 18px, solid offset shadow `0 6px 0 #10150a`).
- Variety name (Baloo 2, 24px) + origin/era subtitle (muted).
- Info block: `#232b1c` rounded rect containing "Consumed: [date]" + "View on OpenStreetMap →" link (yellow, underlined).
- Two buttons side by side: "Edit catch" (neutral `#3a4530` fill) and "Delete catch" (red-brown fill `#4a2b21`, red text `#ff8f7d`).
- No "caught" status pill — removed in review; caught state is implied by the presence of the photo/consumed date.

### Uncaught detail (Turn 2, `id="2b"`)
- Same header/back-link pattern.
- Photo area shows a dimmed placeholder with a centered silhouette shape instead of a photo.
- Name + origin/era subtitle.
- No "not yet caught" status pill (removed in review).
- Full-width primary CTA: "Catch it! 🍏" — red fill `#f2634c`, white text, solid offset shadow, **has a press-state**: `transform: translateY(4px)` + shadow compresses to `0 1px 0 #a5392b` on `:active` (implement as `style-active` equivalent / CSS `:active` in the target framework).

### Log a catch form (Turn 2, `id="2c"`)
- Header, then "Log a catch" title.
- Variety field: text input + autocomplete dropdown showing a matched existing variety and a "+ Create '<query>' as new variety" affordance (yellow text).
- Photo (optional): two side-by-side buttons ("📷 Take photo", "🖼 Gallery"), both `#3a4530` fill; below them a small (110×110) preview thumbnail once a photo is chosen, rounded 14px, solid offset shadow.
- Date + Location fields side by side: Date shows the picked value in a rounded field; Location shows a "📍 Use my location" affordance (red-orange text `#ff8266`) — real app should keep the existing free-text fallback input beneath it (present in the original app, not re-drawn in this mock — carry it over).
- Primary CTA: "Save catch" — yellow fill `#e0c14c`, dark text, solid offset shadow, same press-state treatment as "Catch it!" (`translateY(4px)`, shadow compresses to `0 1px 0 #a8891f`).

## Icon Mark
Replaces the previous raw 🍎 emoji glyph. Custom two-tone mark built from basic shapes (not an illustration): a cream/off-white circle (`#f4ede1`) as the apple body, overlapped by a small yellow (`#e0c14c`) rotated leaf shape at the top right, sitting inside the existing red rounded-square icon chip. Recreate as a small SVG or two overlapping shape layers — keep it simple geometric shapes, not a detailed drawing.

## Explicitly rejected / do NOT add
- Rarity labels, collection progress bars, streaks, or any additional gamification chrome — user asked to keep it minimal, just the log itself.
- "Caught" / "Not yet caught" status pills on detail pages — removed after review.
- Tilt/rotation on uncaught cards — kept flat.
- Ambient color-tinted glow shadows under photos — tried and rejected (looked bad combined with the solid offset shadow); keep the flat solid offset shadow only.

## Assets
No real photography is included — all apple photos in the mock are gradient color placeholders and must be swapped for the user's actual uploaded photos. Fonts are Google Fonts (`Baloo 2`, `Nunito Sans`) — load via existing font-loading approach in the codebase (self-hosted or Google Fonts CDN, per project convention).

## Files
- `Apple-Dex Explorations.dc.html` — contains all explored directions. Reference the `id="1b"` block (Turn 1) and the `id="2a"` / `id="2b"` / `id="2c"` blocks (Turn 2) only.
- `screenshot-1b-main-dex.png` — main dex grid
- `screenshot-2a-caught-detail.png` — caught variety detail page
- `screenshot-2b-uncaught-detail.png` — uncaught variety detail / "Catch it!" page
- `screenshot-2c-log-catch-form.png` — log-a-catch form
