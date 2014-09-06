<?php
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
		print_r($ex->getMessage());
	}
}