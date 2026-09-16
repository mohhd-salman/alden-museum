<?php

namespace Drupal\alden;

use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;

/**
 * Builds render-array props for the alden SDCs from entity field data.
 */
class AldenComponentHelper {

  /**
   * Maps a card-grid column count to its matching responsive image style.
   *
   * Not currently used by any component -- real photography is switched
   * off site-wide in favour of the mockups' CSS gradient stand-ins (see
   * phClass() below) -- but left in place, along with responsiveImage()
   * and mediaFromField(), so the pipeline is ready the moment real photos
   * come back. Nothing here needs to change to re-enable it; only the
   * components' twig templates (currently rendering a .ph div) would need
   * to render {{ card.image }} again instead.
   */
  const CARD_STYLE_BY_COLUMNS = [
    2 => 'alden_card_2col',
    3 => 'alden_card',
    4 => 'alden_card_4col',
  ];

  /**
   * Builds a responsive_image render array from a media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   An image-bundle media entity.
   * @param string $style
   *   A responsive image style ID.
   * @param array $attributes
   *   Extra attributes, e.g. ['loading' => 'eager'].
   *
   * @return array
   *   A #type => 'responsive_image' render array, or an empty array if the
   *   media entity has no image.
   */
  public static function responsiveImage(MediaInterface $media, string $style, array $attributes = []): array {
    if (!$media->hasField('field_media_image') || $media->get('field_media_image')->isEmpty()) {
      return [];
    }
    /** @var \Drupal\file\FileInterface|null $file */
    $item = $media->get('field_media_image')->first();
    $file = $item->entity;
    if (!$file) {
      return [];
    }
    $attributes += ['alt' => $item->alt ?: ''];
    return [
      '#type' => 'responsive_image',
      '#uri' => $file->getFileUri(),
      '#responsive_image_style_id' => $style,
      '#attributes' => $attributes,
    ];
  }

