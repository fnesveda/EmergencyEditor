## Emergency Editor

My goal is to make a minimalist code editor attachable to your webpage and enabling you to edit your webpage from itself.

![Emergency Editor screenshot](https://raw.githubusercontent.com/fnesveda/EmergencyEditor/master/editor/images/screenshot.png)

### Installation
Download the release of the project here from the [releases tab](https://github.com/fnesveda/EmergencyEditor/releases) and copy the _editor_ folder to the root of your webpage project. Now you can access the editor by visiting _`http://your.webpage.address/editor`_.
If you have some rewrite rules in place, you need to tweak them to be able to access the editor.

### Configuration
The editor is made login-free, so if you want to limit the access to the editor, you need to create a _.htaccess_ file in the _editor_ folder and a corresponding .htpasswd file to secure the editor.
There is a [nice tutorial](http://weavervsworld.com/docs/other/passprotect.html) on WeaversWorld about _.htaccess_ and _.htpasswd_.

The editor also contains a config file at _editor/config/config.json_. It contains settings for the root folder and filtering editable files and folders.
##### Config options:
* The _root_ option specifies the topmost folder which is opened in the editor. By default it is set to the parent folder of the _editor_ folder.
* The _blacklist_ option specifies which files and folders should be blacklisted. Wildcards/regular expressions are not yet supported.

### Usage
You use the editor like a normal code editor. To open a file, double-click it in the file tree. Depending on the file type, a code editor or an image pane will open, or if the file is binary or too big, it will download.
There are a lot of commands available in the menu or accessible with keyboard shortcuts. Editor settings are available under the Settings menu item.
File management options are generally in the right-click menu of a file/folder.
