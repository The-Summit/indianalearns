<?php
require '../config/config.php';

$db = new PDO('mysql:host='.INDIANALEARNS_DB_HOST.';dbname=indianalearns',
              INDIANALEARNS_DB_USER,
              INDIANALEARNS_DB_PASS,
              array(PDO::ATTR_PERSISTENT => true));

header('Content-Type: application/json');

$swagger = array();
$paths = array();
$definitions = array();

// header
$swagger['swagger'] = '2.0';
$swagger['info'] = array(
    'title' => "IndianaLearns API",
    'description' => "We believe important conversations about the future of our educational system can be enhanced by utilizing data freely available to the citizens of Indiana. We've organized it here.",
    'version' => "1.0.0"
);
$swagger['schemes'] = array('http');
$swagger['basePath'] = '/api/v1';
$swagger['produces'] = array('application/json');

// paths
$paths = array();

$generic_404 = array('description' => "The requested resource could not be found.");
$limit_params = array(
    array(
        'name' => 'limit',
        'in' => 'query',
        'description' => 'Limit number of results (default 100)',
        'type' => 'number',
        'required' => false
    ),
    array(
        'name' => 'offset',
        'in' => 'query',
        'description' => 'Used in conjunction with the `limit` parameter to offset the results',
        'type' => 'number',
        'required' => false
    ),
    array(
        'name' => 'orderby',
        'in' => 'query',
        'description' => 'Field to sort results',
        'type' => 'string',
        'required' => false
    ),
    array(
        'name' => 'sort',
        'in' => 'query',
        'description' => 'Either `ASC` or `DESC` Used in conjunction with the `orderby` parameter to sort the results',
        'type' => 'string',
        'required' => false
    )
);

/*
 * define paths
 */

// corporation directory
$paths['/corporations'] = array(
    'get' => array(
        'summary' => "Corporation Directory",
        'description' => "Directory of all known school districts/corporations",
        'parameters' => $limit_params,
        'tags' => array('corporations'),
        'responses' => array(
            '200' => array(
                'description' => "An array of Corporations",
                'schema' => array(
                    'type' => 'array',
                    'items' => array('$ref' => 'Corporation')
                )
            ),
            '404' => $generic_404
        )
    )
);

$paths['/corporations/{id}'] = array(
    'get' => array(
        'summary' => "Find specific school corporation by ID",
        'description' => "ID is assigned by the State of Indiana",
        'parameters' => array(array(
            'name' => 'id',
            'in' => 'path',
            'description' => 'School corporation ID as assigned by State of Indiana.',
            'required' => true,
            'type' => 'number'
        )),
        'tags' => array('corporations'),
        'responses' => array(
            '200' => array(
                'description' => "A specific Corporation",
                'schema' => array('$ref' => 'Corporation')
            ),
            '404' => $generic_404
        )
    )
);

// school directory
$paths['/schools'] = array(
    'get' => array(
        'summary' => "School Directory",
        'description' => "Directory of all known schools",
        'parameters' => $limit_params,
        'tags' => array('schools'),
        'responses' => array(
            '200' => array(
                'description' => "An array of Schools",
                'schema' => array(
                    'type' => 'array',
                    'items' => array('$ref' => 'School')
                )
            ),
            '404' => $generic_404
        )
    )
);

$paths['/schools/{id}'] = array(
    'get' => array(
        'summary' => "Find specific school by ID",
        'description' => "ID is assigned by the State of Indiana",
        'parameters' => array(array(
            'name' => 'id',
            'in' => 'path',
            'description' => 'School ID as assigned by State of Indiana.',
            'required' => true,
            'type' => 'number'
        )),
        'tags' => array('schools'),
        'responses' => array(
            '200' => array(
                'description' => "A specific School",
                'schema' => array('$ref' => 'School')
            ),
            '404' => $generic_404
        )
    )
);

// now for the fun part:
// we're going to build up api specs for the report sets
// by introspecting the database, using the 'index' table as a starting point

// grab the list of available reports
$q = $db->prepare('SELECT * FROM indianalearns.index'
                  .' WHERE `table` LIKE \'report_%\';');
$q->setFetchMode(PDO::FETCH_ASSOC);
$q->execute();

