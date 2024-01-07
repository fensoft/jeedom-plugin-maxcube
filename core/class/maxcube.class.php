<?php
/* Copyright (C) fensoft <fensoft@gmail.com>
 * You can modify this file but you'll need to send to the author the modified files for future merging (or not, depending on the quality of your modifications)
 * You need to ask the permision of the author for redistribution/copying/forking
 */
 
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class maxcube extends eqLogic {
  public static $_excludeOnSendPlugin = array("node_modules", ".git", ".gitignore", ".DS_Store", ".gitmodules", "npm-debug.log", "pushtranslations.sh", "npm-cache", ".npmrc");

  public static $_devicetypes = array("1" => "RT", "2" => "RT", "3" => "WT", "4" => "WS");

  public static function pid() {
    return trim( shell_exec ('ps ax | grep "node maxnodeserver.js" | grep -v "grep" | awk \'{print $1}\'') );
  }

  public static function health() {
    $statusNode = (self::pid() != 0);
    $return = array();
    $return[] = array(
      'test' => 'Service',
      'result' => $statusNode ? 'OK' : 'NOK',
      'advice' => 'PID: ' . self::pid(),
      'state' => $statusNode,
    );
    return $return;
  }

  public static function dependancy_info() {
    $return = array();
    $return['log'] = 'maxcube_update';
    $return['progress_file'] = '/tmp/maxcube_in_progress';
    $state = 'ok';
    $v = str_replace("\n", "", shell_exec('node -v | sed "s/v//"'));
    if (version_compare($v, "5.10.0") < 0)
      $state = 'nok';
    if (!file_exists(dirname(__FILE__) . '/../../resources/maxcube.js/node_modules'))
      $state = 'nok';
    $return['state'] = $state;
    return $return;
  }

  public static function dependancy_install() {
    if (file_exists('/tmp/maxcube_in_progress')) {
      return;
    }

    log::remove('maxcube_update');
    $cmd = '/bin/bash -x ' . dirname(__FILE__) . '/../../resources/install_maxcube.sh';
    $cmd .= ' >> ' . log::getPathToLog('maxcube_update') . ' 2>&1 &';
    exec($cmd);
  }

  public static function deamon_info() {
    $return = array();
    $return['log'] = 'maxcube';
    $return['launchable'] = 'ok';
    if(self::pid() != 0)
      $return['state'] = 'ok';
    else
      $return['state'] = 'nok';
    return $return;
  }

  public static function deamon_stop() {
    if (self::pid() != 0) {
      $cmd = "kill " . self::pid();
      log::add('maxcube', 'debug', $cmd);
      shell_exec($cmd);
    }
  }

  public static function deamon_start() {
    if (config::byKey('maxcube_ip', 'maxcube') == "" || config::byKey('maxcube_port', 'maxcube') == "" || config::byKey('socketport', 'maxcube') == "")
      message::add("maxcube", " {{Erreur de configuration: cube non configuré}}");
    if (network::getNetworkAccess('internal', 'proto:ip:port:comp') == "")
      message::add("maxcube", " {{IP interne de jeedom non configurée}}");

    $path = realpath(dirname(__FILE__) . '/../..');
    $url = network::getNetworkAccess('internal', 'proto:ip:port:comp') . '/core/api/jeeApi.php?api=' . config::byKey('api',__CLASS__) . "&plugin=maxcube&type=event&method=update";
    $log = "/dev/null";
    if (config::byKey('debug', 'maxcube') == "1")
      $log = $path . "/../../log/maxcube_debug";
    $cmd = "cd " . $path . "/resources/maxcube.js && bash daemon.sh start " . $log . " " . config::byKey('maxcube_ip', 'maxcube') . " " . config::byKey('maxcube_port', 'maxcube') . " " . config::byKey('socketport', 'maxcube') . " - \"" . $url . "\" temp,valve,setpoint,link_error,battery_low,error,valid,state,mode,panel_locked ". config::byKey('debug', 'maxcube');
    log::add('maxcube', 'debug', $cmd);
    shell_exec($cmd);
  }

  static function applyEvent($_options) {
    $cmd = cmd::byId($_options["event_id"]);
    foreach (cmd::byEqLogicId($_options["id"]) as $key => $val) {
      if ($val->getType() == "info") {
        $cmd_id = str_replace('#', '', $val->getConfiguration('infoName'));
        if (! $cmd_id) {
          $val->event($_options["value"]);
        }
      }
    }
  }

  public function postSave() {
    $elogic = $this;
    $rf_address = $elogic->getConfiguration('rf_address', '');
    $thermostat = $elogic->getConfiguration('thermostat', '');

    if (is_object($elogic)) {
      $device = maxcube::getDevice($rf_address);
      $devicetype = self::$_devicetypes[$device["devicetype"]];
      if ($thermostat == "" && ($devicetype == "WT" || $devicetype == "RT")) {
        $setpointinputid = "setpoint_event";
        $setpointinputlogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), $setpointinputid);
        if (!is_object($setpointinputlogic)) {
          $cmd = new maxcubeCmd();
          $cmd->setIsHistorized(1);
          $cmd->setOrder(2);
          $cmd->setEqLogic_id($elogic->getId());
          $cmd->setEqType('maxcube');
          $cmd->setLogicalId($setpointinputid);
          $cmd->setName($cmd->getLogicalId());
          $cmd->setType('info');
          $cmd->setSubType('numeric');
          $cmd->setTemplate("dashboard","badge");
          $cmd->setTemplate("mobile","badge");
          foreach (array("showStatsOndashboard" => 0, "showStatsOnplan" => 0, "showStatsOnview" => 0, "showStatsOnmobile" => 0, "showNameOndashboard" => 0, "showNameOnplan" => 0, "showNameOnview" => 0, "showNameOnmobile" => 0, "forceReturnLineAfter" => 1) as $key => $val)
            $cmd->setDisplay($key, $val);
          $cmd->setIsVisible(true);
          $cmd->save();
          $setpointinputlogic = $cmd;
        }
        $setpointinputlogic->event($device["setpoint"]);

        $setpointid = "setpoint";
        $setpointlogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), $setpointid);
        if (!is_object($setpointlogic)) {
          $cmd = new maxcubeCmd();
          $cmds = $elogic->getCmd();
          $cmd->setOrder(1);
          $cmd->setEqLogic_id($elogic->getId());
          $cmd->setEqType('maxcube');
          $cmd->setLogicalId($setpointid);
          $cmd->setType('action');
          $cmd->setSubType('slider');
          $cmd->setConfiguration('minValue', "5");
          $cmd->setConfiguration('maxValue', "30");
          $cmd->setConfiguration('updateCmdToValue', "#slider#");
          $cmd->setConfiguration('value', "15");
          $cmd->setTemplate("dashboard","default");
          $cmd->setTemplate("mobile","default");
          foreach (array("showNameOndashboard" => 0, "showNameOnplan" => 0, "showNameOnview" => 0, "showNameOnmobile" => 0, "forceReturnLineAfter" => 1) as $key => $val)
            $cmd->setDisplay($key, $val);
          $cmd->setName("SetPoint");
          $cmd->setValue($setpointinputlogic->getId());
          $cmd->setConfiguration("updateCmdId", $setpointinputlogic->getId());
          $cmd->save();
          $setpointlogic = $cmd;
        }
      }

      if ($devicetype == "WT" || $devicetype == "RT") {
        $tempid = "temp";
        $templogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), $tempid);
        if (!is_object($templogic)) {
          $cmd = new maxcubeCmd();
          if ($thermostat == "")
            $cmd->setIsHistorized(1);
          $cmds = $elogic->getCmd();
          $order = count($cmds);
          $cmd->setOrder($order);
          $cmd->setEqLogic_id($elogic->getId());
          $cmd->setEqType('maxcube');
          $cmd->setConfiguration('minValue', "0");
          $cmd->setConfiguration('maxValue', "35");
          $cmd->setLogicalId($tempid);
          $cmd->setType('info');
          $cmd->setUnite('°C');
          $cmd->setSubType('numeric');
          $cmd->setTemplate("dashboard","default");
          $cmd->setTemplate("mobile","default");
          foreach (array("forceReturnLineAfter" => 1, "showStatsOndashboard" => 0, "showStatsOnplan" => 0, "showStatsOnview" => 0, "showStatsOnmobile" => 0) as $key => $val)
            $cmd->setDisplay($key, $val);
          $cmd->setName("Temp");
          $cmd->save();
          $templogic = $cmd;
        }
        $templogic->event($device["temp"]);
      }

      if ($devicetype == "RT") {
        $valveid = "valve";
        $valvelogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), $valveid);
        if (!is_object($valvelogic)) {
          $cmd = new maxcubeCmd();
          $cmd->setIsHistorized(1);
          $cmds = $elogic->getCmd();
          $order = count($cmds);
          $cmd->setOrder($order);
          $cmd->setEqLogic_id($elogic->getId());
          $cmd->setEqType('maxcube');
          $cmd->setConfiguration('minValue', "0");
          $cmd->setConfiguration('maxValue', "100");
          $cmd->setLogicalId($valveid);
          $cmd->setType('info');
          $cmd->setUnite('%');
          $cmd->setSubType('numeric');
          $cmd->setTemplate("dashboard","badge");
          $cmd->setTemplate("mobile","badge");
          foreach (array("showStatsOndashboard" => 0, "showStatsOnplan" => 0, "showStatsOnview" => 0, "showStatsOnmobile" => 0, "forceReturnLineAfter" => 1) as $key => $val)
            $cmd->setDisplay($key, $val);
          $cmd->setName("Valve");
          $cmd->save();
          $valvelogic = $cmd;
        }
        $valvelogic->event($device["valve"]);
      }

      if ($devicetype == "WS") {
        $stateid = "sensor";
        $statelogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), $stateid);
        if (!is_object($statelogic)) {
          $cmd = new maxcubeCmd();
          $cmds = $elogic->getCmd();
          $order = count($cmds);
          $cmd->setOrder($order);
          $cmd->setEqLogic_id($elogic->getId());
          $cmd->setEqType('maxcube');
          $cmd->setLogicalId($stateid);
          $cmd->setType('info');
          $cmd->setSubType('binary');
          $cmd->setTemplate("dashboard","window");
          $cmd->setTemplate("mobile","window");
          foreach (array("showNameOndashboard" => 0, "showNameOnplan" => 0, "showNameOnview" => 0, "showNameOnmobile" => 0, "forceReturnLineAfter" => 1) as $key => $val)
            $cmd->setDisplay($key, $val);
          $cmd->setName("Window Sensor");
          $cmd->save();
          $statelogic = $cmd;
        }
        $statelogic->event($device["state"] == "closed");
      }

      if ($devicetype == "WT" || $devicetype == "RT") {
        $stateid = "mode";
        $statelogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), $stateid);
        if (!is_object($statelogic)) {
          $cmd = new maxcubeCmd();
          $cmds = $elogic->getCmd();
          $order = count($cmds);
          $cmd->setOrder($order);
          $cmd->setEqLogic_id($elogic->getId());
          $cmd->setEqType('maxcube');
          $cmd->setLogicalId($stateid);
          $cmd->setType('info');
          $cmd->setSubType('string');
          $cmd->setTemplate("dashboard","window");
          $cmd->setTemplate("mobile","window");
          foreach (array("forceReturnLineAfter" => 1) as $key => $val)
            $cmd->setDisplay($key, $val);
          $cmd->setName("Mode");
          $cmd->save();
          $statelogic = $cmd;
        }
        $statelogic->event($device["mode"]);

        foreach (array("Boost", "Auto", "Manual", "On", "Off") as $name) {
          $stateid = "mode_" . strtolower($name);
          $statelogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), $stateid);
          if (!is_object($statelogic)) {
            $cmd = new maxcubeCmd();
            $cmd->setName($name);
            $cmd->setOrder(100);
            $cmd->setEqLogic_id($elogic->getId());
            $cmd->setEqType('maxcube');
            $cmd->setLogicalId($stateid);
            $cmd->setType('action');
            $cmd->setSubType('other');
            $cmd->save();
          }
        }
      }
    }

    if ($thermostat != "") {
      $th = eqLogic::byId($thermostat);
      $th->setConfiguration('temperature_indoor', "#" . maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), "temp")->getId() . "#");
      $th->save();

      $listener = listener::byClassAndFunction('maxcube', 'applyListenerEvent', array('id' => intval($this->getId())));
      if (!is_object($listener)) {
        $listener = new listener();
        $listener->setClass('maxcube');
        $listener->setFunction('applyListenerEvent');
        $listener->setOption(array('id' => intval($this->getId())));
      }
      $listener->emptyEvent();
      $cmd = cmd::byEqLogicIdAndLogicalId($thermostat, "order");
      $listener->addEvent($cmd->getId());
      $listener->save();
    }
  }

  public static function applyListenerEvent($_options) {
    $eqLogic = eqLogic::byId($_options["id"]);
    maxcube::setCubeSetpoint($eqLogic->getConfiguration('rf_address'), $_options["value"]);
  }

  public static function getLogicFromAddress($rf_address) {
    foreach (self::all() as $elogic) {
      if ($elogic->getConfiguration("rf_address") == $rf_address)
        return $elogic;
    }
  }

  public static function event() {
    $rf_address = init("rf_address");
    $device = self::getDevice($rf_address);
    $elogic = self::getLogicFromAddress($rf_address);
    $thermostat = $elogic->getConfiguration('thermostat', '');

    if (!$elogic) {
      log::add("maxcube", 'debug', "skip update " . $rf_address . " " . $property . " " . init("value"));
      return;
    }

    $property = init("property");
    $method = init("method");

    if ($method == "update" && $property != "lastUpdate") {
      log::add("maxcube", 'debug', "update " . $device["room_name"] . "/" . $device["device_name"] . " (" . $rf_address . ") " . $property . "=" . init("value") . "; thermostat is " . $thermostat);
      switch ($property) {
        case "setpoint":
          $temp = init("value");
          $mode = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), "mode");
          if ($temp == "4.5")
            $mode->event("Off");
          elseif ($temp == "30.5")
            $mode->event("On");
          elseif ($thermostat == "") {
            $setpointlogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), "setpoint_event");
            $setpointlogic->event($temp);
          } else {
            $cmdo = maxcubeCmd::byEqLogicIdAndLogicalId($thermostat, "order");
            $cmdo->event($temp);
          }
          break;
        case "temp":
          $templogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), "temp");
          $value = init("value");
          $templogic->event($value);
          break;
        case "valve":
          $valvelogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), "valve");
          $valvelogic->event(init("value"));
          break;
        case "state":
          $statelogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), "sensor");
          $statelogic->event(init("value") == "closed");
          break;
        case "mode":
          $statelogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), "mode");
          if ($statelogic)
            $statelogic->event(init("value"));
          break;
        case "battery_low":
          $battery = (init("value") == "false") ? 100 : 1;
          log::add('maxcube', 'debug', "battery_low " . $rf_address . " = " . $battery);
          $elogic->batteryStatus($battery);
          $elogic->checkAndUpdateCmd('battery', $battery);
          $elogic->setConfiguration('battery',$battery);
          if (init("value") == "true")
            message::add("maxcube", " {{Batterie faible sur le module}} " . $elogic->getObject()->getName() . "/" . $elogic->getName());
          break;
        case "error":
          if (init("value") == "true")
            message::add("maxcube", " {{Erreur avec le module}} " . $elogic->getObject()->getName() . "/" . $elogic->getName());
          break;
        case "link_error":
          if (init("value") == "true")
            message::add("maxcube", "{{Erreur de communication avec le module}} " . $elogic->getObject()->getName() . "/" . $elogic->getName());
          break;
        default:
          log::add("maxcube", 'debug', "error: unhandled " . $rf_address . " " . $property . " " . init("value"));
      }
    }
  }

  public static function getRooms() {
    $url = "http://" . config::byKey('internalAddr') . ":" . config::byKey('socketport', 'maxcube') . "/get";
    return json_decode(file_get_contents($url), true);
  }

  public static function setCubeSetpoint($rf_address, $value) {
    $cfg = self::getRooms();
    $device = self::getDevice($rf_address);
    if ($device["room_id"] != "")
      $rf_address = $device["group_rf_address"];
    return json_decode(file_get_contents("http://" . config::byKey('internalAddr') . ":" . config::byKey('socketport', 'maxcube') . "/set/" . $rf_address . "/" . $value), true);
  }

  public static function setBoost($rf_address, $value) {
    $cfg = self::getRooms();
    $device = self::getDevice($rf_address);
    if ($device["room_id"] != "")
      $rf_address = $device["group_rf_address"];
    return json_decode(file_get_contents("http://" . config::byKey('internalAddr') . ":" . config::byKey('socketport', 'maxcube') . "/boost/" . $rf_address . "/" . $value), true);
  }

  public static function setAuto($rf_address, $value) {
    $cfg = self::getRooms();
    $device = self::getDevice($rf_address);
    if ($device["room_id"] != "")
      $rf_address = $device["group_rf_address"];
    return json_decode(file_get_contents("http://" . config::byKey('internalAddr') . ":" . config::byKey('socketport', 'maxcube') . "/auto/" . $rf_address . "/" . $value), true);
  }

  public static function getDevice($rf) {
    foreach (self::getRooms() as $room) {
      foreach ($room as $room_name => $device) {
        if ($device["rf_address"] == $rf) {
          $device["room_name"] = $room_name;
          return $device;
        }
      }
    }
    return array();
  }

  public static function typeToString($type) {
    switch ($type) {
      case "1":
        return "{{[Radiateur]}}";
      case "2":
        return "{{[Radiateur+]}}";
      case "3":
        return "{{[Thermostat]}}";
      case "4":
        return '{{[Ouverture]}}';
    }
    return "[" . $type . "]";
  }

  public static function typeToIcon($type) {
  switch ($type) {
    case "1":
      return "techno-heating3";
    case "2":
      return "techno-heating3";
    case "3":
      return "jeedom-thermometre";
    case "4":
      return 'jeedom-fenetre-ouverte';
  }
  return "";
  }
}

