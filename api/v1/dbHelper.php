<?php
require_once 'config.php'; // Database setting constants [DB_HOST, DB_NAME, DB_USERNAME, DB_PASSWORD]
class dbHelper {
    private $db;
    private $err;
    function __construct() {
        $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8';
        try {
            $this->db = new PDO($dsn, DB_USERNAME, DB_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (PDOException $e) {
            $response["status"] = "error";
            $response["message"] = 'Connection failed: ' . $e->getMessage();
            $response["data"] = null;
            //echoResponse(200, $response);
            exit;
        }
    }
    function select($table, $columns, $where){
        try{
            $a = array();
            $w = "";
            foreach ($where as $key => $value) {
                $w .= " and " .$key. " like :".$key;
                $a[":".$key] = $value;
            }
            $stmt = $this->db->prepare("select ".$columns." from ".$table." where 1=1 ". $w);
            $stmt->execute($a);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(count($rows)<=0){
                $response["status"] = "warning";
                $response["message"] = "No data found.";
            }else{
                $response["status"] = "success";
                $response["message"] = "Data selected from database";
            }
                $response["data"] = $rows;
        }catch(PDOException $e){
            $response["status"] = "error";
            $response["message"] = 'Select Failed: ' .$e->getMessage();
            $response["data"] = null;
        }
        return $response;
    }
    function monthevents($table){
        
            $stmt = $this->db->prepare("select count(*) from ".$table." WHERE YEAR(event_start_datetime) = YEAR(NOW()) AND MONTH(event_start_datetime)=MONTH(NOW())");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            if($count<=0){
                $response["status"] = "warning";
                $response["message"] = "No data found.";

            }else{
                $response["status"] = "success";
                $response["message"] = "Data selected from database";
            }
            $response["monthevents"] = $count;
        
        return $response;
    }



    function weekevents($table){
            
            $stmt = $this->db->prepare("select count(*) from ".$table." WHERE WEEKOFYEAR(event_start_datetime) = WEEKOFYEAR(NOW())");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            if($count<=0){
                $response["status"] = "warning";
                $response["message"] = "No data found.";

            }else{
                $response["status"] = "success";
                $response["message"] = "Data selected from database";
            }
            $response["weekevents"] = $count;
        
        return $response;
    }

    function totalevents($table){
             
            $stmt = $this->db->prepare("select count(*) from ".$table);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            if($count<=0){
                $response["status"] = "warning";
                $response["message"] = "No data found.";

            }else{
                $response["status"] = "success";
                $response["message"] = "Data selected from database";
            }
            $response["totalevents"] = $count;
        
        return $response;
    }

    function eventexist($table, $columnsArray, $requiredColumnsArray) {
        $this->verifyRequiredParams($columnsArray, $requiredColumnsArray);
        
        try{
            $c = array();
            $v = array();

            foreach ($columnsArray as $key => $value) {
                $c[] = $key;
                $v[] = $value;
            }
            $startdate = "";
            $enddate = "";

            $startdate = $v[1];
            $enddate = $v[2];

            $sql = "select * from events
                    WHERE event_start_datetime BETWEEN '$startdate' AND '$enddate'
                    OR
                    event_end_datetime BETWEEN '$startdate' AND '$enddate'
                    OR
                    event_start_datetime <= '$startdate' AND  event_end_datetime >= '$enddate'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $num_rows = count($rows);
            if($num_rows > 0){
                $response = array();
            $response["status"] = "error";
            $response["message"] = "Event already exist please change your datetime";
            echoResponse(200, $response);
            exit;
            }

        }catch(PDOException $e){
            $response["status"] = "error";
            $response["message"] = 'Select Failed: ' .$e->getMessage();
            $response["data"] = null;
        }
    }



    function eventValid($columnsArray, $requiredColumnsArray) {   
        $this->verifyRequiredParams($columnsArray, $requiredColumnsArray);     
        try{
            $c = array();
            $v = array();

            foreach ($columnsArray as $key => $value) {
                $c[] = $key;
                $v[] = $value;
            }
            $startdate = "";
            $enddate = "";
            $response = array();

            $startdate = $v[1];
            $enddate = $v[2];
            
            $datediff = date("Y-m-d H:i:s", strtotime($startdate ."+30 minutes"));
            //$diffDt = new DateTime($datediff);

            date_default_timezone_set('Asia/Kolkata');
            $date = date('Y-m-d H:i:s', time());
            
                if($startdate < $date or $enddate < $date){
                    $response["status"] = "error";
                    $response["message"] = "Datetime already passed please update your datetime";
                    echoResponse(200, $response);
                    exit;
                }elseif ($startdate > $enddate) {
                    $response["status"] = "error";
                    $response["message"] = "Event start datetime should not be greater than plaese update datetime";
                    echoResponse(200, $response);
                    exit;                
                }elseif($datediff > $enddate){
                    $response["status"] = "error";
                    $response["message"] = "Event should not be less than 30 min";
                    echoResponse(200, $response);
                    exit;
                } 

            }catch(PDOException $e){
                $response["status"] = "error";
                $response["message"] = 'Select Failed: ' .$e->getMessage();
                $response["data"] = null;
            }
        }
    
    function insert($table, $columnsArray, $requiredColumnsArray) {
        $this->verifyRequiredParams($columnsArray, $requiredColumnsArray);
        try{
            $a = array();
            $c = "";
            $v = "";
            foreach ($columnsArray as $key => $value) {
                $c .= $key. ", ";
                $v .= ":".$key. ", ";
                $a[":".$key] = $value;
            }
            $c = rtrim($c,', ');
            $v = rtrim($v,', ');
            $stmt =  $this->db->prepare("INSERT INTO $table($c) VALUES($v)");
            $stmt->execute($a);
            $affected_rows = $stmt->rowCount();
            $lastInsertId = $this->db->lastInsertId();
            $response["status"] = "success";
            $response["message"] = $affected_rows." row inserted into database";
            $response["data"] = $lastInsertId;
        }catch(PDOException $e){
            $response["status"] = "error";
            $response["message"] = 'Insert Failed: ' .$e->getMessage();
            $response["data"] = 0;
        }
        return $response;
    }

    function update($table, $columnsArray, $where, $requiredColumnsArray){ 
        $this->verifyRequiredParams($columnsArray, $requiredColumnsArray);
        try{
            $a = array();
            $w = "";
            $c = "";
            foreach ($where as $key => $value) {
                $w .= " and " .$key. " = :".$key;
                $a[":".$key] = $value;
            }
            foreach ($columnsArray as $key => $value) {
                $c .= $key. " = :".$key.", ";
                $a[":".$key] = $value;
            }
                $c = rtrim($c,", ");

            $stmt =  $this->db->prepare("UPDATE $table SET $c WHERE 1=1 ".$w);
            $stmt->execute($a);
            $affected_rows = $stmt->rowCount();
            if($affected_rows<=0){
                $response["status"] = "warning";
                $response["message"] = "No row updated";
            }else{
                $response["status"] = "success";
                $response["message"] = $affected_rows." row(s) updated in database";
            }
        }catch(PDOException $e){
            $response["status"] = "error";
            $response["message"] = "Update Failed: " .$e->getMessage();
        }
        return $response;
    }
    function delete($table, $where){
        if(count($where)<=0){
            $response["status"] = "warning";
            $response["message"] = "Delete Failed: At least one condition is required";
        }else{
            try{
                $a = array();
                $w = "";
                foreach ($where as $key => $value) {
                    $w .= " and " .$key. " = :".$key;
                    $a[":".$key] = $value;
                }
                $stmt =  $this->db->prepare("DELETE FROM $table WHERE 1=1 ".$w);
                $stmt->execute($a);
                $affected_rows = $stmt->rowCount();
                if($affected_rows<=0){
                    $response["status"] = "warning";
                    $response["message"] = "No row deleted";
                }else{
                    $response["status"] = "success";
                    $response["message"] = $affected_rows." row(s) deleted from database";
                }
            }catch(PDOException $e){
                $response["status"] = "error";
                $response["message"] = 'Delete Failed: ' .$e->getMessage();
            }
        }
        return $response;
    }
    function verifyRequiredParams($inArray, $requiredColumns) {
        $error = false;
        $errorColumns = "";
        foreach ($requiredColumns as $field) {
        // strlen($inArray->$field);
            if (!isset($inArray->$field) || strlen(trim($inArray->$field)) <= 0) {
                $error = true;
                $errorColumns .= $field . ', ';
            }
        }

        if ($error) {
            $response = array();
            $response["status"] = "error";
            $response["message"] = 'Required field(s) ' . rtrim($errorColumns, ', ') . ' is missing or empty';
            echoResponse(200, $response);
            exit;
        }
    }
}

?>
