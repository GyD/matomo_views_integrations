<?php

/**
 * @file
 * Contains \Drupal\matomo_views_integrations\Plugin\views\field\Standard.
 */

namespace Drupal\matomo_views_integrations\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * @ingroup views_field_handlers
 *
 * @ViewsField("matomo_views_dimension_node_target_id")
 */
class NodeTargetId extends Standard {

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
          'value' => 17,
        ];
      }
    }

    return $return;
  }

}
