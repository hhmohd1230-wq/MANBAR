---
name: MANBAR
description: The stage for every student idea. Cinematic public world (landing + sign-in) for a campus-only student platform.
colors:
  lime: "rgb(196 245 58)"
  lime-ink: "rgb(8 16 4)"
  leaf: "rgb(20 187 122)"
  deep-teal: "rgb(7 123 105)"
  mint: "rgb(70 244 151)"
  canopy: "rgb(11 51 33)"
  canopy-lime: "rgb(214 250 120)"
  void: "rgb(5 8 6)"
  void-text: "rgb(236 243 232)"
  void-mute: "rgb(150 168 156)"
  forest: "rgb(9 38 27)"
  forest-text: "rgb(234 246 236)"
  forest-mute: "rgb(163 199 177)"
  paper: "rgb(242 246 239)"
  paper-text: "rgb(12 33 23)"
  paper-mute: "rgb(78 102 88)"
  white: "#ffffff"
  screen-ground: "#f3f8f2"
  screen-ink: "#10261b"
  screen-action: "#0f6b47"
  screen-hairline: "#dfeae2"
  screen-mute: "#5b7466"
  screen-tint: "#e2f3e7"
  signal-red: "#e5484d"
  amber-tint: "#fff4d8"
  amber-ink: "#8a5a00"
typography:
  display:
    fontFamily: "Archivo, 'Arial Narrow', system-ui, sans-serif"
    fontSize: "clamp(3rem, 5.6vw, 5.4rem)"
    fontWeight: 850
    lineHeight: 1
    letterSpacing: "0.01em"
    fontVariation: "'wdth' 120"
  headline-close:
    fontFamily: "Archivo, 'Arial Narrow', system-ui, sans-serif"
    fontSize: "clamp(2.6rem, 6vw, 5.4rem)"
    fontWeight: 800
    lineHeight: 0.96
    letterSpacing: "-0.035em"
    fontVariation: "'wdth' 112"
  headline:
    fontFamily: "Archivo, 'Arial Narrow', system-ui, sans-serif"
    fontSize: "clamp(2.1rem, 4.6vw, 3.9rem)"
    fontWeight: 750
    lineHeight: 1
    letterSpacing: "-0.03em"
    fontVariation: "'wdth' 108"
  tagline:
    fontFamily: "Archivo, 'Arial Narrow', system-ui, sans-serif"
    fontSize: "clamp(1.6rem, 2.7vw, 2.5rem)"
    fontWeight: 700
    lineHeight: 1.08
    letterSpacing: "-0.025em"
    fontVariation: "'wdth' 108"
  title:
    fontFamily: "Archivo, 'Arial Narrow', system-ui, sans-serif"
    fontSize: "clamp(1.5rem, 2.4vw, 2.15rem)"
    fontWeight: 700
    lineHeight: 1.08
    letterSpacing: "-0.025em"
    fontVariation: "'wdth' 106"
  title-sm:
    fontFamily: "Archivo, 'Arial Narrow', system-ui, sans-serif"
    fontSize: "1.45rem"
    fontWeight: 700
    lineHeight: 1.1
    letterSpacing: "-0.025em"
    fontVariation: "'wdth' 108"
  wordmark:
    fontFamily: "Archivo, 'Arial Narrow', system-ui, sans-serif"
    fontSize: "17px"
    fontWeight: 800
    lineHeight: 1
    letterSpacing: "0.06em"
    fontVariation: "'wdth' 118"
  lead:
    fontFamily: "Geist, system-ui, -apple-system, 'Segoe UI', sans-serif"
    fontSize: "clamp(1.05rem, 1.35vw, 1.2rem)"
    fontWeight: 400
    lineHeight: 1.6
  body:
    fontFamily: "Geist, system-ui, -apple-system, 'Segoe UI', sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: 1.6
  button:
    fontFamily: "Geist, system-ui, -apple-system, 'Segoe UI', sans-serif"
    fontSize: "15px"
    fontWeight: 600
    lineHeight: 1
  label:
    fontFamily: "Geist, system-ui, -apple-system, 'Segoe UI', sans-serif"
    fontSize: "13px"
    fontWeight: 500
    lineHeight: 1.3
  measure:
    fontFamily: "'Geist Mono', ui-monospace, 'Cascadia Mono', monospace"
    fontSize: "clamp(1.25rem, 2vw, 1.6rem)"
    fontWeight: 500
    lineHeight: 1.2
    fontFeature: "'tnum'"
  measure-sm:
    fontFamily: "'Geist Mono', ui-monospace, 'Cascadia Mono', monospace"
    fontSize: "12.5px"
    fontWeight: 500
    lineHeight: 1.2
    fontFeature: "'tnum'"
  arabic:
    fontFamily: "Cairo, 'Noto Kufi Arabic', system-ui, sans-serif"
    fontSize: "15px"
    fontWeight: 700
    lineHeight: 1
