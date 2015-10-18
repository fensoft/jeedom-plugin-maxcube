<?php
/* Copyright (C) fensoft <fensoft@gmail.com>
 * You can modify this file but you'll need to send to the author the modified files for future merging (or not, depending on the quality of your modifications)
 * You need to ask the permision of the author for redistribution/copying/forking
 */
 
try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    
    if (init('action') == 'restart') {
      maxcube::restartDaemon();
      ajax::success();
    }
    
    if (init('action') == 'restartDebug') {
      maxcube::stopDaemon();
      maxcube::startDaemonDebug();
      ajax::success();
    }
    
    if (init('action') == 'stop') {
      maxcube::stopDaemon();
      ajax::success();
    }
    
    throw new Exception(__('Aucune methode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
