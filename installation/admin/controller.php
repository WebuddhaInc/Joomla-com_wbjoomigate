<?php

class wbJoomigate_controller extends JControllerBase {

  /**
   * [inspect description]
   * @return [type] [description]
   */
  public function inspect(){
    $args = func_get_args();
    if( count($args) == 1 && is_string(reset($args)) ){
      echo reset($args) . "<br>\n";
    }
    else {
      echo '<pre>' . print_r( $args, true ) . '</pre>';
    }
    if( $this->input->get('format') == 'raw' ){
      ob_end_flush(); flush();
    }
  }

  /**
   * Map Associations
   * @var array
   */
  private $maps = array(
    'menu'      => array(),
    'menu_type' => array(),
    'section'   => array(),
    'category'  => array(),
    'article'   => array()
    );

  /**
   * Datasets
   * @var array
   */
  private $data = array(
    'menu' => array(
      'local' => array(),
      'remote' => array()
      ),
    'menu_type' => array(
      'local' => array(),
      'remote' => array()
      ),
    'section' => array(
      'local' => array(),
      'remote' => array()
      ),
    'category' => array(
      'local' => array(),
      'remote' => array()
      ),
    'article' => array(
      'local' => array(),
      'remote' => array()
      ),
    'component' => array(
      'local' => array(),
      'remote' => array()
      ),
    'module_menu' => array(
      'local' => array(),
      'local_extension' => array(),
      'remote' => array()
      ),
    'module' => array(
      'local' => array(),
      'remote' => array()
      )
    );

  public function execute( $task = null ){
    $task_method = $task;
    $task_control = explode('.', $task, 2);
    if( count($task_control) == 2 ){
      $task_method = array_pop($task_control);
    }
    else {
      $task_method = null;
    }
    $task_control = array_pop($task_control);
    $task_controller = "wbjoomigate_controller_{$task_control}";
    $is_controller = strtolower(get_class($this)) == $task_controller;
    if(
      !$is_controller
      && class_exists($task_controller)
      ){
      (new $task_controller())->execute( $task_method );
    }
    else if(
      !$is_controller
      && is_readable(__DIR__.'/controllers/'.$task_control.'.php')
      ){
      require_once(__DIR__.'/controllers/'.$task_control.'.php');
      (new $task_controller())->execute( $task_method );
    }
    else if(
      method_exists( $this, $task_control )
      ){
      $this->{ $task_control }();
    }
    else {
      $this->display();
    }
  }

  /**
   * [display description]
   * @return [type] [description]
   */
  public function display(){

    // Media
      JHtml::_('behavior.core');

    // Title / Submenu
      wbJoomigate_helper::toolbar();

    // View
      ?>
      <table class=wbjoomigate>
        <tr>
          <td width=20% valign="top" style="border-right:2px solid #ccc;padding-right: 10px;">
            <form id=adminForm target=wbjoomigate_controller>
              <input type="hidden" name="option" value="com_wbjoomigate">
              <input type="hidden" name="tmpl" value="component">
              <input type="hidden" name="rand" value="">
              <input type="hidden" name="task" value="">
              <div class="field text">
                <label for="remote_joomla_version">Remote Joomla Major / Minor</label>
                <input type="text" name="remote_joomla_version" value="<?= htmlspecialchars($this->input->get('remote_joomla_version', '1.5')) ?>">
              </div>
              <div class="field text">
                <label for="remote_db_host">Remote DB Host</label>
                <input type="text" name="remote_db_host" value="<?= htmlspecialchars($this->input->get('remote_db_host', 'localhost')) ?>">
              </div>
              <div class="field text">
                <label for="remote_db_name">Remote DB Name</label>
                <input type="text" name="remote_db_name" value="<?= htmlspecialchars($this->input->get('remote_db_name')) ?>">
              </div>
              <div class="field text">
                <label for="remote_db_user">Remote DB User</label>
                <input type="text" name="remote_db_user" value="<?= htmlspecialchars($this->input->get('remote_db_user')) ?>">
              </div>
              <div class="field text">
                <label for="remote_db_pass">Remote DB Password</label>
                <input type="text" name="remote_db_pass" value="<?= htmlspecialchars($this->input->get('remote_db_pass', null, false)) ?>">
              </div>
              <div class="field text">
                <label for="remote_db_prefix">Remote DB Prefix</label>
                <input type="text" name="remote_db_prefix" value="<?= htmlspecialchars($this->input->get('remote_db_prefix', 'jos_')) ?>">
              </div>
              <div class="field text">
                <label for="uncategorized_catid">Uncategorized Catid</label>
                <input type="text" name="uncategorized_catid" value="">
              </div>
            </form>
          </td>
          <td>
            <iframe name=wbjoomigate_controller style="width:100%;height:100%;border:none;min-height:640px;">
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
      </style>
      <script type="text/javascript">
        Joomla.submitbutton = function(task){
          jQuery('#adminForm')
            .find('input[name=task]')
            .val(task);
          jQuery('#adminForm')
            .find('input[name=rand]')
            .val(Math.random() * 1000);
          jQuery('#adminForm')
            .submit();
        }
      </script>
      <?php

  }

  /**
   * [init_database description]
   * @return [type] [description]
   */
  public function init_database(){

    // Local DB
      $this->local_db = JFactory::getDBO();

    // Remote DB
      $option = $this->getApplication()->getSession()->get('remote_db_options', array());
      if( $this->input->get('remote_db_host') ){
        $option['driver']   = 'mysqli';                                         // Database driver name
        $option['host']     = $this->input->get('remote_db_host', 'localhost'); // Database host name
        $option['user']     = $this->input->get('remote_db_user', '');          // User for database authentication
        $option['password'] = $this->input->get('remote_db_pass', '', false);   // Password for database authentication
        $option['database'] = $this->input->get('remote_db_name', '');          // Database name
        $option['prefix']   = $this->input->get('remote_db_prefix', 'jos_');    // Database prefix (may be empty)
        $this->getApplication()->getSession()->set('remote_db_options', $option);
      }

    // Connect
      $this->remote_db = JDatabaseDriver::getInstance( $option );

    // Return Status
      $this->remote_db->connect();
      return $this->remote_db->connected();

  }

  /**
   * [test_connection description]
   * @return [type] [description]
   */
  public function test_connection(){

    // Initialize Database
      $this->init_database();

    // Remote Database Status
      if( $this->remote_db->connected() ){
        JError::raiseNotice( 100, 'Database Connected' );
      }
      else {
        JError::raiseError( 400, 'Database NOT Connected' );
        $this->inspect( $this->remote_db );
      }

  }

}