rounded:
  chip: "6px"
  control: "8px"
  button: "10px"
  field: "11px"
  button-lg: "12px"
  card: "14px"
  panel: "16px"
  sheet: "24px"
  avatar: "32%"
spacing:
  gutter: "clamp(16px, 4vw, 56px)"
  container: "1240px"
  hero-max: "1520px"
  nav-height: "68px"
  grid-gap: "24px"
  bento-gap: "14px"
  head-gap: "56px"
  tile-pad: "24px"
  section-hero-top: "108px"
  section-journey: "clamp(96px, 16vh, 180px)"
  section-paper: "clamp(70px, 10vh, 120px)"
  section-tail: "max(26vh, 180px)"
components:
  button-primary:
    backgroundColor: "{colors.lime}"
    textColor: "{colors.lime-ink}"
    typography: "{typography.button}"
    rounded: "{rounded.button}"
    padding: "0 20px"
    height: "46px"
  button-primary-sm:
    backgroundColor: "{colors.lime}"
    textColor: "{colors.lime-ink}"
    rounded: "{rounded.button}"
    padding: "0 16px"
    height: "38px"
  button-primary-lg:
    backgroundColor: "{colors.lime}"
    textColor: "{colors.lime-ink}"
    typography: "{typography.button}"
    rounded: "{rounded.button-lg}"
    padding: "0 26px"
    height: "56px"
  button-paper:
    backgroundColor: "{colors.canopy}"
    textColor: "{colors.canopy-lime}"
    typography: "{typography.button}"
    rounded: "{rounded.button}"
    padding: "0 20px"
    height: "48px"
  link-text:
    textColor: "{colors.void-text}"
    typography: "{typography.body}"
  nav-link:
    textColor: "{colors.void-mute}"
    rounded: "{rounded.control}"
    padding: "8px 12px"
  nav-bar:
    textColor: "{colors.void-text}"
    padding: "0 clamp(16px, 4vw, 56px)"
    height: "68px"
  input-field:
    backgroundColor: "{colors.white}"
    textColor: "{colors.screen-ink}"
    typography: "{typography.measure-sm}"
    rounded: "{rounded.field}"
    padding: "0 14px"
    height: "48px"
  chip-active:
    backgroundColor: "{colors.canopy}"
    textColor: "{colors.lime}"
    rounded: "{rounded.control}"
    padding: "6px 10px"
  bento-tile:
    textColor: "{colors.paper-text}"
    rounded: "{rounded.panel}"
    padding: "24px"
  post-card:
    backgroundColor: "{colors.white}"
    textColor: "{colors.screen-ink}"
    rounded: "{rounded.card}"
    padding: "18px"
  readout-value:
    textColor: "{colors.void-text}"
    typography: "{typography.measure}"
  avatar:
    textColor: "{colors.white}"
    rounded: "{rounded.avatar}"
    size: "2.6em"
---

# Design System: MANBAR

## Overview

**Creative North Star: "The Living Campus Stage"**

The public face of MANBAR opens in the dark, like a stage before the lights come up: a near-black void with one vibrant lime signal. The logo draws itself the way the brand film does, then flies into the hero. As the visitor scrolls, the ground morphs from void to deep forest to daylight paper and back to void for the close, so the page reads as one continuous world growing from a seed into a green campus. Behind everything, a Canvas 2D field of drifting leaves and Latin and Arabic letters gives the eLearning, bilingual identity (منبر) a living texture; the sign-in page adds math equations that stream left to right with motion trails.

