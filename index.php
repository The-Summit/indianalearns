<?php
require 'config/config.php';
require 'vendor/autoload.php';

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

$app = new \Slim\Slim(array(
	'db.host' => INDIANALEARNS_DB_HOST,
	'db.user' => INDIANALEARNS_DB_USER,
	'db.pass' => INDIANALEARNS_DB_PASS,

	'db.handle' => new PDO('mysql:host='.INDIANALEARNS_DB_HOST.';dbname=indianalearns', 
	                       INDIANALEARNS_DB_USER, 
	                       INDIANALEARNS_DB_PASS,
	                       array(PDO::ATTR_PERSISTENT => true)),

	'view' => new \Slim\Views\Twig()
));

$app->get('/', function () {
		echo "Hello, user!";
	});

$app->group('/api/v1', function() use ($app) {
		// all api output is JSON
		$app->response->headers->set('Content-Type', 'application/json;charset=utf8');
		
		$app->get('/', function() use ($app) {
				$db = $app->config('db.handle');
				$q = $db->prepare('SELECT name, location, description, origin_url FROM indianalearns.index');
				$q->setFetchMode(PDO::FETCH_ASSOC);
				$q->execute();

				$results = array();
				while($row = $q->fetch()) {
					array_push($results, $row);
				}

				echo prepare_json_output($results);
			});

		$app->get('/corporations(/(:id))', function($id=null) use ($app) {
				$db = $app->config('db.handle');

				if($id == null) {
					$q = $db->prepare('SELECT * FROM indianalearns.directory_corp');
					$q->setFetchMode(PDO::FETCH_ASSOC);
					$q->execute();
				} else {
					$q = $db->prepare('SELECT * FROM indianalearns.directory_corp WHERE id = ?');
					$q->setFetchMode(PDO::FETCH_ASSOC);
					$q->execute(array($id));
				}

				$results = array();
				while($row = $q->fetch()) {
					array_push($results, $row);
				}

				echo prepare_json_output($results);
			});

		$app->get('/schools(/(:id))', function($id=null) use ($app) {
				$db = $app->config('db.handle');

				if($id == null) {
					$q = $db->prepare('SELECT * FROM indianalearns.directory_schools');
					$q->setFetchMode(PDO::FETCH_ASSOC);
					$q->execute();
				} else {
					$q = $db->prepare('SELECT * FROM indianalearns.directory_schools WHERE id = ?');
					$q->setFetchMode(PDO::FETCH_ASSOC);
					$q->execute(array($id));
				}
				
				$results = array();
				while($row = $q->fetch()) {
					array_push($results, $row);
				}

				echo prepare_json_output($results);
			});

		$app->get('/reports/istep_corporations', function() use ($app) {
				$db = $app->config('db.handle');

				$sql = 'SELECT * FROM indianalearns.report_istep_corporations';
				$sql_clauses = array();
				$sql_params = array();

				$corp_id = $app->request->params('corp_id');
				if(!empty($corp_id)) {
					$sql_params['corp_id'] = $corp_id;
					$sql_clauses[] = 'corp_id = :corp_id';
				}

				$corp_name = $app->request->params('corp_name');
				if(!empty($corp_name)) {
					$sql_params['corp_name'] = $corp_name;
					$sql_clauses[] = 'corp_name LIKE `%:corp_name%`';
				}

				$group = $app->request->params('group');
				if(!empty($group)) {
					$sql_params['group'] = $group;
					$sql_clauses[] = '`report_istep_corporations.group` LIKE `%:group%`';
				}

				$year = $app->request->params('year');
				if(!empty($year)) {
					$sql_params['year'] = $year;
					$sql_clauses[] = 'year = :year';
				}

				if(!empty($sql_clauses)) {
					$sql .= ' WHERE ';
					$sql .= implode(' AND ', $sql_clauses);
				}

				$q = $db->prepare($sql);
				$q->setFetchMode(PDO::FETCH_ASSOC);
				$q->execute($sql_params);

				$results = array();
				while($row = $q->fetch()) {
					array_push($results, $row);
				}

				echo prepare_json_output($results);
			});
	});

$app->run();
?>
