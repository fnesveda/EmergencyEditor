## LIBRARY UPDATING
* update the _Makefile_ to download the latest version of the library, if possible
	* the goal is to have the _Makefile_ download the latest version for all libraries, but some are not making it easy
* jQuery UI
	* we only need the _sortable_ utility, so if you are downloading the library manually, you can build a package only with that and its dependencies.
	* the makefile now downloads a complete package, though, since the jQuery download API keeps changing
* Smartmenus
	* when updating Smartmenus from the original source, reapply [this commit](https://github.com/fnesveda/smartmenus/commit/28bec230b154a11a1ea23a1e1ed907d4b82c9e4d) to fix submenus of disabled items
	* I will keep a fork with this commit applied until this is fixed in Vadikom's repo (but he doesn't seem to be responding to pull requests)