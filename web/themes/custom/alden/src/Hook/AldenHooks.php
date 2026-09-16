<?php

namespace Drupal\alden\Hook;

use Drupal\alden\AldenComponentHelper;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for alden.
 */
class AldenHooks {
  /**
   * @file
   * Functions to support theming.
   */

  /**
   * Implements hook_preprocess_image_widget().
   */
  #[Hook('preprocess_image_widget')]
  public function preprocessImageWidget(array &$variables): void {
    $data = &$variables['data'];
    // This prevents image widget templates from rendering preview container
    // HTML to users that do not have permission to access these previews.
    // @todo revisit in https://drupal.org/node/953034
    // @todo revisit in https://drupal.org/node/3114318
    if (isset($data['preview']['#access']) && $data['preview']['#access'] === FALSE) {
      unset($data['preview']);
    }
  }

  /**
   * Implements hook_page_attachments_alter().
   *
   * Preloads the two font files used on virtually every page: Inter 400
   * (body copy) and Instrument Serif 400 (headings, frequently the LCP
   * element). Themes may only implement the _alter variant of this hook.
   */
  #[Hook('page_attachments_alter')]
  public function pageAttachmentsAlter(array &$attachments): void {
    $theme_path = \Drupal::theme()->getActiveTheme()->getPath();
    $fonts = [
      'inter-400.woff2',
      'instrument-serif-400.woff2',
    ];
    foreach ($fonts as $font) {
      $attachments['#attached']['html_head_link'][][] = [
        'rel' => 'preload',
        'href' => '/' . $theme_path . '/fonts/' . $font,
        'as' => 'font',
        'type' => 'font/woff2',
        'crossorigin' => 'anonymous',
      ];
    }
  }

  /**
   * Implements hook_preprocess_page().
   *
   * Exposes the site name for the hardcoded wordmark, whether the
   * current page has a full-bleed hero to sit transparently over (front
   * page, exhibition detail, membership -- everything else starts
   * solid), and which nav link (if any) matches the current page, for
   * aria-current + the underline treatment in page.html.twig.
   */
  #[Hook('preprocess_page')]
  public function preprocessPage(array &$variables): void {
    $variables['site_name'] = \Drupal::config('system.site')->get('name');

    $is_front = \Drupal::service('path.matcher')->isFrontPage();
    $node = \Drupal::routeMatch()->getParameter('node');
    $bundle = $node instanceof NodeInterface ? $node->bundle() : NULL;
    $nid = $node instanceof NodeInterface ? (int) $node->id() : NULL;

    $variables['has_hero'] = $is_front || $bundle === 'exhibition' || $nid === 45;

    $current_path = \Drupal::request()->getPathInfo();
    $variables['nav_current'] = match (TRUE) {
      $is_front => 'home',
      $bundle === 'exhibition' || str_starts_with($current_path, '/exhibitions') => 'exhibitions',
      $bundle === 'event' || $bundle === 'article' || str_starts_with($current_path, '/whats-on') => 'whats_on',
      $nid === 43 => 'visit',
      $nid === 44 => 'about',
      $nid === 45 => 'membership',
      default => NULL,
    };
  }

