# Handoff: Favorite/Star Button — Detail Page

## About the design file
`Apple-Dex Explorations.dc.html` is a design reference containing many explored directions. **Only the star treatment inside the `id="4b"` block matters here.**

## What changed
The previous star implementation floated in isolation on the right edge of the card, misaligned with the name and clashing visually (flat clip-art look, no relationship to the rest of the UI). Fix: the star now sits **inline with the variety name**, right-aligned in that row, top-aligned with the title text. No card/chip/background behind it — just the glyph itself, bare, like the 📍 map icon elsewhere on the page.

## Spec
- Two states, same custom 5-point star SVG (path below), 24×24px:
  - **Unstarred**: outline only, no fill (`fill="none"`), stroke color `#8a9270` (muted olive), `stroke-width="1.5"`, `stroke-linejoin="round"`.
  - **Starred**: filled and stroked in accent yellow `#e0c14c`.
- Placement: same flex row as the variety name, `justify-content: space-between`, so the star sits at the far right, vertically centered with the name's line.
- Tap target: give it real touch-target padding in implementation (e.g. 44×44px hit area) even though the visible glyph stays small/bare — this mock doesn't show padding, but don't skip it in the real app.
- Toggle behavior: tapping switches between the two states above (fill + stroke color swap only — no scale/bounce specified, but a subtle press/scale animation would suit the app's tactile button language if desired).

### SVG path (reuse exactly)
```
<path d="M12 2.5l2.9 6.4 7 .7-5.3 4.7 1.6 6.9L12 17.6 5.8 21.2l1.6-6.9L2.1 9.6l7-.7z"/>
```

## Explicitly decided against
- No background chip/card/circle behind the star (tried, rejected — didn't match the rest of the page, which uses bare icons for secondary actions like the map pin).

## Files
- `Apple-Dex Explorations.dc.html` — reference the name row inside `id="4b"` only.
- `screenshot-4b-detail-with-star.png` — rendered reference (unstarred + starred shown side by side for comparison; in the real app only one state shows at a time, toggled).
