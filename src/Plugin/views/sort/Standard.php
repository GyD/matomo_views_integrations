<?php

namespace Drupal\matomo_views_integrations\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\matomo_views_integrations\Sorter\Sorter;
use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Default implementation of the base sort plugin.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("matomo_views_dimension_standard")
 */
class Standard extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->query->addSort(new Sorter($this->options['id'], $this->options['order']));
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

}
