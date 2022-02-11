<?php

namespace Drupal\matomo_views_integrations\Sorter;

use Drupal\views\ResultRow;


class Sorter {

  /**
   * @var string
   */
  protected $order;

  /**
   * @var string
   */
  protected $field;

  /**
   * Sorter constructor.
   *
   * @param $field
   * @param $direction
   */
  public function __construct($field, $direction) {
    $this->field = $field;
    $this->order = strtolower($direction);
  }

  /**
   * {@inheritdoc}
   */
  public function __invoke(array &$result) {
    usort($result, function (ResultRow $a, ResultRow $b){
      if( $this->order == 'asc' ){
        $a_sort = $a;
        $b_sort = $b;
      }else{
        $a_sort = $b;
        $b_sort = $a;
      }

      return strcasecmp(reset($a_sort->{$this->field}), reset($b_sort->{$this->field}));
    });
  }

}
