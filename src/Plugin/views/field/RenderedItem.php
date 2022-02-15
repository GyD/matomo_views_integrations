<?php

namespace Drupal\matomo_views_integrations\Plugin\views\field;

use Drupal;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles rendering an entity in a certain view mode in Search API Views.
 *
 * @ViewsField("matomo_views_integrations_rendered_item")
 */
class RenderedItem extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setEntityTypeManager($container->get('entity_type.manager'));

    return $plugin;
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $no_view_mode_option = [
      '' => $this->t("Don't include the rendered item."),
    ];

    $nodeEntity = Drupal::service('entity_display.repository');
    $view_modes = $nodeEntity->getViewModes('node');

    foreach ($view_modes as $mode => &$view_mode) {
      $view_modes[$mode] = $view_mode['label'];
    }

    $view_modes = ['default'=>'default'] + $view_modes;

    if (!$view_modes) {
      $form['view_mode'] = [
        '#type' => 'item',
        '#title' => $this->t('View mode for datasource %name', ['%name' => 'Node']),
        '#description' => $this->t("This datasource doesn't have any view modes available. It is therefore not possible to display results of this datasource in this field."),
      ];
    }
    else {
      $form['view_mode'] = [
        '#type' => 'select',
        '#options' => $no_view_mode_option + $view_modes,
        '#title' => 'View mode',
        '#default_value' => key($view_modes),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query($use_groupby = FALSE) {
    //$this->addRetrievedProperty('_object');
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    if (!empty($row->_entity)) {
      return node_view($row->_entity, $this->options['view_mode']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_mode'] = 'default';

    return $options;
  }

}
