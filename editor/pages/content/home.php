<!DOCTYPE html>
<html>
	<head>
		<base href="<?php echo $baseHref ?>" />
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width" />
		<title>Emergency Editor</title>

		<script src="./lib/jquery/jquery.min.js"></script>
		<script src="./lib/jquery-ui/jquery-ui.min.js"></script>

		<script src="./lib/ace/src-min-noconflict/ace.js"></script>
		<script src="./lib/ace/src-min-noconflict/ext-modelist.js"></script>
		<script src="./lib/ace/src-min-noconflict/ext-language_tools.js"></script>

		<script src="./lib/cookie/jquery.cookie.js"></script>

		<script src="./lib/dropzone/dropzone.min.js"></script>
		<link  href="./lib/dropzone/dropzone.min.css" rel="stylesheet" type="text/css" />

		<script src="./lib/fileDownload/jquery.fileDownload.js"></script>

		<link  href="./lib/jstree/themes/default/style.min.css" rel="stylesheet" type="text/css" />
		<script src="./lib/jstree/jstree.min.js"></script>

		<link  href="./lib/smartmenus/sm-core-css.css" rel="stylesheet" type="text/css" />
		<script src="./lib/smartmenus/jquery.smartmenus.min.js"></script>

		<script src="./lib/mousetrap/mousetrap.min.js"></script>
		<script src="./lib/mousetrap/mousetrap-global-bind.min.js"></script>

		<link href="./styles/common.css"   rel="stylesheet" type="text/css" />
		<link href="./styles/jstree.css"   rel="stylesheet" type="text/css" />
		<link href="./styles/home.css"     rel="stylesheet" type="text/css" />
		<link href="./styles/sm-small.css" rel="stylesheet" type="text/css" />
	</head>
	<body>
		<div class="main" id="main">
			<ul id="menu" class="sm sm-small"></ul>
			<div class="container">
				<div id="tree"></div>
				<div class="tree-search-box-container"><input type="search" id="tree-search-box" class="tree-search-box" placeholder="Search files and folders"></div>
				<div id="tab-container" class="tab-container">
					<ul id="tabbar" class="tabbar"></ul>
					<div id="dummy-message" class="tab-dummy-message">
						<span>Double-click a file to open it.</span>
					</div>
					<div id="tab-content" class="tab-content">
						<pre id="editor" class="tab-editor"></pre>
						<div id="image"  class="tab-image">
							<img src="images/placeholder.png" alt=""/>
						</div>
						<div id="custom-elements-wrapper" class="tab-custom-elements-wrapper"></div>
					</div>
					<div id="tab-statusbar" class="tab-statusbar">
						<div id="tab-statusbar-left" class="tab-statusbar-left tab-statusbar-content"></div>
						<div id="tab-statusbar-right" class="tab-statusbar-right tab-statusbar-content"></div>
					</div>
				</div>
			</div>
			<div id="settings-popup" class="popup popup-hidden">
				<div id="settings-popup-background" class="popup-background" onclick="toggleSettingsPopup()"></div>
				<div id="settings-popup-content" class="popup-content">
					<div id="settings-popup-title" class="popup-title">Editor settings <span class="popup-close" onclick="toggleSettingsPopup()"></span></div>
					<div id="settings-popup-items-wrapper" class="settings-popup-items-wrapper">
						<div id="settings-popup-items" class="settings-popup-items"></div>
						<div id="settings-popup-items-shadow" class="settings-popup-items-shadow"></div>
					</div>
				</div>
			</div>
			<div id="upload-popup" class="popup popup-hidden">
				<div id="upload-popup-background" class="popup-background" onclick="toggleUploadPopup()"></div>
				<div id="upload-popup-content" class="popup-content">
					<div id="upload-popup-title" class="popup-title">Upload files <span class="popup-close" onclick="toggleUploadPopup()"></span></div>
					<div id="upload-popup-form-wrapper" class="dropzone">
						<div class="upload-popup-form fallback">
							<form action="index.php" id="upload-popup-form" enctype="multipart/form-data" method="post">
								<input type="hidden" name="folder" id="upload-popup-fallback-folder">
								<input type="hidden" name="action" value="file-operation">
								<input type="hidden" name="operation" value="upload_files">
								<input type="file"   name="file[]" id="upload-popup-files" multiple>
								<input type="submit" name="submit" value="Upload">
							</form>
						</div>
					</div>
				</div>
				<input type="hidden" name="folder" id="upload-popup-folder">
			</div>
			<div id="error-popup" class="popup popup-hidden">
				<div id="error-popup-background" class="popup-background" onclick="toggleErrorPopup()"></div>
				<div id="error-popup-content" class="popup-content error-popup-content">
					<div id="error-popup-title" class="popup-title">Error <span class="popup-close" onclick="toggleErrorPopup()"></span></div>
					<div id="error-popup-text" class="error-popup-text"></div>
				</div>
			</div>
		</div>
<?php include("pages/snippets/browserSupport.php"); ?>
		<script src="./scripts/js/home.js"></script>
	</body>
</html>