The world is cinematic but evidential. Every claim is shown on a real-looking MANBAR screen: smoked-glass laptop and phone shells hold the actual app UI, the journey replays like a screen recording with typing and a demo cursor, and the readout strip carries live database counts. Anything scripted is labelled as demo content in plain words. Density is generous on the dark tones (one idea per viewport) and tighter on paper, where the module bento and comparison table do the explaining.

Type is an expanded grotesk (Archivo, widened 106 to 120%) for display, Geist for reading, and Geist Mono strictly for measurements. Motion settles rather than bounces: exponential ease-out curves, a lens-focus entrance that pulls copy from blur to sharp, and full respect for reduced motion. Confirmed refusals, from the direction contract: the static light-green SaaS template, decorative blobs, eyebrow kickers, equal icon-card grids, and a big-number hero-metric row.

**Key Characteristics:**
- Three section tones (void, forest, paper) declared per section and morphed on the canvas ground with feathered seams.
- One lime signal on dark tones; on paper the accent inverts to canopy green with pale-lime text.
- Living canvas field: leaves plus Latin and Arabic letters (plus equations on sign-in), faded under copy.
- Logo-first intro (trace, draw, leaf, fill, sound) that lands on the hero logo.
- Lens-focus entrance: blur and slight scale resolve to sharp, staggered per element.
- Smoked-glass device shells holding opaque, light product screens.
- Demo layer (cursor, typing, scripted states) always labelled as demo content.
- Mono only for measurements; tabular numerals everywhere numbers change.

## Colors

A dark-to-daylight green world with a single electric lime signal; every tone colour is an RGB triplet token so the canvas and CSS can interpolate between tones.

Tone tokens are declared as space-separated triplets (`--bg`, `--fg`, `--mute`, `--line`, `--line-a`, `--acc`, `--acc-fg`, `--panel-a`) on `[data-tone="void|forest|paper"]` in `public/assets/css/cinema.css`, and repeated on `.tone-paper` for the sign-in card. Components always read `rgb(var(--token) / alpha)`, never a literal, so text matches its own section's ground. `cinema.js` holds a mirror of the same values (object `T`) to paint the canvas; the two must be edited together.

### Primary
- **Stage Lime** (`{colors.lime}`): the one signal on void and forest. Primary buttons, the focus ring (2px, 3px offset), text selection, the live-readout dot, the tagline's emphasised words, the intro caption state, the sign-in progress arc, active route chips. Never used as text on paper.
- **Lime Ink** (`{colors.lime-ink}`): text and icons on lime buttons.

### Secondary
- **Logo Leaf** (`{colors.leaf}`): the logo's middle green. Canvas canopy glow, bento hover spotlight and hairline, compare-table MANBAR column tint and dots, check icons on paper, input focus ring, progress fills.
- **Deep Teal** (`{colors.deep-teal}`): the logo gradient's dark end; leaf/letter tint on paper.
- **Mint** (`{colors.mint}`): the logo gradient's bright start; leaf/letter tint on void and forest.

### Tertiary
- **Canopy** (`{colors.canopy}`): the accent on paper. Buttons on paper (`--acc`), active chips and skill tags, the mentorship time block, the "dev only" chip. Exposed in CSS as `--forest`.
- **Canopy Lime** (`{colors.canopy-lime}`): text on canopy buttons in the paper tone (`--acc-fg`).

### Neutral
- **Void** (`{colors.void}`), **Void Text** (`{colors.void-text}`), **Void Mute** (`{colors.void-mute}`): hero, close, nav at top, sign-in art side. Hairlines are void-text at 10%; panels are white at 4%.
- **Forest** (`{colors.forest}`), **Forest Text** (`{colors.forest-text}`), **Forest Mute** (`{colors.forest-mute}`): the journey section. Hairlines at 12%, panels at 5%.
- **Paper** (`{colors.paper}`), **Paper Text** (`{colors.paper-text}`), **Paper Mute** (`{colors.paper-mute}`): modules, access, compare, and the sign-in card. Hairlines at 12%, panels white at 75%. `color-scheme: light`.

