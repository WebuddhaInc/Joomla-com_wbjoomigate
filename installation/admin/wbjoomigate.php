<?php

// Load
  require_once('controller.php');
  require_once('helper.php');

// Execute
  (new wbJoomigate_controller())->execute(
    JFactory::getApplication()->input->getCmd('task')
    );
