<?php

/**
 * Loader for Meta Override plugin
 *
 * Registers all actions and filters for the plugin.
 *
 * @package Meta_Override
 * @since   1.1.0
 */
class Meta_Override_Loader
{
  /**
   * Array of actions to be registered
   *
   * @var array
   */
  protected $actions;

  /**
   * Array of filters to be registered
   *
   * @var array
   */
  protected $filters;

  /**
   * Initialize the loader
   *
   * @since 1.1.0
   */
  public function __construct()
  {
    $this->actions = array();
    $this->filters = array();
  }

  /**
   * Add a new action to be registered
   *
   * @param string $hook          The name of the WordPress action
   * @param object $component     The object containing the callback
   * @param string $callback      The callback method name
   * @param int    $priority      Optional. Priority (default: 10)
   * @param int    $accepted_args Optional. Number of arguments (default: 1)
   * @since 1.1.0
   * @return void
   */
  public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
  {
    $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
  }

  /**
   * Add a new filter to be registered
   *
   * @param string $hook          The name of the WordPress filter
   * @param object $component     The object containing the callback
   * @param string $callback      The callback method name
   * @param int    $priority      Optional. Priority (default: 10)
   * @param int    $accepted_args Optional. Number of arguments (default: 1)
   * @since 1.1.0
   * @return void
   */
  public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
  {
    $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
  }

  /**
   * Add a hook to the collection
   *
   * @param array  $hooks         The collection of hooks
   * @param string $hook          The name of the WordPress hook
   * @param object $component     The object containing the callback
   * @param string $callback      The callback method name
   * @param int    $priority      Priority
   * @param int    $accepted_args Number of arguments
   * @return array The modified collection
   * @since 1.1.0
   */
  private function add($hooks, $hook, $component, $callback, $priority, $accepted_args)
  {
    $hooks[] = array(
      'hook'          => $hook,
      'component'     => $component,
      'callback'      => $callback,
      'priority'      => $priority,
      'accepted_args' => $accepted_args
    );

    return $hooks;
  }

  /**
   * Register all filters and actions with WordPress
   *
   * @since 1.1.0
   * @return void
   */
  public function run()
  {
    foreach ($this->filters as $hook) {
      add_filter(
        $hook['hook'],
        array($hook['component'], $hook['callback']),
        $hook['priority'],
        $hook['accepted_args']
      );
    }

    foreach ($this->actions as $hook) {
      add_action(
        $hook['hook'],
        array($hook['component'], $hook['callback']),
        $hook['priority'],
        $hook['accepted_args']
      );
    }
  }
}
