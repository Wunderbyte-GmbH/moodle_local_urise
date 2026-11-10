# Mega menu

An editable mega menu for the "Angebot" item of the primary navigation. Editors maintain it in
`/local/urise/megamenu.php`. `theme_lexa` renders it.

Screen design: Figma "UWI_LeXA_Design", page "Megamenü" (node `6040:19450`), click dummy at
`https://www.figma.com/proto/c1mUl2yCjRC37kx7VUTgqG/UWI_LeXA_Design?node-id=7-39283`.

## Where things are

| Part | Files |
|---|---|
| Data, storage, export | `classes/megamenu/menu.php` |
| Editing operations, editor data | `classes/megamenu/editor.php` |
| Filter values with readable names | `classes/megamenu/filteroptions.php` |
| Example content from the design | `classes/megamenu/seed.php`, `cli/megamenu.php --seed` |
| Editor page, preview page | `megamenu.php`, `megamenu_preview.php` |
| Modal forms | `classes/form/megamenu_*_form.php` |
| Delete, move, publish, discard | `classes/external/megamenu_edit.php` (`local_urise_megamenu_edit`) |
| Editor JS | `amd/src/megamenu_editor.js`, `amd/src/megamenu_preview.js` |
| Rendering in the theme | `theme/lexa`: `classes/output/navigation/primary.php` (`attach_megamenu()`), `templates/megamenu.mustache`, `templates/megamenu_block.mustache`, `amd/src/megamenu.js`, `scss/_megamenu.scss` |
| Mobile (drawer) | `theme/lexa`: `templates/theme_boost/primary-drawer-mobile.mustache`, `templates/megamenu_mobile.mustache` (level 1), `templates/megamenu_mobile_panels.mustache` (communities), same JS and SCSS |
| Icons | `pix/megamenu/<name>.svg` (from Figma, background removed), names in `menu::ICONS` |
| Tests | `tests/megamenu/menu_test.php`, `tests/megamenu/editor_test.php` |

## Data

The whole menu is one JSON document in the plugin config. There is no database table.

- `local_urise/megamenu` holds the live menu.
- `local_urise/megamenu_draft` holds the editors' draft. "Veröffentlichen" copies it to the live menu.
- Every save increments `revision`. A save based on an outdated revision is refused with `megamenu:conflict`.
- Every save writes the previous version to `config_log`.

```
menu  = {schemaversion, revision, enabled, attachurl, catalogueurl, tabs[]}
tab   = {id, type: overview|panel|link, label{}, link, color, icon, heading{}, intro{}, hint{},
         blocks[], news: {id, kicker{}}|null, cta: link|null, secondarylinks[]}
block = {id, type: cardrow|linkgrid|chips, title{}, boxed, cards[], links[]}
card  = {id, title{}, subtitle{}, links[]}
link  = {id, type: url|filter|search, label{}, url, filters{column: [values]}, search}
```

- **Texts** (`{}`) are maps of language to text, e.g. `{"de": "…", "en": "…"}`. A missing language falls back
  to the site language.
- **Filter links** open `catalogueurl` with `wbtfilter=<json>`, and **search links** with `wbtsearch=`. The
  catalogue's wunderbyte table must offer the filter column.
- **Colours** (`menu::COLORS`, mirrored in `scss/_megamenu.scss`): blue, petrol, yellow, purple, green, teal, brown,
  salmon, rose. Yellow is `#D6B737` since the design update of 2026-10-20 (contrast of the white icons).
- **Icons** (`menu::ICONS`): one of the nine icons of the design, or none. `export_for_template()` inlines the SVG
  as `iconsvg`. The theme shows it in a box in the entry's colour (32px desktop, 38px mobile, 50px mobile title).
- **Filter values** are the raw custom field values: the 1-based line for select fields, the id for
  dynamic fields.
- **Elements are addressed by paths**, e.g. `["tabs","phd","blocks","b1","links","l2"]`. See `editor::get_ref()`.

`menu::normalise()` cleans any input: it drops invalid values and fills defaults. Import, export and the
example content are available via `php local/urise/cli/megamenu.php --export|--import=FILE|--seed [--force]`.

## Rendering

`theme_lexa\output\navigation\primary::attach_megamenu()`:
- finds the custom menu item whose URL matches `attachurl` (not its label)
- marks it as the trigger
- passes `menu::export_for_template()` to the navbar template

Without JavaScript, the item stays a normal link. `?megamenupreview=1` shows the draft to editors.

### Animations

They follow the Figma prototype.

| When | Figma | Implementation |
|---|---|---|
| Open and close | Dissolve, 300ms ease-out | Panel and backdrop fade |
| Switching community | Smart Animate, 200ms ease-out | See below |
| Mobile drill-down | Smart Animate, 250ms ease-out | Level 1 and the community crossfade, height follows (same code as the community switch) |

How the community switch is built in `amd/src/megamenu.js`:
- Both panels share one grid cell, and the old panel fades out at its own height.
- Elements with the same `data-animkey` (heading, `linkgrid-0`, `chips-0`, `cta`, `secondary-0`, …) move from
  their old to their new position (FLIP).
- If their content differs, they crossfade while moving.
- New elements fade in, and the panel height follows.
- The keys come from `menu::export_for_template()`.

The panel is at least 640px high, and the community colours are CSS classes (`.lexa-mm-color-*`).

### Keyboard

The left column follows the tabs pattern of WAI-ARIA (`amd/src/megamenu.js`, `handleKeydown()`):

