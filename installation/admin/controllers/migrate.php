<?php

class wbJoomigate_controller_migrate extends wbJoomigate_controller {

  /**
   * [display description]
   * @return [type] [description]
   */
  public function display(){

    // Initialize
      $this->init_database();

    // Get Tables
      $tables = $this->remote_get_tables();

  }

  /**
   * [wbmember description]
   * @return [type] [description]
   */
  public function wbcatalog(){

    // Initialize
      $this->init_database();

    // Pre
      echo '<pre>';

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_cat'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_item'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_item_attr'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_item_attr_opt'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_item_catdetail'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_item_img'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_item_img_xref',
        'id',
        'id',
        'id'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_item_price'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_item_stock'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_item_stock_xref'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_item_xref'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_meta'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_type'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_type_attr'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_type_attr_opt'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_type_data'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_type_field'
        );

    // ************************************************
      $this->processTableMerge(
        '#__wbcatalog_type_field_opt'
        );

    // Pre
      die(' ---- complete ---- ');

  }

  private function processTableMerge( $table, $key, $latestField, $compareField, $isNewFunction ){

    // Defaults
      $key          = $key ?: 'id';
      $latestField  = $latestField ?: 'modified';
      $compareField = $compareField ?: 'created';

    // Function Blank
      if( empty($isNewFunction) ){
        $local_db =& $this->local_db;
        $isNewFunction =
          function( $row, $latestValue ) use ($local_db, $table, $key) {
            return
              !(int)$local_db->setQuery("
                SELECT `{$key}`
                FROM `{$table}`
                WHERE `{$key}` = '". (int)$row->{$key} ."'
                LIMIT 1
                ")->loadResult();
          };
      }

    // Batch
      $batch_start = 0;
      $batch_limit = 10;
      $latestValue =
        $this->local_db
          ->setQuery("
            SELECT `{$latestField}`
            FROM `{$table}`
            ORDER BY `{$latestField}` DESC
            LIMIT 1
            ")
          ->loadResult();
      echo "{$table} {$latestValue} \n";
      do {
        $rows =
          $this->remote_db
            ->setQuery("
              SELECT *
              FROM `{$table}`
              WHERE `{$compareField}` >= '{$latestValue}'
              LIMIT {$batch_start}, {$batch_limit}
              ")
            ->loadObjectList();
        $this->_procRows(
          $rows,
          $table,
          $key,
          $latestValue,
          $isNewFunction
          );
        $batch_start += $batch_limit;
      } while( count($rows) );

  }

  private function _procRows( $rows, $table, $key, $latestValue, $isNewFunc ){
    foreach( $rows AS $row ){
      if( $isNewFunc( $row, $latestValue ) ){
        echo "Inserted #". $row->{$key} ." \n";
        $this->local_db->insertObject(
          $table,
          $row,
          $key
          );
      }
      else {
        echo "Updated #". $row->{$key} ." \n";
        $this->local_db->updateObject(
          $table,
          $row,
          $key
          );
      }
    }
  }

  /**
   * [wbmember description]
   * @return [type] [description]
   */
  public function wbmember(){

    // Initialize
      $this->init_database();

    // Pre
      echo '<pre>';

    // ************************************************
    // #__member_client
      $batch_start = 0;
      $batch_limit = 10;
      $latest_date =
        $this->local_db
          ->setQuery("
            SELECT `activity`
            FROM `#__member_client`
            ORDER BY `created` DESC
            LIMIT 1
            ")
          ->loadResult();
      echo "#__member_client {$latest_date} \n";
      do {
        $rows =
          $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__member_client`
              WHERE `activity` >= '{$latest_date}'
              LIMIT {$batch_start}, {$batch_limit}
              ")
            ->loadObjectList();
        $this->_procRows(
          $rows,
          '#__member_client',
          'id',
          $latest_date,
          function( $row ) use ($latest_date) {
            return strtotime($row->created) > strtotime($latest_date);
          }
          );
        $batch_start += $batch_limit;
      } while( count($rows) );

    // ************************************************
    // #__member_client_address
      $batch_start = 0;
      $batch_limit = 10;
      $latest_date =
        $this->local_db
          ->setQuery("
            SELECT `modified`
            FROM `#__member_client_address`
            ORDER BY `modified` DESC
            LIMIT 1
            ")
          ->loadResult();
      echo "#__member_client_address {$latest_date} \n";
      do {
        $rows =
          $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__member_client_address`
              WHERE `modified` >= '{$latest_date}'
              LIMIT {$batch_start}, {$batch_limit}
              ")
            ->loadObjectList();
        $this->_procRows(
          $rows,
          '#__member_client_address',
          'id',
          $latest_date,
          function( $row ) use ($latest_date) {
            return strtotime($row->created) > strtotime($latest_date);
          }
          );
        $batch_start += $batch_limit;
      } while( count($rows) );

    // ************************************************
    // #__member_client_oauth
      $batch_start = 0;
      $batch_limit = 10;
      $latest_id =
        $this->local_db
          ->setQuery("
            SELECT `id`
            FROM `#__member_client_oauth`
            ORDER BY `id` DESC
            LIMIT 1
            ")
          ->loadResult();
      echo "#__member_client_oauth {$latest_id} \n";
      do {
        $rows =
          $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__member_client_oauth`
              WHERE `id` > '{$latest_id}'
              LIMIT {$batch_start}, {$batch_limit}
              ")
            ->loadObjectList();
        $this->_procRows(
          $rows,
          '#__member_client_oauth',
          'id',
          $latest_id,
          function( $row ) use ($latest_id) {
            return $row->id > $latest_id;
          }
          );
        $batch_start += $batch_limit;
      } while( count($rows) );

    // ************************************************
    // #__member_gateway_trx
      $batch_start = 0;
      $batch_limit = 10;
      $latest_date =
        $this->local_db
          ->setQuery("
            SELECT `created`
            FROM `#__member_gateway_trx`
            ORDER BY `created` DESC
            LIMIT 1
            ")
          ->loadResult();
      echo "#__member_gateway_trx {$latest_date} \n";
      do {
        $rows =
          $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__member_gateway_trx`
              WHERE `created` >= '{$latest_date}'
              LIMIT {$batch_start}, {$batch_limit}
              ")
            ->loadObjectList();
        $this->_procRows(
          $rows,
          '#__member_gateway_trx',
          'id',
          $latest_date,
          function( $row ) use ($latest_date) {
            return strtotime($row->created) > strtotime($latest_date);
          }
          );
        $batch_start += $batch_limit;
      } while( count($rows) );

    // ************************************************
    // #__member_invoice
      $batch_start = 0;
      $batch_limit = 10;
      $latest_date =
        $this->local_db
          ->setQuery("
            SELECT `date_created`
            FROM `#__member_invoice`
            ORDER BY `date_created` DESC
            LIMIT 1
            ")
          ->loadResult();
      echo "#__member_invoice {$latest_date} \n";
      do {
        $rows =
          $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__member_invoice`
              WHERE `date_activity` >= '{$latest_date}'
              LIMIT {$batch_start}, {$batch_limit}
              ")
            ->loadObjectList();
        $this->_procRows(
          $rows,
          '#__member_invoice',
          'id',
          $latest_date,
          function( $row ) use ($latest_date) {
            return strtotime($row->date_created) > strtotime($latest_date);
          }
          );
        $batch_start += $batch_limit;
      } while( count($rows) );

    // ************************************************
    // #__member_invoice_item
      $batch_start = 0;
      $batch_limit = 10;
      $latest_date =
        $this->local_db
          ->setQuery("
            SELECT `date_created`
            FROM `#__member_invoice_item`
            ORDER BY `date_created` DESC
            LIMIT 1
            ")
          ->loadResult();
      echo "#__member_invoice_item {$latest_date} \n";
      do {
        $rows =
          $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__member_invoice_item`
              WHERE `date_created` >= '{$latest_date}'
              LIMIT {$batch_start}, {$batch_limit}
              ")
            ->loadObjectList();
        $this->_procRows(
          $rows,
          '#__member_invoice_item',
          'id',
          $latest_date,
          function( $row ) use ($latest_date) {
            return strtotime($row->date_created) > strtotime($latest_date);
          }
          );
        $batch_start += $batch_limit;
      } while( count($rows) );

    // ************************************************
    // #__member_payment
      $batch_start = 0;
      $batch_limit = 10;
      $latest_date =
        $this->local_db
          ->setQuery("
            SELECT `date_created`
            FROM `#__member_payment`
            ORDER BY `date_created` DESC
            LIMIT 1
            ")
          ->loadResult();
      echo "#__member_payment {$latest_date} \n";
      do {
        $rows =
          $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__member_payment`
              WHERE `date_activity` >= '{$latest_date}'
              LIMIT {$batch_start}, {$batch_limit}
              ")
            ->loadObjectList();
        $this->_procRows(
          $rows,
          '#__member_payment',
          'id',
          $latest_date,
          function( $row ) use ($latest_date) {
            return strtotime($row->date_created) > strtotime($latest_date);
          }
          );
        $batch_start += $batch_limit;
      } while( count($rows) );

    // ************************************************
    // #__member_plg_shipping_fedex_api_log
      $batch_start = 0;
      $batch_limit = 10;
      $latest_date =
        $this->local_db
          ->setQuery("
            SELECT `date_created`
            FROM `#__member_plg_shipping_fedex_api_log`
            ORDER BY `date_created` DESC
            LIMIT 1
            ")
          ->loadResult();
      echo "#__member_plg_shipping_fedex_api_log {$latest_date} \n";
      do {
        $rows =
          $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__member_plg_shipping_fedex_api_log`
              WHERE `date_created` >= '{$latest_date}'
              LIMIT {$batch_start}, {$batch_limit}
              ")
            ->loadObjectList();
        $this->_procRows(
          $rows,
          '#__member_plg_shipping_fedex_api_log',
          'id',
          $latest_date,
          function( $row ) use ($latest_date) {
            return strtotime($row->date_created) > strtotime($latest_date);
          }
          );
        $batch_start += $batch_limit;
      } while( count($rows) );

    // ************************************************
    // #__users
      $batch_start = 0;
      $batch_limit = 10;
      $latest_date =
        $this->local_db
          ->setQuery("
            SELECT `registerDate`
            FROM `#__users`
            ORDER BY `registerDate` DESC
            LIMIT 1
            ")
          ->loadResult();
      echo "#__users {$latest_date} \n";
      do {
        $rows =
          $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__users`
              WHERE `lastvisitDate` >= '{$latest_date}'
              LIMIT {$batch_start}, {$batch_limit}
              ")
            ->loadObjectList();
        $this->_procRows(
          $rows,
          '#__users',
          'id',
          $latest_date,
          function( $row ) use ($latest_date) {
            return strtotime($row->registerDate) > strtotime($latest_date);
          }
          );
        $batch_start += $batch_limit;
      } while( count($rows) );

    // ************************************************
    // #__user_keys
      $batch_start = 0;
      $batch_limit = 10;
      $latest_id =
        $this->local_db
          ->setQuery("
            SELECT `id`
            FROM `#__user_keys`
            ORDER BY `id` DESC
            LIMIT 1
            ")
          ->loadResult();
      echo "#__user_keys {$latest_id} \n";
      do {
        $rows =
          $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__user_keys`
              WHERE `id` > '{$latest_id}'
              LIMIT {$batch_start}, {$batch_limit}
              ")
            ->loadObjectList();
        $this->_procRows(
          $rows,
          '#__user_keys',
          'id',
          $latest_date,
          function( $row ) use ($latest_id) {
            return $row->id > $latest_id;
          }
          );
        $batch_start += $batch_limit;
      } while( count($rows) );

    // ************************************************
    // #__user_usergroup_map
      $batch_start = 0;
      $batch_limit = 10;
      $latest_id =
        $this->local_db
          ->setQuery("
            SELECT `user_id`
            FROM `#__user_usergroup_map`
            ORDER BY `user_id` DESC
            LIMIT 1
            ")
          ->loadResult();
      echo "#__user_usergroup_map {$latest_id} \n";
      do {
        $rows =
          $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__user_usergroup_map`
              WHERE `user_id` > '{$latest_id}'
              LIMIT {$batch_start}, {$batch_limit}
              ")
            ->loadObjectList();
        $this->_procRows(
          $rows,
          '#__user_usergroup_map',
          'user_id',
          $latest_id,
          function( $row ) use ($latest_id) {
            return $row->user_id > $latest_id;
          }
          );
        $batch_start += $batch_limit;
      } while( count($rows) );

    // Pre
      die(' ---- complete ---- ');

  }

}