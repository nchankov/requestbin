
<?php
$headers = getallheaders();
$body = file_get_contents("php://input");

$requestlog = 'requests.log';

if (isset($_GET['clear'])) {
	if (is_file($requestlog)) {
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
	    	pre {outline: 1px solid #ccc; padding: 5px; margin: 5px; }
	    	.string { color: green; }
	    	.number { color: darkorange; }
	    	.boolean { color: blue; }
	    	.null { color: magenta; }
	    	.key { color: red; }
	    </style>
	  </head>
	  <body>
	  	<a href="?clear">Clear log</a>
	    <?php
	    //echo '<pre>';
	    print_r($contents);
	    //echo '</pre>';?>
	    <script>
	    	function insertAfter(referenceNode, newNode) {
		        referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
		    }
	    	function syntaxHighlight(json) {
	    	    json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	    	    return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
	    	        var cls = 'number';
	    	        if (/^"/.test(match)) {
	    	            if (/:$/.test(match)) {
	    	                cls = 'key';
	    	            } else {
	    	                cls = 'string';
	    	            }
	    	        } else if (/true|false/.test(match)) {
	    	            cls = 'boolean';
	    	        } else if (/null/.test(match)) {
	    	            cls = 'null';
	    	        }
	    	        return '<span class="' + cls + '">' + match + '</span>';
	    	    });
	    	}

	    	elements = document.querySelectorAll('pre.body');
	    	for (var i = 0; i < elements.length; i++) {
	    		elements[i].addEventListener('click', function() {
	    			var range = document.createRange();
			        range.selectNode(this);
			        window.getSelection().removeAllRanges();
	    			if (typeof this.dataset.clicked == 'undefined' || this.dataset.clicked == 0) {
			        	window.getSelection().addRange(range);
			    		json = JSON.parse(this.innerHTML);
			    		var str = JSON.stringify(json, undefined, 4);
			    		var parsedBody = document.createElement('pre');
			    		parsedBody.innerHTML = syntaxHighlight(str);
			    		insertAfter(this, parsedBody);
		    			this.dataset.clicked = 1;
		    		} else {
		    			this.dataset.clicked = 0;
		    			this.parentNode.removeChild(this.nextElementSibling);
		    		}
		    	});	
	    	}
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
$request .= '<h2>' . $_SERVER['REQUEST_METHOD'] . ' ' . date('Y-m-d H:i:s') . '</h2>';
$request .= "<h3>Headers</h3>";
$request .= '<pre>'.print_r($headers, true).'</pre>';
if ($_SERVER['REQUEST_METHOD'] != 'GET') {
	$request .= '<div class="body">';
	$request .= "<h3>Body</h3>";
	$request .= '<pre class="body">'.print_r($body, true).'</pre>';
	$request .= "</div>";

}
$request .= '</div>';


if (fwrite($fp, $request . $contents) === FALSE) {
    echo "Cannot write to file ($requestlog)";
    exit;
}
fclose($fp);

echo 'ok';
