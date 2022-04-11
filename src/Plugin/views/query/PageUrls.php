<?php

namespace Drupal\matomo_views_integrations\Plugin\views\query;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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

  /**
   * Attach entity to the matomo result
   *
   * @param $matomoAnswer
   */
  public function loadEntity($matomoAnswer) {
    $langcode = NULL;
    $node = NULL;
    $matomoAnswer->hasEntity = FALSE;

    $matomoPageUrl = $matomoAnswer->Actions_PageUrl;

    /** @var Drupal\Core\Path\AliasManager $alias_manager */
    $alias_manager = Drupal::service('path.alias_manager');
    $path = $alias_manager->getPathByAlias($matomoPageUrl);


    $uri = explode('/', $matomoPageUrl);

    if (isset($uri[1])) {
      $langcode = $uri[1];
    }

    $matomoAnswer->langcode = $langcode;

    if ($path == $matomoPageUrl && NULL !== $langcode) {
      $uri_no_langcode = array_splice($uri, 2);
      $url_no_langcode = '/' . implode('/', $uri_no_langcode);

      $path = $alias_manager->getPathByAlias($url_no_langcode);
    }

    $url_fromUserInput = Url::fromUserInput($path);
    $params = [];
    if($url_fromUserInput->isRouted() ) {
      $params = $url_fromUserInput->getRouteParameters();
    }

    if (!empty($params['node'])) {
      /** @var \Drupal\node\Entity\Node $node */
      $node = Drupal\node\Entity\Node::load($params["node"]);

      if (!empty($langcode) && $node->hasTranslation($langcode)) {
        $node = $node->getTranslation($langcode);
      }
    }

    if( null !== $node){
      $matomoAnswer->entity = $node;
      $matomoAnswer->entity_type = 'node';
      $matomoAnswer->hasEntity = TRUE;
    }

    xdebug_break();
  }


}
