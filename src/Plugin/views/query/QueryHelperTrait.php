<?php

namespace Drupal\matomo_views_integrations\Plugin\views\query;

use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ingroup views_query_plugins
 *
 * @ViewsQuery(
 *   id = "matomo_views_dimension",
 *   title = @Translation("Matomo Views Dimenson"),
 *   help = @Translation("Query will be generated and run using the Matomo API
 *   using Dimension Entry point")
 * )
 */
trait QueryHelperTrait {

  /**
   * A simple array of order by clauses.
   *
   * @var array
   */
  public $orderby = [];

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->matomoQueryFactory = Drupal::service('matomo.query_factory');
    $this->matomo_reporting_api_config = Drupal::config('matomo_reporting_api.settings');
  }

  /** {@inheritDoc} */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return parent::create($container, $configuration, $plugin_id, $plugin_definition);
  }

  /** {@inheritDoc} */
  public function execute(ViewExecutable $view) {
    $matomoQuery = $this->fetchMatomo();

    if ($view->pager->useCountQuery() || !empty($view->get_total_rows)) {
      // Normall we would call $view->pager->executeCountQuery($count_query);
      // but we can't in this case, so do the calculation ourselves.
      $view->pager->total_items = count($matomoQuery);
      $view->pager->total_items -= $view->pager->getOffset();
    }


    foreach ($matomoQuery as $item) {
      $result_row = new ResultRow();
      $view->result[] = $result_row;

      $result_row->_entity = $item->_entity;

      foreach ($view->field as $field_name => $field) {
        if (isset($field->realField)) {
          $result_row->{$field_name} = [$item->{$field->realField}];
        }
        else {
          $result_row->{$field_name} = [$item->{$field_name}];
        }
      }
    }

    $this->executeSorts($view);

    if (!empty($this->limit) || !empty($this->offset)) {
      // @todo Re-implement the performance optimization. For the case with no
      // sorts, we can avoid parsing the whole file.
      $view->result = array_slice($view->result, (int) $this->offset, (int) $this->limit);
    }

    // Set the index values after all manipulation is done.
    $this->reIndexResults($view);

    $view->pager->postExecute($view->result);
    $view->pager->updatePageInfo();
    $view->total_rows = $view->pager->getTotalItems();
  }

  /**
   * @return mixed|void
   * @throws \Drupal\matomo_reporting_api\Exception\MissingMatomoServerUrlException
   */
  protected function fetchMatomo() {

    if (empty($this->matomo_reporting_api_config->get('token_auth'))) {
      return;
    }

    $date_from = new DrupalDateTime($this->options['date_from']);
    $date_to = new DrupalDateTime($this->options['date_to']);

    $query = $this->matomoQueryFactory->getQuery($this->MatomoEntryPoint);
    $query->setParameters([
      'filter_limit' => $this->options['count'],
      'period' => 'range',
      'date' => $date_from->format('Y-m-d') . ',' . $date_to->format('Y-m-d'),
      'flat' => 1,
      'filter_sort_order' => 'nb_visits',
    ]);

    if (!empty($this->options['filter_pattern'])) {
      $query->setParameter('filter_pattern', $this->options['filter_pattern']);
    }

    if (!empty($this->options['filter_pattern']) && !empty($this->options['filter_column'])) {
      $query->setParameter('filter_column', $this->options['filter_column']);
    }


    foreach ($this->MatomoExtraParameters as $matomoParameter => $optionKey) {
      $query->setParameter($matomoParameter, $this->options[$optionKey]);
    }

    $response = $query->execute()->getResponse();

    foreach ($response as &$responseRow) {
      $this->loadEntity($responseRow);
      $responseRow->_entity = node_load(17);
    }

    return $response;
  }

  /**
   * Executes all added sorts to a view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to sort.
   */
  protected function executeSorts(ViewExecutable $view) {
    foreach (array_reverse($this->orderby) as $sort) {
      // We need to re-index the results before each sort because the index is
      // used to maintain a stable sort.
      $this->reIndexResults($view);

      $sort($view->result);
    }
  }

  /**
   * Re-indexes the results of a view.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to re-index.
   */
  protected function reIndexResults(ViewExecutable $view) {
    $index = 0;
    foreach ($view->result as $row) {
      $row->index = $index++;
    }
  }

  public function build(ViewExecutable $view) {
    $this->view = $view;
    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();

    $view->build_info['query'] = $this->query();
    $view->build_info['count_query'] = '';
    $this->livePreview = !empty($view->live_preview);
  }

  /**
   * Add an ORDER BY clause to the query.
   *
   * This is only used to support the built-in random sort plugin.
   *
   * @param string $table
   *   The table this field is part of. If a formula, enter NULL.
   *   If you want to orderby random use "rand" as table and nothing else.
   * @param string $field
   *   The field or formula to sort on. If already a field, enter NULL
   *   and put in the alias.
   * @param string $order
   *   Either ASC or DESC.
   * @param string $alias
   *   The alias to add the field as. In SQL, all fields in the order by
   *   must also be in the SELECT portion. If an $alias isn't specified
   *   one will be generated for from the $field; however, if the
   *   $field is a formula, this alias will likely fail.
   * @param array $params
   *   Any params that should be passed through to the addField.
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = []) {
    if ($table === 'rand') {
      $this->orderby[] = 'shuffle';
    }
  }

  /**
   * Adds a sorter callable.
   *
   * @param callable $callback
   *   A callable that can sort a views result.
   *
   * @see \Drupal\matomo_views_integrations\Sorter\Sorter
   */
  public function addSort(callable $callback) {
    $this->orderby[] = $callback;
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

  /** {@inheritDoc} */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $this->getDefaultOptions($options);
    $this->defineExtraOptions($options);

    return $options;
  }

  private function getDefaultOptions($options) {
    $options['operator'] = ['default' => 'in'];
    $options['filter_limit'] = ['default' => 10];
    $options['date_from'] = ['default' => '-7 days'];
    $options['date_to'] = ['default' => 'yesterday'];
  }

  protected function defineExtraOptions(&$options) {
  }

  private function getDefaultOptionsForm(&$form) {
    $form['filter_limit'] = [
      '#title' => t('Amount of result'),
      '#description' => t('Amount of results returned by Matomo API'),
      '#type' => 'textfield',
      '#attributes' => [
        'type' => 'number',
      ],
      '#required' => TRUE,
      '#default_value' => $this->options['filter_limit'],
    ];

    $form['date_from'] = [
      '#title' => t('Date from'),
      '#description' => t('Date from (you can use today, yesterday, -X days)'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->options['date_from'],
    ];

    $form['date_to'] = [
      '#title' => t('Date to'),
      '#description' => t('Date to (you can use today, yesterday, -X days)'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->options['date_to'],
    ];

    $form['filter_pattern'] = [
      '#title' => t('Filter Pattern'),
      '#description' => t('Filter pattern (can use token)'),
      '#type' => 'textfield',
      '#default_value' => $this->options['filter_pattern'],
    ];

    $form['filter_column'] = [
      '#title' => t('Filter column'),
      '#description' => t('Filter column'),
      '#type' => 'textfield',
      '#default_value' => $this->options['filter_column'],
    ];
  }


}
