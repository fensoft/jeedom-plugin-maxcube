release:
	cd ..; rm -rf /tmp/maxcube; cp -rf maxcube /tmp/maxcube
	cd /tmp/maxcube; rm -rf rm -rf Makefile .git .gitmodules .gitignore 3rdparty/maxcube/.gitignore 3rdparty/maxcube/.git
	cd /tmp; zip maxcube -r maxcube
