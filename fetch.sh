ROOT=~/jeedom/plugins/maxcube
for i in resources/maxcube.js/daemon.sh resources/maxcube.js/maxnodeserver.js resources/maxcube.js/package.json; do
  cp $ROOT/$i $i
done
for i in core desktop doc plugin_info; do
  rm -rf $folder
  cp -rf $ROOT/$i/ ./
done