  /**
   * Loads the media entity referenced by a single-value media field.
   */
  public static function mediaFromField($entity, string $field_name): ?MediaInterface {
    if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
      return NULL;
    }
    $media = $entity->get($field_name)->entity;
    return $media instanceof MediaInterface ? $media : NULL;
  }

  /**
   * The mockups' subject-matter -> gradient-placeholder-class mapping,
   * hardcoded by node ID since it's an editorial (not structural) choice
   * matching the approved mockups exactly -- see
   * mockups/alden-homepage-mockup.html and mockups/alden-page-templates.html
   * for the specific class each piece of content is shown with.
   *
   * Node IDs: 25 Kaspar Lindqvist, 26 The Last Shift at Cutter's Point,
   * 27 The Solberg Bequest, 28 Ilse Marchetti, 29 Halvard & Rein,
   * 30 The Ledger of Aurelio Faust (exhibitions, nid 25-30); 31-36 events;
   * 37-42 articles.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Any node that appears as a card/hero/split image in the design.
   * @param string $context
   *   'default', or 'hero'/'split' for Lindqvist specifically, who the
   *   mockups show with a different placeholder in different contexts
   *   (ph-sculpt for hero/split, ph-sculpt2 for cards/listings).
   *   'exhibition-split' is the exhibition detail page's own split
   *   section, which sits directly under that page's ehero -- the mockup
   *   deliberately gives it the *other* sculpt variant so the two
   *   sections don't show an identical gradient back to back.
   *
   * @return string
   *   A ph-* class name, always including the leading "ph-".
   */
  public static function phClass(NodeInterface $node, string $context = 'default'): string {
    if ($node->id() == 25) {
      if ($context === 'exhibition-split') {
        return 'ph-sculpt2';
      }
      return in_array($context, ['hero', 'split'], TRUE) ? 'ph-sculpt' : 'ph-sculpt2';
    }
    $map = [
      26 => 'ph-doc',
      27 => 'ph-textile',
      28 => 'ph-paint',
      29 => 'ph-gallery',
      30 => 'ph-archive',
      // Events and articles featured on the homepage's "What's On"
      // preview and the exhibition detail page's "Related events" --
      // matched to the mockup's own hand-picked classes for its
      // equivalent example cards where one exists, otherwise picked for
      // visual variety against whatever else is in the same grid.
      31 => 'ph-gallery',
      32 => 'ph-archive',
      33 => 'ph-paint',
      34 => 'ph-archive',
      35 => 'ph-textile',
      36 => 'ph-doc',
      37 => 'ph-doc',
    ];
    return $map[$node->id()] ?? 'ph-gallery';
  }

  /**
   * Normalizes a node (exhibition/event/article) into card data.
   *
   * Each bundle keeps its own date field; this derives a common
   * { ph_class, title, url, meta, summary } shape so the grid components
   * don't need to know about per-bundle field differences. `image` is
   * deliberately not part of this shape right now -- see phClass().
   *
   * @param \Drupal\node\NodeInterface $node
   *   The referenced node.
   * @param string $context
   *   Passed through to phClass().
   *
   * @return array
   *   Card data for the cards/news-grid/exhibition-grid components.
   */
  public static function cardFromNode(NodeInterface $node, string $context = 'default'): array {
    $date_formatter = \Drupal::service('date.formatter');
    $meta = '';

    switch ($node->bundle()) {
      case 'exhibition':
        if (!$node->get('field_start_date')->isEmpty()) {
          $start = new \DateTime($node->get('field_start_date')->value);
          $meta = $date_formatter->format($start->getTimestamp(), 'custom', 'j M Y');
          if (!$node->get('field_end_date')->isEmpty()) {
            $end = new \DateTime($node->get('field_end_date')->value);
            $meta .= ' – ' . $date_formatter->format($end->getTimestamp(), 'custom', 'j M Y');
          }
        }
        break;

      case 'event':
        if (!$node->get('field_datetime')->isEmpty()) {
          $date = new \DateTime($node->get('field_datetime')->value, new \DateTimeZone('UTC'));
          $meta = $date_formatter->format($date->getTimestamp(), 'custom', 'j M Y, g:ia');
        }
        break;

      case 'article':
        $meta = $date_formatter->format($node->getCreatedTime(), 'custom', 'j M Y');
        break;
    }

    return [
      'ph_class' => self::phClass($node, $context),
      'title' => $node->label(),
      'url' => $node->toUrl()->toString(),
      'meta' => $meta,
      'summary' => $node->hasField('field_summary') ? $node->get('field_summary')->value : '',
    ];
  }

  /**
   * Classifies an exhibition node as current, upcoming or past.
   *
   * Compares field_start_date/field_end_date against the request time, so
   * it always reflects "now" rather than needing to be curated by hand.
   *
   * @param \Drupal\node\NodeInterface $node
   *   An exhibition node.
   *
   * @return string
   *   One of 'current', 'upcoming', 'past'. Exhibitions missing a start
   *   date are treated as 'upcoming' (nothing to compare against yet).
   */
  public static function exhibitionStatus(NodeInterface $node): string {
    $now = \Drupal::time()->getRequestTime();
    if ($node->get('field_start_date')->isEmpty()) {
      return 'upcoming';
    }
    $start = (new \DateTime($node->get('field_start_date')->value))->getTimestamp();
    if ($now < $start) {
      return 'upcoming';
    }
    if (!$node->get('field_end_date')->isEmpty()) {
      $end = (new \DateTime($node->get('field_end_date')->value))->getTimestamp();
      // End dates are stored as a bare date (midnight); treat the
      // exhibition as open through the whole of that day.
      $end += 86400;
      if ($now >= $end) {
        return 'past';
      }
    }
    return 'current';
  }

  /**
   * The exhibition hero's status-pill text.
   */
  public static function exhibitionStatusLabel(NodeInterface $node): string {
    switch (self::exhibitionStatus($node)) {
      case 'upcoming':
        if (!$node->get('field_start_date')->isEmpty()) {
          $start = new \DateTime($node->get('field_start_date')->value);
          return 'Opens ' . \Drupal::service('date.formatter')->format($start->getTimestamp(), 'custom', 'j F Y');
        }
        return 'Opening soon';

      case 'past':
        return 'Past exhibition';

      default:
        return 'On view now';
    }
  }

  /**
   * The facts bar's "Dates" value, spelled out in full (not abbreviated
   * the way card meta lines are) -- "6 June 2026 – 10 January 2027".
   */
  public static function factsDateRange(NodeInterface $node): string {
    if ($node->get('field_start_date')->isEmpty()) {
      return '';
    }
    $date_formatter = \Drupal::service('date.formatter');
    $start = new \DateTime($node->get('field_start_date')->value);
    $range = $date_formatter->format($start->getTimestamp(), 'custom', 'j F Y');
    if (!$node->get('field_end_date')->isEmpty()) {
      $end = new \DateTime($node->get('field_end_date')->value);
      $range .= ' – ' . $date_formatter->format($end->getTimestamp(), 'custom', 'j F Y');
    }
    return $range;
  }

  /**
   * Placeholder gradient classes for an exhibition's gallery section, one
   * per real field_gallery item -- there is no media entity left to key
   * off (site-wide photography was removed), so this is an editorial
   * per-exhibition sequence, same approach as phClass(). Only sized to
   * however many gallery items the exhibition actually has; the mockup's
   * 5-item gallery is trimmed to real content where an exhibition has
   * fewer.
   */
  public static function galleryPhClasses(NodeInterface $node): array {
    $count = $node->hasField('field_gallery') ? $node->get('field_gallery')->count() : 0;
    $sequences = [
      // Kaspar Lindqvist -- matches the mockup's own 5-item sequence for
      // this exact exhibition.
      25 => ['ph-sculpt', 'ph-sculpt2', 'ph-sculpt3', 'ph-archive', 'ph-doc'],
    ];
    $palette = $sequences[$node->id()] ?? ['ph-gallery', 'ph-archive', 'ph-doc', 'ph-textile', 'ph-sculpt3'];
    $classes = [];
    for ($i = 0; $i < $count; $i++) {
      $classes[] = $palette[$i % count($palette)];
    }
    return $classes;
  }

  /**
   * The event "kind" badge text -- not a structured field on the event
   * content type, so this is a small hardcoded lookup by node ID,
   * matching the mockup's own hand-picked badge per event. Shared by the
   * homepage's "What's On" preview and the exhibition detail page's
   * "related events".
   */
  public static function eventTag(NodeInterface $node): string {
    $tags = [
      31 => "Curator's talk",
      32 => 'Workshop',
      33 => "Curator's talk",
      34 => 'Workshop',
      35 => "Curator's talk",
      36 => "Curator's talk",
    ];
    return $tags[$node->id()] ?? "Curator's talk";
  }

  /**
   * Related events/articles for an exhibition detail page's "Related
   * events" section (mockup: 2 events + 1 journal piece). There is no
   * structural relation between events/articles and exhibitions in the
   * data model, so this is computed, not hand-curated: soonest upcoming
   * events first, topped up with the most recent article that shares a
   * topic term with the exhibition.
   *
   * @return array
   *   Up to $limit { ph_class, tag, tag_class, title, url, meta } arrays,
   *   shaped for the `related` component.
   */
  public static function relatedForExhibition(NodeInterface $node, int $limit = 3): array {
    $storage = \Drupal::entityTypeManager()->getStorage('node');

    // Reserve one slot for a topic-matched article (mirroring the
    // mockup's own 2-events-plus-1-journal-piece rhythm) when one
    // exists; otherwise every slot goes to the soonest upcoming events.
    $articles = [];
    $topic_ids = array_column($node->get('field_topics')->getValue(), 'target_id');
    if ($topic_ids) {
      $article_ids = $storage->getQuery()
        ->condition('type', 'article')
        ->condition('status', 1)
        ->condition('field_topics', $topic_ids, 'IN')
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->accessCheck(TRUE)
        ->execute();
      foreach ($storage->loadMultiple($article_ids) as $article) {
        $articles[] = $article;
      }
    }

    $event_limit = $limit - count($articles);
    $now = \Drupal::time()->getRequestTime();
    $event_ids = $storage->getQuery()
      ->condition('type', 'event')
      ->condition('status', 1)
      ->condition('field_datetime', date('Y-m-d\TH:i:s', $now), '>=')
      ->sort('field_datetime', 'ASC')
      ->range(0, $event_limit)
      ->accessCheck(TRUE)
      ->execute();
    $items = $storage->loadMultiple($event_ids);
    $items = array_merge($items, $articles);

    $related = [];
    foreach (array_slice($items, 0, $limit) as $related_node) {
      $is_event = $related_node->bundle() === 'event';
      $card = self::cardFromNode($related_node);
      $related[] = [
        'ph_class' => $card['ph_class'],
        'tag' => $is_event ? self::eventTag($related_node) : 'Journal',
        'tag_class' => $is_event ? 'ev' : 'jr',
        'title' => $card['title'],
        'url' => $card['url'],
        'meta' => $card['meta'],
      ];
    }
    return $related;
  }

  /**
   * The exhibition listing's status badge: text + modifier class, matching
   * the mockup's .badge/.badge.up/.badge.past exactly (unlike the detail
   * page's status pill, "upcoming" here shows a specific opening month,
   * e.g. "Opening Feb 2027", not just "Upcoming").
   *
   * @return array
   *   { text: string, class: string, is_past: bool }.
   */
  public static function exhibitionBadge(NodeInterface $node): array {
    $status = self::exhibitionStatus($node);
    if ($status === 'past') {
      return ['text' => 'Past', 'class' => 'past', 'is_past' => TRUE];
    }
    if ($status === 'upcoming') {
      $text = 'Opening soon';
      if (!$node->get('field_start_date')->isEmpty()) {
        $start = new \DateTime($node->get('field_start_date')->value);
        $text = 'Opening ' . \Drupal::service('date.formatter')->format($start->getTimestamp(), 'custom', 'M Y');
      }
      return ['text' => $text, 'class' => 'up', 'is_past' => FALSE];
    }
    return ['text' => 'On view', 'class' => '', 'is_past' => FALSE];
  }

  /**
   * An event's meta line for the What's On row list -- "6:00pm · Lecture
   * Room · £8" -- built entirely from real fields (time portion of
   * field_datetime, field_location, field_price).
   */
  public static function eventMetaLine(NodeInterface $node): string {
    $parts = [];
    if (!$node->get('field_datetime')->isEmpty()) {
      $date = new \DateTime($node->get('field_datetime')->value, new \DateTimeZone('UTC'));
      $parts[] = \Drupal::service('date.formatter')->format($date->getTimestamp(), 'custom', 'g:ia');
    }
    if (!$node->get('field_location')->isEmpty()) {
      $parts[] = $node->get('field_location')->value;
    }
    if (!$node->get('field_price')->isEmpty()) {
      $parts[] = $node->get('field_price')->value;
    }
    return implode(' · ', $parts);
  }

  /**
   * An article's estimated read time -- "Five-minute read" -- computed
   * from the real body word count at 200 words/minute, not invented.
   * Rounded up to the nearest minute, minimum one.
   */
  public static function readTime(NodeInterface $node): string {
    if (!$node->hasField('body') || $node->get('body')->isEmpty()) {
      return '';
    }
    $words = str_word_count(strip_tags($node->get('body')->value));
    $minutes = max(1, (int) ceil($words / 200));
    $numbers = [1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten'];
    $word = $numbers[$minutes] ?? (string) $minutes;
    return "$word-minute read";
  }

  /**
   * Splits a datetime value into the row list's "day" + "month year" --
   * a large serif day number with a small month/year underneath.
   *
   * @return array
   *   { day: string, month_year: string }.
   */
  public static function rowWhen(NodeInterface $node): array {
    $value = $node->bundle() === 'event' ? $node->get('field_datetime')->value : $node->get('field_display_date')->value;
    if (!$value) {
      return ['day' => '', 'month_year' => ''];
    }
    $date = new \DateTime($value, new \DateTimeZone('UTC'));
    return [
      'day' => \Drupal::service('date.formatter')->format($date->getTimestamp(), 'custom', 'd'),
      'month_year' => \Drupal::service('date.formatter')->format($date->getTimestamp(), 'custom', 'M Y'),
    ];
  }

}
