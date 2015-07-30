		<div class="unsupported-message-wrapper" id="unsupported-message-wrapper">
			<div class="unsupported-message">
				<div class="unsupported-message-main">Sorry, you're using an unsupported browser.</div>
				<div class="unsupported-message-additional">If you wish to use this tool, please use a supported browser.</div>
				<div class="unsupported-message-additional">Major supported browsers include:</div>
				<div class="unsupported-message-browser-list">
					<div class="unsupported-message-browser-list-item">
						<a href="http://windows.microsoft.com/en-us/internet-explorer">
							<img class="unsupported-message-browser-list-item-image" src="./images/ie.png" alt="Internet Explorer"/>
							<div class="unsupported-message-browser-list-item-title">Internet Explorer version 10+</div>
						</a>
					</div>
					<div class="unsupported-message-browser-list-item">
						<a href="https://www.mozilla.org/en-US/firefox">
							<img class="unsupported-message-browser-list-item-image" src="./images/firefox.png" alt="Mozilla Firefox"/>
							<div class="unsupported-message-browser-list-item-title">Mozilla Firefox</div>
						</a>
					</div>
					<div class="unsupported-message-browser-list-item">
						<a href="http://www.google.com/chrome">
							<img class="unsupported-message-browser-list-item-image" src="./images/chrome.png" alt="Google Chrome"/>
							<div class="unsupported-message-browser-list-item-title">Google Chrome</div>
						</a>
					</div>
					<div class="unsupported-message-browser-list-item">
						<a href="http://www.opera.com">
							<img class="unsupported-message-browser-list-item-image" src="./images/opera.png" alt="Opera"/>
							<div class="unsupported-message-browser-list-item-title">Opera</div>
						</a>
					</div>
					<div class="unsupported-message-browser-list-item">
						<a href="https://www.apple.com/safari">
							<img class="unsupported-message-browser-list-item-image" src="./images/safari.png" alt="Safari"/>
							<div class="unsupported-message-browser-list-item-title">Safari</div>
						</a>
					</div>
					<!-- when Windows 10 comes out, add a link to Edge and find out what's the situation with IE and what to do about it -->
				</div>
			</div>
		</div>
		<script type="text/javascript">
			// detect unsupported browsers (for now Internet Explorer 9 or lower) 
			// we need to use window.atob, which is in IE 10 or greater
			// Dropzone also doesn't support IE 9 or lower
			// there's no point in trying to support IE 8 or lower, because there's just too many unsupported features
			var supportedBrowser = true;
			if (!window.atob) {
				// we are in IE 9 or lower
				supportedBrowser = false;
				document.getElementById("main").style.display = "none";
				document.getElementById("unsupported-message-wrapper").style.display = "block";
			}
			else {
				document.getElementById("main").style.display = "block";
				document.getElementById("unsupported-message-wrapper").style.display = "none";
			}
		</script>
