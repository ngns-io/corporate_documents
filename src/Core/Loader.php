<?php
declare(strict_types=1);

namespace CorporateDocuments\Core;

/**
 * Register all actions and filters for the plugin.
 *
 * @link       https://ngns.io/wordpress/plugins/corporate_documents/
 * @since      1.0.0
 *
 * @package    CorporateDocuments
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 */
class Loader {

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @var array<array<string, mixed>>
	 */
	private array $actions = array();

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @var array<array<string, mixed>>
	 */
	private array $filters = array();

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @param string $hook          The name of the WordPress action that is being registered.
	 * @param object $component     A reference to the instance of the object on which the action is defined.
	 * @param string $callback      The name of the function definition on the $component.
	 * @param int    $priority      Optional. The priority at which the function should be fired. Default is 10.
	 * @param int    $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 *
	 * @return void
	 */
	public function add_action(
		string $hook,
		object $component,
		string $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$this->actions[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @param string $hook          The name of the WordPress filter that is being registered.
	 * @param object $component     A reference to the instance of the object on which the filter is defined.
	 * @param string $callback      The name of the function definition on the $component.
	 * @param int    $priority      Optional. The priority at which the function should be fired. Default is 10.
	 * @param int    $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 *
	 * @return void
	 */
	public function add_filter(
		string $hook,
		object $component,
		string $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$this->filters[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @return void
	 */
	public function run(): void {
		foreach ( $this->filters as $filter ) {
			add_filter(
				$filter['hook'],
				array( $filter['component'], $filter['callback'] ),
				$filter['priority'],
				$filter['accepted_args']
			);
		}

		foreach ( $this->actions as $action ) {
			add_action(
				$action['hook'],
				array( $action['component'], $action['callback'] ),
				$action['priority'],
				$action['accepted_args']
			);
		}
	}
}
