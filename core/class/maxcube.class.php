<?php
/* Copyright (C) fensoft <fensoft@gmail.com>
 * You can modify this file but you'll need to send to the author the modified files for future merging (or not, depending on the quality of your modifications)
 * You need to ask the permision of the author for redistribution/copying/forking
 */
 
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class maxcube extends eqLogic {
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
    
  public static function cron() {
    if (self::pid() == 0 && config::byKey('daemon','maxcube') != "0")
      self::startDaemon();
  }
    
  public static function restartDaemon() {
    config::save('daemon','1','maxcube');
    self::stopDaemon();
    self::startDaemon();
  }
    
  public static function stopDaemon() {
    if (self::pid() != 0) {
      $cmd = "kill " . self::pid();
      log::add('maxcube', 'debug', $cmd);
      shell_exec($cmd);
      config::save('daemon','0','maxcube');
    }
  }
    
  public static function startDaemon() {
    $path = realpath(dirname(__FILE__) . '/../..');
    $url = network::getNetworkAccess('internal', 'proto:ip:port:comp') . '/core/api/jeeApi.php?api=' . config::byKey('api') . "&type=maxcube&method=update";
    $cmd = "cd " . $path . "/3rdparty/maxcube && bash daemon.sh start " . config::byKey('maxcube_ip', 'maxcube') . " " . config::byKey('maxcube_port', 'maxcube') . " " . config::byKey('socketport', 'maxcube') . " - \"" . $url . "\" temp,valve,setpoint,link_error,battery_low,error,valid,state";
    log::add('maxcube', 'debug', $cmd);
    shell_exec($cmd);
  }

  static function applyEvent($_options) {
    $cmd = cmd::byId($_options["event_id"]);
    log::add('maxcube', 'debug', "applyEvent " . json_encode($_options) . " " . $cmd->getEqLogic()->getName() . "@" . $cmd->getName());
    foreach (cmd::byEqLogicId($_options["id"]) as $key => $val) {
      if ($val->getType() == "info") {
        $cmd_id = str_replace('#', '', $val->getConfiguration('infoName'));
        if (! $cmd_id) {
          $val->event($_options["value"]);
          log::add('maxcube', 'debug', "cmd_" . $val->getId() . "=" . $_options["value"]);
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
      log::add('maxcube', 'debug', $devicetype);
      if ($thermostat == "" && ($devicetype == "WT" || $devicetype == "RT")) {
        $setpointinputid = "setpoint_event";
        $setpointinputlogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), $setpointinputid);
        if (!is_object($setpointinputlogic)) {
          $cmd = new maxcubeCmd();
          $cmd->setEventOnly(1);
          $cmd->setIsHistorized(1);
          $cmd->setOrder(count($elogic->getCmd()));
          $cmd->setEqLogic_id($elogic->getId());
          $cmd->setEqType('maxcube');
          $cmd->setLogicalId($setpointinputid);
          $cmd->setName($cmd->getLogicalId());
          $cmd->setType('info');
          $cmd->setSubType('numeric');
          $cmd->setIsVisible(false);
          $cmd->save();
          $setpointinputlogic = $cmd;
        }
          
        $setpointid = "setpoint";
        $setpointlogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), $setpointid);
        if (!is_object($setpointlogic)) {
          $cmd = new maxcubeCmd();
          $cmd->setEventOnly(0);
          $cmds = $elogic->getCmd();
          $order = count($cmds);
          $cmd->setOrder($order);
          $cmd->setEqLogic_id($elogic->getId());
          $cmd->setEqType('maxcube');
          $cmd->setLogicalId($setpointid);
          $cmd->setType('action');
          $cmd->setSubType('slider');
          $cmd->setConfiguration('minValue', "5");
          $cmd->setConfiguration('maxValue', "30");
          $cmd->setConfiguration('updateCmdToValue', "#slider#");
          $cmd->setConfiguration('value', "15");
          $cmd->setTemplate("dashboard","thermostat" );
          $cmd->setTemplate("mobile","thermostat" );
          $cmd->setDisplay('parameters',array('displayName' => 1));
          $cmd->setName("SetPoint");
          $cmd->setValue($setpointinputlogic->getId());
          $cmd->setConfiguration("updateCmdId", $setpointinputlogic->getId());
          $cmd->save();
          $setpointlogic = $cmd;
        }
        $setpointinputlogic->event($device["setpoint"]);
      }
      
      if ($devicetype == "WT" || $devicetype == "RT") {
        $tempid = "temp";
        $templogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), $tempid);
        if (!is_object($templogic)) {
          $cmd = new maxcubeCmd();
          $cmd->setEventOnly(1);
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
          if ($thermostat != "")
            $cmd->setTemplate("dashboard","badge" );
          else
            $cmd->setTemplate("dashboard","thermometre" );
          $cmd->setTemplate("mobile","badge" );
          $cmd->setDisplay('parameters',array('displayName' => 1));
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
          $cmd->setEventOnly(1);
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
          $cmd->setTemplate("dashboard","badge" );
          $cmd->setTemplate("mobile","badge" );
          $cmd->setDisplay('parameters',array('displayName' => 1));
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
          $cmd->setEventOnly(1);
          $cmds = $elogic->getCmd();
          $order = count($cmds);
          $cmd->setOrder($order);
          $cmd->setEqLogic_id($elogic->getId());
          $cmd->setEqType('maxcube');
          $cmd->setLogicalId($stateid);
          $cmd->setType('info');
          $cmd->setSubType('binary');
          $cmd->setTemplate("dashboard","window" );
          $cmd->setTemplate("mobile","window" );
          $cmd->setDisplay('parameters',array('displayName' => 1));
          $cmd->setName("Window Sensor");
          $cmd->save();
          $statelogic = $cmd;
        }
        $statelogic->event($device["state"] == "closed");
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
  
  public function preInsert() {}
  public function postInsert() {}
  public function preUpdate() {}
  public function postUpdate() {}
  public function preRemove() {}
  public function postRemove() {}

  public function toHtml($_version = 'dashboard') {
    return eqLogic::toHtml($_version);
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
    
    if (!$elogic)
      return;
      
    if (init("method") == "update" && init("property") != "lastUpdate") {
      log::add("maxcube", 'debug', "update " . init("rf_address") . " " . init("property") . " " . init("value"));
      switch (init("property")) {
        case "setpoint":
          if ($thermostat == "") {
            $setpointlogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), "setpoint_event");
            $setpointlogic->event(init("value"));
          } else {
            maxcubeCmd::byEqLogicIdAndLogicalId($thermostat, "order")->event(init("value"));
          }
          break;
        case "temp":
          $templogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), "temp");
          $offset = 0;
          if (isset($device["temp_offset"]))
            $offset = $device["temp_offset"];
          $value = init("value");
          $templogic->event($value + $offset);
          break;
        case "valve":
          $valvelogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), "valve");
          $valvelogic->event(init("value"));
          break;
        case "state":
          $statelogic = maxcubeCmd::byEqLogicIdAndLogicalId($elogic->getId(), "sensor");
          $statelogic->event(init("value") == "closed");
          break;
      }
    }
  }
  
  public static function getCubeConfig() {
    $url = "http://" . config::byKey('internalAddr') . ":" . config::byKey('socketport', 'maxcube') . "/get";
    return json_decode(file_get_contents($url), true);
  }
  
  public static function setCubeSetpoint($rf_address, $value) {
    $cfg = self::getCubeConfig();
    $device = self::getDevice($rf_address);
    if ($device["room_id"] != "")
      $rf_address = $cfg["rooms"][$device["room_id"]]["group_rf_address"];
    return json_decode(file_get_contents("http://" . config::byKey('internalAddr') . ":" . config::byKey('socketport', 'maxcube') . "/set/" . $rf_address . "/" . $value), true);
  }
  
  public static function getRooms() {
    $res = self::getCubeConfig();
    unset($res["rooms"]["0"]);
    return $res["rooms"];
  }
  
  public static function getDevice($rf) {
    foreach (maxcube::getRooms() as $room) {
      foreach ($room["devices"] as $device) {
        if ($device["rf_address"] == $rf)
          return $device;
      }
    }
    return array();
  }
  
  public static function typeToString($type) {
    switch ($type) {
      case "1":
        return "[Radiateur]";
      case "2":
        return "[Radiateur+]";
      case "3":
        return "[Thermostat]";
      case "4":
        return '[Ouverture]';
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
        maxcube::setCubeSetpoint($eqLogic->getConfiguration('rf_address'), $_options["slider"]);
        break;
    }
  }
}

?>
