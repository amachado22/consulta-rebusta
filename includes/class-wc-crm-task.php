<?php
/**
 * CRM Logs
 *
  * @author     Adailton Machado
 * @package  WooCommerce_Customer_Relationship_Manager
 * @since    1.0
 */

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_CRM_Task{

  /** @public int Task (post) ID. */
  public $id                          = 0;

  /** @var string Task Subject. */
  public $subject                     = '';  

  /** @public string Task Status. */
  public $task_status                 = '';

  /** @public string Task Priority. */
  public $priority                    = '';

  /** @public string Send Notification Email (yes/no) */
  public $notification_email          = 'no';
  
  /** @public int Post Author (user) ID. */
  public $task_owner;

  /** @public int Customer (user) ID. */
  public $customer_id                   = 0;

  /** @public int Account ID. */
  public $account                    = 0;

  /** @public string Task Date. */
  public $task_date                  = '';

  /** @public string Task Due Date. */
  public $due_date                  = '0000-00-00 00:00:00';

    /** @public string Task Due Date GMT. */
  public $due_date_gmt              = '0000-00-00 00:00:00';

    /** @public string Repeat (yes/no) */
  public $repeat                      = 'no';

  /** @public string Task Start Date. */
  public $srart_date                  = '';

  /** @public string Task End Date. */
  public $end_date                  = '';

  /** @public string Task Repeat type. */
  public $repeat_type                  = 'none';

  /** @public array Task Repeat options. */
  public $repeat_options              = array();

  /** @public string Task Modified Date. */
  public $modified_date               = '';

  /** @public string Task Description. */
  public $description               = '';

  /** @var $post WP_Post. */
  public $post                        = null;

  public function __construct($task = 0) {
    $this->init( $task );
  }

  /**
   * Init/load the task object. Called from the constructor.
   *
   * @param  int|object|WC_Task $task Task to init.
   */
  protected function init( $task ) {
    if ( is_numeric( $task ) ) {
      $this->id   = absint( $task );
      $this->post = get_post( $task );
      $this->get_task( $this->id );
    } elseif ( $task instanceof WC_CRM_Task ) {
      $this->id   = absint( $task->id );
      $this->post = $task->post;
      $this->get_task( $this->id );
    } elseif ( isset( $task->ID ) ) {
      $this->id   = absint( $task->ID );
      $this->post = $task;
      $this->get_task( $this->id );
    }
  }


  /**
   * Gets an task from the database.
   *
   * @param int $id (default: 0).
   * @return bool
   */
  public function get_task( $id = 0 ) {

    if ( ! $id ) {
      return false;
    }

    if ( $result = get_post( $id ) ) {
      $this->populate( $result );
      return true;
    }

    return false;
  }

  /**
   * Populates an task from the loaded post data.
   *
   * @param mixed $result
   */
  public function populate( $result ) {

    // Standard post data
    $statuses   = wc_crm_get_task_statuses();
    $priorities = wc_crm_get_task_priorities();

    $this->id                  = $result->ID;
    $this->subject             = $result->post_title;
    $this->task_owner          = $result->post_author;
    $this->task_date           = $result->post_date;
    $this->modified_date       = $result->post_modified;
    $this->description         = $result->post_content;
    $this->task_status         = isset($statuses[$result->post_status]) ? $result->post_status : ( !empty($statuses) ? key($statuses) : $result->post_status );
    $keys = wc_crm_get_task_populate_fields();
    foreach ($keys as $key) {
      $this->$key = get_post_meta($result->ID, '_'.$key, true);      
    }

    $this->repeat_options = array(
      'srart_date'    => $this->srart_date,
      'end_date'      => $this->end_date,
      'type'          => $this->repeat_type,      
      'daily'        => array(
          'type'          => 1,
          'days'          => '',
      ),
      'weekly'        => array(
          'weeks'         => '',
          'weekdays'      => array(),
      ),
      'monthly' => array(
        'type'          => 1,
        'day'           => 1,
        'noMonths'      => '',
        'weeksequence'  => 1,
        'weekday'       => 1,
      ),
      'yearly' => array(
        'type'          => 1,
        'months'        => 1,
        'days'          => 1,
        'weeksequence'  => 1,
        'weekday'       => 1,
      )
    );
    $_repeat_options = get_post_meta($result->ID, '_repeat_options', true);
    if( is_array($_repeat_options)){
      $this->repeat_options = array_merge($this->repeat_options, $_repeat_options);
    }
    if(empty($this->priority) && !empty($priorities) ){
      $this->priority = key($priorities);
    }
    $this->priority = isset($priorities[$this->priority]) ? $this->priority : ( !empty($priorities) ? key($priorities) : $this->priority );

  } 

  public function is_due_date()
  {
    return ! ( !$this->due_date_gmt || '0000-00-00 00:00:00' == $this->due_date_gmt );
  }

  public function is_repeated()
  {
    return $this->repeat == 'yes' && $this->repeat_type != 'none' ;
  }

  public function get_customer_name()
  {
    $customer_name = '';
    if( $this->customer_id > 0 ){
      $the_customer = new WC_CRM_Customer( $this->customer_id );
      $customer_name = $the_customer->get_name();
    }
    return $customer_name;
  }
  public function get_account_name()
  {
    $account_name = '';
    if( $this->account > 0 ){
      $account_name = get_the_title($this->account);
    }
    return $account_name;
  }
  public function get_owner_name()
  {
    $owner_name = '';
    $user_meta  = (object) get_user_meta( $this->task_owner );    
    $owner_name = trim(implode(' ', array($user_meta->first_name[0],$user_meta->last_name[0])));
    if(empty($owner_name)){
      $owner_name = $user_meta->user_nicename[0];
    }
    return $owner_name;
  }

} //class