  /**
   * Implements hook_preprocess_node().
   *
   * Dispatches by bundle to build each fixed-layout template's props:
   * exhibition detail (alden_hero/alden_facts/alden_split/alden_quote/
   * alden_gallery/alden_related) and the shared basic-page doc/aside
   * layout used by basic pages, events and articles (alden_doc). Node 45
   * (Membership) and 48 (front page) opt out -- they have their own
   * dedicated node--45.html.twig / node--48.html.twig templates.
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $variables['node'];
    if (($variables['view_mode'] ?? '') !== 'full') {
      return;
    }
    switch ($node->bundle()) {
      case 'exhibition':
        $this->preprocessExhibitionNode($variables, $node);
        return;

      case 'page':
        // The front page (node 48) has its own dedicated
        // node--48.html.twig template and needs no preprocessing here.
        if ((int) $node->id() === 48) {
          return;
        }
        // Membership (node 45) also has its own dedicated
        // node--45.html.twig template, but unlike the front page it
        // still uses the shared _doc-layout.html.twig partial for its
        // sticky contact sidebar, so it needs its own variables built.
        if ((int) $node->id() === 45) {
          $this->preprocessMembershipNode($variables, $node);
          return;
        }
        $this->preprocessBasicPageNode($variables, $node);
        return;

      case 'event':
        $this->preprocessEventNode($variables, $node);
        return;

      case 'article':
        $this->preprocessArticleNode($variables, $node);
        return;
    }
  }

  /**
   * The "Visit" info panel + membership CTA shown in every basic-page-
   * style sidebar (basic pages, events, articles) -- real opening
   * hours/admission/address, pulled from the Visit page's own real
   * content once here rather than repeated as literals in three places.
   */
  private function visitPanel(): array {
    return [
      'heading' => 'Visit',
      'rows' => [
        ['dt' => 'Opening hours', 'dd' => 'Tuesday–Sunday, 10am–5pm'],
        ['dt' => 'Admission', 'dd' => '£14 adults<br>Free for members'],
        ['dt' => 'Address', 'dd' => '100 Gallery Street'],
      ],
    ];
  }

  /**
   * Basic pages (Visit/About/Accessibility/Support Us): two-column
   * doc/aside layout, real body copy (already restructured with real
   * subheadings -- see the content itself), a real "Visit" info panel,
   * and a membership CTA. eyebrow/h1 are a small hardcoded per-node
   * lookup -- the real titles are short nav-style labels ("Visit"), so a
   * slightly fuller, purely descriptive h1 is added alongside them
   * (never a substantive new claim, just a structural headline).
   */
  private function preprocessBasicPageNode(array &$variables, NodeInterface $node): void {
    $copy = [
      43 => ['eyebrow' => 'Visit', 'h1' => 'Planning your visit'],
      44 => ['eyebrow' => 'About', 'h1' => 'About the museum'],
      46 => ['eyebrow' => 'Support us', 'h1' => 'Ways to support us'],
      47 => ['eyebrow' => 'Accessibility', 'h1' => 'Accessibility at the museum'],
    ];
    $meta = $copy[$node->id()] ?? ['eyebrow' => $node->label(), 'h1' => $node->label()];

    $variables['alden_doc'] = [
      'eyebrow' => $meta['eyebrow'],
      'h1' => $meta['h1'],
      'date_line' => '',
      'image' => AldenComponentHelper::nodeImage($node, 'alden_split'),
      'body' => $node->get('body')->isEmpty() ? '' : $node->get('body')->first()->processed,
      'panels' => [$this->visitPanel()],
      'cta' => ['url' => '/membership', 'text' => 'Become a member'],
    ];
  }

  /**
   * Events use the basic-page template with a date line added -- real
   * date/time/location/price, all from real fields. The sidebar panel
   * repeats those same facts in the mockup's dl/dt/dd shape rather than
   * the generic Visit panel, since they're more relevant here.
   */
  private function preprocessEventNode(array &$variables, NodeInterface $node): void {
    $date_line = '';
    $when = '';
    if (!$node->get('field_datetime')->isEmpty()) {
      $date = new \DateTime($node->get('field_datetime')->value, new \DateTimeZone('UTC'));
      $when = \Drupal::service('date.formatter')->format($date->getTimestamp(), 'custom', 'j F Y, g:ia');
      $date_line = $when;
    }
    $location = $node->get('field_location')->value ?: '';
    $price = $node->get('field_price')->value ?: '';

    $rows = [];
    if ($when) {
      $rows[] = ['dt' => 'When', 'dd' => $when];
    }
    if ($location) {
      $rows[] = ['dt' => 'Where', 'dd' => $location];
    }
    if ($price) {
      $rows[] = ['dt' => 'Price', 'dd' => $price];
    }

    $summary = $node->get('field_summary')->value ?: '';
    $variables['alden_doc'] = [
      'eyebrow' => "What's On",
      'h1' => $node->label(),
      'date_line' => $date_line,
      'body' => $summary ? '<p>' . $summary . '</p>' : '',
      'panels' => array_filter([
        $rows ? ['heading' => 'Event details', 'rows' => $rows] : NULL,
        $this->visitPanel(),
      ]),
      'cta' => ['url' => '/membership', 'text' => 'Become a member'],
    ];
  }

