<?php
require '.././libs/Slim/Slim.php';
require_once 'dbHelper.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();
$app = \Slim\Slim::getInstance();
$db = new dbHelper();

/**
 * Database Helper Function templates
 */
/*
select(table name, where clause as associative array)
insert(table name, data as associative array, mandatory column names as array)
update(table name, column names as associative array, where clause as associative array, required columns as array)
delete(table name, where clause as array)
*/


// Get events list 
$app->get('/events', function() { 
    global $db;
    $rows = $db->select("events","event_id,event,event_start_datetime,event_end_datetime,status",array());
    echoResponse(200, $rows);
});

// Get this_month_events 
$app->get('/monthevents', function() { 
    global $db;
    $rows = $db->monthevents("events");
    echoResponse(200, $rows);
});

// Get this_week_events 
$app->get('/weekevents', function() { 
    global $db;
    $rows = $db->weekevents("events");
    echoResponse(200, $rows);
});


// Get this_week_events 
$app->get('/totalevents', function() { 
    global $db;
    $rows = $db->totalevents("events");
    echoResponse(200, $rows);
});

// add new event
$app->post('/events', function() use ($app) { 
    $data = json_decode($app->request->getBody());
    $mandatory = array('event','event_start_datetime','event_end_datetime');
    global $db;
    $db ->eventValid($data, $mandatory);
    $db->eventexist("events", $data, $mandatory);
    $rows = $db->insert("events", $data, $mandatory);
    if($rows["status"]=="success"){
         $rows["message"] = "Event added successfully.";
     }
    echoResponse(200, $rows);
});

$app->put('/events/:event_id', function($event_id) use ($app) { 
    $data = json_decode($app->request->getBody());
    $condition = array('event_id'=>$event_id);
    $mandatory = array('event','event_start_datetime','event_end_datetime');
    global $db;
    $db->eventexist("events", $data, $mandatory);

    $rows = $db->update("events", $data, $condition, $mandatory);
    if($rows["status"]=="success")
        $rows["message"] = "event updated successfully.";
    echoResponse(200, $rows);
});

$app->put('/approve/:event_id', function($event_id) use ($app) { 
    $data = json_decode($app->request->getBody());
    $condition = array('event_id'=>$event_id);
    $mandatory = array();
    global $db;
    $rows = $db->update("events", $data, $condition, $mandatory);
    if($rows["status"]=="success")
        $rows["message"] = "event approved successfully.";
    echoResponse(200, $rows);
});

$app->delete('/events/:event_id', function($event_id) { 
    global $db;
    $rows = $db->delete("events", array('event_id'=>$event_id));
    if($rows["status"]=="success")
        $rows["message"] = "Product removed successfully.";
    echoResponse(200, $rows);
});

function echoResponse($status_code, $response) {
    global $app;
    $app->status($status_code);
    $app->contentType('application/json');
    echo json_encode($response,JSON_NUMERIC_CHECK);
}

$app->run();
?>