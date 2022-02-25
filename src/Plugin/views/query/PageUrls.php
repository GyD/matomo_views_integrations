<?php

namespace Drupal\matomo_views_integrations\Plugin\views\query;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ingroup views_query_plugins
 *
 * @ViewsQuery(
 *   id = "matomo_views_integrations_pageurls",
 *   title = @Translation("Matomo Views Dimenson"),
 *   help = @Translation("Query will be generated and run using the Matomo API
 *   using Dimension Entry point")
 * )
 */
class PageUrls extends QueryPluginBase {

  use QueryHelperTrait;

  private $MatomoEntryPoint = 'Actions.getPageUrls';
  private $MatomoExtraParameters = [];

  /** {@inheritDoc`} */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $this->getDefaultOptionsForm($form);

  }


}
