# Image credits

34 photographs sourced from Unsplash (all under the Unsplash License — free for
commercial use, no attribution legally required; credited here anyway as good
practice). Downloaded at 1920px on the long edge via Drupal's file API into
`public://photos/`, one image media entity each (`bundle: image`), attached to
the field noted below. Alt text describes what's actually in the frame, not the
exhibition/page it illustrates.

The first 18 rows were sourced and attached in the initial pass. A follow-up
audit found several image fields still pointing at media entities left over
from the original photography removal — the reference existed but the media
entity itself had been deleted, so the field silently resolved to nothing and
the component fell back to its gradient placeholder. Rows 19–28 (below the
first rule) were sourced to fill those specifically.

A third audit checked the full chain end to end (field → media entity → file
entity → file actually present on disk) in both directions, rather than just
whether a reference resolved. Nothing was broken by that point, but five
exhibitions fell short of the "at least three gallery images" bar (26, 27, 28
and 29 had two; 30 had one, and a mismatched automaton photo reused from
another article at that). Rows 29–34 fill those five gaps with six new
images, two of them (the ledger photos) added to node/30 alone.

A fourth pass checked every *other* page for the same "dangling reference"
gap, not just node fields: exhibitions also each carry their own "split"
section paragraph (a second image lower on the exhibition-detail page,
separate from the hero and gallery), and 5 of 6 of those turned out to be
pointing at media entities deleted in the same original photography
removal that caused the rows-19–28 gap. Only Lindqvist's own split section
worked, because it already reused one of Lindqvist's gallery photos rather
than pointing at its own dedicated media. Fixed the same way, reusing each
exhibition's own already-vetted gallery photo for its split section instead
of sourcing six more images for a one-off placement — see the reuse notes
below.

