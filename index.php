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
		$id = "home";
		makePage($app,$id);
	});
	$app->get('/:id', function ($id) use ($app) {
		makePage($app,$id);
	});
	$app->get('/demos/:id', function ($id) use ($app) {
		makePage($app,$id);
	});
});
$app->group('/api/v1', function() use ($app) {
		// all api output is JSON
		$app->response->headers->set('Content-Type', 'application/json;charset=utf8');
		
		// allow requests from all origins
		$app->response->headers->set('Access-Control-Allow-Origin', '*');

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
					$row['gis_url'] = 
						'http://maps.indiana.edu/arcgis/rest/services/Infrastructure'
						.'/Schools_Districts_2013_USCB/Mapserver/0/query'
						.'?text='.str_replace(' ', '+', $row['name'])
						.'&f=json';
					
					array_push($results, $row);
				}

				echo prepare_json_output($results);
			});

		$app->get('/schools(/(:id))', function($id=null) use ($app) {
				$db = $app->config('db.handle');

				$sql = 'SELECT * FROM indianalearns.directory_schools';

				if($id != null) {
					$sql .= ' WHERE id = :id';
				}

				$sql .= ' LIMIT :offset,:limit';
				$q = $db->prepare($sql);
				$q->setFetchMode(PDO::FETCH_ASSOC);

				$limit = $app->request->params('limit');
				$offset = $app->request->params('offset');
				if(empty($limit)) {
					$limit = 100;
				}
				if(empty($offset)) {
					$offset = 0;
				}
				$q->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
				$q->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

				if($id) {
					$q->bindValue(':id', $id);
				}

				$q->execute();
								
				$results = array();
				while($row = $q->fetch()) {
					// add a link to the arcgis service at maps.indiana.edu
					$row['gis_url'] = 
						'http://maps.indiana.edu/arcgis/rest/services/Infrastructure'
						.'/Schools_IDOE/Mapserver/0/query'
						.'?where=IDOE_ID+%3D+\''.(str_pad($row['id'], 4, '0', STR_PAD_LEFT)).'\''
						.'&f=json';
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
				$sql .= ' LIMIT :offset,:limit';
				// $sql is now complete
				
				// prepare query object and start binding values
				$q = $db->prepare($sql);
				$q->setFetchMode(PDO::FETCH_ASSOC);

				// set default offset/limit values
				$limit = $app->request->params('limit');
				$offset = $app->request->params('offset');
				if(empty($limit)) {
					$limit = 500;
				}
				if(empty($offset)) {
					$offset = 0;
				}
				$q->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
				$q->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

				// bind specific user parameters
				foreach($sql_params as $key=>$value) {
					$q->bindValue(':'.$key, $value);
				}

				$q->execute();

				$results = array();
				while($row = $q->fetch()) {
					array_push($results, $row);
				}

				echo prepare_json_output($results);
			});
	});

$app->run();
?>
