deploy: clean
	rm -rf /home/fensoft/jeedom/plugins/maxcube; cp -rf /tmp/maxcube /home/fensoft/jeedom/plugins

release: clean
	cd /tmp; zip maxcube -r maxcube

clean:
	cd ..; rm -rf /tmp/maxcube; cp -rf maxcube /tmp/maxcube
	cd /tmp/maxcube; rm -rf rm -rf Makefile .git .gitmodules .gitignore 3rdparty/maxcube/.gitignore 3rdparty/maxcube/.git