### Screen palette (product UI inside devices and demos)
The app screens depicted on the laptop, phone, journey scene and bento fragments use their own fixed light palette, because they show the product, not the page: **Screen Ground** (`{colors.screen-ground}`), **Screen Ink** (`{colors.screen-ink}`), **Screen Action** (`{colors.screen-action}`, active rail item, New post, accepted states), **Screen Hairline** (`{colors.screen-hairline}`), **Screen Mute** (`{colors.screen-mute}`), **Screen Tint** (`{colors.screen-tint}`, tags, badges, level card). Status colours are **Signal Red** (`{colors.signal-red}`, notification count, recording dot, misspelling underline) and the amber pair **Amber Tint** / **Amber Ink** (`{colors.amber-tint}` / `{colors.amber-ink}`, "Idea" and "In progress" tags). Avatars are generated per person as `oklch(.6 .12 var(--h))` with white initials.

### Named Rules
**The Tone Owns the Text Rule.** Every section declares `data-tone`; its text, mute, hairline and accent come from that tone, not from the scroll position. Only the canvas ground and the fixed nav morph with scroll.

**The Lime Is Light Rule.** Lime is the single light source on dark tones and covers well under 10% of any viewport. On paper the accent flips to canopy with pale-lime text; lime never sits on paper as text.

**The Depicted Product Rule.** Screens inside devices and demo panels use the screen palette and stay light and opaque in every tone, so the product looks like the product.

## Typography

**Display Font:** Archivo variable (wdth 62 to 125, wght 300 to 900), with 'Arial Narrow', system-ui fallback
**Body Font:** Geist (400 to 700), with system-ui, -apple-system, 'Segoe UI' fallback
**Label/Mono Font:** Geist Mono (400 to 600), with ui-monospace, 'Cascadia Mono' fallback
**Arabic:** Cairo (600, 700), with 'Noto Kufi Arabic' fallback

**Character:** A wide, heavy, slightly technical grotesk shouts the stage name and headlines; a calm humanist-geometric sans carries reading; a mono appears only where a number is being measured. Display headings use `text-wrap: balance`, paragraphs `text-wrap: pretty`.

### Hierarchy
- **Display** (`{typography.display}`): the hero wordmark "MANBAR" beside the logo, widened to 120%. Drops to clamp(2.7rem, 13vw, 3.6rem) under 600px.
- **Headline Close** (`{typography.headline-close}`): the closing call ("Your idea needs a stage.") and, at clamp(2.4rem, 4.4vw, 4.2rem), the sign-in art headline.
- **Headline** (`{typography.headline}`): section heads (h2), max 640px wide, followed by a mute paragraph at clamp(1.02rem, 1.3vw, 1.15rem), max 58ch. The sign-in card h2 uses the same style at clamp(2rem, 3vw, 2.6rem).
- **Tagline** (`{typography.tagline}`): "The stage for every student idea." under the hero wordmark; emphasised words in lime, upright (no italics).
- **Title** (`{typography.title}`): journey step headings. **Title Small** (`{typography.title-sm}`): bento tile headings. Card titles inside screens use Archivo 700 at 1.15 to 1.25em, widened 105 to 108%.
- **Lead / Body** (`{typography.lead}`, `{typography.body}`): hero lead max 46ch; step copy max 42ch; tile copy max 40ch.
- **Label** (`{typography.label}`): readout terms, scene bar, intro caption, legend, footer. Sentence case, no tracking.
- **Measure** (`{typography.measure}`, `{typography.measure-sm}`): readout counts, intro load counter (000 to 100), step counter, timestamps, prices, durations, student numbers, scores, equations.
- **Wordmark** (`{typography.wordmark}`) with **Arabic** (`{typography.arabic}`) name beside it, separated by a 1px hairline at 20%.

### Named Rules
**The Mono Measures Rule.** Geist Mono is reserved for values that are counted, timed, priced or identified. Labels, chips and prose stay in Geist.

**The Expanded Display Rule.** Archivo is always widened (106 to 120%) and tracked tighter as it grows (-0.025em to -0.035em); only the wordmark and the hero name track open (+0.01 to +0.06em).

**The Tabular Rule.** Any number that animates or sits in a column uses `font-variant-numeric: tabular-nums`.

## Layout

