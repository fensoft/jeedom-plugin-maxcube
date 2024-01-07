/* Copyright (C) fensoft <fensoft@gmail.com>
 * You can modify this file but you'll need to send to the author the modified files for future merging (or not, depending on the quality of your modifications)
 * You need to ask the permision of the author for redistribution/copying/forking
 */

if (process.argv[8] == 1) {
  console.log("Connecting to MaxCube " + process.argv[2] + ":" + process.argv[3]);
  console.log("Creating webservice on :" + process.argv[4]);
  console.log("Output log on " + process.argv[5]);
  console.log("Jeedom callback at " + process.argv[6]);
  console.log("Whitelist events " + process.argv[7]);
  console.log("Debug mode " + process.argv[8]);
}

var fs = require('fs');
var util = require('util');
var sleep = require('sleep');
var utf8 = require('utf8');
var old = {};
var whitelist_props = process.argv[7].split(",")

if (process.argv[5] != "-") {
  var log_file = fs.createWriteStream(process.argv[5], {flags : 'a'});
  var log_stdout = process.stdout;
  console.log = function(d) {
    log_file.write(util.format(d) + '\n');
  };
}


var statuses = [{}]
var configuration = {};
var queue = {};
var rf2room = {}
var moment = require('moment');
var MaxCube = require('maxcube');
MaxCube.log = function(message) { console.log('[' + moment().format() + '] ' + message); }
MaxCube.debug = process.argv[8]
var cubes = [];
var ips = process.argv[2].split(',');
for (var i = 0; i < ips.length; i++)
  cubes[i] = new MaxCube(ips[i], process.argv[3]);
var rf2cubeid = {};

function getCube(rf) {
  return cubes[getCubeId(rf)];
}

function getCubeId(rf) {
  return rf2cubeid[rf];
}

function cube2cubeid(cube) {
  for (var cubeid = 0; cubeid < cubes.length; cubeid++)
    if (cubes[cubeid].ip == cube.ip)
      return cubeid;
  return -1;
}

var http = require('http');
var express = require('express');
var path = require('path');
var request = require('request');
 
var app = express();
app.set('port', process.argv[4]); 
app.use(express.static(path.join(__dirname, 'public')));
 
app.get('/get/:id', function (req, res) {
  id = req.params.id;
  res.send(JSON.stringify(statuses[id], null, 2));
});

app.get('/cubes', function (req, res) {
  res.send(JSON.stringify(rf2cubeid, null, 2));
});

app.get('/get', function (req, res) {
  var all = {};
  for (var cube in statuses) {
    if (statuses.hasOwnProperty(cube)) {
      rooms = statuses[cube]["rooms"];
      for (var roomid in rooms) {
        if (rooms.hasOwnProperty(roomid)) {
          room = "0";
          if (roomid != "0")
            room = rooms[roomid]["room_name"];
          devices = rooms[roomid]["devices"];
          for (var deviceid in devices) {
            if (devices.hasOwnProperty(deviceid)) {
              if (all[room] == null)
                all[room] = {};
              all[room][deviceid] = devices[deviceid];
              all[room][deviceid]["cube"] = cube;
              if (roomid != "0")
                all[room][deviceid]["group_rf_address"] = rooms[roomid]["group_rf_address"];
            }
          }
        }
      }
    }
  }
  res.send(JSON.stringify(all, null, 2));
});

app.get('/getConfig', function (req, res) {
  res.send(JSON.stringify(configuration, null, 2));
});

app.get('/set/:rf/:temp', function (req, res) {
  rf = req.params.rf;
  if (status["rooms"][rf2room[rf]]["devices"][rf]["setpoint"] == req.params.temp && status["rooms"][rf2room[rf]]["devices"][rf]["mode"] == 'MANUAL') {
    MaxCube.log("Ignored " + req.params.rf + " to " + req.params.temp);
  } else {
    MaxCube.log("Queued " + req.params.rf + " to " + req.params.temp);
    queue[req.params.rf] = req.params.temp;
  }
  res.send({ result: 'ok'})
});

app.get('/boost/:rf/:temp', function (req, res) {
  MaxCube.log("Boost " + req.params.rf + " to " + req.params.temp);
  getCube(req.params.rf).doBoost(req.params.rf, req.params.temp)
  res.send({ result: 'ok'})
});

app.get('/auto/:rf/:temp', function (req, res) {
  MaxCube.log("Auto " + req.params.rf + " to " + req.params.temp);
  getCube(req.params.rf).doAuto(req.params.rf, req.params.temp);
  res.send({ result: 'ok'})
});

app.get('/vacation/:rf/:temp/:date', function (req, res) {
  MaxCube.log("Boost " + req.params.rf + " to " + req.params.temp);
  getCube(req.params.rf).setVacationTemperature(req.params.rf, req.params.temp, req.params.date)
  res.send({ result: 'ok'})
});
 
http.createServer(app).listen(app.get('port'), function(){
  MaxCube.log('Max-NodeServer listening on port ' + app.get('port'));
});