- Enter, Space or arrow down on "Angebot" open the menu and focus the selected entry; a mouse click leaves the
  focus on the trigger.
- Only the selected entry is in the tab order (roving `tabindex`), so Tab goes straight into the panel and
  Shift+Tab comes back. Arrow up/down move between all entries and show a community right away, Home/End jump.
- Enter, Space or arrow right on an entry move the focus into the panel, to its heading (`tabindex="-1"`) or its
  first link. Arrow left in the panel returns to the selected entry. Escape closes and focuses the trigger.
- In the mobile drawer the rows are buttons in document order; opening a community focuses its back link and
  going back focuses the row again.

### Mobile

Below 768px the desktop panel is hidden and the menu lives in the primary drawer (Figma "Megamenü Mobile").
`primary::attach_megamenu()` also marks the drawer's item (`mobileprimarynav`) and sets `megamenu.mobile`.

- **Level 1** (`megamenu_mobile.mustache`): the item expands to the entries, each with icon, label and chevron.
  The overview entry and link entries are links, communities are buttons (`data-action="lexa-megamenu-drill"`).
  The blocks of the overview tab follow as chips ("Themen für alle Communities").
- **Level 2** (`megamenu_mobile_panels.mustache`): one `<section>` per community, replacing the whole drawer
  content: back link, title bar (tint background, icon box, label), then the body: CTA, intro, blocks, news card
  (with date), secondary links. Card rows become accordions (first card open), a boxed link list becomes a
  collapsed accordion, a plain link list becomes rows with dividers, chips stay chips. Heading and hint are not shown.
- Level 1 and the panels share one grid cell of `.lexa-mm-mobile`, so `animateSwitch()` in `megamenu.js` runs the
  drill-down (250ms) and the drawer scrolls to the top. Closing the drawer (`theme_boost/drawers:hidden`) resets
  to level 1. The focus moves to the back link and back to the opened row.
- The editor preview shows the mobile version with "Handy": click-to-edit works on the title, intro, CTA, links,
  accordion titles, chips and news; tapping a community drills down and selects it in the editor, and vice versa.

## Editor

The editor page has three parts.

- **Live preview.** The page `megamenu_preview.php` runs in an iframe, in the `embedded` layout, with the theme
  template in preview mode. It is an iframe because the page header already contains the real menu with the
  same ids. "Desktop" shows the panel scaled to the editor width; "Handy" (`?view=mobile`) shows the drawer
  at 375px in a phone frame (`templates/megamenu_preview_mobile.mustache`, theme partials, content scrolls).
  The choice is kept in `localStorage`.
  - The preview and the editor talk via `postMessage` (same origin only).
  - Clicking an element with `data-editpath` opens its form.
  - Hovering shows the component name.
  - Switching communities is synced in both directions.
- **Structure list.** Elements can be added, edited, deleted and dragged (`core/sortable_list`). Moving only
  goes to lists of the community that is shown.
- **Component picker.** "Block hinzufügen" shows each block type rendered with the theme partial
  `theme_lexa/megamenu_block` and example content.

Permission: `local/urise:canedit` (system context).

## Testing

- Run PHPUnit file by file: `vendor/bin/phpunit local/urise/tests/megamenu/editor_test.php`.
  `--filter` loads a broken test of another plugin on this dev site.
- **Design and browser checks** live outside the repo in `~/megamenu-qa/` on the dev VM (stdlib Python, drive the
  installed Chromium via the DevTools protocol, `cdp.py` is the client):
  - `cmp_capture.py`: renders every Figma frame (desktop variants, mobile screens) via the REST API and screenshots the
    same views on the site, then writes side-by-side images to `/tmp/figma_cmp/pair_*.png`. Run after design or CSS changes.
  - `figma_spec.py <nodeid>` and `dom_spec.py desktop|mobile <tab>`: the same tree of sizes, paddings, colours and
    fonts from Figma and from the rendered page, for a numeric diff.
  - `mm_e2e.py` (mobile drill-down, desktop icons), `mm_keys_e2e.py` (keyboard), `mm_click_e2e.py` and
    `mm_editor_e2e.py` (editor phone preview with real clicks, need `mkuser.php` for a temporary manager),
    `resultcount.py` (how many offers every catalogue link shows), `linkcheck.php` (HTTP status of every link).
  - Kill stale browsers with `pkill -f "[c]hromium --headless"` before a run; a killed run leaves one behind.
- For the end-to-end test, drive Chromium via the DevTools protocol, logged in as a temporary manager:
  1. Back up `megamenu` and `megamenu_draft`.
  2. Run the test (22 checks): preview, hover, click-to-edit, sync, picker, add, move, settings, discard,
     publish against the live menu, delete.
  3. Restore both settings and delete the user.

## Open

- **Desktop design update 2026-10-20.** Only the icons and colours were taken over so far; the other changes on
  the Megamenü page are size tweaks of the left column (label 192px wide next to the icon).
- **Several filters per link.** The link form handles one filter column per link; the data model allows several.
- **Phase 2.** Generate items from wunderbyte_table filter values, with counts. Cache them per language and
  invalidate on `setbackoptionstable`.
- **Old navbar hack.** "Communities" and "Support" still use the `[navbarhtml]` hack in
  `theme/lexa/layout/drawers.php` and `login.php`, which matches the menu items by their text.
- **Base branch.** The `feature_megamenu` branch is based on `main`. `MOODLE_500_DEV` needs
  mod_booking 2026091500.
