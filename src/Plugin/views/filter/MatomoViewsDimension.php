<?php

namespace Drupal\matomo_views_integrations\Plugin\views\filter;

use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\Standard;
use Drupal\views\ViewExecutable;

/**
 * Filters by given list of node title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("matomo_views_dimension")
 */
class MatomoViewsDimension extends Standard {

  /**
   * The Matomo query factory.
   *
   * @var \Drupal\matomo_reporting_api\MatomoQueryFactoryInterface
   */
  protected $matomoQueryFactory;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $matomo_reporting_api_config;

  /** {@inheritDoc} */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['operator'] = ['default' => 'in'];
    $options['filter_limit'] = ['default' => 10];
    $options['date_from'] = ['default' => '-7 days'];
    $options['date_to'] = ['default' => 'yesterday'];
    $options['idDimension'] = ['default' => NULL];

    return $options;
  }

  /**
   * {@inheritDoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->matomoQueryFactory = Drupal::service('matomo.query_factory');
    $this->matomo_reporting_api_config = Drupal::config('matomo_reporting_api.settings');

    $this->value = 0;
  }

  /**
   * {@inheritDoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['filter_limit'] = [
      '#title' => t('Amount of result'),
      '#description' => t('Amount of results returned by Matomo API'),
      '#type' => 'textfield',
      '#attributes' => [
        'type' => 'number',
      ],
      '#required' => true,
      '#default_value' => $this->options['filter_limit'],
    ];

    $form['date_from'] = [
      '#title' => t('Date from'),
      '#description' => t('Date from (you can use today, yesterday, -X days)'),
      '#type' => 'textfield',
      '#required' => true,
      '#default_value' => $this->options['date_from'],
    ];

    $form['date_to'] = [
      '#title' => t('Date to'),
      '#description' => t('Date to (you can use today, yesterday, -X days)'),
      '#type' => 'textfield',
      '#required' => true,
      '#default_value' => $this->options['date_to'],
    ];

    $form['idDimension'] = [
      '#title' => t('Matomo Dimension ID'),
      '#description' => t('Date to (you can use today, yesterday, -X days)'),
      '#type' => 'textfield',
      '#attributes' => [
        'type' => 'number',
      ],
      '#required' => true,
      '#default_value' => $this->options['idDimension'],
    ];
  }

  /** {@inheritDoc} */
  public function adminSummary() {
    return NULL;
  }

  /**
   * Before the query is run, get node id from matomo API
   *
   * {@inheritDoc}
   */
  public function preQuery() {
    parent::preQuery();

    if (empty($this->options['idDimension']) && !empty($this->matomo_reporting_api_config->get('token_auth'))) {
      return;
    }

    $date_from = new DrupalDateTime($this->options['date_from']);
    $date_to = new DrupalDateTime($this->options['date_to']);

    $query = $this->matomoQueryFactory->getQuery('CustomDimensions.getCustomDimension');
    $query->setParameters([
      'filter_limit' => $this->options['count'],
      'period' => 'range',
      'date' => $date_from->format('Y-m-d') . ',' . $date_to->format('Y-m-d'),
      'flat' => 1,
      'filter_sort_order' => 'nb_visits',
      'idDimension' => $this->options['idDimension'],
    ]);
    $response = $query->execute()->getResponse();

    // todo: get id from matomo
  }

}
