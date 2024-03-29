<?php
 if (!isConnect('admin')) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'maxcube');
$eqLogics = eqLogic::byType('maxcube');
?>

<div class="row row-overflow">
  <div class="col-lg-2 col-md-3 col-sm-4">
    <div class="bs-sidebar">
      <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
        <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
        <?php
          foreach ($eqLogics as $eqLogic) {
            echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName() . '</a></li>';
          }
        ?>
      </ul>
    </div>
  </div>
  
  

  <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
        <center>
          <i class="fa fa-plus-circle" style="font-size : 6em;color:#00979c;"></i>
        </center>
        <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#00979c"><center>{{Ajouter}}</center></span>
      </div>
      <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
        <center>
          <i class="fa fa-wrench" style="font-size : 6em;color:#767676;"></i>
        </center>
        <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
      </div>
    </div>
    
    <legend>{{Mes périphériques Max!}}</legend>
    <div class="eqLogicThumbnailContainer">
      <?php foreach ($eqLogics as $eqLogic) { ?>
        <?php
          $device = maxcube::getDevice($eqLogic->getConfiguration('rf_address', ''));
          if (! $device || $device["devicetype"] == "")
            $icon = "error.png";
          else
            $icon = $device["devicetype"] . "/icon.jpg";
        ?>
        <div class="eqLogicDisplayCard cursor" data-eqLogic_id="<?php echo $eqLogic->getId(); ?>" style="background-color : #ffffff ; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
          <center><img src="plugins/maxcube/core/config/devices/<?php echo $icon; ?>" height="105" width="105" /></center>
          <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;word-wrap: break-word;">
             <center><?php echo $eqLogic->getHumanName(true, true); ?></center>
          </span>
        </div>
      <?php } ?>
    </div>
  </div>

  <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
    <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
    <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>

    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation"><a class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
      <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
      <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>

    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
        <form class="form-horizontal">
          <fieldset>
            <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}  <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Nom de l'équipement maxcube}}</label>
              <div class="col-sm-3">
                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement maxcube}}"/>
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label" >{{Objet parent}}</label>
              <div class="col-sm-3">
                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                  <option value="">{{Aucun}}</option>
                  <?php
                    foreach (jeeObject::all() as $object) {
                      echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                    }
                  ?>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Catégorie}}</label>
              <div class="col-sm-8">
                <?php
                  foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                    echo '<label class="checkbox-inline">';
                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                    echo '</label>';
                  }
                ?>
              </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label" ></label>
              <div class="col-sm-8">
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
              </div>
            </div>
        
            <div class="form-group">
              <label class="col-sm-3 control-label" >{{Objet MaxCube}}</label>
              <div class="col-sm-3">
                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="rf_address">
                  <option value="">{{Aucun}}</option>
                  <?php
                    foreach (maxcube::getRooms() as $room_name => $room) {
                      if ($room_name != "0")
                        echo '<option style="font-weight: bold;" value="" disabled>&#8594; ' . $room_name . ' &#8592; </option>';
                      foreach ($room as $device) {
                        if (! $device || (array_key_exists("device_type", $device) && !in_array($device["device_type"], array(1, 2, 3, 4))) || ($room_name == "0" && $device["device_type"] == "4"))
                          continue;
                        echo '<option class="fa ' . maxcube::typeToIcon($device["devicetype"]) . '" style="display:block;" value="' . $device["rf_address"] . '">';
                        if ($room_name != "0")
                          echo $room_name . "::";
                        echo $device["device_name"] . ' ' . maxcube::typeToString($device["devicetype"]) . '</option>';
                      }
                    }
                  ?>
                </select>
              </div>
            </div>
        
            <div class="form-group">
              <label class="col-sm-3 control-label" >{{Thermostat}}</label>
              <div class="col-sm-3">
                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="thermostat">
                  <option value="">{{Aucun}}</option>
                  <?php
                    foreach(eqLogic::byType('thermostat') as $thermostat)
                      echo '<option value="' . $thermostat->getID() . '">' . ($thermostat->getObject() ? $thermostat->getObject()->getName() . " - " : "") . $thermostat->getName() . '</option>';
                  ?>
                </select>
              </div>
            </div>
          </fieldset>
        </form>
      </div>

      <div role="tabpanel" class="tab-pane" id="commandtab">
        <legend>{{MaxCube}}</legend>
        <table id="table_cmd" class="table table-bordered table-condensed">
          <thead>
            <tr>
              <th style="width: 300px;">{{Nom}}</th>
              <th style="width: 130px;" class="expertModeVisible">{{Type}}</th>
              <th class="expertModeVisible">{{Logical ID (info) ou Commande brute (action)}}</th>
              <th>{{Paramètres}}</th>
              <th style="width: 100px;">{{Options}}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include_file('desktop', 'maxcube', 'js', 'maxcube');?>
<?php include_file('core', 'plugin.template', 'js');?>
