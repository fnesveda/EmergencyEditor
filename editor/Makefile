LIBRARIES=ace adminer cookie dropzone fileDownload jquery jquery-ui jstree mousetrap Services_JSON smartmenus
all : lib

.PHONY : lib clean $(LIBRARIES)

lib : $(LIBRARIES)
	
ace :
	rm -rf tmp
	mkdir tmp
	git clone https://github.com/ajaxorg/ace-builds.git tmp
	mkdir -p lib/ace
	cp -r tmp/src-min-noconflict lib/ace/src-min-noconflict
	cp tmp/LICENSE lib/ace/LICENSE
	rm -rf tmp
	
adminer :
	mkdir -p lib/adminer
	curl -L http://www.adminer.org/latest-en.php -o lib/adminer/adminer.php
	curl -L https://raw.githubusercontent.com/vrana/adminer/master/designs/pepa-linha/adminer.css -o lib/adminer/adminer.css
	echo "Header unset X-Frame-Options" > lib/adminer/.htaccess
	
cookie :
	mkdir -p lib/cookie
	curl -L https://raw.githubusercontent.com/carhartl/jquery-cookie/master/src/jquery.cookie.js -o lib/cookie/jquery.cookie.js
	
dropzone :
	rm -rf tmp
	mkdir tmp
	mkdir -p lib/dropzone
	curl -L https://github.com/enyo/dropzone/archive/v4.0.1.tar.gz | tar xz -C tmp
	cp tmp/dropzone-4.0.1/dist/min/dropzone.min.css lib/dropzone/dropzone.min.css
	cp tmp/dropzone-4.0.1/dist/min/dropzone.min.js lib/dropzone/dropzone.min.js
	rm -rf tmp
	
fileDownload :
	mkdir -p lib/fileDownload
	curl -L https://raw.githubusercontent.com/johnculviner/jquery.fileDownload/master/src/Scripts/jquery.fileDownload.js -o lib/fileDownload/jquery.fileDownload.js
	
jquery :
	mkdir -p lib/jquery
	curl -L http://code.jquery.com/jquery-2.1.4.min.js -o lib/jquery/jquery.min.js
	
jquery-ui :
	rm -rf tmp
	mkdir tmp
	mkdir -p lib/jquery-ui
	curl -L --data "version=1.11.4&core=on&widget=on&mouse=on&sortable=on&theme=none&theme-folder-name=no-theme&scope=" http://download.jqueryui.com/download | tar xz -C tmp
	cp tmp/jquery-ui-1.11.4.custom/jquery-ui.min.js lib/jquery-ui/jquery-ui.min.js
	rm -rf tmp
	
jstree :
	rm -rf tmp
	mkdir tmp
	mkdir -p lib/jstree
	mkdir -p lib/jstree/themes
	curl -L https://github.com/vakata/jstree/archive/3.1.1.zip | tar xz -C tmp
	cp tmp/jstree-3.1.1/dist/jstree.min.js lib/jstree/jstree.min.js
	cp -r tmp/jstree-3.1.1/dist/themes/default lib/jstree/themes/default
	rm -rf tmp
	
mousetrap :
	rm -rf tmp
	mkdir tmp
	mkdir -p lib/mousetrap
	curl -L https://github.com/ccampbell/mousetrap/archive/1.5.3.tar.gz | tar xz -C tmp
	cp tmp/mousetrap-1.5.3/mousetrap.min.js lib/mousetrap/mousetrap.min.js
	cp tmp/mousetrap-1.5.3/plugins/global-bind/mousetrap-global-bind.min.js lib/mousetrap/mousetrap-global-bind.min.js
	rm -rf tmp
	
Services_JSON :
	rm -rf tmp
	mkdir tmp
	mkdir -p lib/Services_JSON
	curl -L http://download.pear.php.net/package/Services_JSON-1.0.3.tgz | tar xz -C tmp
	cp tmp/Services_JSON-1.0.3/JSON.php lib/Services_JSON/JSON.php
	echo "Deny from all" > lib/Services_JSON/.htaccess
	rm -rf tmp

smartmenus :
	rm -rf tmp
	mkdir tmp
	mkdir -p lib/smartmenus
	curl -L -A "Mozilla/4.0" http://www.smartmenus.org/files/?file=smartmenus-jquery/smartmenus-1.0.0-beta1.zip | tar xz -C tmp
	cp tmp/smartmenus-1.0.0-beta1/jquery.smartmenus.min.js lib/smartmenus/jquery.smartmenus.min.js
	cp tmp/smartmenus-1.0.0-beta1/css/sm-core-css.css lib/smartmenus/sm-core-css.css
	rm -rf tmp

clean : 
	find lib -type d -mindepth 1 -maxdepth 1 | xargs rm -rf