| Media | Used on | Field | Photographer | Source | Licence |
|---|---|---|---|---|---|
| exhibition-25-hero (id 41) | Kaspar Lindqvist: The Maquettes — hero | `node/25` `field_hero_media` | Elijah Crouch | https://unsplash.com/photos/Dl7CtzT7Iyw | Unsplash License |
| exhibition-26-hero (id 42) | The Last Shift at Cutter's Point — hero | `node/26` `field_hero_media` | Annie Spratt | https://unsplash.com/photos/BwJ-5B80vww | Unsplash License |
| exhibition-27-hero (id 43) | The Solberg Bequest: New Weaving — hero | `node/27` `field_hero_media` | Zuzana Kacerová | https://images.unsplash.com/photo-1564656622440-e6206eb5ee63 | Unsplash License |
| exhibition-28-hero (id 44) | Ilse Marchetti: Under the Surface — hero | `node/28` `field_hero_media` | Cat Guffin | https://unsplash.com/photos/0Ip-dmYYIcI | Unsplash License |
| exhibition-29-hero (id 45) | Halvard & Rein: The Workshop Drawings — hero | `node/29` `field_hero_media` | Lucas Kepner | https://unsplash.com/photos/Yn8D5B8C-eY | Unsplash License |
| exhibition-30-hero (id 46) | The Ledger of Aurelio Faust — hero | `node/30` `field_hero_media` | camera obscura | https://unsplash.com/photos/rvVhr2LngP4 | Unsplash License |
| exhibition-25-gallery-1 (id 47) | Lindqvist gallery, item 1 | `node/25` `field_gallery` | Timeea Pirvulescu | https://unsplash.com/photos/JPUaexVgz7s | Unsplash License |
| exhibition-25-gallery-2 (id 48) | Lindqvist gallery, item 2 — also the homepage split image | `node/25` `field_gallery`; paragraph 22 `field_split_media` | Alina Bondar | https://unsplash.com/photos/GuIuAJgJkWM | Unsplash License |
| exhibition-25-gallery-3 (id 49) | Lindqvist gallery, item 3 — also the exhibition-detail split image | `node/25` `field_gallery`; paragraph 8 `field_split_media` | Matt Seymour | https://unsplash.com/photos/pzbbViakdX0 | Unsplash License |
| exhibition-25-gallery-4 (id 50) | Lindqvist gallery, item 4 | `node/25` `field_gallery` | mojtaba mosayebzadeh | https://unsplash.com/photos/isZJP9q3XaM | Unsplash License |
| exhibition-25-gallery-5 (id 51) | Lindqvist gallery, item 5 | `node/25` `field_gallery` | mojtaba mosayebzadeh | https://unsplash.com/photos/V9mVXYKGyxc | Unsplash License |
| event-31 (id 52) | Curator's Talk: Casting the Maquettes | `node/31` `field_event_image` | Changbok Ko | https://unsplash.com/photos/F8t2VGnI47I | Unsplash License |
| event-34 (id 53) | Family Workshop: Weaving on a Cardboard Loom | `node/34` `field_event_image` | Kelly Sikkema | https://unsplash.com/photos/V1VkOENg_KQ | Unsplash License |
| article-37 (id 54) | How We Found Six Paintings Inside One | `node/37` `field_image` | Philip Swinburn | https://unsplash.com/photos/vS7LVkPyXJU | Unsplash License |
| article-39 (id 55) | New to the Collection: An Automaton by Costin Radu | `node/39` `field_image` | Yunshuo Qu | https://unsplash.com/photos/MrIEU6Fkrkw | Unsplash License |
| home-hero (id 56) | Front page hero | Paragraph 20 (front page hero) `field_hero_media` | Paolo Chiabrando | https://unsplash.com/photos/uezooiynnt8 | Unsplash License |
| membership-hero (id 57) | Membership page hero | `node/45` `field_hero_media` | Barney Goodman | https://unsplash.com/photos/oZhvfoMAD30 | Unsplash License |
| visit-about-hero (id 58) | Visit and About page banners (same image, both pages) | `node/43` and `node/44` `field_hero_media` | MIROV (@denmirov) | https://unsplash.com/photos/KWVSXLB6rao | Unsplash License |
| gallery-26-1 (id 59) | Cutter's Point gallery, item 1 | `node/26` `field_gallery` | Oxa Roxa | https://unsplash.com/photos/n2FrveiBoCw | Unsplash License |
| gallery-26-2 (id 60) | Cutter's Point gallery, item 2 — also its own exhibition-detail split image | `node/26` `field_gallery`; paragraph 10 `field_split_media` | Moralis Tsai | https://unsplash.com/photos/Pk2CGYxmbew | Unsplash License |
| gallery-27-1 (id 61) | Solberg gallery, item 1 — also Petra Vinter's article image | `node/27` `field_gallery`; `node/38` `field_image` | Edo | https://unsplash.com/photos/dHJ7ACrfRQ0 | Unsplash License |
| gallery-28-1 (id 62) | Marchetti gallery, item 1 — also Live Demonstration's event image | `node/28` `field_gallery`; `node/32` `field_event_image` | laura adai | https://unsplash.com/photos/s6U7Gq93UU8 | Unsplash License |
| gallery-28-2 (id 63) | Marchetti gallery, item 2 | `node/28` `field_gallery` | Annie Spratt | https://unsplash.com/photos/xz485Eku8O4 | Unsplash License |
| gallery-29-1 (id 64) | Halvard & Rein gallery, item 1 — also Members' Preview's event image | `node/29` `field_gallery`; `node/35` `field_event_image` | Calvin Wise | https://unsplash.com/photos/6hVS5dXige8 | Unsplash License |
| gallery-29-2 (id 65) | Halvard & Rein gallery, item 2 | `node/29` `field_gallery` | Julia Berezina | https://unsplash.com/photos/UjsqZTBf00c | Unsplash License |
| event-33 (id 66) | Closing Lecture: What the Ledger Shows | `node/33` `field_event_image` | ARTO SURAJ | https://unsplash.com/photos/N0xZnXCqCYg | Unsplash License |
| event-36 (id 67) | Annual Address: Collecting Without a Collection Plan | `node/36` `field_event_image` | ARTO SURAJ | https://unsplash.com/photos/kFCor16bqq4 | Unsplash License |
| article-41 (id 68) | Reading Ruth Halvard's Handwriting | `node/41` `field_image` | Micah Boswell | https://unsplash.com/photos/00nHr1Lpq6w | Unsplash License |
| gallery-26-3 (id 69) | Cutter's Point gallery, item 3 | `node/26` `field_gallery` | Alex Quezada | https://unsplash.com/photos/DHbw4hYyiu8 | Unsplash License |
| gallery-27-2 (id 70) | Solberg gallery, item 2 — also its own exhibition-detail split image | `node/27` `field_gallery`; paragraph 12 `field_split_media` | Mick Haupt | https://unsplash.com/photos/LZrx0NWxvQY | Unsplash License |
| gallery-28-3 (id 71) | Marchetti gallery, item 3 — also its own exhibition-detail split image | `node/28` `field_gallery`; paragraph 14 `field_split_media` | Steve A Johnson | https://unsplash.com/photos/amiXH5ithAA | Unsplash License |
| gallery-29-3 (id 72) | Halvard & Rein gallery, item 3 — also its own exhibition-detail split image | `node/29` `field_gallery`; paragraph 16 `field_split_media` | Sven Mieke | https://unsplash.com/photos/fteR0e2BzKo | Unsplash License |
| gallery-30-2 (id 73) | Ledger gallery, item 2 — also its own exhibition-detail split image | `node/30` `field_gallery`; paragraph 18 `field_split_media` | Magic Fan | https://unsplash.com/photos/CA4QegCOiDQ | Unsplash License |
| gallery-30-3 (id 74) | Ledger gallery, item 3 | `node/30` `field_gallery` | Magic Fan | https://unsplash.com/photos/GiK5H8JoYfU | Unsplash License |

