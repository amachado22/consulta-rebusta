<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Get all task statuses.
 *
 * @since 3.0.6
 * @return array
 */
function wc_crm_get_task_statuses() {
  $task_statuses = array(
    'wcrm-not-started' => _x( 'Not Started', 'Task status', 'wc_crm' ),
    'wcrm-deferred'    => _x( 'Deferred', 'Task status', 'wc_crm' ),
    'wcrm-in-progress' => _x( 'In Progress', 'Task status', 'wc_crm' ),
    'wcrm-completed'   => _x( 'Completed', 'Task status', 'wc_crm' ),
    'wcrm-waiting'     => _x( 'Waiting', 'Task status', 'wc_crm' ),
  );
  return apply_filters( 'wc_crm_task_statuses', $task_statuses );
}


/**
 * Get all task priorities.
 *
 * @since 3.0.6
 * @return array
 */
function wc_crm_get_task_priorities() {
  $task_priorities = array(
    'normal'     => _x( 'Normal', 'Task priority', 'wc_crm' ),
	'hightest'   => _x( 'Highest', 'Task priority', 'wc_crm' ),
    'high'       => _x( 'High', 'Task priority', 'wc_crm' ),
    'low'        => _x( 'Low', 'Task priority', 'wc_crm' ),
    'lowest'     => _x( 'Lowest', 'Task priority', 'wc_crm' ),
  );
  return apply_filters( 'wc_crm_task_priorities', $task_priorities );
}

function wc_crm_get_task_populate_fields()
{
    $keys = array('priority', 'repeat', 'customer_id', 'account', 'due_date', 'due_date_gmt', 'notification_email', 'end_date', 'srart_date', 'repeat_type');
    return array_merge($keys, apply_filters('wc_crm_task_populate', array()) );
}
