<?php

class wbJoomigate_controller_import_j15x extends wbJoomigate_controller {

  /**
   * [init_datasets description]
   * @return [type] [description]
   */
  private function init_datasets(){

    // Params
      $remote_version = $this->input->get('remote_joomla_version', '1.5');

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
      if( version_compare($remote_version, '2.5', '>=') ){
        $this->data['menu']['remote']
          = $this->remote_db
            ->setQuery("
              SELECT `menu`.*
              FROM `#__menu` AS `menu`
              ORDER BY `menu`.`menutype`
                , `menu`.`level`
                , `menu`.`lft`
            ")
            ->loadObjectList();
      }
      else {
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
      }

    // Remote Sections
      if( version_compare($remote_version, '2.5', '>=') ){
        $this->data['section']['remote']
          = array();
      }
      else {
        $this->data['section']['remote']
          = $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__sections`
            ")
            ->loadObjectList();
      }

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

    // Component
      $this->data['component']['local']
        = $this->local_db
          ->setQuery("
            SELECT *
            FROM `#__extensions`
            WHERE `type` = 'component'
            ")
          ->loadObjectList();

    // Component
      if( version_compare($remote_version, '2.5', '>=') ){
        $this->data['component']['remote']
          = $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__extensions`
              WHERE `type` = 'component'
              ")
            ->loadObjectList();
      }
      else {
        $this->data['component']['remote']
          = $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__components`
              WHERE `option` != ''
                AND `parent` = 0
              ")
            ->loadObjectList();
      }

    // Module
      $this->data['module']['local_extension']
        = $this->local_db
          ->setQuery("
            SELECT *
            FROM `#__extensions`
            WHERE `type` = 'module'
            ")
          ->loadObjectList();
      $this->data['module']['local']
        = $this->local_db
          ->setQuery("
            SELECT *
            FROM `#__modules`
            ")
          ->loadObjectList();

    // Module
      if( version_compare($remote_version, '2.5', '>=') ){
        $this->data['module']['remote_extension']
          = $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__extensions`
              WHERE `type` = 'module'
              ")
            ->loadObjectList();
        $this->data['module']['remote']
          = $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__modules`
              ")
            ->loadObjectList();
      }
      else {
        $this->data['module']['remote']
          = $this->remote_db
            ->setQuery("
              SELECT *
              FROM `#__modules`
              ")
            ->loadObjectList();
      }

    // Module Menu
      $this->data['module_menu']['local']
        = $this->local_db
          ->setQuery("
            SELECT *
            FROM `#__modules_menu`
            ")
          ->loadObjectList();

    // Module
      $this->data['module_menu']['remote']
        = $this->remote_db
          ->setQuery("
            SELECT *
            FROM `#__modules_menu`
            ")
          ->loadObjectList();

  }

  /**
   * [init_maps description]
   * @return [type] [description]
   */
  private function init_maps(){

    // Params
      $remote_version = $this->input->get('remote_joomla_version', '1.5');

    // Defaults
      $this->maps['category']['0'] = $this->input->getInt('uncategorized_catid', 0);

    // Component
      foreach( $this->data['component']['remote'] AS $remote_component ){
        foreach( $this->data['component']['local'] AS $local_component ){
          if( $local_component->element == $remote_component->option ){
            $this->maps['component'][ $remote_component->id ] = $local_component->extension_id;
            break;
          }
        }
      }

    // Module
      foreach( $this->data['module']['remote'] AS $remote_module ){
        foreach( $this->data['module']['local'] AS $local_module ){
          if(
            $local_module->position == $remote_module->position
            && $local_module->module == $remote_module->module
            && $local_module->ordering == $remote_module->ordering
            ){
            $this->maps['module'][ $remote_module->id ] = $local_module->id;
            break;
          }
        }
      }

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

    // Categories
      if( version_compare($remote_version, '2.5', '>=') ){

        // Categories
          foreach( $this->data['category']['remote'] AS $remote_content_category ){
            foreach( $this->data['category']['local'] AS $local_content_category ){
              if(
                $local_content_category->parent_id == $remote_content_category->parent_id
                && $local_content_category->alias == $remote_content_category->alias
                ){
                $this->maps['category'][ $remote_content_category->id ] = $local_content_category->id;
                break;
              }
            }
          }

      }
      else {

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
            && (
              $local_menu->alias == $remote_menu->alias
              ||
              preg_replace('/\-\d+$/','',$local_menu->alias) == $remote_menu->alias
              )
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
   * [import_users_v25 description]
   * @return [type] [description]
   */
  private function import_users_v25(){

    // Params
      if( version_compare( $this->input->get('remote_joomla_version', '1.5'), '2.5', '<') ){
        JError::raiseError( 400, 'Invalid Version' );
        return;
      }

    // Read Remote
      $batchCount = $this->input->getInt('batchCount', 0);
      $batchLimit = $this->input->getInt('batchLimit', 100);
      $rows = $this->local_db
        ->setQuery("
          SELECT *
          FROM `#__users`
          ORDER BY `id`
          LIMIT {$batchCount}, {$batchLimit}
          ")
        ->loadObjectList();
      while( count($rows) ){
        $remote_row = array_shift( $rows );
        $local_user = new JTableUser( $this->local_db );
        $local_user->load( $remote_row->id );
        $local_user->bind( (array)$remote_user );
        $this->inspect( $local_user );
        die('123');
      }

  }

  /**
   * [import_content_v25 description]
   * @return [type] [description]
   */
  private function import_content_v25(){

    // Params
      if( version_compare( $this->input->get('remote_joomla_version', '1.5'), '2.5', '<') ){
        JError::raiseError( 400, 'Invalid Version' );
        return;
      }

    // Read Remote
      $batchCount = $this->input->getInt('batchCount', 0);
      $batchLimit = $this->input->getInt('batchLimit', 100);
      $rows = $this->local_db
        ->setQuery("
          SELECT *
          FROM `#__users`
          ORDER BY `id`
          LIMIT {$batchCount}, {$batchLimit}
          ")
        ->loadObjectList();
      while( count($rows) ){
        $remote_row = array_shift( $rows );
        $local_user = new JTableUser( $this->local_db );
        $local_user->load( $remote_row->id );
        $local_user->bind( (array)$remote_user );
        $this->inspect( $local_user );
        die('123');
      }

  }

  /**
   * [import_module description]
   * @return [type] [description]
   */
  public function import_module(){

    // Params
      if( version_compare( $this->input->get('remote_joomla_version', '1.5'), '1.5', '>') ){
        JError::raiseError( 400, 'Invalid Version' );
        return;
      }

    // Setup
      $this->init_database();
      $this->init_datasets();
      $this->init_maps();

    // Translate Modules
      foreach( $this->data['module']['remote'] AS $remote_module ){
        if( empty($this->maps['module'][ $remote_module->id ]) ){
          $mod_element = $remote_module->module;
          switch( $mod_element ){
            case 'mod_mainmenu':
              $mod_element = 'mod_mainmenu';
              break;
          }
          foreach( $this->data['module']['local_extension'] AS $local_module_extension ){
            if( $local_module_extension->element == $mod_element ){
              $module = new JTableModule( $this->local_db );
              $module->bind( (array)$remote_module );
              $module->id          = null;
              $module->checked_out = 0;
              if(
                !$module->check()
                || !$module->store()
                ){
                $this->inspect( $module->getErrors(), $remote_module );
                return;
              }
              $this->inspect( 'create module: ' . $module->position.'.'.$module->module );
              $this->maps['module'][ $remote_module->id ] = $module->id;
              $this->data['module']['local'][] = $module->getProperties();
            }
          }
        }
      }

    // Translate Modules Menu
      foreach( $this->data['module']['remote'] AS $remote_module ){
        if( isset($this->maps['module'][ $remote_module->id ]) ){
          foreach( $this->data['module_menu']['remote'] AS $remote_module_menu ){
            if(
              isset($this->maps['menu'][ $remote_module_menu->menuid ])
              &&  $remote_module->id == $remote_module_menu->moduleid
              ){
              $found = false;
              foreach( $this->data['module_menu']['local'] AS $local_module_menu ){
                if(
                  $local_module_menu->moduleid == $this->maps['module'][ $remote_module_menu->moduleid ]
                  && $local_module_menu->menuid == $this->maps['menu'][ $remote_module_menu->menuid ]
                  ){
                  $found = true;
                  break;
                }
              }
              if( !$found ){
                $module_menu = array(
                  'moduleid' => $this->maps['module'][ $remote_module_menu->moduleid ],
                  'menuid'   => $this->maps['menu'][ $remote_module_menu->menuid ]
                  );
                $this->local_db
                  ->setQuery(
                    $this->local_db
                    ->getQuery(true)
                    ->insert( $this->local_db->quoteName('#__modules_menu') )
                    ->columns( $this->local_db->quoteName(array_keys($module_menu)) )
                    ->values( implode(',', array_values($module_menu)) )
                    )
                  ->query();
                $this->inspect( 'create module_menu: ' . $module_menu['moduleid'] . '-' . $module_menu['menuid'] );
                $this->data['module_menu']['local'][] = $module_menu;
              }
            }
          }
        }
      }

    // Complete
      $this->app->enqueueMessage('Module Import Complete');
      // $this->app->redirect('index.php?option=com_wbjoomigate', 'Import Complete');

  }

  /**
   * [import_menu description]
   * @return [type] [description]
   */
  public function import_menu(){

    // Params
      if( version_compare( $this->input->get('remote_joomla_version', '1.5'), '1.5', '>') ){
        JError::raiseError( 400, 'Invalid Version' );
        return;
      }

    // Setup
      $this->init_database();
      $this->init_datasets();
      $this->init_maps();

    // Translate Menu Types
      foreach( $this->data['menu_type']['remote'] AS $remote_menu_type ){
        if( empty($this->maps['menu_type'][ $remote_menu_type->id ]) ){
          $menu_type = new JTableMenuType( $this->local_db );
          $menu_type->bind( array(
            'menutype'    => $remote_menu_type->menutype,
            'title'       => $remote_menu_type->title,
            'description' => $remote_menu_type->description
            ));
          if(
            !$menu_type->check()
            || !$menu_type->store()
            ){
            $this->inspect( $menu_type->getErrors(), $remote_menu_type );
            return;
          }
          $this->inspect( 'create menu_type: ' . $menu_type->menutype );
          $this->maps['menu_type'][ $remote_menu_type->id ] = $menu_type->id;
          $this->data['menu_type']['local'][] = $menu_type->getProperties();
        }
      }

    // Translate Menu Types
      foreach( $this->data['menu_type']['remote'] AS $remote_menu_type ){
        $this->_copy_remote_menu( 0, 0, $remote_menu_type );
      }

    // Complete
      $this->app->enqueueMessage('Menu Import Complete');
      // $this->app->redirect('index.php?option=com_wbjoomigate', 'Import Complete');

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
            $menu->id           = null;
            $menu->checked_out  = 0;
            $menu->alias        = $alias;
            $menu->level        = $remote_menu->sublevel;
            $menu->title        = $remote_menu->name;
            $menu->params       = json_encode( parse_ini_string($menu->params) );
            $menu->parent_id    = ((int)$remote_menu->parent ? $this->maps['menu'][ $remote_menu->parent ] : 0);
            $menu->home         = 0;
            $menu->client_id    = 0;
            $menu->language     = '*';
            $menu->component_id = isset($this->maps['component'][ $remote_menu->componentid ])
                                  ? $this->maps['component'][ $remote_menu->componentid ]
                                  : 0;
            if( $menu->type == 'component' ){
              switch( $menu->type ){
                case 'component':
                  switch( $remote_menu->component_option ){
                    case 'com_content':
                      $link = parse_url( $menu->link );
                      if( !empty($link) ){
                        parse_str( $link['query'], $query );
                        if( !empty($query) && !empty($query['id']) && isset($this->maps['article'][ $query['id'] ]) ){
                          $query['id'] = $this->maps['article'][ $query['id'] ];
                          }
                        $menu->link = $link['path'] . '?' . http_build_query($query);
                      }
                      break;
                  }
                  break;
              }
            }
            $menu->setLocation($menu->parent_id, 'last-child');
            if(
              !$menu->check()
              || !$menu->store()
              || !$menu->rebuild()
              || !$menu->rebuildPath()
              ){
              $this->inspect( $menu->getErrors(), $remote_menu );
              return;
            }
            $this->inspect( 'create menu', $menu->parent_id.'.'.$menu->alias );
            $this->maps['menu'][ $remote_menu->id ] = $menu->id;
            $this->data['menu']['local'][] = $menu->getProperties();
        }
        $this->_copy_remote_menu( $level + 1, $remote_menu->id, $remote_menu_type );
      }
    }
  }

  /**
   * [import_content description]
   * @return [type] [description]
   */
  public function import_content(){

    // Params
      if( version_compare( $this->input->get('remote_joomla_version', '1.5'), '1.5', '>') ){
        JError::raiseError( 400, 'Invalid Version' );
        return;
      }

    // Setup
      $this->init_database();
      $this->init_datasets();
      $this->init_maps();

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
            $this->inspect( $category->getErrors(), $remote_content_section );
            return;
          }
          $this->inspect( 'create category', $category->path );
          $this->maps['section'][ $remote_content_section->id ] = $category->id;
          $this->data['section']['local'][] = $section->getProperties();
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
                $this->inspect( $category->getErrors(), $remote_content_category );
                return;
              }
              $this->inspect( 'create category',  $category->path );
              $this->maps['category'][ $remote_content_category->id ] = $category->id;
              $this->data['category']['local'][] = $category->getProperties();
            }
            $this->_copy_remote_categories( 3, $remote_content_category->id );
          }
        }
      }

    // Port Remote Content to Local Content
      foreach( $this->data['article']['remote'] AS $remote_content_article ){
        if( empty($this->maps['article'][ $remote_content_article->id ]) ){
          $article = new JTableContent( $this->local_db );
          $article->bind( (array)$remote_content_article );
          $article->id          = null;
          $article->checked_out = 0;
          $article->catid       = $this->maps['category'][ $remote_content_article->catid ];
          if(
            !$article->check()
            || !$article->store()
            ){
            $this->inspect( $article->getErrors(), $remote_content_article );
            return;
          }
          $this->inspect( 'create article', $article->catid.'.'.$article->alias );
          $this->maps['article'][ $remote_content_article->id ] = $article->id;
          $this->data['article']['local'][] = $article->getProperties();
        }
      }

    // Complete
      $this->app->enqueueMessage('Content Import Complete');
      // $this->app->redirect('index.php?option=com_wbjoomigate', 'Import Complete');

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
              $this->inspect( $category->getErrors(), $remote_content_category );
              return;
            }
            $this->inspect( 'create category', $category->path );
            $this->maps['category'][ $remote_content_category->id ] = $category->id;
            $this->data['category']['local'][] = $category->getProperties();
          }
          $this->_copy_remote_categories( $level + 1, $remote_content_category->id );
        }
      }
    }

  }

}