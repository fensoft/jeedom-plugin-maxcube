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
            <label class="col-lg-4 control-label">{{IP du MaxCube}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="maxcube_ip" value='192.168.0.11' />
            </div>
        </div>
        <div class="form-group expertModeVisible">
            <label class="col-lg-4 control-label">{{Port du MaxCube (62910 par défaut)}}</label>
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
            <label class="col-sm-4 control-label">{{Gestion du démon}}</label>
            <div class="col-sm-8">
                <a class="btn btn-success" id="bt_maxcube_restart"><i class='fa fa-play'></i> {{(Re)démarrer}}</a>
                <a class="btn btn-danger" id="bt_maxcube_stop"><i class='fa fa-stop'></i> {{Arrêter}}</a>
            </div>
        </div>
    </fieldset>
</form>
<script>
    $('#bt_maxcube_restart').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/maxcube/core/ajax/maxcube.ajax.php", // url du fichier php
            data: {
                action: "restartDeamon"
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#div_alert').showAlert({message: '{{Le démon a été correctement (re)demarré}}', level: 'success'});
        }
    });
    });
    
    $('#bt_maxcube_stop').on('click', function () {
        $.ajax({// fonction permettant de faire de l'ajax
            type: "POST", // methode de transmission des données au fichier php
            url: "plugins/maxcube/core/ajax/maxcube.ajax.php", // url du fichier php
            data: {
                action: "stopDeamon"
            },
            dataType: 'json',
            error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            $('#div_alert').showAlert({message: '{{Le démon a été correctement arreté}}', level: 'success'});
        }
    });
    });
    
    function maxcube_postSaveConfiguration(){
        $.ajax({
            type: "POST",
             url: "plugins/maxcube/core/ajax/maxcube.ajax.php",
             data: {
                 action: "restartDeamon",
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