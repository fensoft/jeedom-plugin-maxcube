<?php
/* Copyright (C) fensoft <fensoft@gmail.com>
 * You can modify this file but you'll need to send to the author the modified files for future merging (or not, depending on the quality of your modifications)
 * You need to ask the permision of the author for redistribution/copying/forking
 */
 
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
<form class="form-horizontal">
    <fieldset>
        <legend>{{Général}}</legend>
        <div class="form-group expertModeVisible">
            <label class="col-lg-4 control-label">{{IP des MaxCube (séparés par des ,)}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="maxcube_ip" value='192.168.0.11' />
            </div>
        </div>
        <div class="form-group expertModeVisible">
            <label class="col-lg-4 control-label">{{Port des MaxCube (62910 par défaut)}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="maxcube_port" value='62910' />
            </div>
        </div>
        <div class="form-group expertModeVisible">
            <label class="col-lg-4 control-label">{{Port socket interne (7767 par défaut)}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="socketport" value='7767' />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">{{Mode debug}}</label>
            <div class="col-sm-2">
                <input type="checkbox" class="configKey" data-l1key="debug" checked="">
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">{{Debug}}</label>
            <div class="col-sm-2">
                <a class="label label-danger" href="<?php echo network::getNetworkAccess('internal', 'proto:ip') . ":" . config::byKey('socketport', 'maxcube'); ?>/get" target="_blank">{{Voir config JSON}}</a>
            </div>
        </div>
    </fieldset>
</form>
<script>
    function maxcube_postSaveConfiguration(){
        $.ajax({
            type: "POST",
             url: "plugins/maxcube/core/ajax/maxcube.ajax.php",
             data: {
                 action: "restart",
             },
             dataType: 'json',
             error: function (request, status, error) {
                 handleAjaxError(request, status, error);
             },
             success: function (data) {
                                  if (data.state != 'ok') {
                                          $('#div_alert').showAlert({message: data.result, level: 'danger'});
                                          return;
                                  }
                          }
                  });
          }


</script>