app.get('/configure/display/:rf/:val', function (req, res) {
  if (req.params.val == "0")
    getCube(req.params.rf).send('000082000000' + req.params.rf + '0004');
  else
    getCube(req.params.rf).send('000082000000' + req.params.rf + '0000');
  res.send({ result: 'ok'})
});

for (var i = 0; i < cubes.length; i++) {
MaxCube.log('connecting to cube ' + i + '...')
myMaxCube = cubes[i];

myMaxCube.once('connected', function (cubeStatus) {
  cubeid = cube2cubeid(this);
  MaxCube.log('connected to cube ' + cubeid)
  
  statuses[cubeid] = cubeStatus
  rf2cubeid[cubeStatus["rf_address"]] = cubeid;

  //dirty hack in the library to "wake up" valve every 15min to get the actual temperature (valve will send their temperature only on valve movement)
  myMaxCube.updateTriggerJob.cancel();
  myMaxCube.updateTriggerResetJob.cancel();
});

myMaxCube.once('metadataUpdate', function (metadata_) {
  MaxCube.log('metadataUpdate')
  cubeid = cube2cubeid(this);
  status = statuses[cubeid];
  metadata = metadata_
  if (! status["rooms"])
    status["rooms"] = {}
  for (var index in metadata.rooms) {
    room = metadata.rooms[index]
    room["room_name"] = utf8.decode(room["room_name"]);
    status["rooms"][room.room_id] = room
  }
  for (var index in metadata.devices) {
    device = metadata.devices[index]
    if (device.hasOwnProperty("device_name")) {
      device["device_name"] = utf8.decode(device["device_name"]);
    }
    if (!status["rooms"][device.room_id]) {
      status["rooms"][device.room_id] = {}
    }
    if (!status["rooms"][device.room_id]["devices"]) {
      status["rooms"][device.room_id]["devices"] = {}
    }
    rf2room[device.rf_address] = device.room_id
    status["rooms"][device.room_id]["devices"][device.rf_address] = device
  }
  
  for (var iroom in status["rooms"]) {
    room = status["rooms"][iroom]
    for (var idevice in room["devices"]) {
      device = room["devices"][idevice]
      rf2cubeid[device["rf_address"]] = cubeid;
    }
  }
  
  MaxCube.log(JSON.stringify(status));
  MaxCube.log(JSON.stringify(rf2cubeid));
});

myMaxCube.on('configurationUpdate', function (configuration_) {
  MaxCube.log('configurationUpdate')
  if (configuration_ == null)
    return;
  cubeid = cube2cubeid(this);
  status = statuses[cubeid];
  configuration[configuration_.rf_address] = configuration_
  if (! rf2room[configuration_.rf_address])
    rf2room[configuration_.rf_address] = "0"
  if (! status["rooms"][rf2room[configuration_.rf_address]])
    status["rooms"][rf2room[configuration_.rf_address]] = {}
  if (! status["rooms"][rf2room[configuration_.rf_address]]["devices"])
    status["rooms"][rf2room[configuration_.rf_address]]["devices"] = {}
  if (! status["rooms"][rf2room[configuration_.rf_address]]["devices"][configuration_.rf_address])
    status["rooms"][rf2room[configuration_.rf_address]]["devices"][configuration_.rf_address] = {}
  for (var key in configuration_) {
    status["rooms"][rf2room[configuration_.rf_address]]["devices"][configuration_.rf_address][key] = configuration_[key];
  }
});

myMaxCube.on('statusUpdate', function (devicesStatus) {
  MaxCube.log('statusUpdate')
  cubeid = cube2cubeid(this);
  status = statuses[cubeid];
  for (var index in devicesStatus) {
    var device = devicesStatus[index]
    for (var key in device) {
      if (typeof rf2room[device.rf_address] != 'undefined')
        status["rooms"][rf2room[device.rf_address]]["devices"][device.rf_address][key] = device[key]
      else
        MaxCube.log("empty " + device.rf_address)
    }
  }
  for (var iroom in status["rooms"]) {
    room = status["rooms"][iroom]
    for (var idevice in room["devices"]) {
      device = room["devices"][idevice]
    }
  }

  var maxsend = 50; //avoid too many requests
  for (var iroom in status["rooms"]) {
    room = status["rooms"][iroom]
    for (var idevice in room["devices"]) {
      device = room["devices"][idevice]
      for (var prop in device) {
        if (!old[device.rf_address])
          old[device.rf_address] = {}
        if (typeof old[device.rf_address][prop] == 'undefined' || old[device.rf_address][prop] != device[prop]) {
          if (whitelist_props.indexOf(prop) > -1 && typeof device[prop] != 'undefined') {
            MaxCube.log("new prop " + prop + " = " + device[prop] + " for " + device.rf_address)
            old[device.rf_address][prop] = device[prop]
            request.post(process.argv[6], { form: { rf_address: device.rf_address, property: prop, value: device[prop] } } );
            maxsend = maxsend - 1;
            if (maxsend == 0)
              return;
          } else {
            old[device.rf_address][prop] = device[prop]
          }
        }
      }
    }
  }

  for (var rf in queue) {
    MaxCube.log("Set " + rf + " to " + queue[rf]);
    getCube(rf).setTemperature(rf, queue[rf])
    delete queue[rf];
  }
});

}