// now iterate over each report and build the api description for that endpoint
while($row = $q->fetch()) {
    // we can provide several pieces of information here:
    //  * summary
    //  * description
    //  * parameters
    //  * tags
    //  * responses

    // for now we'll keep it pretty minimal, filling in text for humans from the index table.
    // we can add support for more details later

    $location = $row['location'];        // api endpoint
    $summary = $row['name'];
    $description = $row['description'];
    $tags = array('reports');

    $parameters = array();
    $return_items = array();

    // api query parameters are determined based on table columns, so we'll query the INFORMATION_SCHEMA
    $q2 = $db->prepare('SELECT * FROM INFORMATION_SCHEMA.COLUMNS'
                       .' WHERE TABLE_SCHEMA = \'indianalearns\' AND TABLE_NAME = :table');
    $q2->bindParam(':table', $row['table'], PDO::PARAM_STR);
    $q2->setFetchMode(PDO::FETCH_ASSOC);
    $q2->execute();

    while($col = $q2->fetch()) {

        // simplistic parameter type conversion, may want/need to do more research into Swagger's type system
        $type = 'number';
        if(strpos($col['DATA_TYPE'], 'CHAR') !== false) {
            $type = 'string';
        }


        $parameters[]= array(
            'name' => $col['COLUMN_NAME'],
            'in' => 'query',
            'description' => $col['COLUMN_COMMENT'],    // we'll need to make sure we add comments to tables/columns that are missing them
            'required' => false,
            'type' => $type
        );

        $return_items[$col['COLUMN_NAME']]= array(
            'description' => $col['COLUMN_COMMENT'],    // we'll need to make sure we add comments to tables/columns that are missing them
            'type' => $type
        );
    }

    // all the report endpoints support the basic limit/offset query parameters
    $parameters = array_merge($parameters, $limit_params);

    $responses = array(
        '200' => array(
            'description' => $description,
            'schema' => array(
                'type' => 'array',
                // for now, the report apis only return lists of records (which are basically the same as the parameters)
                'items' => array('properties' => $return_items )
            )
        ),
        '404' => $generic_404
    );

    $paths[$row['location']] = array(
        'get' => array(                 // for now the api only supports GET anyway
            'summary' => $summary,
            'description' => $description,
            'parameters' => $parameters,
            'tags' => $tags,
            'responses' => $responses
        )
    );
}

/*
 * define definitions
 */
$definitions['Corporation'] = array(
    'properties' => array(
        "id" => array(
            "type" => "number",
            "description" => "School corporation ID as assigned by State of Indiana."
        ),
        "name" => array(
            "type" => "string",
            "description" => "School corporation name as registered in the State of Indiana."
        ),
        "address" => array(
            "type" => "string",
            "description" => "Street address of corporation headquarters."
        ),
        "city" => array(
            "type" => "string",
            "description" => "City of corporation headquarters."
        ),
        "zip" => array(
            "type" => "number",
            "description" => "ZIP of corporation headquarters."
        ),
        "grade_span" => array(
            "type" => "string",
            "description" => "A span of grades the corporation covers, where KG = Kindergarten, PK = Pre-Kindergarten, and SP & ED = Special Education."
        ),
        "phone" => array(
            "type" => "string",
            "description" => "Phone number of corporation headquarters."
        ),
        "fax" => array(
            "type" => "string",
            "description" => "Fax of corporation headquarters."
        ),
        "superintendent_name" => array(
            "type" => "string",
            "description" => "The name of the superintendent of the corporation."
        ),
        "superintendent_email" => array(
            "type" => "string",
            "description" => "The email of the superintendent of the corporation."
        ),
        "gis_url" => array(
            "type" => "string",
            "description" => "The GIS URL for the maps.indiana.edu entry related to the corporation."
        )
    )
);

$definitions['School'] = array(
    'properties' => array(
        "id" => array(
            "type" => "number",
            "description" => "School ID as assigned by State of Indiana."
        ),
        "name" => array(
            "type" => "string",
            "description" => "School name as registered in the State of Indiana."
        ),
        "address" => array(
            "type" => "string",
            "description" => "Street address of school."
        ),
        "city" => array(
            "type" => "string",
            "description" => "City of school."
        ),
        "zip" => array(
            "type" => "number",
            "description" => "ZIP of school."
        ),
        "grade_span" => array(
            "type" => "string",
            "description" => "A span of grades the school covers, where KG = Kindergarten, PK = Pre-Kindergarten, and SP & ED = Special Educatio.n10"
        ),
        "phone" => array(
            "type" => "string",
            "description" => "Phone number of schol."
        ),
        "fax" => array(
            "type" => "string",
            "description" => "Fax of school."
        ),
        "principal_name" => array(
            "type" => "string",
            "description" => "The name of the principal of the shcool."
        ),
        "principal_email" => array(
            "type" => "string",
            "description" => "The email of the principal of the shcool."
        ),
        "gis_url" => array(
            "type" => "string",
            "description" => "The GIS URL for the maps.indiana.edu entry related to the school."
        ),
        "lat" => array(
            "type" => "number",
            "description" => "The latitude of the school"
        ),
        "lon" => array(
            "type" => "number",
            "description" => "The longitude of the school"
        )
    )
);

// attach our paths/definitions to the swagger object
$swagger['paths'] = $paths;
$swagger['definitions'] = $definitions;

// $q = $db->prepare('SELECT name, location, description, origin_url FROM indianalearns.index');
// $q->setFetchMode(PDO::FETCH_ASSOC);
// $q->execute();

// $results = array();
// while($row = $q->fetch()) {
//     array_push($results, $row);
// }

// $swagger['test'] = $results;

echo json_encode($swagger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