  /**
   * Articles use the basic-page template with a date line added -- the
   * real published date and a real computed read time (word count / 200
   * wpm, not invented) instead of the mockup's example estimates.
   */
  private function preprocessArticleNode(array &$variables, NodeInterface $node): void {
    $published = \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'custom', 'j F Y');
    $read_time = AldenComponentHelper::readTime($node);
    $date_line = $read_time ? "$published · $read_time" : $published;

    $rows = [['dt' => 'Published', 'dd' => $published]];
    if (!$node->get('field_author_name')->isEmpty()) {
      $rows[] = ['dt' => 'Written by', 'dd' => $node->get('field_author_name')->value];
    }
    if ($read_time) {
      $rows[] = ['dt' => 'Read time', 'dd' => $read_time];
    }

    $variables['alden_doc'] = [
      'eyebrow' => 'Journal',
      'h1' => $node->label(),
      'date_line' => $date_line,
      'body' => $node->get('body')->isEmpty() ? '' : $node->get('body')->first()->processed,
      'panels' => [
        ['heading' => 'About this piece', 'rows' => $rows],
        $this->visitPanel(),
      ],
      'cta' => ['url' => '/membership', 'text' => 'Become a member'],
    ];
  }

  /**
   * Builds node 45's (Membership) template variables. This page's
   * content (prices, tier scope, benefits, FAQ) is fixed, one-off copy
   * that will only ever describe this one page, the same way the
   * homepage hero's copy is hardcoded rather than stored in a reusable
   * field. Every fact below is real, taken from the museum's real
   * Visit/About/Support Us copy (the mockup's own extra tier perks --
   * guest passes, priority booking -- aren't in any real field, so
   * they're dropped rather than invented; see the final build report
   * for the full list).
   */
  private function preprocessMembershipNode(array &$variables, NodeInterface $node): void {
    $variables['alden_hero'] = [
      'title' => 'Three visits and it has paid for itself',
      'eyebrow' => 'Membership',
      'min_height' => '58svh',
      'ph_class' => 'ph-gallery',
      'image' => AldenComponentHelper::nodeImage($node, 'alden_hero'),
    ];

    $variables['alden_lede'] = 'Membership runs for twelve months from the day you join and covers unlimited entry to the museum and every ticketed exhibition.';

    $variables['alden_tiers'] = [
      [
        'name' => 'Individual',
        'price' => '£48',
        'per' => 'per year, one named adult',
        'benefits' => [
          'Unlimited free entry for twelve months',
          'Free entry to all ticketed exhibitions',
          'Ten percent off in the shop and café',
          "Members' newsletter before each opening",
        ],
        'featured' => FALSE,
      ],
      [
        'name' => 'Joint',
        'price' => '£75',
        'per' => 'per year, two adults at one address',
        'benefits' => [
          'Everything in Individual, for two people',
          "Invitations to members' preview evenings",
        ],
        'featured' => TRUE,
        'flag' => 'Most chosen',
      ],
      [
        'name' => 'Family',
        'price' => '£95',
        'per' => 'per year, two adults and up to four children',
        'benefits' => [
          'Everything in Joint',
          'Covers up to four children',
        ],
        'featured' => FALSE,
      ],
    ];

    $variables['alden_benefits'] = [
      'eyebrow' => 'What membership does',
      'items' => [
        ['number' => '01', 'heading' => 'See it first', 'body' => "Members' preview evenings are held the week before each exhibition opens to the public."],
        ['number' => '02', 'heading' => 'Come back as often as you like', 'body' => 'Membership covers unlimited free entry for twelve months, to the museum and every ticketed exhibition.'],
        ['number' => '03', 'heading' => 'Help keep the doors open', 'body' => 'The museum receives no automatic annual government funding and relies on ticketed exhibitions and membership income to cover most running costs.'],
      ],
    ];

    $variables['alden_faq'] = [
      'heading' => 'Questions we get asked',
      'items' => [
        ['question' => 'Can I use my membership on the day I join?', 'answer' => 'Yes. Join at the front desk and you can enter immediately; your card arrives by post within ten working days.', 'open' => TRUE],
        ['question' => 'Does membership cover guests?', 'answer' => 'Membership is non-transferable and does not include guest entry. A guest ticket can be bought at a member discount on the day.'],
        ['question' => 'How do I join?', 'answer' => 'You can join at the front desk, by phone, or online.'],
        ['question' => 'Is membership Gift Aid-eligible?', 'answer' => 'The museum is a registered charity, and UK taxpayers can Gift Aid their membership, which adds twenty-five percent at no extra cost to you.'],
      ],
    ];

    $variables['alden_doc'] = [
      'panels' => [
        [
          'heading' => 'Join in person',
          'rows' => [
            ['dt' => 'Front desk', 'dd' => 'Tue–Sun, 10am–5pm'],
          ],
        ],
        [
          'heading' => 'Already a member?',
          'rows' => [
            ['dt' => 'Renewals and changes', 'dd' => 'members@alden.example'],
          ],
        ],
      ],
    ];
  }

  /**
   * Exhibition detail: hero, facts + lede, split, body on mist, pull
   * quote, gallery grid, related events. See node--exhibition.html.twig.
   */
  private function preprocessExhibitionNode(array &$variables, NodeInterface $node): void {
    $variables['alden_hero'] = [
      'title' => $node->label(),
      'status_text' => AldenComponentHelper::exhibitionStatusLabel($node),
      'ph_class' => AldenComponentHelper::phClass($node, 'hero'),
      'image' => AldenComponentHelper::nodeImage($node, 'alden_hero'),
    ];

    $topics = [];
    foreach ($node->get('field_topics')->referencedEntities() as $term) {
      $topics[] = ['label' => $term->label(), 'url' => $term->toUrl()->toString()];
    }
    $variables['alden_facts'] = [
      'dates' => AldenComponentHelper::factsDateRange($node),
      'curator' => $node->get('field_curator')->value ?: '',
      'topics' => $topics,
      'lede' => $node->get('field_summary')->value ?: '',
    ];

    $variables['alden_split'] = NULL;
    $variables['alden_quote'] = NULL;
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('paragraph');
    foreach ($node->get('field_components')->referencedEntities() as $paragraph) {
      if ($paragraph->bundle() === 'split' && !$variables['alden_split']) {
        $variables['alden_split'] = $view_builder->view($paragraph);
      }
      if ($paragraph->bundle() === 'quote' && !$variables['alden_quote']) {
        $variables['alden_quote'] = $view_builder->view($paragraph);
      }
    }

    $variables['alden_gallery'] = AldenComponentHelper::galleryItems($node);
    $variables['alden_related'] = AldenComponentHelper::relatedForExhibition($node);
  }

  /**
   * Implements hook_preprocess_views_view_unformatted().
   *
   * exhibitions_page and whats_on_page both bypass Views' normal
   * field/row rendering entirely -- their View only exists to build the
   * filtered/sorted node ID list (exposed filters, sort); the actual
   * markup comes from the alden:xcard/alden:row-list SDCs, fed from the
   * same AldenComponentHelper card-building logic used everywhere else
   * in the theme. See views-view-unformatted--exhibitions-page.html.twig
   * and views-view-unformatted--whats-on-page.html.twig.
   */
  #[Hook('preprocess_views_view_unformatted')]
  public function preprocessViewsViewUnformatted(array &$variables): void {
    $view = $variables['view'];
    $view_id = $view->id();
    if (!in_array($view_id, ['exhibitions_page', 'whats_on_page'], TRUE)) {
      return;
    }

    $query = \Drupal::request()->query;

    if ($view_id === 'exhibitions_page') {
      $current = $query->get('state', 'all');
      $variables['alden_tabs'] = [
        ['label' => 'All', 'url' => '/exhibitions', 'filter' => 'all', 'active' => $current === 'all'],
        ['label' => 'On view', 'url' => '/exhibitions?state=1', 'filter' => 'current', 'active' => $current === '1'],
        ['label' => 'Upcoming', 'url' => '/exhibitions?state=2', 'filter' => 'upcoming', 'active' => $current === '2'],
        ['label' => 'Past', 'url' => '/exhibitions?state=3', 'filter' => 'past', 'active' => $current === '3'],
      ];
      $items = [];
      foreach ($view->result as $row) {
        $node = $row->_entity;
        $badge = AldenComponentHelper::exhibitionBadge($node);
        $items[] = [
          'ph_class' => AldenComponentHelper::phClass($node, 'card'),
          'image' => AldenComponentHelper::nodeImage($node, 'alden_card'),
          'badge_text' => $badge['text'],
          'badge_class' => $badge['class'],
          'is_past' => $badge['is_past'],
          'state' => AldenComponentHelper::exhibitionStatus($node),
          'title' => $node->label(),
          'url' => $node->toUrl()->toString(),
          'meta' => AldenComponentHelper::factsDateRange($node),
          'summary' => $node->get('field_summary')->value ?: '',
        ];
      }
      $variables['alden_items'] = $items;
    }
    else {
      $current = $query->get('kind', 'all');
      $variables['alden_tabs'] = [
        ['label' => 'Everything', 'url' => '/whats-on', 'filter' => 'all', 'active' => $current === 'all'],
        ['label' => 'Talks', 'url' => '/whats-on?kind=1', 'filter' => 'talk', 'active' => $current === '1'],
        ['label' => 'Workshops', 'url' => '/whats-on?kind=2', 'filter' => 'workshop', 'active' => $current === '2'],
        ['label' => 'Journal', 'url' => '/whats-on?kind=3', 'filter' => 'journal', 'active' => $current === '3'],
      ];
      $tag_labels = ['talk' => 'Talk', 'workshop' => 'Workshop', 'journal' => 'Journal'];
      $now = \Drupal::time()->getRequestTime();
      $items = [];
      foreach ($view->result as $row) {
        $node = $row->_entity;
        $is_event = $node->bundle() === 'event';
        // A "what's on" page is read as forward-looking programming --
        // past events are trimmed here rather than via a Views filter
        // (Views' exposed-filter groups can't express "(article) OR
        // (event AND future)" combined with a separately-ANDed exposed
        // kind filter without contrib; the pager is off, so there's no
        // pager/count mismatch from trimming post-query). Articles have
        // no such concept and always show.
        if ($is_event && !$node->get('field_datetime')->isEmpty()) {
          $event_time = (new \DateTime($node->get('field_datetime')->value, new \DateTimeZone('UTC')))->getTimestamp();
          if ($event_time < $now) {
            continue;
          }
        }
        $kind = $node->get('field_kind')->value;
        $items[] = [
          'ph_class' => AldenComponentHelper::phClass($node, 'default'),
          'image' => AldenComponentHelper::nodeImage($node, 'alden_thumb'),
          'when' => AldenComponentHelper::rowWhen($node),
          'tag' => $is_event ? AldenComponentHelper::eventTag($node) : $tag_labels[$kind],
          'tag_class' => $is_event ? 'ev' : 'jr',
          'kind' => $kind,
          'title' => $node->label(),
          'url' => $node->toUrl()->toString(),
          'summary' => $is_event ? ($node->get('field_summary')->value ?: '') : ($node->get('field_summary')->value ?: ''),
          'meta' => $is_event ? AldenComponentHelper::eventMetaLine($node) : AldenComponentHelper::readTime($node),
        ];
      }
      $variables['alden_items'] = $items;
    }
  }

  /**
   * Implements hook_preprocess_paragraph().
   *
   * Builds the `alden` variable each paragraph--<bundle>.html.twig passes
   * straight into its matching SDC via Twig's `include('alden:<name>', …)`.
   * This is the glue between paragraph field values and component props,
   * rebuilt to match mockups/alden-homepage-mockup.html exactly -- see
   * each SDC's own component.yml for the prop shape it expects.
   */
  #[Hook('preprocess_paragraph')]
  public function preprocessParagraph(array &$variables): void {
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $variables['paragraph'];

    switch ($paragraph->bundle()) {
      case 'hero':
        // The front page is the only page using this right now, so h1 is
        // always correct here (see hero.component.yml for why the level
        // is still a prop). The eyebrow and two CTAs are fixed homepage
        // chrome, not stored on the paragraph (there is nowhere on the
        // existing hero fields to put them) -- matching the mockup's own
        // hardcoded hero copy exactly.
        $hero_media = AldenComponentHelper::mediaFromField($paragraph, 'field_hero_media');
        $variables['alden'] = [
          'eyebrow' => 'Now on view · Until 10 January',
          'heading_level' => 1,
          'title' => $paragraph->get('field_title')->value ?: '',
          'lede' => $paragraph->get('field_standfirst')->value ?: '',
          'primary_url' => '/exhibitions',
          'primary_text' => 'See the exhibition',
          'secondary_url' => Url::fromRoute('entity.node.canonical', ['node' => 43])->toString(),
          'secondary_text' => 'Plan your visit',
          'ph_class' => 'ph-sculpt',
          'image' => $hero_media ? AldenComponentHelper::responsiveImage($hero_media, 'alden_hero') : [],
        ];
        break;

      case 'split':
        $parent = $paragraph->getParentEntity();
        $ph_class = $parent instanceof NodeInterface && $parent->bundle() === 'exhibition'
          ? AldenComponentHelper::phClass($parent, 'exhibition-split')
          : ($paragraph->get('field_reversed')->value ? 'ph-paint' : 'ph-sculpt');
        $split_media = AldenComponentHelper::mediaFromField($paragraph, 'field_split_media');
        $variables['alden'] = [
          'eyebrow' => $paragraph->get('field_eyebrow')->value ?: 'Exhibition',
          'heading' => $paragraph->get('field_title')->value ?: '',
          'heading_level' => 2,
          'body' => $paragraph->get('field_body')->isEmpty() ? '' : $paragraph->get('field_body')->first()->processed,
          'link_url' => '',
          'link_text' => '',
          'reversed' => (bool) $paragraph->get('field_reversed')->value,
          'ph_class' => $ph_class,
          'image' => $split_media ? AldenComponentHelper::responsiveImage($split_media, 'alden_split') : [],
        ];
        break;

      case 'card_grid':
        $cards = [];
        foreach ($paragraph->get('field_grid_items')->referencedEntities() as $node) {
          if ($node instanceof \Drupal\node\NodeInterface) {
            $card = AldenComponentHelper::cardFromNode($node, 'card');
            $cards[] = [
              'ph_class' => $card['ph_class'],
              'image' => $card['image'],
              'meta' => $card['meta'],
              'title' => $card['title'],
              'url' => $card['url'],
              'summary' => $card['summary'],
            ];
          }
        }
        $variables['alden'] = [
          'eyebrow' => 'On view now',
          'heading' => $paragraph->get('field_title')->value ?: '',
          'heading_level' => 2,
          'link_url' => '/exhibitions',
          'link_text' => 'All exhibitions',
          'cards' => $cards,
        ];
        break;

      case 'featured_grid':
        $cards = [];
        foreach ($paragraph->get('field_grid_items')->referencedEntities() as $node) {
          if ($node instanceof \Drupal\node\NodeInterface) {
            $card = AldenComponentHelper::cardFromNode($node);
            $is_event = $node->bundle() === 'event';
            $cards[] = [
              'ph_class' => $card['ph_class'],
              'image' => $card['image'],
              'tag' => $is_event ? AldenComponentHelper::eventTag($node) : 'Article',
              'tag_class' => $is_event ? 'ev' : '',
              'title' => $card['title'],
              'url' => $card['url'],
              'meta' => $card['meta'],
              'summary' => $card['summary'],
            ];
          }
        }
        $variables['alden'] = [
          'on_mist' => TRUE,
          'eyebrow' => 'This season',
          'heading' => $paragraph->get('field_title')->value ?: '',
          'heading_level' => 2,
          'link_url' => '/whats-on',
          'link_text' => 'Full programme',
          'cards' => $cards,
        ];
        break;

      case 'exhibition_grid':
        $cards = [];
        foreach ($paragraph->get('field_grid_items')->referencedEntities() as $node) {
          if ($node instanceof \Drupal\node\NodeInterface) {
            $card = AldenComponentHelper::cardFromNode($node);
            $cards[] = [
              'ph_class' => $card['ph_class'],
              'image' => $card['image'],
              'title' => $card['title'],
              'url' => $card['url'],
              'meta' => $card['meta'],
              'summary' => $card['summary'],
            ];
          }
        }
        $variables['alden'] = [
          'eyebrow' => 'Opening soon',
          'heading' => $paragraph->get('field_title')->value ?: '',
          'heading_level' => 2,
          'cards' => $cards,
        ];
        break;

      case 'stat_row':
        $values = array_column($paragraph->get('field_stat_value')->getValue(), 'value');
        $labels = array_column($paragraph->get('field_stat_label')->getValue(), 'value');
        $stats = [];
        foreach ($values as $delta => $value) {
          if (isset($labels[$delta])) {
            $stats[] = [
              'value' => $value,
              'plain' => $delta === 1,
              'label' => $labels[$delta],
            ];
          }
        }
        $variables['alden'] = [
          'eyebrow' => 'The collection',
          'heading' => $paragraph->get('field_title')->value ?: '',
          'heading_level' => 2,
          'stats' => $stats,
        ];
        break;

      case 'announcement_band':
        $link_url = '';
        $link_text = '';
        if (!$paragraph->get('field_link')->isEmpty()) {
          $link_item = $paragraph->get('field_link')->first();
          $link_url = $link_item->getUrl()->toString();
          $link_text = $link_item->title;
        }
        $variables['alden'] = [
          'label' => "Members' preview",
          'text' => $paragraph->get('field_title')->value ?: '',
          'url' => $link_url ?: '#',
        ];
        break;

      case 'testimonial_row':
        $quotes = array_column($paragraph->get('field_testimonial_quote')->getValue(), 'value');
        $names = array_column($paragraph->get('field_testimonial_name')->getValue(), 'value');
        $items = [];
        foreach ($quotes as $delta => $quote) {
          $items[] = [
            'quote' => $quote,
            'attribution' => $names[$delta] ?? '',
          ];
        }
        $variables['alden'] = ['quotes' => $items];
        break;

      case 'visit_strip':
        $items = [
          ['label' => 'Opening hours', 'value' => $paragraph->get('field_hours')->value ?: ''],
          ['label' => 'Admission', 'value' => $paragraph->get('field_admission')->value ?: ''],
          ['label' => 'Getting here', 'value' => $paragraph->get('field_address')->value ?: ''],
          ['label' => 'Access', 'value' => 'Step-free throughout<br>Hearing loop in all galleries'],
        ];
        $variables['alden'] = [
          'eyebrow' => 'Plan your visit',
          'heading' => $paragraph->get('field_title')->value ?: '',
          'heading_level' => 2,
          'items' => $items,
        ];
        break;

      case 'quote':
        $variables['alden'] = [
          'quote' => $paragraph->get('field_quote')->value ?: '',
          'attribution' => $paragraph->get('field_attribution')->value ?: '',
        ];
        break;

      case 'cta_band':
        $link_url = '';
        $link_text = '';
        if (!$paragraph->get('field_link')->isEmpty()) {
          $link_item = $paragraph->get('field_link')->first();
          $link_url = $link_item->getUrl()->toString();
          $link_text = $link_item->title;
        }
        $variables['alden'] = [
          'heading' => $paragraph->get('field_title')->value ?: '',
          'heading_level' => 2,
          'body' => $paragraph->get('field_body')->isEmpty() ? '' : $paragraph->get('field_body')->first()->value,
          'link_url' => $link_url,
          'link_text' => $link_text,
        ];
        break;
    }
  }

}
