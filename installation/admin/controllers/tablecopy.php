<?php

class wbJoomigate_controller_tablecopy extends wbJoomigate_controller {

  /**
   * [display description]
   * @return [type] [description]
   */
  public function display(){

    // Initialize
      $this->init_database();

    // Get Tables
      $tables = $this->remote_get_tables();

    // View
      ?>
      <table class=wbjoomigate>
        <tr>
          <td width=20% valign="top" style="border-right:2px solid #ccc;padding-right: 10px;">
            <form target=wbjoomigate_controller_tablecopy>
              <input type="hidden" name="option" value="com_wbjoomigate">
              <input type="hidden" name="tmpl" value="component">
              <input type="hidden" name="rand" value="">
              <input type="hidden" name="task" value="tablecopy.process">
              <div class="field text">
                <label for="copy_tables">Select Tables to Copy</label>
                <select name="copy_tables[]" multiple=true><?php
                  foreach( $tables AS $table ){
                    echo '<option value="'. $table .'">'. $table .'</option>';
                  }
                ?></select>
              </div>
              <div class="field button">
                <button type="button" onclick="
                  jQuery(this).closest('form').find('input[name=rand]').val(Math.random());
                  jQuery(this).closest('form').submit();
                  ">Copy Table Records</button>
              </div>
            </form>
          </td>
          <td>
            <iframe name=wbjoomigate_controller_tablecopy style="width:100%;height:100%;border:none;min-height:640px;">
            </iframe>
          </td>
        </tr>
      </table>
      <style>
        table.wbjoomigate {
          border-collapse: collapse;
          padding:0;
          margin:0;
          width: 100%;
          height: 100%;
        }
        table.wbjoomigate tr td {
          padding: 10px;
          height: 100%;
        }
        table.wbjoomigate form {
          display: block;
          height: 100%;
        }
        table.wbjoomigate select[name^=copy_tables] {
          height: 100%;
          min-height: 480px;
          width: 100%;
        }
      </style>
      <?php

  }

  /**
   * [wbmember description]
   * @return [type] [description]
   */
  public function wbmember(){

    $this->input->set('copy_tables', array(
      'jos_viewlevels',
      'jos_user_keys',
      'jos_user_notes',
      'jos_user_profiles',
      'jos_user_usergroup_map',
      'jos_usergroups',
      'jos_users',
      'jos_member_activity_log',
      'jos_member_cache',
      'jos_member_client',
      'jos_member_client_access',
      'jos_member_client_address',
      'jos_member_client_oauth',
      'jos_member_client_store',
      'jos_member_currency',
      'jos_member_discount',
      'jos_member_dutytaxrule',
      'jos_member_gateway_trx',
      'jos_member_group',
      'jos_member_invoice',
      'jos_member_invoice_item',
      'jos_member_package',
      'jos_member_payment',
      'jos_member_plg_shipping_fedex_api_log',
      'jos_member_session',
      'jos_member_taxgroup'
      ));
    $this->process();

  }

  /**
   * [wbmember description]
   * @return [type] [description]
   */
  public function wbcatalog(){

    $this->input->set('copy_tables', array(
      'jos_wbcatalog_bundle',
      'jos_wbcatalog_bundle_group_xref',
      'jos_wbcatalog_bundle_item_xref',
      'jos_wbcatalog_cat',
      'jos_wbcatalog_item',
      'jos_wbcatalog_item_attr',
      'jos_wbcatalog_item_attr_opt',
      'jos_wbcatalog_item_catdetail',
      'jos_wbcatalog_item_img',
      'jos_wbcatalog_item_img_xref',
      'jos_wbcatalog_item_price',
      'jos_wbcatalog_item_stock',
      'jos_wbcatalog_item_stock_xref',
      'jos_wbcatalog_item_xref',
      'jos_wbcatalog_meta',
      'jos_wbcatalog_mfgr',
      'jos_wbcatalog_supplier',
      'jos_wbcatalog_type',
      'jos_wbcatalog_type_attr',
      'jos_wbcatalog_type_attr_opt',
      'jos_wbcatalog_type_data',
      'jos_wbcatalog_type_field',
      'jos_wbcatalog_type_field_opt'
      ));
    $this->process();

  }

