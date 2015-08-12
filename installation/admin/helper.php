<?php

if( !function_exists('inspect') ){
  function inspect(){
    echo '<pre>' . print_r( func_get_args(), true ) . '</pre>';
  }
}

class wbJoomigate_helper {

  public static function toolbar(){

    JToolBarHelper::title( JText::_( 'Joomla Migration Script' ), 'generic.png' );

    JToolBarHelper::custom( 'test_connection', 'cog.png', 'cog_f2.png', 'Test Connection', false );

    JToolBarHelper::custom( 'tablecopy', 'cog.png', 'cog_f2.png', 'Copy Tables', false );
    JToolBarHelper::custom( 'tablecopy.wbmember', 'cog.png', 'cog_f2.png', 'Copy wbMember', false );

    JToolBarHelper::custom( 'migrate.wbmember', 'cog.png', 'cog_f2.png', 'v2.5 - Merge wbMember', false );
    JToolBarHelper::custom( 'migrate.wbcatalog', 'cog.png', 'cog_f2.png', 'v2.5 - Merge wbCatalog', false );

    JToolBarHelper::custom( 'joomla_v15x.import_module', 'cog.png', 'cog_f2.png', 'v1.5 - Modules', false );
    JToolBarHelper::custom( 'joomla_v15x.import_content', 'cog.png', 'cog_f2.png', 'v1.5 - Content', false );
    JToolBarHelper::custom( 'joomla_v15x.import_menu', 'cog.png', 'cog_f2.png', 'v1.5 - Menu', false );

  }

}
