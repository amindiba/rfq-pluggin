<?php
/**
 * Loader - PSR-4 style autoloader and component registration.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Loader
 *
 * Manages action/filter hook registration for plugin components.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Loader {

    private $hooks = array('actions' => array(), 'filters' => array());

    /**
     * Register an action hook.
     *
     * @param string $hook         The WordPress hook name.
     * @param object $component    The component instance.
     * @param string $callback     The method to call.
     * @param int    $priority     Priority (default: 10).
     * @param int    $accepted_args Number of arguments (default: 1).
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->hooks['actions'][] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * Register a filter hook.
     *
     * @param string $hook         The WordPress hook name.
     * @param object $component    The component instance.
     * @param string $callback     The method to call.
     * @param int    $priority     Priority (default: 10).
     * @param int    $accepted_args Number of arguments (default: 1).
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->hooks['filters'][] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * Register all hooks with WordPress.
     */
    public function run() {
        if (!isset($this->hooks)) {
            return;
        }

        foreach ($this->hooks['actions'] as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->hooks['filters'] as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}