class maxcubeCmd extends cmd {
  public function execute($_options = array()) {
    $eqLogic = $this->getEqLogic();
    switch ($this->getLogicalId()) {
      case "setpoint":
        log::add('maxcube', 'debug', "setpoint " . $eqLogic->getConfiguration('rf_address') . " to " . $_options["slider"]);
        maxcube::setCubeSetpoint($eqLogic->getConfiguration('rf_address'), $_options["slider"]);
        break;
      case "mode_boost":
        $setpoint = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), "setpoint_event")->execCmd();
        log::add('maxcube', 'debug', "boost " . $eqLogic->getConfiguration('rf_address'));
        maxcube::setBoost($eqLogic->getConfiguration('rf_address'), $setpoint);
        break;
      case "mode_auto":
        $setpoint = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), "setpoint_event")->execCmd();
        log::add('maxcube', 'debug', "auto " . $eqLogic->getConfiguration('rf_address'));
        maxcube::setAuto($eqLogic->getConfiguration('rf_address'), $setpoint);
        break;
      case "mode_manual":
        $setpoint = cmd::byEqLogicIdAndLogicalId($eqLogic->getId(), "setpoint_event")->execCmd();
        log::add('maxcube', 'debug', "manual " . $eqLogic->getConfiguration('rf_address') . " to " . $setpoint);
        maxcube::setCubeSetpoint($eqLogic->getConfiguration('rf_address'), $setpoint);
        break;
      case "mode_on":
        log::add('maxcube', 'debug', "mode on " . $eqLogic->getConfiguration('rf_address'));
        maxcube::setCubeSetpoint($eqLogic->getConfiguration('rf_address'), 30.5);
        break;
      case "mode_off":
        log::add('maxcube', 'debug', "mode off " . $eqLogic->getConfiguration('rf_address'));
        maxcube::setCubeSetpoint($eqLogic->getConfiguration('rf_address'), 4.5);
        break;
      default:
        log::add('maxcube', 'debug', "unknown action " . $this->getLogicalId());
    }
  }
}

?>
