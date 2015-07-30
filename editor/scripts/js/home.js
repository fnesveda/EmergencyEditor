/**
 * Main function of the script.
 * Encapsulates the whole functionality, and is called it only in a supported browser.
 * @function main
 */
function main() {
	// ----- GENERAL LANGUAGE ADDITIONS -----

	/**
	 * Extracts the last part of the path after the last '/' (with extension).
	 * @param {string} path - The path from which to extract the last part.
	 * @returns {string} The last item of the path.
	 * @function getFilenameFromPath
	 */
	window.getFilenameFromPath = function(path) {
		return path.substr(path.lastIndexOf('/')+1);
	};

	// ----- GENERAL IDE VARIABLES AND FUNCTIONS -----

	/**
	 * Random ID of the editor instance, used in distinguishing between iframes in script testers.
	 * @var {number} instanceID
	 */
	var instanceID = Math.floor(Math.random() * 1000);

	/**
	 * Specifies if we are on a Mac, or somewhere else.
	 * Used for choosing keyboard shortcuts.
	 * @var {string} platform
	 */
	var platform = /(Mac|iPhone|iPod|iPad)/i.test(navigator.platform)?'mac':'win';

	/**
	 * Supported image types (these are supported in all major browsers).
	 * @var {array} supportedImageExtensions
	 */
	var supportedImageExtensions = ['png', 'jpg', 'jpeg', 'bmp', 'gif'];

	/**
	 * Maximum file size, below which we'll try to open the file.
	 * @var {number} MAXFILESIZE
	 */
	var MAXFILESIZE = 2*1024*1024;

	
	/**
	 * Executes a command in the IDE (mostly used by the menu)
	 * @param {string} cmd - The command to execute.
	 * @function execCmd
	 */
	window.execCmd = function(cmd) {
		switch (cmd) {
			// IDE commands
			case "toggleSettingsPopup":
				toggleSettingsPopup();
				break;
			case "openSQLManager":
				openSQLManager();
				break;
			case "openScriptTester":
				openScriptTester();
				break;
			case "openVersionControl":
				openVersionControl();
				break;
			// tabs commands
			case "saveCurrentFile":
				tabs.getActiveTab().save();
				break;
			case "saveAllFiles":
				tabs.saveAllFiles();
				break;
			case "downloadCurrentFile":
				tabs.getActiveTab().downloadFile();
				break;
			case "revertCurrentFile":
				tabs.getActiveTab().revert();
				break;
			case "closeCurrentFile":
				tabs.getActiveTab().close();
				break;
			case "closeAllTabs":
				tabs.closeAllTabs();
				break;
			case "selectNextTab":
				tabs.selectNextTab(true);
				break;
			case "selectPreviousTab":
				tabs.selectPreviousTab(true);
				break;
			// ace commands
			case "overwrite":
				editor.execCommand(cmd);
				updateStatusbarOverwrite();
				break;
			case "undo":
			case "redo":
			case "blockindent":
			case "blockoutdent":
			case "togglecomment":
			case "toggleBlockComment":
			case "tolowercase":
			case "touppercase":
			case "sortlines":
			case "find":
			case "findAll":
			case "findnext":
			case "findprevious":
			case "replace":
			case "jumptomatching":
			case "fold":
			case "foldall":
			case "foldOther":
			case "unfold":
			case "unfoldall":
				editor.execCommand(cmd);
				break;
			default:
				console.log("Unknown command: "+cmd);
				break;
		}
	};

	// ----- COMMAND KEYBINDINGS -----
	// ace has keyboard shortcuts with '-' as the separator, so we stick to that for easier menu generation
	/**
	 * Keyboard shortcuts for commands.
	 * @var {array} commandKeyBindings
	 */
	var commandKeyBindings = [
		{ command: 'saveCurrentFile', win: 'Alt-S', mac: 'Ctrl-S'},
		{ command: 'saveAllFiles', win: 'Alt-Shift-S', mac: 'Ctrl-Shift-S'},
		{ command: 'revertCurrentFile', win: 'Alt-R', mac: 'Ctrl-R'},
		{ command: 'closeCurrentFile', win: 'Alt-W', mac: 'Ctrl-W'},
		{ command: 'closeAllTabs', win: 'Alt-Shift-W', mac: 'Ctrl-Shift-W'},
		{ command: 'selectNextTab', win: 'Alt-PageDown', mac: 'Command-PageDown'},
		{ command: 'selectPreviousTab', win: 'Alt-PageUp', mac: 'Command-PageUp'}
	];

	/**
	 * Registers keyboard shortcuts for commands
	 * @function registerCommandKeyBindings
	 */
	window.registerCommandKeyBindings = function() {
		for (var i = 0; i < commandKeyBindings.length; i++) {
			// Mousetrap needs keys separated by '+'
			// if we have a shortcut with the '-' key in the future, we need to take care of that
			Mousetrap.bindGlobal(commandKeyBindings[i][platform].replace(/-/g, '+').toLowerCase(), $.proxy(execCmd, this, commandKeyBindings[i].command));
		}
	};

	// ----- STATUSBAR SCRIPTS -----
	/**
	 * Sets content of the left statusbar part.
	 * @param {string} code - The HTML code which to set to the statusbar part.
	 * @function setStatusbarLeft
	 */
	window.setStatusbarLeft = function(code) {
		$("#tab-statusbar-left").html(code);
	};

	/**
	 * Sets content of the right statusbar part.
	 * @param {string} code - The HTML code which to set to the statusbar part.
	 * @function setStatusbarRight
	 */
	window.setStatusbarRight = function(code) {
		$("#tab-statusbar-right").html(code);
	};

	/** 
	 * Updates the statusbar overwrite field.
	 * @function updateStatusbarOverwrite
	 */
	window.updateStatusbarOverwrite = function() {
		var overwrite = editor.getOption("overwrite") ? "OVERWRITE" : "INSERT";
		$("#tab-statusbar-overwrite").text(overwrite);
	};

	/**
	 * Updates the statusbar info about current cursor position / editor selection.
	 * When multiple cursors / selections are present, it shows info only for the chronologically last one.
	 * @function updateStatusbarSelection
	 */
	window.updateStatusbarSelection = function() {
		var sel = editor.getSelectionRange();
		var text = sel.start.row+1 + ":" + sel.start.column;
		if (!editor.selection.isEmpty()) {
			text += " - " + sel.end.row+1 + ":" + sel.end.column;
		}
		$("#tab-statusbar-selection").text(text);
	};

	/**
	 * Sets the statusbar style based on the currently active tab.
	 * @function themeStatusbar
	 */
	window.themeStatusbar = function() {
		var tabType = 'empty';
		if ((typeof tabs != 'undefined') && !tabs.isEmpty()) {
			tabType = tabs.getActiveTab().type;
		}
		switch(tabType) {
			case 'editor':
				// style the statusbar to look the same as the ace gutter
				$("#tab-statusbar").css("background-color", $('.ace_gutter').css('background-color'));
				$("#tab-statusbar").css("border-color", $('.ace_gutter').css('background-color'));
				$("#tab-statusbar").css("color", $('.ace_gutter').css('color'));
			break;
			default:
				// all types of tabs apart from 'editor' look nearly the same (and nearly the same as the IDE)
				$("#tab-statusbar").css("background-color", '');
				$("#tab-statusbar").css("border-color", '');
				$("#tab-statusbar").css("color", '');
			break;
		}
	};
	
	// ----- VERSION CONTROL SCRIPTS -----
	/**
	 * Opens the version control page in a new window.
	 * @function openVersionControl
	 */
	window.openVersionControl = function() {
		window.open("rvsn/");
	};

	// ----- SQL MANAGER SCRIPTS -----
	/**
	 * Opens a SQL manager tab.
	 * @function openSQLManager
	 */
	window.openSQLManager = function() {
		var tab = tabs.addTab();
		tab.type = 'SQLManager';
		tab.setTitle('SQL Manager');
		// set the custom element to an iframe with Adminer
		var SQLManagerElement = $('<div class="tab-custom-element" id="custom-element-'+tab.tabNumber+'"><iframe seamless class="sql-manager-iframe" src="./lib/adminer/adminer.php" /></div>');
		$("#custom-elements-wrapper").append(SQLManagerElement);
		tab.select();
	};

	// ----- SCRIPT TESTER SCRIPTS -----
	/**
	 * Open a script tester tab.
	 * @function openScriptTester
	 */
	window.openScriptTester = function() {
		var tab = tabs.addTab();
		tab.type = 'scriptTester';
		tab.setTitle('Script tester');
		// create the custom script tester element
		var tabNumber = tab.tabNumber;
		var testerElement = $(
			'<div class="tab-custom-element" id="custom-element-'+tabNumber+'">'+
				'<div class="script-tester-wrapper">'+
					'<form id="script-tester-'+tabNumber+'-form" class="script-tester-form" target="script-tester-'+tabNumber+'-iframe-'+instanceID+'">'+
						'<div class="script-tester-actions-wrapper">'+
							'<div class="script-tester-action-wrapper">'+
								'<input class="script-tester-action" id="script-tester-'+tabNumber+'-action" placeholder="Request URI"/>'+
							'</div>'+
							'<button type="submit" id="script-tester-'+tabNumber+'-submit-button" class="script-tester-submit-button" >Send request</button>'+
							'<button type="button" class="script-tester-fields-toggle-button" onclick="toggleScriptTesterFields('+tabNumber+');">Toggle fields</button>'+
						'</div>'+
						'<div class="script-tester-fields-wrapper" id="script-tester-'+tabNumber+'-fields-wrapper">'+
							'<div class="script-tester-inputs-wrapper" id="script-tester-'+tabNumber+'-GET-wrapper">'+
								'<button type="button" class="script-tester-add-input-button" onclick="addInput(\'GET\', '+tabNumber+');">Add GET field</button>'+
							'</div>'+
							'<div class="script-tester-inputs-wrapper" id="script-tester-'+tabNumber+'-POST-wrapper">'+
								'<button type="button" class="script-tester-add-input-button" onclick="addInput(\'POST\', '+tabNumber+');">Add POST field</button>'+
							'</div>'+
							'<div class="script-tester-inputs-wrapper" id="script-tester-'+tabNumber+'-FILE-wrapper">'+
								'<button type="button" class="script-tester-add-input-button" onclick="addInput(\'FILE\', '+tabNumber+');">Add FILE field</button>'+
							'</div>'+
						'</div>'+
					'</form>'+
					'<div class="script-tester-iframe-wrapper">'+
						'<iframe name="script-tester-'+tabNumber+'-iframe-'+instanceID+'" seamless class="script-tester-iframe" />'+
					'</div>'+
				'</div>'+
			'</div>');
		$("#custom-elements-wrapper").append(testerElement);
		
		// when submitting the script tester form parse all the input fields and deal with them accordingly
		$('#script-tester-'+tabNumber+'-form').submit(function(event){
			var form = $('#script-tester-'+tabNumber+'-form');
			var action = $('#script-tester-'+tabNumber+'-action');
			var gets = $('#script-tester-'+tabNumber+'-GET-wrapper');
			var posts = $('#script-tester-'+tabNumber+'-POST-wrapper');
			var files = $('#script-tester-'+tabNumber+'-FILE-wrapper');
			
			action = encodeURI(action.val());
			if (action.indexOf('?') < 0) {
				action = action + '?';
			}
			gets = gets.children("div");
			posts = posts.children("div");
			files = files.children("div");
			
			var postsEmpty = true;
			var filesEmpty = true;
			// for each POST and FILE input field pair set the name of the second input to the value of the first,
			// so it gets sent with the form when it's submitted
			posts.each(function(index, element) {
				var name = $(element).children().first().val();
				if (name.length > 0)
					postsEmpty = false;
				$(element).children().last().attr("name", name);
			});
			files.each(function(index, element) {
				var name = $(element).children().first().val();
				if (name.length > 0)
					filesEmpty = false;
				$(element).children().last().attr("name", name);
			});
			// if there are no POST and FILE fields, send the form with GET
			if (postsEmpty && filesEmpty) {
				form.attr("method", "GET");
				gets.each(function(index, element) {
					var name = $(element).children().first().val();
					$(element).children().last().attr("name", name);
				});
			}
			// otherwise send the form as GET and put the values of the inputs to the form action 
			else {
				form.attr("method", "POST");
				gets.each(function(index, element) {
					var name = $(element).children().first().val();
					$(element).children().last().attr("name", "");
					var value = $(element).children().last().val();
					if (name.length > 0)
						action += '&' + encodeURIComponent(name) + '=' + encodeURIComponent(value);
				});
			}
			form.attr("action", action);
		});
		tab.select();
	};

	/**
	 * Submits the form in the script tester.
	 * @param {number} tabNumber - The number of the tab in which is the script tester located.
	 * @function submitScriptTesterForm
	 */
	window.submitScriptTesterForm = function(tabNumber) {
		$('#script-tester-'+tabNumber+'-form').submit();
	};

	/**
	 * Adds an parameter input to a script tester.
	 * @param {string} type - The type of the input which to add.
	 * @param {number} tabNumber - The number of the tab in which is the script tester located.
	 * @function addInput
	 */
	window.addInput = function(type, tabNumber) {
		var input = '<div class="script-tester-input-wrapper"><input placeholder="Field name" class="script-tester-input script-tester-input-name"/>';
		switch(type) {
			case 'GET':
			case 'POST':
				input += '<input class="script-tester-input script-tester-input-value" placeholder="Field value"/>';
			break;
			case 'FILE':
				input += '<input type="file" class="script-tester-input script-tester-input-file" placeholder="Field value"/>';
			break;
		}
		input += '</div>';
		$(input).insertBefore($('#script-tester-'+tabNumber+'-'+type+'-wrapper>button'));
	};

	/**
	 * Toggle the display of parameter fields in a script tester tab
	 * @param {number} tabNumber - The number of the tab in which is the script tester located.
	 * @function toggleScriptTesterFields
	 */
	window.toggleScriptTesterFields = function(tabNumber) {
		$("#script-tester-"+tabNumber+"-fields-wrapper").toggle(100);
	};

	// ----- ACE SCRIPTS -----

	/** 
	 * Editor settings not changeable by the user.
	 * @var {object} aceOptions
	 */
	var aceOptions = {
		animatedScroll: true,
		cursorStyle: "ace",
		displayIndentGuides: true,
		dragEnabled: true,
		enableBasicAutocompletion: true,
		enableLiveAutocompletion: true,
		enableMultiselect: true,
		enableSnippets: false,
		fadeFoldWidgets: false,
		fontFamily: undefined,
		highlightActiveLine: true,
		highlightGutterLine: true,
		mergeUndoDeltas: true,
		newLineMode: "auto",
		scrollPastEnd: 0,
		selectionStyle: "line",
		showFoldWidgets: true,
		showGutter: true,
		showLineNumbers: true
	};

	/** 
	 * Editor settings changeable by the user.
	 * @var {object} aceUserOptions
	 */
	var aceUserOptions = {
		behavioursEnabled: false,
		fontSize: 12,
		highlightSelectedWord: true,
		overwrite: false,
		printMarginColumn: 80,
		scrollSpeed: 2,
		showInvisibles: true,
		showPrintMargin: true,
		tabSize: 4,
		theme: "ace/theme/monokai",
		useSoftTabs: false,
		wrap: "off",
		wrapBehavioursEnabled: true
	};

	/** 
	 * Saves current editor options to a cookie.
	 * @function saveAceOptions
	 */
	window.saveAceOptions = function() {
		$.cookie('ace-options', aceUserOptions, { expires: 5*365, path: '/' });
	};
	
	/**
	 * Initializes the editor.
	 * @function initializeAce
	 */
	window.initializeAce = function() {
		// required for automatically choosing syntax mode for files
		modelist = ace.require('ace/ext/modelist');
		// required for autocompletion
		ace.require("ace/ext/language_tools");
		editor = ace.edit('editor');

		// this doesn't work when called straight away, has to be delayed
		editor.on("changeSelection", function() {
			setTimeout(updateStatusbarSelection, 0);
		});

		// load settings currently saved in the cookie and combine them with the default ones
		$.cookie.json = true;
		$.extend(aceUserOptions, $.cookie('ace-options'));
		// save them back to the cookie
		saveAceOptions();
		
		// set editor options
		editor.setOptions(aceOptions);
		editor.setOptions(aceUserOptions);
	};

	// ----- MENU SCRIPTS -----
	/**
	 * Items of the menu
	 * @var {array} menuItems
	 */
	var menuItems = [
		{ title: 'File', disabledFor: ['empty', 'SQLManager', 'scriptTester'], submenu: [
			{ title: 'Save file', command: 'saveCurrentFile', disabledFor: ['image'] },
			{ title: 'Save all', command: 'saveAllFiles' },
			{ separator: true },
			{ title: 'Download file', command: 'downloadCurrentFile' },
			{ separator: true },
			{ title: 'Revert file', command: 'revertCurrentFile', disabledFor: ['image'] },
			{ title: 'Close file', command: 'closeCurrentFile' },
			{ title: 'Close all', command: 'closeAllTabs' },
		]},
		{ title: 'Edit', disabledFor: ['empty', 'image', 'SQLManager', 'scriptTester'], submenu: [
			{ title: 'Undo', command: 'undo' },
			{ title: 'Redo', command: 'redo' },
			{ separator: true },
			{ title: 'Indent', command: 'blockindent' },
			{ title: 'Unindent', command: 'blockoutdent' },
			{ separator: true },
			{ title: 'Toggle comment', command: 'togglecomment' },
			{ title: 'Toggle block comment', command: 'toggleBlockComment' },
			{ separator: true },
			{ title: 'To lowercase', command: 'tolowercase' },
			{ title: 'To uppercase', command: 'touppercase' },
			{ separator: true },
			{ title: 'Sort lines', command: 'sortlines' },
			{ separator: true },
			{ title: 'Toggle overwrite', command: 'overwrite' },
		]},
		{ title: 'Find', disabledFor: ['empty', 'image', 'SQLManager', 'scriptTester'], submenu: [
			{ title: 'Find', command: 'find' },
			{ title: 'Find all', command: 'findAll' },
			{ title: 'Find next', command: 'findnext' },
			{ title: 'Find previous', command: 'findprevious' },
			{ separator: true },
			{ title: 'Replace', command: 'replace' },
			{ separator: true },
			{ title: 'Jump to matching bracket', command: 'jumptomatching' },
		]},
		{ title: 'View', disabledFor: ['empty', 'image', 'SQLManager', 'scriptTester'], submenu: [
			{ title: 'Fold', command: 'fold' },
			{ title: 'Fold all', command: 'foldall' },
			{ title: 'Fold everything else', command: 'foldOther' },
			{ separator: true },
			{ title: 'Unfold', command: 'unfold' },
			{ title: 'Unfold all', command: 'unfoldall' },
		]},
		{ title: 'Window', disabledFor: ['empty'], submenu: [
			{ title: 'Next Tab', command: 'selectNextTab' },
			{ title: 'Previous Tab', command: 'selectPreviousTab' },
		]},
		{ title: 'SQL Manager', command: 'openSQLManager' },
		{ title: 'Script Tester', command: 'openScriptTester' },
		{ title: 'Version Control', command: 'openVersionControl' },
		{ title: 'Settings', command: 'toggleSettingsPopup' }
	];

	/**
	 * Finds keyboard shortcuts from Ace and elsewhere and adds them to menu items
	 * @function generateKeyboardShortcuts
	 */
	window.generateKeyboardShortcuts = function() {
		/**
		 * Recursively applies a function to a menu item and it's submenu
		 * @param {array} menu - The menu to whose items to apply the function
		 * @param {function} func - The function which to apply
		 * @function applyToAllMenuItems 
		 */
		function applyToAllMenuItems(menu, func) {
			for (var i = 0; i < menu.length; i++) {
				if (typeof menu[i].submenu === 'object') {
					applyToAllMenuItems(menu[i].submenu, func);
				}
				func(menu[i]);
			}
		}
		
		/**
		 * Adds a keyboard shortcut to a menu item
		 * @param {object} menuItem - The menu item to which to add the keyboard shortcut
		 * @return {boolean} Whether we found a keyboard shortcut to add to the menu item.
		 * @function setMenuItemKeyboardShortcut
		 */
		function setMenuItemKeyboardShortcut(menuItem) {
			// find if we have a custom keyboard shortcut for the command contained in the menu item
			for (var i = 0; i < unparsedCommandKeyBindings.length; i++) {
				// if yes, add it to the menu item and delete it from the unprocessed commands so we don't have to search through it later
				if (unparsedCommandKeyBindings[i].command === menuItem.command) {
					var cmd = unparsedCommandKeyBindings.splice(i, 1)[0];
					if (platform == 'mac') {
						menuItem.key = cmd.mac;
					}
					else {
						menuItem.key = cmd.win;
					}
					return true;
				}
			}
			// if we're still here, we didn't have a custom keyboard shortcut for the command, so we have to try if Ace has it
			if (editor.commands.commands.hasOwnProperty(menuItem.command)) {
				var bindKey = editor.commands.commands[menuItem.command].bindKey;
				// this is either a string or an object
				// if it is a string, use it directly
				if (typeof bindKey === 'string') {
					menuItem.key = bindKey;
				}
				// if it is an object, it contains a shortcut for mac and for win keyboards, choose the right one
				else {
					if (platform == 'mac') {
						menuItem.key = bindKey.mac;
					}
					else {
						menuItem.key = bindKey.win;
					}
				}
				if (menuItem.key) {
					// keep only the last keyboard shortcut (delete the first ones separated by '|')
					menuItem.key = menuItem.key.replace(/.*\|/g, '');
					return true;
				}
			}
			return false;
		}
		// shallow copy the command key bindings so we can delete from the new array without affecting the original one
		var unparsedCommandKeyBindings = commandKeyBindings.slice();
		// try set the keyboard shortcut for all menu items
		applyToAllMenuItems(menuItems, setMenuItemKeyboardShortcut);
	};

	/** 
	 * Generates the element tree for the menu item (called recursively for submenus).
	 * @param {object} element - The parent element to which to append the generated DOM elements
	 * @param {array} items - The menu items from which to generate the DOM elements
	 * @return {object} The generated element.
	 * @function generateMenu
	 */
	window.generateMenu = function(element, items) {
		for (var i = 0; i < items.length; i++) {
			// prepare all default values and extend them with the item so we don't have to test for undefined later
			var item = {title: '', command: '', disabledFor: [], separator: false, key: '', submenu: []};
			$.extend(item, items[i]);
			var itemHTML = '';
			if (item.separator) {
				itemHTML = '<li class="sm-separator"></li>';
			}
			else {
				var onclick = '';
				if (item.command !== '') {
					onclick = ' onclick="event.preventDefault(); execCmd(\''+item.command+'\'); $(\'#menu\').smartmenus(\'menuHideAll\');"';
				}
				var disabledFor = '';
				if (item.disabledFor.length > 0) {
					disabledFor = ' disabled-for="'+item.disabledFor.join(' ')+'"';
				}
				var keyboardShortcut = '';
				if (item.key && item.key !== '') {
					// keys are separated by '-' in item.key
					// we don't have any keyboard shortcut with '-' now, so we don't have to handle it with any special care
					var keys = item.key.split('-');
					for (var j = 0; j < keys.length; j++) {
						// replace key text representation with a glyph or a shorter form, if suitable
						if (platform == 'mac') {
							keys[j] = keys[j].replace(/Command/i, '⌘');
							keys[j] = keys[j].replace(/Ctrl/i, '⌃');
							keys[j] = keys[j].replace(/Option/i, '⌥');
							keys[j] = keys[j].replace(/Alt/i, '⌥');
							keys[j] = keys[j].replace(/Shift/i, '⇧');
						}
						keys[j] = keys[j].replace(/PageUp/i, 'PgUp');
						keys[j] = keys[j].replace(/PageDown/i, 'PgDn');
					}
					// join the keys in a style typical for the platform
					if (platform == 'mac') {
						keyboardShortcut = keys.join('');
					}
					else {
						keyboardShortcut = keys.join('<span class="menu-item-keyboard-shortcut-separator">+</span>');
					}
				}
				// finally create the item HTML
				itemHTML =
					'<li>'+
						'<a href="#"'+onclick+disabledFor+'>'+
							'<div class="menu-item">'+
								'<span class="menu-item-title">'+item.title+'</span>'+
								'<span class="menu-item-keyboard-shortcut">'+keyboardShortcut+'</span>'+
							'</div>'+
						'</a>'+
					'</li>';
			}
			// create the element from the HTML markup
			var menuItem = $(itemHTML);
			
			// first generate the submenu, then append it to the parent element
			// this way the menu is generated fully and then appended to the DOM as one piece
			if (item.submenu.length > 0)
				generateMenu($('<ul></ul>'), item.submenu).appendTo(menuItem);
			menuItem.appendTo(element);
		}
		return element;
	};

	/**
	 * Disables menu items for different kinds of tab types.
	 * @param {string} type - The type of the currently active tab
	 * @function disableMenuItems
	 */
	window.disableMenuItems = function(type) {
		$("#menu *").removeClass("disabled");
		$("#menu a[disabled-for~="+type+"]").addClass("disabled");
	};
	
	/**
	 * Initializes the menu
	 * @function initializeMenu
	 */
	window.initializeMenu = function() {
		generateKeyboardShortcuts();
		generateMenu($('#menu'), menuItems);
		$('#menu').smartmenus({
			mainMenuSubOffsetX: -1,
			subMenusSubOffsetX: 10,
			subMenusSubOffsetY: 0,
			showOnClick: true
		});
		disableMenuItems('empty');
	}


	// ----- FILE ACTIONS SCRIPTS -----
	/**
	 * Invokes a file download
	 * @param {string} filePath - The path of the file to be downloaded
	 * @function downloadFile
	 */
	window.downloadFile = function(filePath) {
		$.fileDownload('?action=file-operation&operation=download-item&id=' + filePath, {
			failCallback: function(responseHtml, url) {
				// every browser seems to give a different responseHtml
				// let's display a generic message instead of trying to parse it on a browser by browser basis
				pushErrorMessage("There was an error downloading the file.");
			}
		});
	};

	/**
	 * Opens a file (or downloads it, if necessary)
	 * @param {string} filePath - The path of the file to be opened
	 * @function openFile
	 */
	window.openFile = function(filePath) {
		// don't try to open a file which is already open in some tab
		var fileTab = tabs.getTabForFile(filePath);
		if (typeof fileTab !== 'undefined') {
			fileTab.select();
		}
		else {
			// get info about the file from the server
			$.ajax({
				method: 'GET',
				url: '?action=file-operation&operation=get-info&id='+filePath
			})
			.done(function(d) {
				if(d && typeof d.type !== 'undefined' && d.type == 'file') {
					// if the file is binary but not an image, or it is too big to open as code, download it
					if ((d.binary && $.inArray(d.ext, supportedImageExtensions) < 0) || d.size > MAXFILESIZE) {
						downloadFile(filePath);
					}
					else {
						// if the file appears to be an image, open it as such
						if ($.inArray(d.ext, supportedImageExtensions) > -1) {
							openFileAsImage(filePath);
						}
						// otherwise open it as code
						else {
							openFileAsCode(filePath);
						}
					}
				}
				else {
					// we should never get here, but who knows
					pushErrorMessage('There was an error getting information about the file.');
				}
			})
			.fail(function(request) {
				parseAndShowError(JSON.parse(request.responseText));
			});
		}
	};

	/** 
	 * Opens a file even when it is bigger than 2 MB or binary (and not an image).
	 * @param {string} filePath - The path of the file to be opened
	 * @function forceOpenFile
	 */
	window.forceOpenFile = function(filePath) {
		// don't try to open a file which is already open in some tab
		var fileTab = tabs.getTabForFile(filePath);
		if (typeof fileTab !== 'undefined') {
			fileTab.select();
		}
		else {
			// get info about the file from the server
			$.ajax({
				method: 'GET',
				url: '?action=file-operation&operation=get-info&id='+filePath
			})
			.done(function(d) {
				if(d && typeof d.type !== 'undefined' && d.type == 'file') {
					// if the file appears to be an image, open it as such
					if ($.inArray(d.ext, supportedImageExtensions) > -1) {
						openFileAsImage(filePath);
					}
					// otherwise open it as code
					else {
						openFileAsCode(filePath);
					}
				}
				else {
					// we should never get here, but who knows
					pushErrorMessage('There was an error getting information about the file.');
				}
			})
			.fail(function(request) {
				parseAndShowError(JSON.parse(request.responseText));
			});
		}
	};

	/**
	 * Opens a file in a code editor.
	 * @param {string} filePath - The path of the file to be opened
	 * @function openFileAsCode
	 */
	window.openFileAsCode = function(filePath) {
		// don't try to open a file which is already open in some tab
		var fileTab = tabs.getTabForFile(filePath);
		if (typeof fileTab !== 'undefined') {
			fileTab.select();
		}
		else {
			// get content of the file from the server
			$.ajax({
				method: 'GET',
				url: '?action=file-operation&operation=get-content&id='+filePath
			})
			.done(function(d) {
				// if the requested file is really a file, open it in an editor tab
				if(d && typeof d.type !== 'undefined' && d.type == 'file') {
					// open an editor tab
					var tab = tabs.addTab();
					tab.type = 'editor';
					tab.setFilePath(filePath);
					// create an EditSession from the contents of the file and save it into the tab
					// if the file is binary, it was sent encoded with base64, so we have to deal with that
					if (d.base64) {
						tab.data.editSession = new ace.EditSession(atob(d.content), modelist.getModeForPath('.'+d.ext).mode);
					}
					else {
						tab.data.editSession = new ace.EditSession(d.content, modelist.getModeForPath('.'+d.ext).mode);
					}
					// set up all the necessary stuff for the EditSession
					tab.data.editSession.setUndoManager(new ace.UndoManager());
					tab.data.editSession.on("change", $.proxy(tab.setModified, tab, true));
					tab.select();
				}
				else {
					// opening the file failed but the request succeeded, it's probably a folder or there was some other problem
					pushErrorMessage("There was a problem with getting contents of the requested file.");
				}
			})
			.fail(function(request) {
				parseAndShowError(JSON.parse(request.responseText));
			});
		}
	};

	/**
	 * Opens a file as an image.
	 * @param {string} filePath - The path of the file to be opened
	 * @function openFileAsImage
	 */
	window.openFileAsImage = function(filePath) {
		// don't try to open a file which is already open in some tab
		var fileTab = tabs.getTabForFile(filePath);
		if (typeof fileTab !== 'undefined') {
			fileTab.select();
		}
		else {
			// get content of the file from the server (force base64 encoding)
			$.ajax({
				method: 'GET',
				url: '?action=file-operation&operation=get-content-base64&id='+filePath
			})
			.done(function(d) {
				// if the requested file is really a file, open it in an image tab
				if(d && typeof d.type !== 'undefined' && d.type == 'file') {
					// create the image tab
					var tab = tabs.addTab();
					tab.type = 'image';
					tab.setFilePath(filePath);
					// save the image into the tab data
					tab.data.image = 'data:'+d.mimetype+';base64,'+d.content;
					tab.select();
				}
				else {
					// opening the file failed but the request succeeded, it's probably a folder or there was some other problem
					pushErrorMessage("There was a problem with getting contents of the requested file.");
				}
			})
			.fail(function(request) {
				parseAndShowError(JSON.parse(request.responseText));
			});
		}
	};

	// ----- SETTINGS POPUP SCRIPTS -----
	/**
	 * Toggles the display of the settings popup
	 * @function toggleSettingsPopup
	 */
	window.toggleSettingsPopup = function() {
		$('#settings-popup').toggleClass("popup-hidden");
	};

	/**
	 * Generates an item for the settings popup.
	 * @param {string} type - The type of the item
	 * @param {string} option - The name of the Ace option represented by the settings item
	 * @param {string} text - Title of the settings item
	 * @param {array|object} values - The possible values of the settings item
	 * @param {boolean} separator - Whether there should be a separator after the item
	 * @function generateSettingsPopupItem
	 */
	window.generateSettingsPopupItem = function(type, option, text, values, separator) {
		// if the item should have a separator after it, assign a special class to it
		var separatorClass = '';
		if (separator) {
			separatorClass = ' settings-popup-item-separator-after';
		}
		// create the appropriate markup
		var item = $('<div class="settings-popup-item'+separatorClass+'"></div>');
		var label = $('<label for="settings-item-'+option+'">'+text+'</label>');
		var input = null;
		var checkbox = null;
		// create the right inputs / selects
		switch(type) {
			case "checkbox":
				input = $('<input type="checkbox" id="settings-item-'+option+'"/>');
				checkbox = $('<label for="settings-item-'+option+'" class="settings-item-checkbox"></label>');
				input.prop('checked', aceUserOptions[option]);
				input.change(function() {
					editor.setOption(option, $(this).is(":checked"));
					aceUserOptions[option] = $(this).is(":checked");
					saveAceOptions(); 
				});
			break;
			case "number":
				input = $('<input type="number" id="settings-item-'+option+'"/>');
				if (values.min)  input.attr('min',  values.min);
				if (values.max)  input.attr('max',  values.max);
				if (values.step) input.attr('step', values.step);
				input.val(aceUserOptions[option]);
				input.change(function() {
					editor.setOption(option, parseInt($(this).val()));
					aceUserOptions[option] = parseInt($(this).val());
					saveAceOptions(); 
				});
			break;
			case "select":
				input = $('<select id="settings-item-'+option+'"></select>');
				values.forEach(function(group) {
					var optgroup = $('<optgroup></optgroup>');
					if (group.label && typeof(group.label) == 'string') {
						optgroup.attr('label', group.label);
					}
					group.values.forEach(function(option) {
						optgroup.append($('<option value="'+option.value+'">'+option.text+'</option>'));
					});
					input.append(optgroup);
				});
				input.val(aceUserOptions[option]);
				input.change(function() {
					editor.setOption(option, $(this).val());
					aceUserOptions[option] = $(this).val();
					saveAceOptions(); 
					// change the statusbar colors when the theme is changed
					if (option == 'theme') {
						// this has to be delayed because Ace takes some time to change its theme
						setTimeout(themeStatusbar, 100);
					}
				});
			break;
		}
		// append all the necessary elements to the item and then add it to the popup
		item.append(label);
		item.append(input);
		item.append(checkbox);
		$("#settings-popup-items").append(item);
	};

	/**
	 * Generates all the settings popup items.
	 * @function populateSettingsPopup
	 */
	window.populateSettingsPopup = function() {
		// the list of themes could be generated from Ace's theme files list
		// but then we wouldn't know which ones are dark and which ones are Light
		// so instead we basically take it over from Ace's kitchen sink
		generateSettingsPopupItem("select", "theme", "Theme", [
			{ label: "Light", values: [
				{ value: 'ace/theme/chrome', text: 'Chrome' },
				{ value: 'ace/theme/clouds', text: 'Clouds' },
				{ value: 'ace/theme/crimson_editor', text: 'Crimson Editor' },
				{ value: 'ace/theme/dawn', text: 'Dawn' },
				{ value: 'ace/theme/dreamweaver', text: 'Dreamweaver' },
				{ value: 'ace/theme/eclipse', text: 'Eclipse' },
				{ value: 'ace/theme/github', text: 'GitHub' },
				{ value: 'ace/theme/katzenmilch', text: 'KatzenMilch' },
				{ value: 'ace/theme/kuroir', text: 'Kuroir' },
				{ value: 'ace/theme/solarized_light', text: 'Solarized Light' },
				{ value: 'ace/theme/textmate', text: 'TextMate' },
				{ value: 'ace/theme/tomorrow', text: 'Tomorrow' },
				{ value: 'ace/theme/xcode', text: 'XCode' }
			]},
			{ label: "Dark", values: [
				{ value: 'ace/theme/ambiance', text: 'Ambiance' },
				{ value: 'ace/theme/chaos', text: 'Chaos' },
				{ value: 'ace/theme/clouds_midnight', text: 'Clouds Midnight' },
				{ value: 'ace/theme/cobalt', text: 'Cobalt' },
				{ value: 'ace/theme/idle_fingers', text: 'idle Fingers' },
				{ value: 'ace/theme/kr_theme', text: 'krTheme' },
				{ value: 'ace/theme/merbivore', text: 'Merbivore' },
				{ value: 'ace/theme/merbivore_soft', text: 'Merbivore Soft' },
				{ value: 'ace/theme/mono_industrial', text: 'Mono Industrial' },
				{ value: 'ace/theme/monokai', text: 'Monokai' },
				{ value: 'ace/theme/pastel_on_dark', text: 'Pastel on dark' },
				{ value: 'ace/theme/solarized_dark', text: 'Solarized Dark' },
				{ value: 'ace/theme/terminal', text: 'Terminal' },
				{ value: 'ace/theme/tomorrow_night', text: 'Tomorrow Night' },
				{ value: 'ace/theme/tomorrow_night_blue', text: 'Tomorrow Night Blue' },
				{ value: 'ace/theme/tomorrow_night_bright', text: 'Tomorrow Night Bright' },
				{ value: 'ace/theme/tomorrow_night_eighties', text: 'Tomorrow Night 80s' },
				{ value: 'ace/theme/twilight', text: 'Twilight' },
				{ value: 'ace/theme/vibrant_ink', text: 'Vibrant Ink' }
			]}
		]);
		generateSettingsPopupItem("number", "fontSize", "Font size", {min: 1, step: 1}, true);
		generateSettingsPopupItem("number", "scrollSpeed", "Scrolling speed", {min: 1, max: 100, step: 1});
		generateSettingsPopupItem("checkbox", "showPrintMargin", "Show print margin");
		generateSettingsPopupItem("number", "printMarginColumn", "Print margin column", {min: 1, max: 1000, step: 1});
		generateSettingsPopupItem("checkbox", "showInvisibles", "Show invisible characters");
		generateSettingsPopupItem("checkbox", "highlightSelectedWord", "Highlight other occurrences of the selected word", {}, true);
		generateSettingsPopupItem("checkbox", "useSoftTabs", "Use soft tabs");
		generateSettingsPopupItem("number", "tabSize", "Tab size", {min: 1, max: 16, step: 1}, true);
		generateSettingsPopupItem("checkbox", "behavioursEnabled", "Auto-pair parentheses, brackets and quotation marks");
		generateSettingsPopupItem("checkbox", "wrapBehavioursEnabled", "Surround selection when typing brackets or quotes");
	};

	// ----- ERROR POPUP SCRIPTS -----
	/**
	 * FIFO queue of errors which to show, showNextErrorMessage() will shift them out from the front and show them.
	 * @var {array} errorQueue
	 */
	var errorQueue = [];
	
	/**
	 * Toggles the error popup.
	 * If there are any unprocessed errors, it will show the next one instead of hiding the popup.
	 * @function toggleErrorPopup
	 */
	window.toggleErrorPopup = function() {
		if (errorQueue.length > 0) {
			showNextErrorMessage();
			$('#error-popup').removeClass("popup-hidden");
		}
		else {
			$('#error-popup').addClass("popup-hidden");
		}
	};

	/**
	 * Shows the first error message in the queue (and removes it from there).
	 * @function showNextErrorMessage
	 */
	window.showNextErrorMessage = function() {
		var errorMessage = errorQueue.shift();
		if (typeof(errorMessage) !== 'undefined') {
			$("#error-popup-text").html(errorMessage);
		}
	};

	/**
	 * Adds an error message to the error queue and shows the error popup, if it isn't already shown
	 * @param {string} errorMessage - The error message to be shown
	 * @function pushErrorMessage
	 */ 
	window.pushErrorMessage = function(errorMessage) {
		errorQueue.push(errorMessage);
		if ($('#error-popup').hasClass("popup-hidden")) {
			toggleErrorPopup();
		}
	};

	/**
	 * Tries to parse an error and show it.
	 * Mainly used by .fail() callbacks from AJAX requests.
	 * @param (object) error - Error generated during the AJAX request.
	 * @function parseAndShowError
	 */
	window.parseAndShowError = function(error) {
		if (typeof(error.errorMessage) !== 'undefined') {
			pushErrorMessage(error.errorMessage);
		}
	};

	// ----- UPLOAD POPUP SCRIPTS -----
	/**
	 * Toggles the upload popup display.
	 * @param {string} uploadPopupFolder - Folder to which to upload the files
	 * @function toggleUploadPopup
	 */
	window.toggleUploadPopup = function(uploadPopupFolder) {
		// set the right upload folder to the form
		if (typeof uploadPopupFolder === "string") {
			$("#upload-popup-folder").val(uploadPopupFolder);
		}
		// clean up the dropzone
		dropzone.removeAllFiles();
		
		$('#upload-popup').toggleClass("popup-hidden");
	};

	// ----- DROPZONE SCRIPTS -----
	// do not autodiscover Dropzone elements 
	Dropzone.autoDiscover = false;
	
	/** 
	 * Creates the Dropzone
	 * @function initializeDropzone
	 */
	window.initializeDropzone = function() {
		// fallback form for cases when Dropzone is not supported
		$('#upload-popup-form').submit(function(e) {
			e.preventDefault();
			var folder = $("#upload-popup-folder").val();
			$("#upload-popup-fallback-folder").val(folder);
			$.ajax({
				url: 'index.php',
				method: 'POST',
				data: new FormData(this),
				processData: false,
				contentType: false
			})
			.done(function() {
				$("#tree").jstree().refresh();
			})
			.fail(function(request) {
				parseAndShowError(JSON.parse(request.responseText));
			});
		});
		// create the dropzone
		dropzone = new Dropzone('#upload-popup-form-wrapper', {
			url: "index.php",
			uploadMultiple: true,
			dictDefaultMessage: "Drop files to upload or click to select them"
		});
		// set up the right data when sending the dropzone
		dropzone.on('sending', function(file, xhr, formData) {
			formData.append('action', 'file-operation');
			formData.append('operation', 'upload-files');
			formData.append('folder', $("#upload-popup-folder").val());
		});
		// refresh the jstree when a file is uploaded successfully
		dropzone.on('success', function(file){
			$("#tree").jstree(true).refresh();
		});
		// dropzone handles errors on its own via parsing the request response, no need to write an independent error handler
	};
	
	// ----- TABS SCRIPTS -----
	/**
	 * Class for all tab related actions
	 * @param {object} tabBarElem - The DOM element containing the tab bar
	 * @class Tabs
	 */
	window.Tabs = function(tabBarElem) {
		/**
		 * Tabs storage
		 * @member {array} tabs
		 * @memberOf Tabs#
		 */
		this.tabs = [];
		/**
		 * The currently active tab number (or -1, if there's none)
		 * @member {number} activeTabNumber
		 * @memberOf Tabs#
		 */
		this.activeTabNumber = -1;
		/**
		 * The biggest number assigned to a tab yet
		 * @member {number} maxTabNumber
		 * @memberOf Tabs#
		 */
		this.maxTabNumber = -1;
		/**
		 * The number of currently open tabs (not the length of the tabs storage)
		 * @member {number} openTabCount
		 * @memberOf Tabs#
		 */
		this.openTabCount = 0;
		/**
		 * The element of the tabbar (containing all the tab handles)
		 * @member {object} tabBarElem
		 * @memberOf Tabs#
		 */
		this.tabBarElem = $(tabBarElem);
		/**
		 * The maximum width of the tab handles [px]
		 * @member {number} maxTabWidth
		 * @memberOf Tabs#
		 */
		this.maxTabWidth = 200;
		
		/**
		 * Adds a new tab to the instance and returns it for further handling.
		 * @return The created tab.
		 * @function addTab#
		 * @memberOf Tabs#
		 */
		this.addTab = function() {
			// create a tab
			var tabNumber = ++this.maxTabNumber;
			var tab = new Tab(tabNumber, this);
			
			// create tab close button
			var tabCloseButtonHTML = '<a href="#" class="tab-close-button"></a>';
			var tabCloseButton = $(tabCloseButtonHTML);
			tabCloseButton.on('click', $.proxy(tab.close, tab));
			
			// create the tab handle and add it to the tab bar
			var tabHandleHTML = 
				'<li class="tab-handle" tab-number='+tabNumber+'>'+
					'<span class="tab-title">New tab</span>'+
				'</li>';
			var tabHandle = $(tabHandleHTML);
			tabHandle.append(tabCloseButton);
			tabHandle.on('click', $.proxy(tab.click, tab));
			this.tabBarElem.append(tabHandle);
			
			// add the tab to the data storage
			this.tabs.push(tab);
			this.openTabCount++;
			this.tabsEmpty(false);
			this.resizeTabs();
			return tab;
		};
		
		/**
		 * Closes the specified tab.
		 * @param {number} tabNumber - The number of the tab which is to be closed.
		 * @return {boolean} Whether the tab was successfully closed.
		 * @function closeTab
		 * @memberOf Tabs#
		 */
		this.closeTab = function(tabNumber) {
			var result = this.isTab(tabNumber);
			result = result && (this.getTab(tabNumber).invalidate());
			result = result && ((this.activeTabNumber != tabNumber) || this.selectClosestTab());
			result = result && (this.removeTab(tabNumber));
			return result;
		};
		
		/**
		 * Selects the specified tab.
		 * @param {number} tabNumber - The number of the tab which is to be selected.
		 * @return {boolean} Whether the tab was successfully selected.
		 * @function selectTab
		 * @memberOf Tabs#
		 */
		this.selectTab = function(tabNumber) {
			// no need to select the tab if it is already active
			if (this.activeTabNumber == tabNumber) {
				return true;
			}
			// do the selecting part
			if (!(this.isTab(tabNumber) && this.getTab(tabNumber).showContent() && this.getTab(tabNumber).setHandleHighlight(true))) {
				return false;
			}
			
			// deselect the previously active tab
			var previouslyActiveTab = this.getActiveTab();
			if (typeof previouslyActiveTab !== 'undefined' && previouslyActiveTab.open) {
				previouslyActiveTab.setHandleHighlight(false);
			}
			
			// set the active tab to the one just selected
			this.activeTabNumber = tabNumber;
			
			// theme the statusbar accordingly to the current tab
			themeStatusbar();
				
			return true;
		};
		
		/**
		 * Renames the specified tab.
		 * @param {number} tabNumber - The number of the tab which is to be renamed.
		 * @param {string} newName - The new name of the tab.
		 * @return {boolean} Whether the tab was successfully renamed.
		 * @function renameTab
		 * @memberOf Tabs#
		 */
		this.renameTab = function(tabNumber, newName) {
			return (this.isTab(tabNumber) && this.getTab(tabNumber).rename(newName));
		};
		
		/**
		 * Deletes the tab handle of the specified tab from the tabbar.
		 * @param {number} tabNumber - The number of the tab whose handle is to be removed.
		 * @return {boolean} Whether the tab handle was successfully removed.
		 * @function removeTab
		 * @memberOf Tabs#
		*/
		this.removeTab = function(tabNumber) {
			this.getTabHandle(tabNumber).remove();
			// if there are no tabs, call the function handling that situation 
			if (--this.openTabCount <= 0) {
				this.tabsEmpty(true);
			}
			// resize the tab handles if needed
			this.resizeTabs();
			return true;
		};
		
		/**
		 * Resizes the tab handles so they fit in the width of the tabbar.
		 * @return {number} The new width of a tab handle.
		 * @function resizeTabs
		 * @memberOf Tabs#
		 */
		this.resizeTabs = function() {
			// assess the correct tab handle width
			var tabWidth = this.tabBarElem.width() / this.openTabCount;
			if (tabWidth >= this.maxTabWidth) {
				tabWidth = this.maxTabWidth;
			}
			// restyle the tab handles
			this.getTabHandles().css('border-right-style', 'none');
			this.getTabHandles().filter('*:last-of-type').css('border-right-style', 'solid');
			this.getTabHandles().css('width', tabWidth + 'px');

			return tabWidth;
		};

		/**
		 * Function to be called when changing the number of tabs.
		 * Handles cases when you open the first tab or close the last one.
		 * @param {boolean} empty - Whether there are any tabs open.
		 * @function tabsEmpty
		 * @memberOf Tabs#
		 */
		this.tabsEmpty = function(empty) {
			// if there are no tabs, hide everything in the tab container and show the dummy message
			if (empty) {
				$("#tabbar").hide();
				$('#tab-content').hide();
				$('#tab-statusbar').hide();
				disableMenuItems('empty');
				this.activeTabNumber = -1;
				$('#dummy-message').show();
			}
			// otherwise show the tabbar, statusbar and tab content
			else {
				$("#tabbar").show();
				$('#tab-content').show();
				$('#tab-statusbar').show();
				$('#dummy-message').hide();
			}
		};
		
		/**
		 * Returns whether we have any tabs open.
		 * @return {boolean} Whether there are any tabs open.
		 * @function isEmpty
		 * @memberOf Tabs#
		 */
		this.isEmpty = function() {
			return (this.openTabCount <= 0);
		};
		
		/**
		 * Checks if a tab of the specified number exists
		 * @param {number} tabNumber - The number of the tab which is to be checked.
		 * @return {boolean} Whether the tab exists.
		 * @function isTab
		 * @memberOf Tabs#
		 */
		this.isTab = function(tabNumber) {
			return (typeof this.tabs[tabNumber] !== 'undefined');
		};
		
		/**
		 * Retrieves a tab of the specified tab number.
		 * @param {number} tabNumber - The number of the tab which is to be retrieved.
		 * @return {object} The retrieved tab.
		 * @function getTab
		 * @memberOf Tabs#
		 */
		this.getTab = function(tabNumber) {
			return this.getTabs()[tabNumber];
		};
		
		/**
		 * Retrieves all tabs.
		 * This is a helper method so the other functions don't have to access the data directly in case of data storage change.
		 * @return {array} All tabs in the tabbar
		 * @function getTabs
		 * @memberOf Tabs#
		 */
		this.getTabs = function() {
			return this.tabs;
		};
		
		/**
		 * Retrieves elements of all tab handles.
		 * @return {array} All tab handles in the tabbar
		 * @function getTabHandless
		 * @memberOf Tabs#
		 */
		this.getTabHandles = function() {
			return this.tabBarElem.children();
		};
		
		/**
		 * Retrieves the currently active tab.
		 * @return {object} The retrieved tab.
		 * @function getActiveTab
		 * @memberOf Tabs#
		 */
		this.getActiveTab = function() {
			return this.getTab(this.activeTabNumber);
		};
		
		/**
		 * Retrieves the tab handle from a specific tab.
		 * @param {number} tabNumber - The number of the tab whose handle is to be retrieved.
		 * @return {object} The retrieved tab handle.
		 * @function getTabHandle
		 * @memberOf Tabs#
		 */
		this.getTabHandle = function(tabNumber) {
			return this.tabBarElem.children("[tab-number='"+tabNumber+"']");
		};
		
		/**
		 * Checks whether a tab is open or not.
		 * @param {number} tabNumber - The number of the tab which to check.
		 * @return {boolean} Whether the tab is open.
		 * @function isTabOpen
		 * @memberOf Tabs#
		 */
		this.isTabOpen = function(tabNumber) {
			return (this.isTab(tabNumber) && this.getTab(tabNumber).open);
		};
		
		/**
		 * Marks the tab as containing a modified file
		 * @param {number} tabNumber - The number of the tab which to mark.
		 * @return {boolean} Whether the tab was marked correctly.
		 * @function setTabModified
		 * @memberOf Tabs#
		 */
		this.setTabModified = function(tabNumber, modified) {
			return (this.isTab(tabNumber) && this.getTab(tabNumber).setModified(modified));
		};
		
		/**
		 * Checks whether there are any tabs marked as modified.
		 * @return {boolean} Whether there are any modified tabs.
		 * @function isAnyTabModified
		 * @memberOf Tabs#
		 */
		this.isAnyTabModified = function() {
			var tabs = this.getTabs();
			for (var i = 0; i < tabs.length; i++) {
				if (tabs[i].open && tabs[i].isFileTab() && tabs[i].modified)
					return true;
			}
			return false;
		};
		
		/**
		 * Retrieves a tab of containing a specific file.
		 * @param {string} filePath - The path of the open file.
		 * @return {object} The retrieved tab.
		 * @function getTabForFile
		 * @memberOf Tabs#
		 */
		this.getTabForFile = function(filePath) {
			var tabs = this.getTabs();
			for (var i = 0; i < tabs.length; i++) {
				if (tabs[i].open && tabs[i].isFileTab() && tabs[i].data.filePath == filePath)
					return tabs[i];
			}
			return;
		};
		
		/**
		 * Retrieves tabs containing files from a specific folder.
		 * @param {string} folderPath - The path of the folder.
		 * @return {array} The retrieved tabs.
		 * @function getTabsInPath
		 * @memberOf Tabs#
		 */
		this.getTabsInPath = function(folderPath) {
			// make sure the path ends with a slash, so we don't accidentally search for just a path prefix, but for a directory
			if (folderPath.slice(-1) !== '/') {
				folderPath = folderPath + '/';
			}
			function filterTabs(tab) {
				return tab.isInPath(folderPath);
			}
			return this.getTabs().filter(filterTabs);
		};
		
		/**
		 * Closes all open tabs.
		 * @return {boolean} Whether all the tabs were correctly closed.
		 * @function closeAllTabs
		 * @memberOf Tabs#
		 */
		this.closeAllTabs = function() {
			var result = true;
			this.getTabs().forEach(function(tab) {
				result &= tab.close();
			});
			return result;
		};
		
		/**
		 * Closes the currently active tab.
		 * @return {boolean} Whether the was correctly closed.
		 * @function closeActiveTab
		 * @memberOf Tabs#
		 */
		this.closeActiveTab = function() {
			return this.getActiveTab().close();
		};
		
		/**
		 * Saves all the currently open tabs.
		 * @function saveAllTabs
		 * @memberOf Tabs#
		 */
		this.saveAllTabs = function() {
			this.getTabs().forEach(function(tab) {
				tab.save();
			});
		};
		
		/**
		 * Retrieves the tab to the right of the currently active tab.
		 * @param {boolean} cycle - Whether to return the leftmost tab if the currently active tab is the rightmost one.
		 * @return {object} The retrieved tab.
		 * @function getNextTab
		 * @memberOf Tabs#
		 */
		this.getNextTab = function(cycle) {
			// we can't search for the next open tab in the tab storage
			// the tab handles are sortable and so not necessarily in the same order
			var nextTabNumber = parseInt(this.getTabHandle(this.activeTabNumber).next().attr("tab-number"));
			if (isNaN(nextTabNumber) && cycle) {
				nextTabNumber = parseInt(this.getTabHandles().first().attr("tab-number"));
			}
			return this.getTab(nextTabNumber);
		};

		/**
		 * Retrieves the tab to the left of the currently active tab.
		 * @param {boolean} cycle - Whether to return the rightmost tab if the currently active tab is the leftmost one.
		 * @return {object} The retrieved tab.
		 * @function getPreviousTab
		 * @memberOf Tabs#
		 */
		this.getPreviousTab = function(cycle) {
			// we can't search for the previous open tab in the tab storage
			// the tab handles are sortable and so not necessarily in the same order
			var previousTabNumber = parseInt(this.getTabHandle(this.activeTabNumber).prev().attr("tab-number"));
			if (isNaN(previousTabNumber) && cycle) {
				previousTabNumber = parseInt(this.getTabHandles().last().attr("tab-number"));
			}
			return this.getTab(previousTabNumber);
		};
		
		/**
		 * Selects the tab closest to the currently active tab.
		 * @return {boolean} Whether the tab was successfully selected.
		 * @function selectClosestTab
		 * @memberOf Tabs#
		 */
		this.selectClosestTab = function() {
			return this.selectPreviousTab(false) || this.selectNextTab(false);
		};
		
		/**
		 * Selects the tab to the right of the currently active tab.
		 * @param {boolean} cycle - Whether to select the leftmost tab if the currently active tab is the rightmost one.
		 * @return {boolean} Whether the tab was successfully selected.
		 * @function selectNextTab
		 * @memberOf Tabs#
		 */
		this.selectNextTab = function(cycle) {
			if (this.openTabCount <= 1) {
				return true;
			}
			var nextTab = this.getNextTab(cycle);
			if (typeof nextTab !== 'undefined')
				return nextTab.select();
			
			return false;
		};

		/**
		 * Selects the tab to the left of the currently active tab.
		 * @param {boolean} cycle - Whether to select the rightmost tab if the currently active tab is the leftmost one.
		 * @return {boolean} Whether the tab was succesfully selected.
		 * @function selectPreviousTab
		 * @memberOf Tabs#
		 */
		this.selectPreviousTab = function(cycle) {
			if (this.openTabCount <= 1) {
				return true;
			}
			var previousTab = this.getPreviousTab(cycle);
			if (typeof previousTab !== 'undefined')
				return previousTab.select();
			return false;
		};
	};

	/**
	 * Class representing a single tab.
	 * @param {number} number - The identifier of the created tab.
	 * @param {object} container - The Tabs object representing the parent tabbar.
	 * @class Tab
	 */
	window.Tab = function(number, container) {
		/**
		 * The identifier of the tab.
		 * @member {number} tabNumber
		 * @memberOf Tab#
		 */
		this.tabNumber = number;

		/**
		 * The Tabs object representing the parent tabbar.
		 * @member {object} container
		 * @memberOf Tab#
		 */
		this.container = container;
		
		/**
		 * Indicates whether the tab is currently open.
		 * @member {boolean} open
		 * @memberOf Tab#
		 */
		this.open = true;
		/**
		 * Indicates whether the file open in the tab has any unsaved changes.
		 * @member {boolean} modified
		 * @memberOf Tab#
		 */
		this.modified = false;
		/**
		 * The type of the tab.
		 * @member {string} type
		 * @memberOf Tab#
		 */
		this.type = '';
		/**
		 * The title of the tab.
		 * @member {title} title
		 * @memberOf Tab#
		 */
		this.title = 'New tab';
		
		/**
		 * Property used for storing additional data for the tab content etc.
		 * @member {object} data
		 * @memberOf Tab#
		 */
		this.data = {};
		
		/**
		 * Marks the tab as modified.
		 * @param {boolean} modified - Whether the tab is to be marked as modified, or not modified.
		 * @return {boolean} Whether the tab was successfully marked.
		 * @function setModified
		 * @memberOf Tab#
		 */
		this.setModified = function(modified) {
			if (!this.open) {
				return false;
			}
			this.getTabHandle().toggleClass("tab-handle-modified", modified);
			this.modified = modified;
			return true;
		};
		
		/**
		 * Closes the tab.
		 * Calls the parent container to close this tab, which then calls invalidate on this tab.
		 * @param {object} event - The event calling to close the tab.
		 * @return {boolean} Whether the tab was successfully closed.
		 * @function close
		 * @memberOf Tab#
		 */
		this.close = function(event) {
			if (typeof event !== 'undefined') {
				event.preventDefault();
				event.stopPropagation();
			}
			return this.container.closeTab(this.tabNumber);
		};
		
		/**
		 * Selects the tab.
		 * Calls the parent container to selects this tab.
		 * @return {boolean} Whether the tab was successfully selected.
		 * @function select
		 * @memberOf Tab#
		 */
		this.select = function() {
			return this.container.selectTab(this.tabNumber);
		};
		
		/**
		 * Handles click events on the tab handle.
		 * Takes care of selecting the tab after its handle was clicked.
		 * @param {object} event - The event generated from the click.
		 * @return {boolean} Whether the tab was successfully selected.
		 * @function click
		 * @memberOf Tab#
		 */
		this.click = function(event) {
			if (typeof event !== 'undefined') {
				event.preventDefault();
				event.stopPropagation();
			}
			return this.select();
		};
		
		/**
		 * Sets the tab handle's highlight
		 * @param {boolean} highlighted - Whether to highlight the tab handle, or not.
		 * @return {boolean} Whether the tab handle was succesfully highlighted.
		 * @function setHandleHighlight
		 * @memberOf Tab#
		 */
		this.setHandleHighlight = function(highlighted) {
			if (!this.open) {
				return false;
			}
			this.getTabHandle().toggleClass("tab-handle-active", highlighted);
			return true;
		};
		
		/**
		 * Invalidates the tab.
		 * This method invalidates the tab data and properties, and if there are any unsaved changes, prompts the user to save them.
		 * @param {boolean} force - Whether to force the invalidation and don't ask for user confirmation in case of unsaved changes.
		 * @return {boolean} Whether the tab was succesfully invalidated.
		 * @function invalidate
		 * @memberOf Tab#
		 */
		this.invalidate = function(force) {
			// try to save the file if it is modified and the invalidation is not forced
			if (!force && this.isFileTab() && this.modified) {
				if (confirm("Do you want to save the file '" + this.title + "'?")) {
					this.save();
				}
			}
			// if this was a custom element tab type, delete the element from the DOM
			if (this.type == 'SQLManager' || this.type == 'scriptTester') {
				$('#tab-content>#custom-elements-wrapper>#custom-element-'+this.tabNumber).remove();
			} 
			this.title = '';
			this.tabNumber = -1;
			this.open = false;
			this.type = 'closed-tab';
			this.data = {};
			return true;
		};
		
		/**
		 * Checks if the tab contains an open file, either code or image.
		 * @return {boolean} Whether the tab contains a file.
		 * @function isFileTab
		 * @memberOf Tab#
		 */
		this.isFileTab = function() {
			return (this.type == 'editor' || this.type == 'image');
		};
		
		/**
		 * Checks if the tab is the currently active one.
		 * @return {boolean} Whether the tab is currently active.
		 * @function isActiveTab
		 * @memberOf Tab#
		 */
		this.isActiveTab = function() {
			return (this.tabNumber == this.container.activeTabNumber);
		};
		
		/**
		 * Sets the path of the file open in the tab.
		 * @param {string} filePath - The new file path.
		 * @return {boolean} Whether the file path was succesfully modified.
		 * @function setFilePath
		 * @memberOf Tab#
		 */
		this.setFilePath = function(filePath) {
			if (this.isFileTab()) {
				this.data.filePath = filePath;
				// change the title and statusbar info accordingly
				this.setTitle(getFilenameFromPath(filePath));
				setStatusbarLeft(this.data.filePath);
				return true;
			}
			return false;
		};
		
		/**
		 * Changes the prefix of the file path.
		 * Used when we are moving directories and we want to change the tab data accordingly.
		 * @param {string} originalPrefix - The original prefix of the file path.
		 * @param {string} newPrefix - The new prefix of the file path.
		 * @return {boolean} Whether the file path was modified for this tab.
		 * @function changeFilePath
		 * @memberOf Tab#
		 */
		this.changeFilePath = function(originalPrefix, newPrefix) {
			// check if the path prefixes are really directories
			if (originalPrefix.slice(-1) !== '/') {
				originalPrefix = originalPrefix + '/';
			}
			if (newPrefix.slice(-1) !== '/') {
				newPrefix = newPrefix + '/';
			}
			if (!this.isInPath(originalPrefix)) {
				return false;
			}
			var newPath = newPrefix + this.data.filePath.substr(originalPrefix.length);
			return this.setFilePath(newPath);
		};
		
		/**
		 * Checks if the file open in this tab is in a specific folder.
		 * @param {string} folderPath - The path of the folder in which we're checking if the file is located.
		 * @return {boolean} Whether the file is in the folder, or not.
		 * @function isInPath
		 * @memberOf Tab#
		 */
		this.isInPath = function(folderPath) {
			// check if the path is really a directory
			if (folderPath.slice(-1) !== '/') {
				folderPath = folderPath + '/';
			}
			return (this.isFileTab() && (this.data.filePath.indexOf(folderPath) === 0));
		};
		
		/**
		 * Retrieves the tab handle for this tab.
		 * @return {object} The tab handle of this tab.
		 * @function getTabHandle
		 * @memberOf Tab#
		 */
		this.getTabHandle = function() {
			return this.container.getTabHandle(this.tabNumber);
		};
		
		/**
		 * Sets the title of the tab.
		 * @param {string} title - The new title to set to the tab.
		 * @return {boolean} Whether the title was succesfully modified.
		 * @function setTitle
		 * @memberOf Tab#
		 */
		this.setTitle = function(title) {
			this.title = title;
			this.getTabHandle().children(".tab-title").text(title);
			return true;
		};
		
		/**
		 * Renames the tab (alias for setTitle).
		 * @param {string} newName - The new name to set to the tab.
		 * @return {boolean} Whether the name of the tab was succesfully modified.
		 * @function rename
		 * @memberOf Tab#
		 */
		this.rename = function(newName) {
			return this.setTitle(newName);
		};
		
		/**
		 * Displays the contents of the tab.
		 * This method displays the contents of the tab in the DOM and hides contents of all other tabs.
		 * @return {boolean} Whether the contents were succesfully displayed.
		 * @function showContent
		 * @memberOf Tab#
		 */
		this.showContent = function() {
			if (!this.open) {
				return false;
			}
			if (this.container.activeTabNumber == this.tabNumber) {
				return true;
			}
			
			// every tab type has to be shown differently
			// but basically this hides everything else, prepares the right content, enables only the right menu items and then shows the content
			// this will be so much nicer once EcmaScript 6 is widely supported and we'll have class inheritance
			switch(this.type) {
				case 'image':
					$('#tab-content>*:not(#image)').hide();
					
					$('#tab-content>#image>img').one('load', function() {
						// resize the image so it's centered once it loads
						$(this).css({'marginTop':'-' + $(this).height()/2 + 'px','marginLeft':'-' + $(this).width()/2 + 'px'});
					})
					.attr('src', this.data.image);
					
					disableMenuItems('image');

					setStatusbarLeft(this.data.filePath);
					setStatusbarRight("");
					
					$('#tab-content>#image').show();
				break;
				case 'editor':
					$('#tab-content>*:not(#editor)').hide();
					
					editor.setSession(this.data.editSession);
					// we need to set the options again for the new editsession
					editor.setOptions(aceOptions);
					editor.setOptions(aceUserOptions);
					
					disableMenuItems('editor');
					
					setStatusbarLeft(this.data.filePath);
					setStatusbarRight('<div class="tab-statusbar-right-cell" id="tab-statusbar-overwrite"></div><div class="tab-statusbar-right-cell" id="tab-statusbar-selection"></div>');
					$('#tab-statusbar-overwrite').on("click", function() {
						execCmd('overwrite');
					});
					updateStatusbarSelection();
					updateStatusbarOverwrite();
					
					$('#tab-content>#editor').show();
				break;
				case 'SQLManager':
				case 'scriptTester':
					$('#tab-content>*:not(#custom-elements-wrapper)').hide();
					
					$('#tab-content>#custom-elements-wrapper>*').hide();
					$('#tab-content>#custom-elements-wrapper>#custom-element-'+this.tabNumber).show();

					disableMenuItems(this.type);
					
					setStatusbarLeft("");
					setStatusbarRight("");

					$('#tab-content>#custom-elements-wrapper').show();
				break;
			}
			return true;
		};
		
		/**
		 * Saves the changes of the file in the tab.
		 * This method sends a request to the server to change the contents of the currently open file to the new contents located in the editor.
		 * @return {boolean} Whether the request to save the file was succesfully sent.
		 * @function save
		 * @memberOf Tab#
		 */
		this.save = function() {
			if (this.open && this.type == 'editor') {
				$.ajax({
					type: "POST",
					url: "index.php",
					context: this,
					data: {
						action: 'file-operation',
						operation: 'save-file',
						filePath: this.data.filePath,
						content: this.data.editSession.getValue()
					}
				})
				.done(function(d) {
					this.setModified(false);
					// if the file didn't exist previously, refresh jstree so it will show itself
					if (d.new) {
						$("#tree").jstree(true).refresh();
					}
				})
				.fail(function(request) {
					parseAndShowError(JSON.parse(request.responseText));
				});
				return true;
			}
			else {
				return false;
			}
		};
		
		/**
		 * Reverts the file to the contents located on the server.
		 * This method basically downloads the whole file again from the server and creates a new EditSession for it.
		 * @return {boolean} Whether the request to revert the file was succesfully sent.
		 * @function revert
		 * @memberOf Tab#
		 */
		this.revert = function() {
			if (this.type != 'editor') return false;
			$.ajax({
				method: "GET",
				context: this,
				url: '?action=file-operation&operation=get-content&id=' + this.data.filePath
			})
			.done(function(d) {
				if(d) {
					// create a new EditSession from the response from the server
					this.data.editSession = new ace.EditSession(d.content, modelist.getModeForPath('.'+d.ext).mode);
					this.data.editSession.setUndoManager(new ace.UndoManager());
					this.data.editSession.on("change", $.proxy(function(e) {
						this.setModified(true);
					}, this));
					this.setModified(false);
					if (this.isActiveTab()) 
						editor.setSession(this.data.editSession);
				}
			})
			.fail(function(request) {
				parseAndShowError(JSON.parse(request.responseText));
			});
			return true;
		};
		
		/**
		 * Initiates the download the file open in the tab.
		 * @return {boolean} Whether the file download was succesfully initiated.
		 * @function downloadFile
		 * @memberOf Tab#
		 */
		this.downloadFile = function() {
			if (!this.isFileTab()) {
				return false;
			}
			return downloadFile(this.data.filePath);
		};
	};
	
	/**
	 * Initializes the tabbar and the Tabs object.
	 * @function initializeTabs
	 */
	window.initializeTabs = function() {
		tabs = new Tabs($("#tabbar"));
		// make the tab bar sortable (with jQuery UI)
		$("#tabbar").sortable({
			placeholder: 'tab-sort-placeholder',
			scroll: false,
			start: function(e, ui){
				ui.placeholder.width(ui.helper.outerWidth());
			},
			update: function(e, ui) {
				// this will reapply the CSS border-right style for the right tab handles
				tabs.resizeTabs();
			}
		});
		// check for modified tabs before closing the window
		window.onbeforeunload = function(e) {
			if (tabs.isAnyTabModified()) {
				return "There are some tabs with unsaved changes. Are you sure you want to close the window and lose them?";
			}
		};
	};

	// ----- JSTREE FUNCTIONS -----
	/**
	 * Initializes the JSTree file manager.
	 * @function initializeJSTree
	 */
	window.initializeJSTree = function() {
		// resize the tree on resizing the window (46 = menu height + search height)
		$(window).resize(function() {
			var h = Math.max($(window).height() - 46, 100);
			$('#tree').height(h);
		}).resize();
		
		// initialize the jstree
		// a huge portion of the jstree initialization was taken over from the jstree demo and modified for our needs
		$('#tree').jstree({
			'plugins': ['state','dnd','sort','types','contextmenu','unique','search'],
			'types': {
				'folder': { 'icon': 'folder' },
				'file': { 'valid_children': [], 'icon': 'file' }
			},
			'core': {
				'data': {
					'url': '?action=file-operation&operation=get-node',
					'data': function(node) {
						return { 'id': node.id };
					},
					'error': function(request) {
						parseAndShowError(JSON.parse(request.responseText));
					}
				},
				'check_callback': function(o, n, p, i, m) {
					if(m && m.dnd && m.pos !== 'i') { return false; }
					if(o === "move_node" || o === "copy_node") {
						if(this.get_node(n).parent === this.get_node(p).id) { return false; }
					}
					return true;
				},
				'themes': {
					'name': 'default',
					'responsive': false,
					'variant': 'small',
					'stripes': true
				},
				'dblclick_toggle': false
			},
			'sort': function(a, b) {
				return this.get_type(a) === this.get_type(b) ? (this.get_text(a).toLowerCase() > this.get_text(b).toLowerCase() ? 1 : -1) : (this.get_type(a) < this.get_type(b) ? 1 : -1);
			},
			'dnd': {
				large_drop_target: true
			},
			'contextmenu': {
				'items': function(node) {
					var contextMenuItems = {
						"open": {
							"separator_before": false,
							"separator_after": true,
							"_disabled": false,
							"label": "Open",
							"submenu": {
								"open_file": {
									"separator_after": true,
									"label": "Open file",
									"action": function(data) {
										var inst = $.jstree.reference(data.reference);
										var obj = inst.get_node(data.reference);
										forceOpenFile(obj.id);
									}
								},
								"download_file": {
									"label": "Download file",
									"action": function(data) {
										var inst = $.jstree.reference(data.reference);
										var obj = inst.get_node(data.reference);
										downloadFile(obj.id);
									}
								}
							}
						},
						"create": {
							"separator_before": false,
							"separator_after": true,
							"_disabled": false,
							"label": "New",
							"submenu": {
								"create_folder": {
									"separator_after": true,
									"label": "Folder",
									"action": function(data) {
										var inst = $.jstree.reference(data.reference);
										var obj = inst.get_node(data.reference);
										inst.create_node(obj, { type: "folder", text: "New folder" }, "last", function(new_node) {
											setTimeout(function() { inst.edit(new_node); },0);
										});
									}
								},
								"create_file": {
									"label": "File",
									"action": function(data) {
										var inst = $.jstree.reference(data.reference);
										var obj = inst.get_node(data.reference);
										inst.create_node(obj, { type: "file", text: "New file" }, "last", function(new_node) {
											setTimeout(function() { inst.edit(new_node); },0);
										});
									}
								}
							}
						},
						"upload": {
							"separator_before": false,
							"separator_after": true,
							"_disabled": false,
							"label": "Upload here",
							"action": function(data) {
								var inst = $.jstree.reference(data.reference);
								var obj = inst.get_node(data.reference);
								toggleUploadPopup(obj.id);
							}
						},
						"rename": {
							"separator_before": false,
							"separator_after": false,
							"_disabled": false,
							"label": "Rename",
							"action": function(data) {
								var inst = $.jstree.reference(data.reference);
								var obj = inst.get_node(data.reference);
								inst.edit(obj);
							}
						},
						"remove": {
							"separator_before": false,
							"icon": false,
							"separator_after": false,
							"_disabled": false,
							"label": "Delete",
							"action": function(data) {
								var inst = $.jstree.reference(data.reference);
								var obj = inst.get_node(data.reference);
								if (inst.is_selected(obj)) {
									inst.delete_node(inst.get_selected());
								}
								else {
									inst.delete_node(obj);
								}
							}
						},
						"ccp": {
							"separator_before": true,
							"icon": false,
							"separator_after": false,
							"label": "Edit",
							"action": false,
							"submenu": {
								"cut": {
									"separator_before": false,
									"separator_after": false,
									"label": "Cut",
									"action": function(data) {
										var inst = $.jstree.reference(data.reference);
										var obj = inst.get_node(data.reference);
										if (inst.is_selected(obj)) {
											inst.cut(inst.get_selected());
										}
										else {
											inst.cut(obj);
										}
									}
								},
								"copy": {
									"separator_before": false,
									"icon": false,
									"separator_after": false,
									"label": "Copy",
									"action": function(data) {
										var inst = $.jstree.reference(data.reference);
										var obj = inst.get_node(data.reference);
										if (inst.is_selected(obj)) {
											inst.copy(inst.get_selected());
										}
										else {
											inst.copy(obj);
										}
									}
								},
								"paste": {
									"separator_before": false,
									"icon": false,
									"_disabled": function(data) {
										return !$.jstree.reference(data.reference).can_paste();
									},
									"separator_after": false,
									"label": "Paste",
									"action": function(data) {
										var inst = $.jstree.reference(data.reference);
										var obj = inst.get_node(data.reference);
										inst.paste(obj);
									}
								}
							}
						}
					};
					if (this.get_type(node) === "file") {
						delete contextMenuItems.create;
						delete contextMenuItems.upload;
					}
					else {
						delete contextMenuItems.open;
					}
					return contextMenuItems;
				}
			}
		})
		.on('delete_node.jstree', function(e, data) {
			$.post('index.php', { action: 'file-operation', operation: 'delete-item', 'id': data.node.id })
				.done(function(d) {
					// set all affected tabs as modified
					if (data.node.type == "folder") {
						var affectedTabs = tabs.getTabsInPath(data.node.id);
						affectedTabs.forEach(function(tab) {
							tab.setModified(true);
						});
					}
					else {
						var affectedTab = tabs.getTabForFile(data.node.id);
						if (typeof affectedTab !== 'undefined') {
							affectedTab.setModified(true);
						}
					}
					data.instance.set_id(data.node, d.id);
				})
				.fail(function(request) {
					data.instance.refresh();
					parseAndShowError(JSON.parse(request.responseText));
				});
		})
		.on('create_node.jstree', function(e, data) {
			$.post('index.php', { action: 'file-operation', operation: 'create-item', 'type': data.node.type, 'id': data.node.parent, 'text': data.node.text })
				.done(function(d) {
					data.instance.set_id(data.node, d.id);
				})
				.fail(function(request) {
					data.instance.refresh();
					parseAndShowError(JSON.parse(request.responseText));
				});
		})
		.on('rename_node.jstree', function(e, data) {
			$.post('index.php', { action: 'file-operation', operation: 'rename-item', 'id': data.node.id, 'text': data.text })
				.done(function(d) {
					// change filepaths for all affected tabs
					if (data.node.type == "folder") {
						var affectedTabs = tabs.getTabsInPath(data.node.id);
						affectedTabs.forEach(function(tab) {
							tab.changeFilePath(data.node.id, d.id);
						});
					}
					else {
						var affectedTab = tabs.getTabForFile(data.node.id);
						if (typeof affectedTab !== 'undefined') {
							affectedTab.setFilePath(d.id);
						}
					}
					data.instance.set_id(data.node, d.id);
				})
				.fail(function(request) {
					data.instance.refresh();
					parseAndShowError(JSON.parse(request.responseText));
				});
		})
		.on('move_node.jstree', function(e, data) {
			$.post('index.php', { action: 'file-operation', operation: 'move-item', 'id': data.node.id, 'parent': data.parent })
				.done(function(d) {
					// change filepaths for all affected tabs
					if (data.node.type == "folder") {
						var affectedTabs = tabs.getTabsInPath(data.node.id);
						affectedTabs.forEach(function(tab) {
							tab.changeFilePath(data.node.id, d.id);
						});
					}
					else {
						var affectedTab = tabs.getTabForFile(data.node.id);
						if (typeof affectedTab !== 'undefined') {
							affectedTab.setFilePath(d.id);
						}
					}
					data.instance.refresh();
				})
				.fail(function(request) {
					data.instance.refresh();
					parseAndShowError(JSON.parse(request.responseText));
				});
		})
		.on('copy_node.jstree', function(e, data) {
			$.post('index.php', { action: 'file-operation', operation: 'copy-item', 'id': data.original.id, 'parent': data.parent })
				.done(function(d) {
					data.instance.refresh();
				})
				.fail(function(request) {
					data.instance.refresh();
					parseAndShowError(JSON.parse(request.responseText));
				});
		})
		.on('dblclick.jstree', function(e, data) {
			// undocumented feature of jstree, data parameter passed empty
			// get the doubleclicked node
			var nodeId = $(e.target).closest('li')[0].id;
			var node = $("#tree").jstree(true).get_node(nodeId);
			// if the node is a folder, expand/collapse it
			if (node.type == "folder" || node.type == "default") {
				$("#tree").jstree(true).toggle_node(e.target);
			}
			// if the tab is a file, try to open it, if it's not already open
			else {
				var fileTab = tabs.getTabForFile(nodeId);
				if (typeof fileTab !== 'undefined') {
					fileTab.select();
				}
				else {
					openFile(nodeId);
				}
			}
		});
		// search the jstree on changing the value of the search box
		$('#tree-search-box').keyup(function() {
			var searchValue = $('#tree-search-box').val();
			$('#tree').jstree(true).search(searchValue);
		});
	};
	
	// ----- MISCELLANEOUS -----
	$(window).resize(function() {
		// resize the image on resizing the window
		var img = $('#image>img');
		img.css({'marginTop':'-' + img.height()/2 + 'px','marginLeft':'-' + img.width()/2 + 'px'});
	}).resize();

	// ----- INITIALIZE EVERYTHING -----
	$(function() {
		// initialize everything when the DOM is loaded
		// it has to be in this order
		initializeJSTree();
		registerCommandKeyBindings();
		initializeAce();
		initializeMenu();
		populateSettingsPopup();
		initializeDropzone();
		initializeTabs();
	});

}

if (supportedBrowser) {
	main();
}