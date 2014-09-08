<?php
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine;
// since we're not currently using PHP > 5.3, we're missing some niceties and have to implement them ourselves
function indent_json_string($json) {
	// nicked from http://www.daveperrett.com/articles/2008/03/11/format-json-with-php/
	$result      = '';
    $pos         = 0;
    $strLen      = strlen($json);
    $indentStr   = '  ';
    $newLine     = "\n";
    $prevChar    = '';
    $outOfQuotes = true;

    for ($i=0; $i<=$strLen; $i++) {

	    // Grab the next character in the string.
	    $char = substr($json, $i, 1);

	    // Are we inside a quoted string?
	    if ($char == '"' && $prevChar != '\\') {
		    $outOfQuotes = !$outOfQuotes;

		    // If this character is the end of an element,
		    // output a new line and indent the next line.
        
	    } else if(($char == '}' || $char == ']') && $outOfQuotes) {
		    $result .= $newLine;
		    $pos --;
		    for ($j=0; $j<$pos; $j++) {
			    $result .= $indentStr;
            
		    }
        
	    }

	    // Add the character to the result string.
	    $result .= $char;

	    // If the last character was the beginning of an element,
	    // output a new line and indent the next line.
	    if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
		    $result .= $newLine;
		    if ($char == '{' || $char == '[') {
			    $pos ++;
            
		    }

		    for ($j = 0; $j < $pos; $j++) {
			    $result .= $indentStr;
            
		    }
	    }

	    $prevChar = $char;
    }

    return $result;
}

// takes an array of key=>value pairs, returns a pretty-printed JSON string
function prepare_json_output($data) {
	return indent_json_string(preg_replace(';\\\/;', '/', json_encode($data)));
}

function prepForHumans(){
	$app = \Slim\Slim::getInstance();
	
	// Compile LESS & add stylesheet link to view
	$style = autoCompileLess();
	$app->view->appendData(array("style"=>$style));
	
	//Include current location for navigation class
	$uri = $app->request->getResourceUri();
	$app->view()->appendData(array("uri" => $uri));

	// Make Markdown available
	$engine = new MarkdownEngine\MichelfMarkdownEngine();
	$app->view->parserExtensions = array(new MarkdownExtension($engine));
	
	// Set content type for browsers
	$app->response->headers->set('Content-Type', 'text/html;charset=utf8');
}
function autoCompileLess() {
  // load the cache
	try {
		$asset_path = 'assets/css/';
		$cache_dir = 'cache/';
		$less_files = array( $asset_path. 'style.less' => '/' );
		Less_Cache::SetCacheDir($asset_path . $cache_dir);
		return "/". $asset_path . $cache_dir . Less_Cache::Get( $less_files);
	 } catch (Exception $ex) {
		echo "LESS PHP had a compile error: ";
		echo $ex->getMessage();
	}
}
function makePage($app,$id){
	if(!is_readable("pages/" . $id . ".page") && !is_readable("templates/" . $id . ".twig")){
		$app->response->setStatus(404);
		$id = "404";
	}elseif(is_readable("pages/" . $id . ".page")){
		$text = explode("~~~",file_get_contents("pages/" . $id . ".page"));
	}elseif(is_readable("templates/" . $id . ".twig")){
		$text = array();
	}
	$app->render("page.twig",array(
		"page"=>$id,
		"title"=>array_shift($text),
		"body"=>array_shift($text)
		)
	);
}