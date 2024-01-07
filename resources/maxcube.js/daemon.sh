#!/bin/bash
BIN=`which node`
if [ "$BIN" = "" ]; then
  BIN=`which nodejs`
fi
case "$1" in
  start)
    if [ `ps aux | grep node | grep maxnodeserver | grep -v grep | awk '{print $2}' | wc -l` -eq 1 ]; then
      echo already running
      exit 1
    fi
    $BIN maxnodeserver.js $3 $4 $5 $6 $7 $8 $9 > $2 2>&1 &
    ;;
  service)
    if [ `ps aux | grep node | grep maxnodeserver | grep -v grep | awk '{print $2}' | wc -l` -eq 1 ]; then
      echo already running
      exit 1
    fi
    echo wont log to $2
    $BIN maxnodeserver.js $3 $4 $5 $6 $7 $8 $9
    ;;
  stop)
    kill -9 `ps aux | grep node | grep maxnodeserver | grep -v grep | awk '{print $2}'`
    ;;
  status)
    if [ `ps aux | grep node | grep maxnodeserver | grep -v grep | awk '{print $2}' | wc -l` -eq 1 ]; then
      echo is running
    else
      echo is not running
    fi
esac
