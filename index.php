<?php
require 'config/config.php';
require 'vendor/autoload.php';
require 'utils.php';

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

$app->get('/', function () use ($app) {
		$app->redirect('/home/');
	});
$app->group('/home', 'prepForHumans', function() use ($app){
		$app->get('/', function () use ($app){
				$file = "index";
				$text = file_get_contents("pages/" . $file . ".page");
				$app->render($file . ".twig",array("text"=>$text));
			});
		$app->get('/demos', function () use ($app) {
				$file = "demos";
				$text = explode("~~~",file_get_contents("pages/" . $file . ".page"));
				$app->render($file . ".twig",array("head"=>$text[0],"demos"=>$text[1]));
			});
	});

$app->group('/api/v1', function() use ($app) {
		// all api output is JSON
		$app->response->headers->set('Content-Type', 'application/json;charset=utf8');

		// set the 404 / route not found handler to something that won't try to output HTML
		$app->notFound(function() use ($app) {
				echo prepare_json_output(array('error'=>
				                               array('code'=>404,
				                                     'message'=>'the requested resource could not be found')));
			});
		
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

		$app->get('/reports/:report', function($report) use ($app) {
				if($report === 'istep_corporations' || $report === 'istep_schools_public') {
					$table = 'report_'.$report;
				} else {
					$app->halt(404, array('error'=>
					                      array('code'=>404,
					                            'message'=>'the requested resource could not be found')));
				}

				$db = $app->config('db.handle');

				// get the list of queryable fields for this report
				//$sql = 'SELECT report_column, field_type, operations, id_table FROM indianalearns.meta_report_queryables WHERE report_id = 4;';
				$sql = 'SELECT'
					.'   COLUMN_NAME,'
					.'   DATA_TYPE,'
					.'   COLUMN_COMMENT'
					.'  FROM information_schema.COLUMNS'
					.'  WHERE TABLE_SCHEMA = \'indianalearns\''
					.'    AND TABLE_NAME = \''.$table.'\'';
				$q = $db->prepare($sql);
				$q->execute();

				// prepare to assemble our actual query
				$sql = 'SELECT * FROM indianalearns.'.$table;
				$sql_clauses = array();
				$sql_params = array();

				// iterate over queryable fields
				while($row = $q->fetch()) {
					// see if the request included a parameter matching this queryable field
					$col = $row['COLUMN_NAME'];
					$request_field = $app->request->params($col);
					if(!empty($request_field)) {
						// TODO: test for comparison operators, >, >=, etc
						$sql_params[$col] = $request_field;    // $sql_params['corp_id'] = $app->request->params('corp_id');
						$sql_clauses[]= '`' . $col . '`' . ' = :' . $col;  // something like 'corp_id = :corp_id'
					}
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
