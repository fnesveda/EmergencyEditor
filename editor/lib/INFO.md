## LIBRARY UPDATING
* update the _Makefile_ to download the latest version of the library, if possible
	* the goal is to have the _Makefile_ download the latest version for all libraries, but some are not making it easy
* jQuery UI
	* we only need the _sortable_ utility, so if you are downloading the library manually, you can build a package only with that and its dependencies.
* Smartmenus
	* when updating Smartmenus from the original source, reapply [this commit](https://github.com/fnesveda/smartmenus/commit/6228ad82123247c40df3dc0ee4297ededa49dc7f) to fix submenus of disabled items