A full-bleed, sectioned scroll. Content sits in a centred container (`{spacing.container}`) with a fluid side gutter (`{spacing.gutter}`, 16px at phone width). The hero alone is a 12-column grid up to `{spacing.hero-max}`, 24px column gap, at least 100dvh: copy spans columns 1 to 5, the device rig spans 6 to 12, and the live readout strip runs full width along the bottom under a hairline. Section heads sit above content with a 56px gap.

- **Journey (forest):** a 5:7 split; the left column is a list of tall steps (66vh each, inactive steps at 32% opacity), the right column is a sticky glass scene centred in the viewport that swaps panels per step.
- **Modules (paper):** a 6-column bento with 14px gaps: the idea-feed tile spans 4 columns and 2 rows, five tiles span 2, two span 3.
- **Access (paper):** two equal columns; copy and checks left, the roster-match demo right.
- **Compare (paper):** a horizontally scrollable table (min 640px) in a 16px-rounded, 90% tone-filled frame.
- **Close (void):** centred mark, headline, line and button at 88vh, then a hairline footer.
- **Sign-in:** a 1.1:1 split; the void art column on the left, the paper card column on the right as a sheet with 24px left corners.

Vertical rhythm is set by section padding: journey `{spacing.section-journey}` top, paper sections `{spacing.section-paper}`, and `{spacing.section-tail}` at the end of journey and compare to give the tone seam room to morph. The fixed nav is `{spacing.nav-height}` tall; `scroll-padding-top` is 84px.

**Responsive:** at 1100px the hero stacks (copy, devices up to 720px, readout). At 900px nav links hide, the journey becomes one column with the scene sticky under the nav, the bento drops to 2 columns, access stacks, and sign-in stacks with the card as a bottom sheet (24px top corners). At 600px the bento is 1 column, the large button goes full width, the Arabic wordmark hides, and readout items pair up.

### Canvas field
A fixed full-viewport canvas (z 0) sits behind the page (z 1), nav (z 50) and intro (z 100). It paints each section's ground with a feathered seam that starts 22% of the viewport height above the boundary, eased in-out, then drifts leaves (about 38%), letters (about 62%, 40% of them Arabic) and, on sign-in, equations (about 20%) left to right with parallax by depth. The pointer pushes nearby pieces aside within 140px. A soft leaf-coloured canopy glow sits behind the hero devices, and a void gradient covers the left 55% of the hero to keep copy legible. Particle count scales with viewport area (26 to 96, plus 10 on sign-in); device pixel ratio caps at 1.5.

### Named Rules
**The Reading Zone Rule.** Wherever the field crosses copy (hero copy, section heads, steps, checks, close, footer, legend; sign-in headline, line, modules, brand) it fades to 15% opacity inside the text box plus 12px.

**The Feathered Seam Rule.** Tones never meet at a hard line; the ground blends across 22% of the viewport above each section boundary.

## Elevation & Depth

