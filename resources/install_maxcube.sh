#!/bin/bash
ME=`cd $(dirname $0); pwd`

touch /tmp/maxcube_in_progress
echo 0 > /tmp/maxcube_in_progress
echo "************************* "
echo "  $(date +'%d/%m/%Y %H:%M:%S') Installation des dépendances"
echo "************************* "

sudo apt-get install -y git
echo 10 > /tmp/maxcube_in_progress
BIN=`which node`
if [ "$BIN" = "" ]; then
  echo "Node not found"
  major=0
else
  actual=`$BIN -v`
  major=`$BIN -v | sed "s#v##" | sed "s#[.].*##"`
  echo "Node version: ${actual} (major $major)"
fi

echo 30 > /tmp/maxcube_in_progress
if [ `arch` == "armv6l" -a $major -lt 5 ]; then
  echo "Raspberry 1 detected, using armv6 package"
  sudo npm rebuild
  sudo apt-get -y --purge autoremove nodejs npm
  sudo rm /etc/apt/sources.list.d/nodesource.list
  wget http://node-arm.herokuapp.com/node_latest_armhf.deb
  sudo dpkg -i node_latest_armhf.deb
  rm node_latest_armhf.deb
elif [ $major -lt 8 ]; then
  echo "using official repository"
  curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
  sudo apt-get install -y nodejs npm
fi

if [ "$BIN" = "" ]; then
  BIN=`which node`
  new=`$BIN -v`;
  echo "New node version installed: ${new}"
fi

echo 50 > /tmp/maxcube_in_progress
git --version
cd $ME
sudo rm -rf npm-cache
cd $ME/maxcube.js
sudo rm -rf node_modules
sudo rm -f package-lock.json
echo "cache = \"$ME/npm-cache\"" > .npmrc
echo "Starting npm install"
npm i
echo 100 > /tmp/maxcube_in_progress
echo "************************* "
echo "  $(date +'%d/%m/%Y %H:%M:%S') Installation terminée"
echo "************************* "
rm /tmp/maxcube_in_progress
