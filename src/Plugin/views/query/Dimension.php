<?php

namespace Drupal\matomo_views_integrations\Plugin\views\query;

use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ingroup views_query_plugins
 *
 * @ViewsQuery(
 *   id = "matomo_views_integrations_dimension",
 *   title = @Translation("Matomo Views Dimenson"),
 *   help = @Translation("Query will be generated and run using the Matomo API
 *   using Dimension Entry point")
 * )
 */
class Dimension extends QueryPluginBase {

  use QueryHelperTrait;

  private $MatomoEntryPoint = 'CustomDimensions.getCustomDimension';
  private $MatomoExtraParameters = [
    'idDimension' => 'idDimension'
  ];

  /** {@inheritDoc`} */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $this->getDefaultOptionsForm($form);

    $form['idDimension'] = [
      '#title' => t('Matomo Dimension ID'),
      '#description' => t('Date to (you can use today, yesterday, -X days)'),
      '#type' => 'textfield',
      '#attributes' => [
        'type' => 'number',
      ],
      '#required' => TRUE,
      '#default_value' => $this->options['idDimension'],
    ];
  }

  protected function defineExtraOptions(&$options){
    $options['idDimension'] = ['default' => NULL];
  }

  /**
   * Ensures a table exists in the query.
   *
   * This replicates the interface of Views' default SQL backend to simplify
   * the Views integration of the Search API. Since the Search API has no
   * concept of "tables", this method implementation does nothing. If you are
   * writing Search API-specific Views code, there is therefore no reason at all
   * to call this method.
   * See https://www.drupal.org/node/2484565 for more information.
   *
   * @return string
   *   An empty string.
   */
  public function ensureTable() {
    return '';
  }


}
