<?php

namespace Drupal\alden_core\Hook;

use Drupal\alden\AldenComponentHelper;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;

/**
 * Hook implementations that must live in a module, not the theme.
 *
 * Several of these (hook_entity_bundle_field_info_alter(),
 * hook_entity_base_field_info_alter(), hook_page_attachments()) are only
 * invoked via \Drupal::moduleHandler()->alter()/invokeAllWith(), never the
 * theme manager -- themes cannot implement them.
 */
class AldenCoreHooks {

  /**
   * Implements hook_field_widget_single_element_form_alter().
   *
   * The per-node field_metatag override is scoped to Title and Description
   * only (see metatag.settings:entity_type_groups, which restricts the
   * widget to the "basic" group) -- but that group also includes Keywords
   * and Abstract, two tags nobody asked for here. Strip them so the
   * override field shows exactly the two fields it's meant to.
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function fieldWidgetSingleElementFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    if ($context['widget']->getPluginId() !== 'metatag_firehose') {
      return;
    }
    if (isset($element['basic'])) {
      unset($element['basic']['keywords'], $element['basic']['abstract']);
    }
  }

  /**
   * Implements hook_node_presave().
   *
   * Warns (does not block) when the *effective* <title> tag -- the
   * per-node field_metatag override if set, otherwise the resolved
   * default pattern -- exceeds 60 characters. Resolved via metatag.manager
   * itself (tagsFromEntityWithDefaults() + generateTokenValues()) rather
   * than re-implementing the "[node:title] | Alden Museum" pattern here,
   * so this can never drift out of sync with the actual Metatag defaults
   * configuration.
   */
  #[Hook('node_presave')]
  public function nodePresave(ContentEntityInterface $node): void {
    if (!$node->hasField('field_metatag')) {
      return;
    }
    /** @var \Drupal\metatag\MetatagManagerInterface $manager */
    $manager = \Drupal::service('metatag.manager');
    $raw_tags = $manager->tagsFromEntityWithDefaults($node);
    if (empty($raw_tags['title'])) {
      return;
    }
    // Resolve only the title tag: other default tags (schema_event_url,
    // etc.) use [node:url]/[node:nid], which fail on a new node that
    // doesn't have an ID yet at presave time.
    $resolved = $manager->generateTokenValues(['title' => $raw_tags['title']], $node);
    $title = (string) ($resolved['title'] ?? '');
    $length = mb_strlen($title);
    if ($length > 60) {
      \Drupal::messenger()->addWarning(t('The generated page title is @length characters, over the 60-character search-result budget: "@title". Consider a shorter title, or set a custom one in "Meta tags" below.', [
        '@length' => $length,
        '@title' => $title,
      ]));
    }
  }

  /**
   * Implements hook_node_presave().
   *
   * A second, independent node_presave listener (the attribute-based hook
   * system allows more than one per hook, unlike classic procedural
   * hook_node_presave()) -- materializes two computed fields that the
   * exhibition-listing and what's-on Views need as real, filterable/
   * sortable field data rather than PHP-computed values:
   * - exhibition.field_status: current/upcoming/past, from
   *   AldenComponentHelper::exhibitionStatus() -- also refreshed daily by
   *   hook_cron() below, so it doesn't go stale between edits.
   * - event/article.field_display_date: a single shared datetime field
   *   so the What's On View can sort both bundles by one field (events
   *   sort by when they happen, articles by when they were published).
   */
  #[Hook('node_presave')]
  public function alignComputedFields(NodeInterface $node): void {
    if ($node->bundle() === 'exhibition') {
      $node->set('field_status', AldenComponentHelper::exhibitionStatus($node));
    }
    elseif ($node->bundle() === 'event' && $node->hasField('field_datetime') && !$node->get('field_datetime')->isEmpty()) {
      $node->set('field_display_date', $node->get('field_datetime')->value);
    }
    elseif ($node->bundle() === 'article') {
      $created = $node->getCreatedTime() ?: \Drupal::time()->getRequestTime();
      $node->set('field_display_date', date('Y-m-d\TH:i:s', $created));
    }
  }

  /**
   * Implements hook_cron().
   *
   * Keeps exhibition.field_status correct as time passes without anyone
   * re-saving the node -- an exhibition due to open today should show as
   * "On view" without an editor having to touch it.
   */
  #[Hook('cron')]
  public function refreshExhibitionStatus(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $ids = $storage->getQuery()
      ->condition('type', 'exhibition')
      ->accessCheck(FALSE)
      ->execute();
    foreach ($storage->loadMultiple($ids) as $node) {
      $status = AldenComponentHelper::exhibitionStatus($node);
      if ($node->get('field_status')->value !== $status) {
        $node->set('field_status', $status);
        $node->save();
      }
    }
  }

}
