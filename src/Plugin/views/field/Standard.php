<?php

/**
 * @file
 * Contains \Drupal\matomo_views_integrations\Plugin\views\field\Standard.
 */

namespace Drupal\matomo_views_integrations\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;
use Drupal\views\ResultRow;

/**
 * @ingroup views_field_handlers
 *
 * @ViewsField("matomo_views_dimension_standard")
 */
class Standard extends FieldPluginBase implements MultiItemsFieldHandlerInterface {

  /**
   * Called to add the field to a query.
   */
  public function query() {
    $this->field_alias = $this->options['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * Renders a single item of a row.
   *
   * @param int $count
   *   The index of the item inside the row.
   * @param mixed $item
   *   The item for the field to render.
   *
   * @return string
   *   The rendered output.
   *
   * @see \Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface
   */
  public function render_item($count, $item) {
    return $item['value'];
  }

  /**
   * Gets an array of items for the field.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row object containing the values.
   *
   * @return array
   *   An array of items for the field.
   *
   * @see \Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface
   */
  public function getItems(ResultRow $row) {
    $return = [];
    if ($values = $this->getValue($row)) {
      foreach ($values as $value) {
        $return[] = [
          'value' => $value,
        ];
      }
    }

    return $return;
  }

  /**
   * Render all items in this field together.
   *
   * @param array $items
   *   The items provided by getItems for a single row.
   *
   * @return string
   *   The rendered items.
   *
   * @see \Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface
   */
  public function renderItems($items) {
    if (!empty($items)) {
      if ($this->options['type'] == 'separator') {
        $render = [
          '#type' => 'inline_template',
          '#template' => '{{ items|safe_join(separator) }}',
          '#context' => [
            'items' => $items,
            'separator' => $this->sanitizeValue($this->options['separator'], 'xss_admin'),
          ],
        ];
      }
      else {
        $render = [
          '#theme' => 'item_list',
          '#items' => $items,
          '#title' => NULL,
          '#list_type' => 'ul',
        ];
      }
      return drupal_render($render);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    return parent::defineOptions();
  }

}