## Reuse notes

Three images are deliberately reused rather than sourced twice, since they're
the same building/subject appearing in a second, lower-visibility context:

- **exhibition-28-hero** (Marchetti's own hero, an empty art studio) also
  fills the homepage's second split section, which is about the same
  exhibition.
- **exhibition-25-gallery-2** and **-gallery-3** (two of Lindqvist's five
  gallery photos) also fill the homepage's first split section and the
  exhibition-detail page's own split section respectively, so the hero,
  split and gallery sections of Lindqvist's own pages don't all show the
  identical photo.
- **visit-about-hero** fills both the Visit and About page banners — the
  brief asked for 3 interior photos across 4 destinations (Home, Visit,
  About, Membership), so one was always going to cover two pages.

## Sourcing notes (for the record)

A few search terms didn't turn up a usable free result on the first pass and
were substituted for a comparable free alternative in the same spirit,
because the first candidate failed the rejection rules in this brief
(cold/saturated colour, visible real institution or branding, or a
paid/Unsplash+ licence):

- **Ilse Marchetti's hero** (`oil painting texture` / `art restoration`): the
  literal search terms returned only heavily saturated paint-and-brush shots
  or Unsplash+ restoration photography. Substituted an empty art studio
  (easels, natural light) — the exhibition is about a painter's practice, not
  a specific painting.
- **Home's interior** (`art gallery interior`): several candidates showed
  real gallery/museum interiors with visible branding or, in one case, what
  appears to be a war memorial with inscribed names — rejected outright.
  Substituted a tightly cropped shot of classical stone columns in raking
  light, abstract enough not to identify a specific building.
- **"Craft workshop" for the Family Workshop event**: the literal search
  returned a cluttered craft table with visible product/brand labels.
  Substituted two balls of yarn in a basket — plainer, and closer to what the
  event (weaving on a cardboard loom) is actually about.