Depth comes from tone contrast, smoked glass and long, soft drop shadows, not from a stack of grey card shadows. Page-level panels on the tones are near-flat: a translucent fill (`--panel` at the tone's `--panel-a`) plus a 1px inset hairline. Glass is reserved for physical things: the device shells, the journey scene and the solid nav. Product content placed on glass is opaque white with its own small shadow.

### Shadow Vocabulary
- **Lime lift** (`box-shadow: inset 0 1px 0 rgb(255 255 255 / .35), 0 10px 28px -10px rgb(var(--acc) / .6)`): primary buttons; on hover the outer part grows to `0 16px 36px -10px` at .75.
- **Tone hairline** (`box-shadow: inset 0 0 0 1px rgb(var(--line) / var(--line-a))`): bento tiles, dev-login panel, table frame, recording badge.
- **Nav hairline** (`box-shadow: 0 1px 0 rgb(var(--line) / var(--line-a))`): the nav once scrolled past 24px, with `backdrop-filter: blur(18px) saturate(1.3)` over the tone at 72%.
- **Glass shell** (`box-shadow: inset 0 0 0 1px rgb(255 255 255 / .16), inset 0 1px 0 rgb(255 255 255 / .38), 0 4em 7em -2em rgb(0 0 0 / .7)`): laptop lid and phone frame, over a 155 to 160deg white gradient at 4 to 22% with `backdrop-filter: blur(16px) saturate(1.4)`, plus a moving radial glare that follows the tilt.
- **Scene glass** (`box-shadow: inset 0 0 0 1px rgb(255 255 255 / .12), inset 0 1px 0 rgb(255 255 255 / .2), 0 40px 80px -40px rgb(0 0 0 / .6)`): the journey scene, blur 14px.
- **Content float** (`box-shadow: 0 1px 0 rgb(16 38 27 / .04), 0 18px 40px -24px rgb(0 0 0 / .45)`): post cards on dark tones; on paper they soften to `0 1px 2px rgb(16 38 27 / .08), 0 18px 40px -26px rgb(16 38 27 / .35)`, and small paper fragments (compose box, service, course, slot) use the first layer alone.
- **Match card** (`box-shadow: 0 30px 60px -36px rgb(16 38 27 / .5), 0 1px 2px rgb(16 38 27 / .06)`): the roster-match result card.
- **Sheet edge** (`box-shadow: -40px 0 120px -40px rgb(0 0 0 / .8)`): the sign-in paper sheet against the void.
- **Mark halo** (`box-shadow: 0 0 0 1px rgb(var(--lime) / .5), 0 0 0 10px rgb(var(--lime) / .06), 0 0 80px rgb(var(--leaf) / .45)`): the logo mark in the close section.

### Named Rules
**The Smoked Glass Rule.** Glass (backdrop blur plus a white gradient and inset highlights) belongs to device shells, the journey scene and the scrolled nav. Content cards are opaque.

**The Hairline Rule.** Panels on a tone are separated by an inset 1px hairline in the tone's own line colour, never by a solid border colour picked per section.

## Shapes

Soft, product-grade rounding that scales with object size: chips and tags `{rounded.chip}`, nav links and small controls `{rounded.control}`, buttons `{rounded.button}`, inputs `{rounded.field}`, large buttons and the match field `{rounded.button-lg}`, posts and comments `{rounded.card}`, tiles, scene, table frame and dev panel `{rounded.panel}`, the sign-in sheet `{rounded.sheet}`. Avatars are rounded squares at `{rounded.avatar}`, echoing the logo tile (corner radius about 20% of its side; the close mark uses 24%). Device shells scale their radii in em with the rig (lid 1.3em, phone 2.5em). Circles are reserved for status dots, timeline nodes, the demo cursor ripple and the compare marks (yes = filled dot, limited = half-filled ring, no = short dash).

The logo (`public/assets/img/logo.svg`, `favicon.svg`, `logo-full.svg`) is a rounded green tile (gradient mint to leaf to deep teal), a white M with rounded arches, a two-leaf sprout, a microphone and a wide stage bar. `app/views/partials/logo_motion.php` splits the same geometry into drawable layers for the intro.

## Components

### Buttons
Confident, lit from within: a solid accent block with an inner top highlight and a coloured under-glow.
- **Shape:** gently rounded (`{rounded.button}`; `{rounded.button-lg}` for the large size).
- **Primary:** `{components.button-primary}`: lime ground, lime-ink text, Geist 600 15px, 46px tall, 20px side padding, 10px gap to an optional 16 to 18px stroke icon. Small (38px, 14px text) in the nav; large (56px, 16px text) for the hero and close calls.
- **On paper:** the same component reads `--acc`/`--acc-fg`, so on paper it becomes `{components.button-paper}` (canopy ground, pale-lime text).
- **Hover / Focus / Active:** on fine pointers, `brightness(1.06)` and a deeper glow (240ms); focus-visible is the global 2px lime outline at 3px offset; active scales to .97 (160ms ease-out).
- **Text link:** Geist 500 with an underline at 30% of the text colour, 6px offset; on hover the underline turns accent and the arrow gap widens from 8 to 12px.

### Chips
- **Style:** small Geist 500 labels at 12.5 to 13px with 6px to 8px radius. Inactive chips use a 1px inset hairline at 20% or a 6 to 7% text-colour fill.
- **State:** active chips go `{components.chip-active}` (canopy ground, lime text) on paper; route chips go lime on dark. Compose-type chips transition background and colour over 220ms.

### Cards / Containers
- **Bento tile:** `{components.bento-tile}`: translucent white at the tone's panel alpha, tone hairline, 16px radius, 24px padding, min 230px tall, heading then mute copy then a fragment pinned to the bottom. On fine pointers a 420px leaf-tinted radial spotlight follows the cursor and the hairline turns leaf at 45%.
- **Post card:** `{components.post-card}`: opaque white, screen ink, 14px radius, 18px padding, avatar plus name and meta, an Archivo title, body, tag pills, and a reaction footer with a tabular count.
- **Glass scene:** the journey's sticky stage, 16px radius, min 470px, with a scene bar (logo, "manbar · aau", mono step counter). Panels cross-fade with blur 8px, 14px rise and .985 scale (360 to 520ms).

### Inputs / Fields
- **Style:** `{components.input-field}`: white, 1.5px border at the tone line's 18%, 11px radius, 48px tall. Placeholder in Geist at `#8aa093`.
- **Focus:** border turns leaf and a 4px leaf ring at 18% appears (200ms).
- **Google button:** white, 50px tall, 12px radius, hairline border at 18%, Geist 600 15px, full width; disabled with a hint line when sign-in is not configured.

### Navigation
- **Style:** fixed, 68px tall, transparent at the top. Brand on the left (34px logo, Archivo wordmark, Arabic name after a hairline), section links on the right (Geist 500 14px in mute, 8px radius, 8px 12px padding), then a small primary button.
- **States:** links brighten to the tone's text colour over a 6% fill on hover. After 24px of scroll the bar becomes solid smoked glass with a bottom hairline (300ms). The nav takes the tone of the section underneath it.
- **Mobile:** links hide at 900px; the button moves to the right edge. The Arabic name hides at 600px.

### Live Readout (signature)
A definition list under a top hairline: each item is a Geist 13px mute term over a large value. Counts come from the database and count up from zero over 1400ms (cubic ease-out) after the entrance; values use `{components.readout-value}`. The first item is a lime "Live" term with a 7px dot that pings every 2.2s.

### Intro Sequence (signature)
A full-screen layer over the void at 90% (z 100) plays on every landing load:
1. **Count:** the tile outline traces in mint-green (`#5dff9b`, glowing) as a 000 to 100 load counter in Geist Mono over at least 1150ms, holding at 92 until the page and fonts are ready (forced at 3s). The caption cycles "Loading campus", "Drawing the stage", "Growing ideas", "Ready".
2. **`s-draw`** (0ms): the counter blurs out; the M, stage bar, mic cradle and stand draw (700ms, staggered 0 to 420ms) and the mic capsule scales in.
3. **`s-leaf`** (560ms): the two leaves grow from their stems (620ms, expo out).
4. **`s-fill`** (800ms): the gradient fill fades in (650ms), the trace fades, and a glass sheen sweeps across the tile (1100ms).
5. **`s-sound`** (1020ms): two pairs of sound waves pulse out, an orbit light circles the mark (1300ms), and sparks twinkle.
6. **Fly** (1900ms): the mark translates and scales onto `#cHeroLogo` over 820ms (in-out), the backdrop clears, the lens-focus entrance starts 120ms in, and the layer is removed.
Any pointer, key, wheel or touch skips to the page (240ms fade). Reduced motion removes the intro entirely. A CSS safety net hides the layer at 12s.

### Lens-Focus Entrance (signature)
Elements marked as focus targets start at opacity 0, `blur(18px)` and `scale(1.035)`, then settle sharp: opacity 900ms ease-out, blur 1300ms and scale 1500ms on the lens curve, staggered 90ms by index (`--i`). A CSS fallback reveals them at 6s if script never runs. Under reduced motion only opacity changes (400ms).

### Device Rig (signature)
A smoked-glass laptop with an overlapping phone in a 3D rig sized in container units (1em = 1/64 of the rig width). It rests at rotateY -9deg, rotateX 5deg and follows the pointer on fine pointers (up to 8deg / 6deg, phone counter-shifted), with glare layers sliding opposite. The screens are live HTML/SVG: an activity list that prepends a new item every 2.6s, a growing timeline, a lifting board card, a level bar and a use-case diagram with travelling pulses. Labelled "Product preview with demo content".

### Demo Layer (signature)
Journey scenes and the idea-feed tile replay like screen recordings: a white arrow cursor with a dark outline and drop shadow travels between targets (760ms in-out), shrinks to .82 on click with a lime ripple, types text character by character (18 to 30ms per character, a green caret while typing), and pressed targets scale to .93. Counters bump, chips flash a 4px lime ring, cards are dragged across columns. Scripts run only while visible and loop with a pause. Every demo surface is `aria-hidden` and carries a visible label: "Walkthrough uses MANBAR's demo content.", "Demo recording 00:00" (with a blinking red dot and a mono clock), "Demo roster entry.", "Product preview with demo content".

### Compare Table
Rows of feature names against MANBAR and four other products. On entering view, marks pop in row by row (75ms per row, 60ms per column, expo out), the MANBAR score counts to 9/9, and the MANBAR column carries a leaf tint with a periodic scan and ring pulse on its dots. Rows tint leaf at 7% on hover.

### Motion
- **Curves:** `--ease-out` `cubic-bezier(.23,1,.32,1)` for state changes and entrances; `--ease-lens` `cubic-bezier(.16,1,.3,1)` (exponential ease-out) for the lens settle, pops and leaf growth; `--ease-io` `cubic-bezier(.77,0,.175,1)` only for travel (intro strokes, the fly, cursor travel, dragged cards). No easing curve overshoots.
- **Durations:** press 160ms, hover 200 to 240ms, nav state 300ms, panel swaps 360 to 520ms, content entrances 500 to 680ms, lens settle 900 to 1500ms.
- **Reduced motion:** smooth scroll off; intro removed; canvas painted static (no drift) and redrawn only on resize or scroll; lens entrance becomes a 400ms fade; device tilt, live list, demo scripts and count-up disabled; demo cursor hidden; compare marks shown statically; looping pings, lifts, dashes and fills stopped.

## Do's and Don'ts

### Do:
- **Do** give every new public section a `data-tone` of void, forest or paper and read all colour through `rgb(var(--token) / alpha)`.
- **Do** keep lime as the only accent on void and forest, and switch to canopy with pale-lime text on paper.
- **Do** set Archivo widened (106 to 120%) with negative tracking for headings, Geist for reading, and Geist Mono only for counts, times, prices, IDs and equations.
- **Do** use `{rounded.button}` buttons with the lime lift shadow and the 2px lime focus ring at 3px offset.
- **Do** show the product on opaque light screens (screen palette) inside smoked-glass shells.
- **Do** label every scripted or sample surface as demo content in visible plain words, and mark the scripted layer `aria-hidden`.
- **Do** add new copy blocks to the canvas reading-zone list so the field fades to 15% behind them.
- **Do** enter content with the lens-focus pattern (blur 18px, scale 1.035, 90ms stagger) and ease out on `--ease-out` or `--ease-lens`.
- **Do** provide a reduced-motion path that keeps state visible and drops movement to opacity only.

### Don't:
- **Don't** add eyebrow kickers above section heads; a head is an Archivo h2 and one mute paragraph.
- **Don't** build equal icon-card grids, decorative blobs or a big-number hero-metric row; the readout strip stays a hairline definition list.
- **Don't** put lime text or lime fills on paper.
- **Don't** use glass for content cards; glass is for devices, the journey scene and the scrolled nav.
- **Don't** use Geist Mono for labels, chips or prose.
- **Don't** use overshooting or springy easing curves for interface state; settle with exponential ease-out.
- **Don't** show invented testimonials, partner logos or numbers; counts come from the database and sample people are labelled demo.
- **Don't** bring the signed-in app's Inter / Plus Jakarta Sans type, gradient buttons or uppercase pills into this world.

## Out of Scope: Signed-in App (Legacy)

The signed-in application (`public/assets/css/app.css`, `app/views/layout.php` and the views it renders) is an older, pre-existing light-green system: Inter body, Plus Jakarta Sans headings, a `--g50` to `--g950` green scale, gradient primary buttons, 10 to 30px radii and soft green shadows. It was not redesigned and this document does not govern it; it is pending alignment with the cinematic world. Public pages that render through `layout_public.php` without the cinematic flag (onboarding, error pages) still use the legacy `public/assets/css/landing.css`. Do not restyle these surfaces from this file until an alignment pass is approved.
