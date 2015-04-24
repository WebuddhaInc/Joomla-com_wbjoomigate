<?php

if( !function_exists('inspect') ){
  function inspect(){
    echo '<pre>' . print_r( func_get_args(), true ) . '</pre>';
  }
}

class wbJoomigate_controller extends JControllerBase {

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
      )
    );

  /**
   * [execute description]
   * @return [type] [description]
   */
  public function execute(){

    $this->init_database();
    $this->init_datasets();
    $this->init_maps();
    $task_method = 'task_' . $this->input->getCmd('task');
    if( !method_exists( $this, $task_method ) ){
      $task_method = 'task_default';
    }
    $this->{ $task_method }();

  }

  /**
   * [init_database description]
   * @return [type] [description]
   */
  private function init_database(){

    $this->local_db = JFactory::getDBO();

    $option = array(); //prevent problems
    $option['driver']   = 'mysqli';             // Database driver name
    $option['host']     = 'localhost';          // Database host name
    $option['user']     = 'webuddha_j15v3';       // User for database authentication
    $option['password'] = '$W(#$=HFb5Wy';   // Password for database authentication
    $option['database'] = 'webuddha_j15v3';      // Database name
    $option['prefix']   = 'jos_';             // Database prefix (may be empty)
    $this->remote_db = JDatabaseDriver::getInstance( $option );

  }

  /**
   * [init_datasets description]
   * @return [type] [description]
   */
  private function init_datasets(){

    // Local Menu Types
      $this->data['menu_type']['local']
        = $this->local_db
          ->setQuery("
            SELECT *
            FROM #__menu_types
            ORDER BY `id`
          ")
          ->loadObjectList();

    // Remote Menu Types
      $this->data['menu_type']['remote']
        = $this->remote_db
          ->setQuery("
            SELECT *
            FROM #__menu_types
            ORDER BY `id`
          ")
          ->loadObjectList();

    // Local Categories
      $this->data['category']['local']
        = $this->local_db
          ->setQuery("
            SELECT *
            FROM #__categories
            WHERE `extension` = 'com_content'
            ORDER BY `lft`, `level`
          ")
          ->loadObjectList();

    // Remote Categories
      $this->data['category']['remote']
        = $this->remote_db
          ->setQuery("
            SELECT *
            FROM #__categories
          ")
          ->loadObjectList();

    // Local Menu
      $this->data['menu']['local']
        = $this->local_db
          ->setQuery("
            SELECT `menu`.*
            FROM `#__menu` AS `menu`
            ORDER BY `menu`.`menutype`
              , `menu`.`level`
              , `menu`.`lft`
          ")
          ->loadObjectList();

    // Remote Menu
      $this->data['menu']['remote']
        = $this->remote_db
          ->setQuery("
            SELECT `menu`.*
              , `component`.`option` AS `component_option`
            FROM `#__menu` AS `menu`
            LEFT JOIN `#__components` AS `component` ON `component`.`id` = `menu`.`componentid`
            ORDER BY `menu`.`menutype`
              , `menu`.`sublevel`
              , `menu`.`ordering`
          ")
          ->loadObjectList();

    // Remote Sections
      $this->data['section']['remote']
        = $this->remote_db
          ->setQuery("
            SELECT *
            FROM #__sections
          ")
          ->loadObjectList();

    // Local Articles
      $this->data['article']['local']
        = $this->local_db
          ->setQuery("
            SELECT `id`, `alias`, `catid`
            FROM `#__content`
          ")
          ->loadObjectList();

    // Remote Articles
      $this->data['article']['remote']
        = $this->remote_db
          ->setQuery("
            SELECT *
            FROM `#__content`
          ")
          ->loadObjectList();

  }

  /**
   * [init_maps description]
   * @return [type] [description]
   */
  private function init_maps(){

    // Menu Type
      foreach( $this->data['menu_type']['remote'] AS $remote_menu_type ){
        foreach( $this->data['menu_type']['local'] AS $local_menu_type ){
          if( $local_menu_type->menutype == $remote_menu_type->menutype ){
            $this->maps['menu_type'][ $remote_menu_type->id ] = $local_menu_type->id;
            break;
          }
        }
      }

    // Menu
      foreach( $this->data['menu_type']['remote'] AS $remote_menu_type ){
        $this->_map_remote_menu( 0, 0, $remote_menu_type );
      }

    // Sections > Local Categories
      foreach( $this->data['section']['remote'] AS $remote_content_section ){
        foreach( $this->data['category']['local'] AS $local_content_category ){
          if( $local_content_category->level == 1 && $local_content_category->alias == $remote_content_section->alias ){
            $this->maps['section'][ $remote_content_section->id ] = $local_content_category->id;
            break;
          }
        }
      }

    // Categories
      foreach( $this->maps['section'] AS $remote_section_id => $local_category_id ){
        foreach( $this->data['category']['remote'] AS $remote_content_category ){
          if(
            !(int)$remote_content_category->parent_id
            && $remote_content_category->section == $remote_section_id
            ){
            foreach( $this->data['category']['local'] AS $local_content_category ){
              if(
                $local_content_category->level == 2
                && $local_content_category->parent_id == $local_category_id
                && $local_content_category->alias == $remote_content_category->alias
                ){
                $this->maps['category'][ $remote_content_category->id ] = $local_content_category->id;
                break;
              }
            }
          }
        }
      }

    // Articles
      foreach( $this->data['article']['remote'] AS $remote_content_article ){
        foreach( $this->data['article']['local'] AS $local_content_article ){
          if(
            $local_content_article->alias == $remote_content_article->alias
            && (
              ($local_content_article->catid == $this->maps['category'][ $remote_content_article->catid ])
              ||
              ($local_content_article->catid = $this->maps['category']['0'] && (int)$remote_content_article->catid == 0)
              )
           ){
            $this->maps['article'][ $remote_content_article->id ] = $local_content_article->id;
            break;
          }
        }
      }

      inspect( $this->maps );die('123');

  }

  /**
   * [_map_remote_menu description]
   * @param  [type] $level             [description]
   * @param  [type] $parent_id         [description]
   * @param  [type] &$remote_menu_type [description]
   * @return [type]                    [description]
   */
  private function _map_remote_menu( $level, $parent_id, &$remote_menu_type ){
    foreach( $this->data['menu']['remote'] AS $remote_menu ){
      if(
        $remote_menu->menutype == $remote_menu_type->menutype
        && (int)$remote_menu->parent == (int)$parent_id
        && (int)$remote_menu->sublevel == (int)$level
        ){
        foreach( $this->data['menu']['local'] AS $local_menu ){
          if(
            $local_menu->menutype == $remote_menu_type->menutype
            && $local_menu->alias == $remote_menu->alias
            && (
              ((int)$local_menu->parent_id == 1 && !(int)$remote_menu->parent)
              || ($local_menu->parent_id == $this->maps['menu'][ $remote_menu->parent ])
              )
            ){
            $this->maps['menu'][ $remote_menu->id ] = $local_menu->id;
            break;
          }
        }
        $this->_map_remote_menu( $level + 1, $remote_menu->id, $remote_menu_type );
      }
    }
  }

  /**
   * [task_default description]
   * @return [type] [description]
   */
  private function task_default(){
    JHtml::_('behavior.core');
    ?>
    <form id="adminForm">
      <input type="hidden" name="option" value="com_wbjoomigate">
      <input type="hidden" name="task" value="">
      <ul>
        <li><a onclick="Joomla.submitbutton('import_content');">Import Content</a></li>
        <li><a onclick="Joomla.submitbutton('import_menu');">Import Menu</a></li>
      </ul>
      <div class="field text">
        <label for="uncategorized_catid">Uncategorized Catid</label>
        <input type="text" name="uncategorized_catid" value="2">
      </div>
    </form>
    <?php
  }

  /**
   * [task_import_menu description]
   * @return [type] [description]
   */
  private function task_import_menu(){

    // Flush
      $this->local_db->setQuery("
        DELETE FROM `#__menu`
        WHERE `id` > 105
        ")
        ->query();

    // Translate Menu Types
      foreach( $this->data['menu_type']['remote'] AS $remote_menu_type ){
        if( empty($this->maps['menu_type'][ $remote_menu_type->id ]) ){
          $menu_type = new JTableMenuType( $this->local_db );
          $menu_type->menutype = $remote_menu_type->menutype;
          $menu_type->title = $remote_menu_type->title;
          $menu_type->description = $remote_menu_type->description;
          if(
            !$menu_type->check()
            || !$menu_type->store()
            ){
            inspect( $menu_type->getErrors(), $remote_menu_type );
          }
          inspect( 'create menu_type', $menu_type->menutype );
          $this->maps['menu_type'][ $remote_menu_type->id ] = $menu_type->id;
        }
      }

    // Translate Menu Types
      foreach( $this->data['menu_type']['remote'] AS $remote_menu_type ){
        $this->_copy_remote_menu( 0, 0, $remote_menu_type );
        die();
      }

  }

  /**
   * [_copy_remote_menu description]
   * @param  [type] $level             [description]
   * @param  [type] $parent_id         [description]
   * @param  [type] &$remote_menu_type [description]
   * @return [type]                    [description]
   */
  private function _copy_remote_menu( $level, $parent_id, &$remote_menu_type ){
    foreach( $this->data['menu']['remote'] AS $remote_menu ){
      if(
        $remote_menu->menutype == $remote_menu_type->menutype
        && (int)$remote_menu->parent == (int)$parent_id
        && (int)$remote_menu->sublevel == (int)$level
        ){
        if( empty($this->maps['menu'][ $remote_menu->id ]) ){

          // Lookup Alias from same level other menu
            $alias = $remote_menu->alias;
            if( !(int)$remote_menu->parent){
              $count = 1;
              do {
                $duplicate_root_alias
                  = $this->local_db
                    ->setQuery("
                      SELECT COUNT(*)
                      FROM `#__menu`
                      WHERE `parent_id` = '1'
                        AND `alias` = '". $this->local_db->escape($alias) ."'
                      ")
                    ->loadResult();
                if( $duplicate_root_alias ){
                  $alias = $remote_menu->alias . '-' . $count++;
                }
              } while( $duplicate_root_alias );
            }

          // Create
            $menu = new JTableMenu( $this->local_db );
            $menu->bind( (array)$remote_menu );
            $menu->id        = null;
            $menu->alias     = $alias;
            $menu->level     = $remote_menu->sublevel;
            $menu->title     = $remote_menu->name;
            $menu->params    = json_encode( parse_ini_string($menu->params) );
            $menu->parent_id = ((int)$remote_menu->parent ? $this->maps['menu'][ $remote_menu->parent ] : 0);
            if( $remote_menu->component_option ){
              if( !isset($this->map['component_object'][ $remote_menu->componentid ]) ){
                $this->map['component_object'][ $remote_menu->componentid ]
                  = $this->local_db
                    ->setQuery("
                      SELECT *
                      FROM `#__extensions`
                      WHERE `type` = 'component'
                        AND `element` = '". $this->local_db->escape($remote_menu->component_option) ."'
                      ")
                    ->loadObject();
              }
              $menu->component_id
                = isset($this->map['component_object'][ $remote_menu->componentid ])
                ? $this->map['component_object'][ $remote_menu->componentid ]->id
                : 0;
            }
            if( $menu->type == 'component' ){
              switch( $menu->type ){
                case 'component':
                  if( isset($this->map['component_object'][ $remote_menu->componentid ]) ){
                    $link = parse_url( $menu->link );
                    parse_str( $link['query'], $query );
                    inspect( $link, $query, $menu, $remote_menu, $this->map );
                    inspect( $this->map['component_object'][ $remote_menu->componentid ] );
                    die();
                    die();
                  }
                  break;
              }
            }
            $menu->home      = 0;
            $menu->client_id = 0;
            $menu->language  = '*';
            $menu->setLocation($menu->parent_id, 'last-child');
            if(
              !$menu->check()
              || !$menu->store()
              || !$menu->rebuild()
              || !$menu->rebuildPath()
              ){
              inspect( $menu->getErrors(), $remote_menu );
              die();
            }
            inspect( 'create menu', $menu->parent_id.'.'.$menu->alias );
            $this->maps['menu'][ $remote_menu->id ] = $menu->id;
        }
        $this->_copy_remote_menu( $level + 1, $remote_menu->id, $remote_menu_type );
      }
    }
  }

  /**
   * [task_import_content description]
   * @return [type] [description]
   */
  private function task_import_content(){

    // Defaults
      $this->maps['category']['0'] = $this->input->getInt('uncategorized_catid', 2);

    // Port Remote Sections to Local Categories
      foreach( $this->data['section']['remote'] AS $remote_content_section ){
        if( empty($this->maps['section'][ $remote_content_section->id ]) ){
          $category = new JTableCategory( $this->local_db );
          $category->bind(array(
            'extension' => 'com_content',
            'title'     => $remote_content_section->title,
            'alias'     => $remote_content_section->alias,
            'published' => $remote_content_section->published,
            'level'     => 1
            ));
          $category->setLocation(1, 'last-child');
          if(
            !$category->check()
            || !$category->store()
            || !$category->rebuild()
            || !$category->rebuildPath()
            ){
            inspect( $category->getErrors() );
            die();
          }
          inspect( 'create category', $category->path );
          $this->maps['section'][ $remote_content_section->id ] = $category->id;
        }
      }

    // Port Remote Categories to Local Categories
      foreach( $this->maps['section'] AS $remote_section_id => $local_category_id ){
        foreach( $this->data['category']['remote'] AS $remote_content_category ){
          if(
            !(int)$remote_content_category->parent_id
            && $remote_content_category->section == $remote_section_id
            ){
            if( empty($this->maps['category'][ $remote_content_category->id ]) ){
              $category = new JTableCategory( $this->local_db );
              $category->bind(array(
                'extension' => 'com_content',
                'title'     => $remote_content_category->title,
                'alias'     => $remote_content_category->alias,
                'published' => $remote_content_category->published,
                'level'     => 2
                ));
              $category->setLocation($local_category_id, 'last-child');
              if(
                !$category->check()
                || !$category->store()
                || !$category->rebuild()
                || !$category->rebuildPath()
                ){
                inspect( $category->getErrors() );
                die();
              }
              inspect( 'create category', $category->path );
              $this->maps['category'][ $remote_content_category->id ] = $category->id;
            }
            $this->_copy_remote_categories( 3, $remote_content_category->id );
          }
        }
      }

    // Port Remote Content to Local Content
      foreach( $this->data['article']['remote'] AS $remote_content_article ){
        if( empty($this->maps['article'][ $remote_content_article->id ]) ){
          $article = new JTableContent( $this->local_db );
          $article->title            = $remote_content_article->title;
          $article->alias            = $remote_content_article->alias;
          $article->introtext        = $remote_content_article->introtext;
          $article->fulltext         = $remote_content_article->fulltext;
          $article->created          = $remote_content_article->created;
          $article->created_by       = $remote_content_article->created_by;
          $article->created_by_alias = $remote_content_article->created_by_alias;
          $article->modified         = $remote_content_article->modified;
          $article->modified_by      = $remote_content_article->modified_by;
          $article->state            = $remote_content_article->state;
          $article->publish_up       = $remote_content_article->publish_up;
          $article->publish_down     = $remote_content_article->publish_down;
          $article->images           = $remote_content_article->images;
          $article->urls             = $remote_content_article->urls;
          $article->checked_out      = $remote_content_article->checked_out;
          $article->checked_out_time = $remote_content_article->checked_out_time;
          $article->ordering         = $remote_content_article->ordering;
          $article->metakey          = $remote_content_article->metakey;
          $article->metadesc         = $remote_content_article->metadesc;
          $article->hits             = $remote_content_article->hits;
          $article->catid            = $this->maps['category'][ $remote_content_article->catid ];
          if(
            !$article->check()
            || !$article->store()
            ){
            inspect( $article->getErrors(), $article );
            die();
          }
          inspect( 'create article', $article->catid.'.'.$article->alias );
          $this->maps['article'][ $remote_content_article->id ] = $article->id;
        }
      }

    // Complete
      $this->app->redirect('index.php?option=com_wbjoomigate', 'Import Complete');

  }

  /**
   * Map nested categories - future use (not in j1.5.x)
   * @param  [type] $level                      [description]
   * @param  [type] $remote_content_category_id [description]
   * @param  [type] $this->maps['category']               [description]
   * @return [type]                             [description]
   */
  private function _copy_remote_categories( $level, $remote_content_category_id ){
    if( isset($this->maps['category'][ (int)$remote_content_category_id] ) ){
      foreach( $this->data['category']['remote'] AS $remote_content_category ){
        if( $remote_content_category->parent_id == $remote_content_category_id ){
          foreach( $this->data['category']['local'] AS $local_content_category ){
            if(
              $local_content_category->level == $level
              && $local_content_category->parent_id == $this->maps['category'][(int)$remote_content_category->parent_id]
              && $local_content_category->alias == $remote_content_category->alias
              ){
              $this->maps['category'][ $remote_content_category->id ] = $local_content_category->id;
              break;
            }
          }
          if( empty($this->maps['category'][ $remote_content_category->id ]) ){
            $category = new JTableCategory( $this->local_db );
            $category->bind(array(
              'extension' => 'com_content',
              'title'     => $remote_content_category->title,
              'alias'     => $remote_content_category->alias,
              'published' => $remote_content_category->published,
              'level'     => 2
              ));
            $category->setLocation($this->maps['category'][(int)$remote_content_category->parent_id], 'last-child');
            if(
              !$category->check()
              || !$category->store()
              || !$category->rebuild()
              || !$category->rebuildPath()
              ){
              inspect( $category->getErrors() );
              die();
            }
            inspect( 'create category', $category->path );
            $this->maps['category'][ $remote_content_category->id ] = $category->id;
          }
          $this->_copy_remote_categories( $level + 1, $remote_content_category->id );
        }
      }
    }

  }

}
