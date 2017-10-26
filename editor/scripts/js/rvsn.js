/**
 * Main function of the script.
 * Encapsulates the whole functionality, and is called it only in a supported browser.
 * @function main
 */
function main() {
	// ----- GENERAL IDE VARIABLES AND FUNCTIONS -----

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
	 * Initializes the editor.
	 * @function initializeAce
	 */
	window.initializeAce = function() {
		if ($("#editor").length > 0) {
			// required for automatically choosing syntax mode for files
			modelist = ace.require('ace/ext/modelist');
			editor = ace.edit('editor');

			// load settings currently saved in the cookie and combine them with the default ones
			$.extend(aceUserOptions, Cookies.getJSON('ace-options'));
			
			// set editor options
			editor.setOptions(aceOptions);
			editor.setOptions(aceUserOptions);
			editor.setOption('readOnly', true);
		}
	};

	// ----- FILE ACTIONS SCRIPTS -----
	/**
	 * Invokes a file download
	 * @param {string} filePath - The path of the file to be downloaded
	 * @function downloadFile
	 */
	window.downloadFile = function(filePath) {
		$.fileDownload('?action=rvsn-operation&operation=download-file&commit=' + commitID + '&id=' + filePath, {
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
		// get info about the file from the server
		$.ajax({
			method: 'GET',
			url: '?action=rvsn-operation&operation=get-info&commit=' + commitID + '&id=' + filePath
		})
		.done(function(d) {
			test = d;
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
	};

	/** 
	 * Opens a file even when it is bigger than 2 MB or binary (and not an image).
	 * @param {string} filePath - The path of the file to be opened
	 * @function forceOpenFile
	 */
	window.forceOpenFile = function(filePath) {
		// get info about the file from the server
		$.ajax({
			method: 'GET',
			url: '?action=rvsn-operation&operation=get-info&commit=' + commitID + '&id=' + filePath
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
	};

	/**
	 * Opens a file in a code editor.
	 * @param {string} filePath - The path of the file to be opened
	 * @function openFileAsCode
	 */
	window.openFileAsCode = function(filePath) {
		// get content of the file from the server
		$.ajax({
			method: 'GET',
			url: '?action=rvsn-operation&operation=get-content&commit=' + commitID + '&id=' + filePath
		})
		.done(function(d) {
			// if the requested file is really a file, open it in an editor tab
			if(d && typeof d.type !== 'undefined' && d.type == 'file') {
				// create an EditSession from the contents of the file and save it into the tab
				// if the file is binary, it was sent encoded with base64, so we have to deal with that
				if (d.base64) {
					editor.setSession(new ace.EditSession(atob(d.content), modelist.getModeForPath('.'+d.ext).mode));
				}
				else {
					editor.setSession(new ace.EditSession(d.content, modelist.getModeForPath('.'+d.ext).mode));
				}
				$('#file-dummy-message').hide();
				$('#file-content').show();
				$('#file-image').hide();
				$('#editor').show();
			}
			else {
				// opening the file failed but the request succeeded, it's probably a folder or there was some other problem
				pushErrorMessage("There was a problem with getting contents of the requested file.");
			}
		})
		.fail(function(request) {
			parseAndShowError(JSON.parse(request.responseText));
		});
	};

	/**
	 * Opens a file as an image.
	 * @param {string} filePath - The path of the file to be opened
	 * @function openFileAsImage
	 */
	window.openFileAsImage = function(filePath) {
		// get content of the file from the server (force base64 encoding)
		$.ajax({
			method: 'GET',
			url: '?action=rvsn-operation&operation=get-content&base64=true&commit=' + commitID + '&id=' + filePath
		})
		.done(function(d) {
			// if the requested file is really a file, open it in an image tab
			if(d && typeof d.type !== 'undefined' && d.type == 'file') {
				// save the image into the tab data
				$('#file-image > img')
				.one('load', function() {
					// resize the image so it's centered once it loads
					$(this).css({'marginTop':'-' + $(this).height()/2 + 'px','marginLeft':'-' + $(this).width()/2 + 'px'});
				})
				.attr('src', 'data:'+d.mimetype+';base64,'+d.content);

				$('#file-dummy-message').hide();
				$('#file-content').show();
				$('#editor').hide();
				$('#file-image').show();
			}
			else {
				// opening the file failed but the request succeeded, it's probably a folder or there was some other problem
				pushErrorMessage("There was a problem with getting contents of the requested file.");
			}
		})
		.fail(function(request) {
			parseAndShowError(JSON.parse(request.responseText));
		});
	};

	// ----- REVERT TO COMMIT SCRIPTS -----
	/**
	 * Sends a request to revert an item to a specific commit
	 * @param {string} commitID - The ID of the commit to which to revert to.
	 * @param {string} itemID - The ID of the item which to revert.
	 * @function revertToCommit
	 */
	window.revertToCommit = function(commitID, itemID) {
		var requestData = {
			action: 'rvsn-operation',
			operation: 'revert-all',
			commit: commitID
		};
		if (typeof(itemID) !== 'undefined') {
			requestData.operation = 'revert-item';
			requestData.item = itemID;
		}
		$.ajax({
			method: "POST",
			url: "index.php",
			data: requestData
		})
		.done(function(d) {
			$('#commit-revert-button > *').html('Revert successful!');
			$('#commit-revert-button').addClass('success');
			$('#commit-revert-button').removeAttr('onclick');
		})
		.fail(function(request) {
			$('#commit-revert-button > *').html('Revert failed!');
			$('#commit-revert-button').addClass('fail');
			$('#commit-revert-button').removeAttr('onclick');
			parseAndShowError(JSON.parse(request.responseText));
		});
	};
	
	// ----- BROWSE AT COMMIT SCRIPTS -----
	/**
	 * Navigates to a page where the user can browse the state of his project at a selected commit.
	 * @param {string} commitSelectBoxName - The name of the select box where the user selected his commit.
	 * @function browseAtSelectedCommit
	 */
	window.browseAtSelectedCommit = function(commitSelectBoxName) {
		var commitSelectBoxID = '#commit-select-box-'+commitSelectBoxName;
		var commitID = $(commitSelectBoxID+' > input').val();
		
		if (commitID == '-1') {
			$(commitSelectBoxID+' > .commit-select-box-current').removeClass('error');
			// trigger redraw
			$(commitSelectBoxID+' > .commit-select-box-current').outerWidth();
			$(commitSelectBoxID+' > .commit-select-box-current').addClass('error');
		}
		else {
			// can't use window.location.href due to IE's way of handling base href
			window.open("rvsn/browse/"+commitID, "_self");
		}
	};
	
	// ----- COMMIT COMPARE SCRIPTS -----
	/**
	 * Opens or closes a specific commit select box.
	 * @param {string} commitSelectBoxID - The ID of the commit select box to toggle.
	 * @function toggleCommitSelectBox
	 */
	window.toggleCommitSelectBox = function(commitSelectBoxID) {
		$('#'+commitSelectBoxID).toggleClass('expanded');
	};
	
	/**
	 * Marks a commit as selected when it is clicked.
	 * @param {string} commitSelectBoxID - The ID of the commit select box where the commit was clicked.
	 * @param {string} commitID - The ID of the clicked commit.
	 * @function commitSelectBoxSelectCommit
	 */
	window.commitSelectBoxSelectCommit = function(commitSelectBoxID, commitID) {
		$('#'+commitSelectBoxID+' > input').val(commitID);
		$('#'+commitSelectBoxID+' > .commit-select-box-current').empty();
		$('#'+commitSelectBoxID+' > .commit-select-box-current').append($('#'+commitSelectBoxID+'-item-'+commitID).children().not('.commit-select-box-item-datetime').clone());
		toggleCommitSelectBox(commitSelectBoxID);
	};
	
	/**
	 * Navigates to a page showing the comparison between two selected commits.
	 * @param {string} olderSelectBoxName - The name of the select box with the older commit for the comparison.
	 * @param {string} newerSelectBoxName - The name of the select box with the newer commit for the comparison.
	 * @function compareSelectedCommits
	 */
	window.compareSelectedCommits = function(olderSelectBoxName, newerSelectBoxName) {
		var olderSelectBoxID = '#commit-select-box-'+olderSelectBoxName;
		var newerSelectBoxID = '#commit-select-box-'+newerSelectBoxName;
		var olderCommitID = $(olderSelectBoxID+' > input').val();
		var newerCommitID = $(newerSelectBoxID+' > input').val();
		var validCommitIDs = true;
		if (olderCommitID == '-1') {
			$(olderSelectBoxID+' > .commit-select-box-current').removeClass('error');
			// trigger redraw
			$(olderSelectBoxID+' > .commit-select-box-current').outerWidth();
			$(olderSelectBoxID+' > .commit-select-box-current').addClass('error');
			validCommitIDs = false;
		}
		if (newerCommitID == '-1') {
			$(newerSelectBoxID+' > .commit-select-box-current').removeClass('error');
			// trigger redraw
			$(olderSelectBoxID+' > .commit-select-box-current').outerWidth();
			$(newerSelectBoxID+' > .commit-select-box-current').addClass('error');
			validCommitIDs = false;
		}
		if (validCommitIDs) {
			// can't use window.location.href due to IE's way of handling base href
			window.open("rvsn/compare/"+olderCommitID+"/"+newerCommitID, "_self");
		}
	};
	
	// ----- COMMIT POPUP SCRIPTS -----
	/**
	 * Toggles the display of the popup for creating a new commit.
	 * @function toggleCommitPopup
	 */
	window.toggleCommitPopup = function() {
		$('#commit-popup').toggleClass("popup-hidden");
	};

	/**
	 * Sends a request to commit the changes in the user's project.
	 * @function commitChanges
	 */
	window.commitChanges = function() {
		$.ajax({
			method: "POST",
			url: "index.php",
			data: {
				action: 'rvsn-operation',
				operation: 'commit-changes',
				title: $('#commit-form-title').val(),
				comment: $('#commit-form-comment').val(),
			}
		})
		.done(function(d) {
			$('#commit-form-submit-button > *').html('Commit successful!');
			$('#commit-form-submit-button').addClass('success');
			$('#commit-form-submit-button').removeAttr('onclick');
		})
		.fail(function(request) {
			$('#commit-form-submit-button > *').html('Commit failed!');
			$('#commit-form-submit-button').addClass('fail');
			$('#commit-form-submit-button').removeAttr('onclick');
			parseAndShowError(JSON.parse(request.responseText));
		});
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


	// ----- JSTREE FUNCTIONS -----
	/**
	 * Initializes the JSTree file manager.
	 * @function initializeJSTree
	 */
	window.initializeJSTree = function() {
		if ($('#tree').length > 0) {
			// initialize the jstree
			// a huge portion of the jstree initialization was taken over from the jstree demo and modified for our needs
			$('#tree').jstree({
				'plugins': ['state','sort','types','contextmenu','unique','search'],
				'types': {
					'folder': { 'icon': 'folder' },
					'file': { 'valid_children': [], 'icon': 'file' }
				},
				'core': {
					'data': {
						'url': '?action=rvsn-operation&operation=get-node&commit=' + commitID,
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
				'contextmenu': {
					'items': function(node) {
						var contextMenuItems = {
							"open_file": {
								"label": "Open file",
								"action": function(data) {
									var inst = $.jstree.reference(data.reference);
									var obj = inst.get_node(data.reference);
									forceOpenFile(obj.id);
								}
							},
							"download_file": {
								"separator_after": true,
								"label": "Download file",
								"action": function(data) {
									var inst = $.jstree.reference(data.reference);
									var obj = inst.get_node(data.reference);
									downloadFile(obj.id);
								}
							},
							"revert_item": {
								"label": "Revert to this commit",
								"action": function(data) {
									var inst = $.jstree.reference(data.reference);
									var obj = inst.get_node(data.reference);
									revertToCommit(commitID, obj.id);
								}
							}
						};
						if (this.get_type(node) === "folder") {
							delete contextMenuItems.open_file;
							delete contextMenuItems.download_file;
						}
						return contextMenuItems;
					}
				}
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
					openFile(nodeId);
				}
			});
			// search the jstree on changing the value of the search box
			$('#tree-search-box').keyup(function() {
				var searchValue = $('#tree-search-box').val();
				$('#tree').jstree(true).search(searchValue);
			});
		}
	};

	// ----- MISCELLANEOUS FUNCTIONS -----
	$(window).resize(function() {
		// resize the image on resizing the window
		var img = $('#file-image > img');
		img.css({'marginTop':'-' + img.height()/2 + 'px','marginLeft':'-' + img.width()/2 + 'px'});
	}).resize();
	
	// initialize everything when the DOM is ready
	$(function() {
		initializeAce();
		initializeJSTree();
	});
}

if (supportedBrowser) {
	main();
}