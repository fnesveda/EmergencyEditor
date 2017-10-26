LIBRARIES=ace adminer cookie dropzone fileDownload jquery jquery-ui jstree mousetrap Services_JSON smartmenus
all : lib

.PHONY : lib clean $(LIBRARIES)

lib : $(LIBRARIES)
	
ace :
	rm -rf tmp
	mkdir tmp
	rm -rf lib/ace
	mkdir -p lib/ace
	curl -L https://www.github.com/ajaxorg/ace-builds/tarball/master | tar -xzf - -C tmp
	cp -r tmp/ajaxorg-ace-builds-*/src-min-noconflict lib/ace/src-min-noconflict
	cp tmp/ajaxorg-*/LICENSE lib/ace/LICENSE
	rm -rf tmp
	
adminer :
	rm -rf lib/adminer
	mkdir -p lib/adminer
	curl -L http://www.adminer.org/latest-en.php -o lib/adminer/adminer.php
	curl -L https://raw.githubusercontent.com/vrana/adminer/master/designs/pepa-linha/adminer.css -o lib/adminer/adminer.css
	echo "Header unset X-Frame-Options" > lib/adminer/.htaccess
	
cookie :
	rm -rf lib/cookie
	mkdir -p lib/cookie
	curl -L https://raw.githubusercontent.com/carhartl/jquery-cookie/master/src/jquery.cookie.js -o lib/cookie/jquery.cookie.js
	
dropzone :
	rm -rf lib/dropzone
	mkdir -p lib/dropzone
	curl -L https://raw.githubusercontent.com/enyo/dropzone/master/dist/min/dropzone.min.js -o lib/dropzone/dropzone.min.js
	curl -L https://raw.githubusercontent.com/enyo/dropzone/master/dist/min/dropzone.min.css -o lib/dropzone/dropzone.min.css
	curl -L https://raw.githubusercontent.com/enyo/dropzone/master/LICENSE -o lib/dropzone/LICENSE
	
fileDownload :
	rm -rf lib/fileDownload
	mkdir -p lib/fileDownload
	curl -L https://raw.githubusercontent.com/johnculviner/jquery.fileDownload/master/src/Scripts/jquery.fileDownload.js -o lib/fileDownload/jquery.fileDownload.js
	curl -L https://raw.githubusercontent.com/johnculviner/jquery.fileDownload/master/LICENSE -o lib/fileDownload/LICENSE
	
jquery :
	rm -rf lib/jquery
	mkdir -p lib/jquery
	curl -L https://code.jquery.com/jquery-3.2.1.min.js -o lib/jquery/jquery.min.js
	
jquery-ui :
	rm -rf tmp
	mkdir tmp
	rm -rf lib/jquery-ui
	mkdir -p lib/jquery-ui
	curl -L http://jqueryui.com/resources/download/jquery-ui-1.12.1.zip | tar xz -C tmp
	cp tmp/jquery-ui-*/jquery-ui.min.js lib/jquery-ui/jquery-ui.min.js
	rm -rf tmp
	
jstree :
	rm -rf tmp
	mkdir tmp
	rm -rf lib/jstree
	mkdir -p lib/jstree
	mkdir -p lib/jstree/themes
	curl -L https://www.github.com/vakata/jstree/archive/3.3.4.zip | tar xz -C tmp
	cp tmp/jstree-*/dist/jstree.min.js lib/jstree/jstree.min.js
	cp -r tmp/jstree-*/dist/themes/default lib/jstree/themes/default
	rm -rf tmp
	
mousetrap :
	rm -rf tmp
	mkdir tmp
	rm -rf lib/mousetrap
	mkdir -p lib/mousetrap
	curl -L https://www.github.com/ccampbell/mousetrap/archive/1.6.1.tar.gz | tar xz -C tmp
	cp tmp/mousetrap-*/mousetrap.min.js lib/mousetrap/mousetrap.min.js
	cp tmp/mousetrap-*/plugins/global-bind/mousetrap-global-bind.min.js lib/mousetrap/mousetrap-global-bind.min.js
	rm -rf tmp
	
Services_JSON :
	rm -rf tmp
	mkdir tmp
	rm -rf lib/Services_JSON
	mkdir -p lib/Services_JSON
	curl -L http://download.pear.php.net/package/Services_JSON-1.0.3.tgz | tar xz -C tmp
	cp tmp/Services_JSON-*/JSON.php lib/Services_JSON/JSON.php
	echo "Deny from all" > lib/Services_JSON/.htaccess
	rm -rf tmp

smartmenus :
	rm -rf lib/smartmenus
	mkdir -p lib/smartmenus
	curl -L https://raw.githubusercontent.com/fnesveda/smartmenus/disabled-items-no-click/dist/jquery.smartmenus.min.js -o lib/smartmenus/jquery.smartmenus.min.js
	curl -L https://raw.githubusercontent.com/fnesveda/smartmenus/disabled-items-no-click/dist/css/sm-core-css.css -o lib/smartmenus/sm-core-css.css

clean : 
	find lib -type d -mindepth 1 -maxdepth 1 | xargs rm -rf