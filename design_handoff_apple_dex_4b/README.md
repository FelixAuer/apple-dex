# Handoff: Apple-Dex Variety Detail Page — Design 4b

## About the design file
`Apple-Dex Explorations.dc.html` is a **design reference**, not production code — it contains many explored directions. **Only the block with `id="4b"` matters here.** Recreate it in the app's existing codebase, using its existing component patterns (buttons, cards, etc.) rather than copying this HTML.

## Fidelity
High-fidelity. Colors, type, spacing, and shadow treatments below are final.

## Screen: Variety detail (caught)
Reference: `screenshot-4b-detail.png`, and `id="4b"` in the HTML file.

### Layout, top to bottom
1. **Header bar** — app icon mark (see below) + "Apple-Dex" wordmark. No back button (removed from this design — user navigates via OS/tab back or a nav pattern already in the app).
2. **Photo** — full-width, ~220px tall, 18px border radius. Solid offset shadow (`0 6px 0 #10150a`) plus a soft color-tinted glow shadow beneath, tinted to roughly match the dominant photo color (`0 26px 40px -14px rgba(165,57,43,0.5)` for a reddish apple — adjust tint per photo, or omit the glow if per-photo color extraction isn't feasible yet).
3. **Name + meta row**: variety name (large, bold) on its own line. Below it, one row: origin/era text, a "·" separator, then **"More info"** as a plain link (no icon, no arrow — just text, same weight/color as other links).
4. **Notes — pull-quote style**: the user's tasting note, indented with a 3px solid left border in the accent yellow, set in a slightly larger serif-adjacent display weight, wrapped in quotation marks. This is the visual focal point of the page (user confirmed notes should outrank the map link in priority).
5. **Scoffed date + map row**: a single row, space-between — "Scoffed [date]" on the left (muted small text), and on the right just a **📍 pin emoji/icon** (no "View on map" text — icon only, same accent-yellow color as other links).
6. **Action buttons**: two full-width-split buttons side by side — "Edit entry" (neutral) and "Delete entry" (red/danger), both with the tactile solid-offset-shadow button style.

### Design tokens
**Colors**
- App shell / outer card background: `#1e2419`
- Header/info-row background: `#232b1c`
- Neutral button fill: `#3a4530`, shadow `#171d10`
- Delete button: fill `#4a2b21`, text `#ff8f7d`, shadow `#241511`
- Primary text: `#f4ede1`
- Secondary/meta text: `#b7c19c`
- Tertiary/date text: `#9aa384`
- Accent yellow (links, quote border): `#e0c14c`

**Typography**
- Headings/name/quote: `Baloo 2`, weight 600–700
- Body/UI/links: `Nunito Sans`, weight 400–700
- Sizes: name 26px, quote 16px, meta/links ~12.5–13px, buttons 13px

**Shape/shadow language**
- Border radius: 14px on info rows/buttons, 18px on photo, 24px on outer card frame.
- Buttons: flat fill + solid (non-blurred) offset shadow in a darker shade of the same hue — never a blurred shadow on buttons. Primary/danger CTAs get a slightly larger offset (`0 5px 0`) and compress on press (`translateY(4px)`, shadow reduces to `0 1px 0`).
- A subtle grain/noise overlay sits over the whole card (SVG feTurbulence, ~50% opacity, `mix-blend-mode: overlay`) to avoid a flat/solid-color look — recreate as a shared noise texture asset if easier.

### Icon mark
Custom two-tone glyph replacing any apple emoji: a cream circle (`#f4ede1`) as the body, overlapped by a small rotated yellow leaf shape (`#e0c14c`) top-right, inside a red rounded-square chip (`#e8543f`, with matching offset shadow `#a5392b`). Simple geometric shapes, not a detailed illustration.

## Explicitly decided against
- No status pill ("caught"/"not yet caught").
- No "Back to Dex" link/button on this screen.
- No icon next to "More info" — text only.
- No "View on map" text — pin icon only.
- No rarity/progress/gamification chrome.

## Files
- `Apple-Dex Explorations.dc.html` — reference `id="4b"` only.
- `screenshot-4b-detail.png` — rendered reference screenshot.
