#!/bin/bash
ME=`cd $(dirname $0); pwd`

touch /tmp/maxcube_in_progress
echo 0 > /tmp/maxcube_in_progress
echo "Installing maxcube dependencies"

sudo apt-get install -y git
echo 10 > /tmp/maxcube_in_progress
BIN=`which node`
if [ "$BIN" = "" ]; then
  BIN=`which nodejs`
fi
if [ "$BIN" = "" ]; then
  echo "No nodejs found"
  major=0
else
  actual=`$BIN -v`
  major=`$BIN -v | sed "s#v##" | sed "s#[.].*##"`
  echo "Current version: ${actual} (major $major)"
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

BIN=`which node`
if [ "$BIN" = "" ]; then
  BIN=`which nodejs`
fi
new=`$BIN -v`;
echo "new version installed: ${new}"

echo 50 > /tmp/maxcube_in_progress
git --version
cd $ME/maxcube.js
sudo rm -rf node_modules
echo "cache = \"$ME/npm-cache\"" > .npmrc
npm i
echo 100 > /tmp/maxcube_in_progress
rm /tmp/maxcube_in_progress