  /**
   * [process description]
   * @return [type] [description]
   */
  public function process(){

    // Initialize
      $this->init_database();

    // Tables
      $tables = $this->remote_get_tables();

    // Process Selected
      $batch_flush  = $this->input->get('batch_flush', $this->getApplication()->getSession()->get('batch_flush', true), 'bool');
      $copy_tables  = $this->input->get('copy_tables', $this->getApplication()->getSession()->get('copy_tables', array()), 'array');
      $batch_table  = $this->getApplication()->getSession()->get('batch_table', reset($copy_tables));
      $batch_count  = 0;
      $batch_start  = $this->getApplication()->getSession()->get('batch_start', 0);
      $batch_limit  = 1000;
      $batch_stage  = $this->getApplication()->getSession()->get('batch_stage', 0);
      $batch_total  = $this->getApplication()->getSession()->get('batch_total', 0);

    // Start
      $this->inspect( "Processing [{$batch_table}] of ". count($copy_tables) ." table(s), {$batch_stage} step(s), {$batch_total} record(s)" );

    // Process
      $tmp_tables = $copy_tables;
      while( count($tmp_tables) ){
        $copy_table = array_shift($tmp_tables);

        // This Batch Table
          if( $copy_table == $batch_table ){

            // Flush on Start
              if( $batch_start == 0 && $batch_flush ){
                $this->inspect( 'flush table', $copy_table );
                $this->local_db
                  ->setQuery("
                    TRUNCATE `{$copy_table}`
                    ")
                  ->query();
              }

            // Queue Rows
              $rows =
                $this->remote_db
                  ->setQuery("
                    SELECT *
                    FROM `{$copy_table}`
                    LIMIT {$batch_start}, {$batch_limit}
                    ")
                  ->loadObjectList();
              while( !empty($rows) ){
                $row = array_shift($rows);
                $columns = $values = array();
                foreach( $row AS $key => $val ){
                  $columns[] = $key;
                  $values[]  = $this->local_db->quote($val);
                }
                $this->inspect( 'inserting record '.$batch_total );
                $this->local_db
                  ->setQuery(
                    $this->local_db
                      ->getQuery(true)
                        ->insert($this->local_db->quoteName($copy_table))
                        ->columns($this->local_db->quoteName($columns))
                        ->values(implode(',', $values))
                    )
                  ->execute();
                $batch_count++;
                $batch_start++;
                $batch_total++;
                $this->getApplication()->getSession()->set('batch_start', $batch_start);
              }

            // Batch Stage Completed
              break;

          }

      }

    // We've finished the table
      if(
        !$batch_count
        || $batch_count < $batch_limit
        ){
        JError::raiseNotice( 100, "Table Completed: {$batch_table}, {$batch_stage} step(s), ". ($batch_start+$batch_count) ." record(s)" );
        $batch_start = 0;
        $batch_table = array_shift($tmp_tables);
      }

    // Update Session
      $this->getApplication()->getSession()->set('copy_tables', $copy_tables);
      $this->getApplication()->getSession()->set('batch_start', $batch_start);
      $this->getApplication()->getSession()->set('batch_table', $batch_table);
      $this->getApplication()->getSession()->set('batch_stage', ++$batch_stage);
      $this->getApplication()->getSession()->set('batch_total', $batch_total);

    // Redirect
      if( $batch_table ){
        ob_get_flush(); flush();
        $this->getApplication()->redirect('index.php?option=com_wbjoomigate&task=tablecopy.process&tmpl=component&rand='.rand(0,9999), 10);
      }
      else {
        $this->getApplication()->getSession()->set('copy_tables', array());
        $this->getApplication()->getSession()->set('batch_flush', null);
        $this->getApplication()->getSession()->set('batch_table', null);
        $this->getApplication()->getSession()->set('batch_start', 0);
        $this->getApplication()->getSession()->set('batch_stage', 0);
        $this->getApplication()->getSession()->set('batch_total', 0);
        JError::raiseNotice( 100, "Processing Complete - ". count($copy_tables) ." table(s), {$batch_stage} step(s), {$batch_total} record(s)" );
      }

  }

  /**
   * [remote_get_tables description]
   * @return [type] [description]
   */
  private function remote_get_tables(){

    // Request
      $rows =
        $this->remote_db
          ->setQuery("
            SHOW FULL TABLES
            ")
          ->loadObjectList();

    // Report
      $tables = array();
      foreach( $rows AS $row ){
        $tables[] = reset($row);
      }
      return $tables;

  }


}