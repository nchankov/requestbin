<?php
$headers = getallheaders();
$body = file_get_contents("php://input");
$requestlog = 'requests.log';

function is_admin()
{
	$env = parse_ini_file('.env');
	if ($env['admin_ip'] == $_SERVER['REMOTE_ADDR']) {
		return true;
	}
	// check if it's using proxy. Make sure you configure your proxy server (nginx)
	// with the following directive:
	// proxy_set_header X-Real-IP $remote_addr;
	$headers = getallheaders();
	if ($env['admin_ip'] == $headers['X-Real-IP']) {
		return true;
	}
	return false;
}

function format_headers($headers)
{
	$return = '<table class="headers">';
	$return .= '<thead>';
	$return .= '<tr><th>Key</th><th>Value</th></tr>';
	$return .= '</thead>';
	$return .= '<tbody>';
	foreach ($headers as $key => $value) {
		$return .= '<tr>';
		$return .= '<td class="key">' . $key . '</td>';
		$return .= '<td class="value highlight">' . $value . '</td>';
		$return .= '</tr>';
	}
	$return .= '</tbody>';
	$return .= '</table>';
	return $return;
}

//check if new data arrived
if (isset($_GET['changed'])) {
	if (is_file($requestlog)) {
		header('Content-type: application/json');
		header("Content-Disposition: inline; filename=ajax.json");
		echo json_encode(['size' => filesize($requestlog)]);
	} else {
		echo json_encode(['size' => false]);
	}
	die();
}

//clear the log
if (isset($_GET['clear'])) {
	if (is_admin() && is_file($requestlog)) {
		unlink($requestlog);
	}
	header('Location: ' . $_SERVER['SCRIPT_URI'] . '?inspect');
	die();
}

$contents = '';
if (is_file($requestlog)) {
	$contents = file_get_contents($requestlog);
}
if (isset($_GET['inspect'])) {
	?>
	<!DOCTYPE html>
	<html lang="en">

	<head>
		<meta charset="utf-8">
		<script>
			let size;
			async function logJSONData() {
				const response = await fetch("?changed");
				const jsonData = await response.json();
				if (typeof size == 'undefined') {
					size = jsonData.size;
				} else {
					if (size != jsonData.size) {
						console.log('new request came in!');
						window.location.reload();
					}
				}
			}
			setInterval(function() {
				
				logJSONData();
			}, 3000);
		</script>
		<style>
			pre.body {
				overflow-x: auto;
				background-color: #efefef;
				padding: 15px;
			}

			.request {
				margin-bottom: 20px;
				padding: 30px;
			}

			h3 {
				margin-bottom: 0;
				padding-bottom: 0;
			}

			pre {
				outline: 1px solid #ccc;
				padding: 5px;
				margin: 5px;
			}

			.string {
				color: green;
			}

			.number {
				color: darkorange;
			}

			.boolean {
				color: blue;
			}

			.null {
				color: magenta;
			}

			.key {
				color: red;
			}

			.clear-log {
				position: fixed;
				top: 10px;
				right: 10px;
				padding: 10px 15px;
				background-color: #f00;
				border: 1px solid #900;
				text-decoration: none;
				border-radius: 5px;
				color: #333;
				box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.7);
			}

			.clear-log:active {
				background-color: #d00;
				color: #fff;
				box-shadow: none;
			}

			table.headers {
				width: 100%;
			}

			table.headers thead th {
				border: 1px solid #ddd;
				background: #ccc;
				padding: 9px;
			}

			table.headers tbody td.key {
				border: 1px solid #efefef;
				background: #efefef;
				padding: 9px;
			}

			table.headers tbody td.value {
				border: 1px solid #efefef;
				padding: 9px;
				word-wrap: break-word;
				width: 100%;
			}

			table.headers tbody {
				overflow: auto;
				max-height: 30px;
			}
		</style>
	</head>

	<body>
		<?php if (is_admin()): ?>
			<a href="?clear" class="clear-log" onclick="return confirm('Are you sure?');">Clear log</a>
		<?php endif; ?>
		<?php
		print_r($contents);
		?>
		<script>
			function insertAfter(referenceNode, newNode) { referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling); }; function syntaxHighlight(json) { json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) { var cls = 'number'; if (/^"/.test(match)) { if (/:$/.test(match)) { cls = 'key'; } else { cls = 'string'; } } else if (/true|false/.test(match)) { cls = 'boolean'; } else if (/null/.test(match)) { cls = 'null'; } return '<span class="' + cls + '">' + match + '</span>'; }); }
			elements = document.querySelectorAll('pre.body'); for (var i = 0; i < elements.length; i++) { elements[i].addEventListener('click', function () { var range = document.createRange(); range.selectNode(this); window.getSelection().removeAllRanges(); if (typeof this.dataset.clicked == 'undefined' || this.dataset.clicked == 0) { window.getSelection().addRange(range); json = JSON.parse(this.innerHTML); var str = JSON.stringify(json, undefined, 4); var parsedBody = document.createElement('pre'); parsedBody.innerHTML = syntaxHighlight(str); insertAfter(this, parsedBody); this.dataset.clicked = 1; } else { this.dataset.clicked = 0; this.parentNode.removeChild(this.nextElementSibling); } }); }
			elements = document.querySelectorAll('.highlight'); for (var i = 0; i < elements.length; i++) { elements[i].addEventListener('click', function () { var range = document.createRange(); range.selectNode(this); window.getSelection().removeAllRanges(); window.getSelection().addRange(range); }); }
		</script>
	</body>

	</html>
	<?php
	die();
}

if (!$fp = fopen($requestlog, 'w+')) {
	echo "Cannot open file ($requestlog)";
	exit;
}
$request = '<div class="request">';
$request .= '<h2>' . $_SERVER['REQUEST_METHOD'] . ' to ' . $_SERVER['REQUEST_URI'] . ' ' . date('Y-m-d H:i:s') . '</h2>';
$request .= "<h3>Headers</h3>";
$request .= '<pre>' . format_headers($headers) . '</pre>';
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
	$request .= '<div class="body">';
	if ($body) {
		$request .= "<h3>Body</h3>";
		$request .= '<pre class="body">' . print_r($body, true) . '</pre>';
	}
	if ($_POST) {
		$request .= "<h3>_POST</h3>";
		$request .= '<pre class="body">' . print_r($_POST, true) . '</pre>';
	}
	$request .= "</div>";

}
//real url path
$prefix = substr(dirname($_SERVER['SCRIPT_FILENAME']), strlen(dirname(__DIR__)));
//fake path (rewrite url part)
$fake_path = substr(parse_url($_SERVER['SCRIPT_URI'], PHP_URL_PATH), strlen($prefix) + 1);

$response = null;
if (is_file(__DIR__ . '/' . $fake_path)) {
	$response = file_get_contents(__DIR__ . '/' . $fake_path);
	$request .= '<div class="body">';
	$request .= "<h3>Response</h3>";
	$request .= '<pre class="body">' . $response . '</pre>';
	$request .= "</div>";
}
$request .= '</div>';


if (fwrite($fp, $request . $contents) === FALSE) {
	echo "Cannot write to file ($requestlog)";
	exit;
}
fclose($fp);

if ($response) {
	header('Content-Type: application/json');
	echo $response;
} else {
	echo 'ok';
}
