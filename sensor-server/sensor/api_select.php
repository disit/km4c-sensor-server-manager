<?php

/* Sensor Server
   Copyright (C) 2017 DISIT Lab http://www.disit.org - University of Florence

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as
   published by the Free Software Foundation, either version 3 of the
   License, or (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */ 

function get_sensors()
{
    //leggo le variabili  $SELECT_username e $SELECT_password dal file di configurazione
    $path = realpath(dirname(__FILE__));
    $config_filename = $path. "/api_config.php";
    include($config_filename);

    $file_log = NULL;
    $file_log = create_file_log();
    //scrittura
    $now   = new DateTime;
    $time = $now->format('H:i:s');

    if(isset($_GET["user"]) && isset($_GET["pwd"]) && $_GET["user"]==$SELECT_username && $_GET["pwd"]==$SELECT_password){
      //connessione DB
      $conn = mysql_connect($DB_host, $DB_username, $DB_password );//prendere da file
      if (!$conn) {
          http_response_code(500);
          $stringData = "\n".$time.' | ERROR | '. $_SERVER['REMOTE_ADDR'] .' | Could not connect: ' . mysql_error(). 
            " (Database Credentials: ".$config_filename. ")";
          fwrite($file_log, $stringData);
          die('Could not connect: ' . mysql_error());
      }
      else{
          $message = '';
          
          //------------- inizio ----------query per risolvere la coesistenza delle doppie colonne
          $power_not_used = 'power';
          $rssi_not_used = 'rssi';
          $frequency_not_used = 'frequency';
          $uid_not_used = 'uid';
          //inserisco le righe html NUOVO metodo
          $query_column_exists = "SELECT column_name FROM   information_schema.columns where 
            (table_name = 'sensors' AND TABLE_SCHEMA= 'sensors' and (column_name='power' || column_name='rssi' || column_name='frequency'))";
          $result_col = mysql_query($query_column_exists, $conn);
          while ($row = mysql_fetch_array($result_col)) {
            if($row['column_name'] == 'power')
               $power_not_used = 'power_n';
            else if($row['column_name'] == 'rssi')
                $rssi_not_used = 'rssi_n';
            else if($row['column_name'] == 'frequency')
                $frequency_not_used = 'frequency_n';
            /*else if($row['column_name'] == 'uid')
                $uid_not_used = 'device_id';*/
          }
          //------------- fine ----------query per risolvere la coesistenza delle doppie colonne


          if(isset($_GET["type"]) && $_GET["type"]=='html'){//caso 1 chiedo html
             // $message = "HTML<br><br>";
              $query_header = "SELECT column_name FROM   information_schema.columns 
                              where table_name = 'sensors' AND TABLE_SCHEMA= 'sensors' 
                              ORDER  BY ordinal_position ";

              $power_done = false;
              $rssi_done = false;
              $frequency_done = false;
              //$uid_done = false;

              mysql_select_db('sensors');
              $columns = mysql_query($query_header, $conn);

              $select = "<html><table border =1>";
              //sistemo header della tabella
              $columnsList = array();
              while ($row = mysql_fetch_array($columns)) {
                  if(($row['column_name'] == 'frequency') || ($row['column_name'] == 'frequency_n')  ){ 
                    if(!$frequency_done){
                        $select .= "<td>".'frequency'.' (Mhz)'."</td>";
                        $frequency_done = true;
                    }
                  }
                  else if(($row['column_name'] == 'power' ) || ($row['column_name'] == 'power_n' )){
                      if(!$power_done){
                        $power_done = true;
                        $select .= "<td>".'power'.' (dB)'."</td>";
                      }
                  }
                  else if(($row['column_name'] == 'rssi' ) || ($row['column_name'] == 'rssi_n' ) ){
                      if(!$rssi_done){
                        $rssi_done = true;
                        $select .= "<td>".'rssi'.' (dB)'."</td>";
                      }
                  }
/*
                  else if(($row['column_name'] == 'device_id' ) || ($row['column_name'] == 'uid' ) ){
                      if(!$uid_done){
                        $uid_done = true;
                        $select .= "<td>".'uid'."</td>";
                      }
                  }*/

                  else if(($row['column_name'] == 'altitude' ) || ($row['column_name'] == 'accuracy' ))
                      $select .= "<td>".$row['column_name'].' (m)'."</td>";
                  else if(($row['column_name'] == 'speed' ) )
                      $select .= "<td>".$row['column_name'].' (m/s)'."</td>";
                  else if(($row['column_name'] == 'heading' ) )
                      $select .= "<td>".$row['column_name'].' (degrees relative to the true north)'."</td>";
                  else 
                      $select .= "<td>".$row['column_name']."</td>";
                  //riempo la riga
                  $columnsList[] = $row['column_name']; 
              }
              $query ="SELECT * FROM sensors.sensors ";
              if(isset($_GET["limit"]) && is_numeric($_GET["limit"])){
                  $limit = " LIMIT ".$_GET["limit"];
                  $query .= $limit;
                  if(isset($_GET["offset"])  && is_numeric($_GET["offset"])){
                      $offset = " OFFSET ".$_GET["offset"];
                      $query .= $offset;
                  }
              }
              else
                $query .= " LIMIT 1000 ";


              mysql_select_db('sensors');
              $result = mysql_query($query, $conn);
              $res = '';
              
              while ($row = mysql_fetch_array($result)) {
                  $select .= "<tr>";//inizio riga
                  foreach ($columnsList as $value){
                   $row_pure = '';
                      if(($value == 'power')){//qui ci entro quando ho $row['power'] oppure $row['power_n'] 
                          if($row['power'] != NULL)
                            $row_pure = str_replace(' dB', '', $row[$value]);
                          else if ($row['power_n'] != NULL)
                            $row_pure = $row['power_n'];
                            
                          $select .= "<td> ".$row_pure."</td> ";
                      }
                      else if($value == 'rssi'){
                          if($row['rssi'] != NULL)
                            $row_pure = str_replace(' dB', '', $row[$value]);
                          else if ($row['rssi_n'] != NULL)
                            $row_pure = $row['rssi_n'];

                          $select .= "<td> ".$row_pure."</td> ";
                      }
                      else if($value == 'frequency'){
                          if($row['frequency'] != NULL)
                              $row_pure = str_replace(' Mhz', '', $row[$value]);
                          else if ($row['frequency_n'] != NULL)
                              $row_pure = $row['frequency_n'];
                          
                          $select .= "<td> ".$row_pure."</td> ";
                      }
/*
                      else if($value == 'device_id'){
                          if($row['device_id'] != NULL)
                              $row_pure = $row['device_id'];
                          else if ($row['uid'] != NULL) 
                              $row_pure = $row['uid'];
                          
                          $select .= "<td> ".$row_pure."</td> ";
                      }
*/
                      else if(($value != $power_not_used) && ($value != $rssi_not_used ) && ($value != $frequency_not_used ) && ($value != $uid_not_used )){
                            $value = isset($row[$value]) ? $row[$value] : '';
                            $select .= "<td> ".$value."</td> ";
                      }
                  }
                  $select .= "</tr>";//fine riga
              }
              $select .= "</html></table>";

              if(!$result ){
                  die('Could not enter data: ' . mysql_error());
                  $stringData = "\n".$time.' | '. $_SERVER['REMOTE_ADDR'].' | Query to obtain html results | ERROR: Could not enter data: ' . mysql_error();
                  fwrite($file_log, $stringData);
              }
              else{
                  $message .= "Query effettuata:".$query." <br><br>Attualmente nel DB sono presenti i seguenti risultati: <br><br>".$select;
                  $stringData = "\n".$time.' | '. $_SERVER['REMOTE_ADDR']. " | Query managed (type=html): ".$query;
                  fwrite($file_log, $stringData);
              }
          }
          else if(isset($_GET["type"]) && $_GET["type"]=='csv'){ //------------------------ chiedo un csv
              $dt = new DateTime('');
              $date =  $dt->format('_Y_m_d-H:i:s');
              //$message = "Downloading a CSV file... ".$date;
              $fileName = 'sensors'.$date.'.csv';

              header('Content-Description: File Transfer');
              header("Content-type: text/csv");
              header("Content-Disposition: attachment; filename={$fileName}");
              //header("Expires: 0");
              //header("Pragma: public");

              $fh = @fopen( 'php://output', 'w' );

              //seleziono il DB
              mysql_select_db('sensors_csv');

              //query per l'header
              $query_header = "SELECT distinct(column_name) FROM information_schema.columns
                              WHERE  table_name = 'sensors' ORDER  BY ordinal_position ";
              $columns = mysql_query($query_header, $conn);
              
              $power_done = false;
              $rssi_done = false;
              $frequency_done = false;
              //sistemo header del file csv
              while ($row = mysql_fetch_array($columns)) {
                //if(($row['column_name'] != 'power_n') && ($row['column_name'] != 'rssi_n') && ($row['column_name'] != 'frequency_n') ){
                  
                  if(($row['column_name'] == 'power') || ($row['column_name'] == 'power_n') ){
                      if(!$power_done){
                        $power_done = true;
                        $writecolumnsList[] = 'power (dB)';
                      }
                  }
                  else if(($row['column_name'] == 'rssi') || ($row['column_name'] == 'rssi_n') ){
                      if(!$rssi_done){
                        $rssi_done = true;
                        $writecolumnsList[] = 'rssi (dB)';
                      }
                  }
                  else if(($row['column_name'] == 'frequency') || ($row['column_name'] == 'frequency_n')){
                      if(!$frequency_done){
                        $frequency_done = true;
                        $writecolumnsList[] = 'frequency (Mhz)';
                      }
                  }
                  else if($row['column_name'] == 'speed')
                      $writecolumnsList[] = $row['column_name'].' (m/s)';
                  else if($row['column_name'] == 'altitude')
                      $writecolumnsList[] = $row['column_name'].' (m)';
                  else if($row['column_name'] == 'accuracy')
                      $writecolumnsList[] = $row['column_name'].' (m)';
                  else if($row['column_name'] == 'heading')
                      $writecolumnsList[] = $row['column_name'].' (degrees relative to the true north)';    
                  else 
                      $writecolumnsList[] = $row['column_name'];
                //}
                $columnsList[] = $row['column_name'];
              }

              fputcsv($fh, $writecolumnsList);//metto l'header
              //query per i dati
              $query = "SELECT * FROM sensors.sensors ";


              if(isset($_GET["limit"]) && is_numeric($_GET["limit"])){
                  $limit = " LIMIT ".$_GET["limit"];
                  $query .= $limit;
                  if(isset($_GET["offset"]) && is_numeric($_GET["offset"])){
                      $offset = " OFFSET ".$_GET["offset"];
                      $query .= $offset;
                  }
              }
              else
                $query .= "LIMIT 1000";

              $results = mysql_query($query, $conn);
              $datatotal = array();
              $i=1;
              //sistemo le righe del csv
              while ($data = mysql_fetch_array($results)) {
                  foreach ($columnsList as $value){
                      $row_pure = '';
                      if(($value == 'power') || ($value == 'power_n')){
                          if($data['power'] != NULL)
                            $row_pure = str_replace(' dB', '', $data['power']);
                          else if($data['power_n'] != NULL)
                            $row_pure = $data['power_n'];
                          
                          $datatotal[$i]['power'] = $row_pure ;
                      }
                      else if(($value == 'rssi') || ($value == 'rssi_n')){
                          if($data['rssi'] != NULL)
                            $row_pure = str_replace(' dB', '', $data['rssi']);
                          else if($data['rssi_n'] != NULL)
                            $row_pure = $data['rssi_n'];
                          
                          $datatotal[$i]['rssi'] = $row_pure ;
                      }
                      else if(($value == 'frequency') || ($value == 'frequency_n')){
                          if($data['frequency'] != NULL)
                             $row_pure = str_replace(' Mhz', ' ', $data['frequency']);
                          else if($data['frequency_n'] != NULL)
                            $row_pure = $data['frequency_n'];

                          $datatotal[$i]['frequency'] = $row_pure ;
                      }
                      else if(($value == 'network_name') || ($value == 'capabilities') || ($value == 'device_model')){
                        $row_pure = str_replace("\\", "\\\\", $data[$value]);
                        $datatotal[$i][$value] = $row_pure ;
                      }
                      else    
                          $datatotal[$i][] = $data[$value];
                  }
                  $i++;
              }
              //scrivo i dati nel file
              foreach ($datatotal as $data){
                  fputcsv($fh, $data);//data
              }

              // Close the file
              fclose($fh);
              
              //query csv andata a buon fine
              $stringData = "\n".$time.' | '. $_SERVER['REMOTE_ADDR'].' | Query managed (type=csv): ' .$query;
              fwrite($file_log, $stringData);
              

              // Make sure nothing else is sent, our file is done
              exit;

          }
          else if(isset($_GET["type"]) && $_GET["type"]=='json'){//------------------------ chiedo un json
              $dt = new DateTime('');
              $date =  $dt->format('_Y_m_d-H:i:s');
              //$message = "Downloading a CSV file... ".$date;
              $fileName = 'sensors_json'.$date.'.txt';

              header('Content-Description: File Transfer');
              header("Content-type: application/json");
              header("Content-Disposition: attachment; filename={$fileName}");
              //header("Expires: 0");
              //header("Pragma: public");

              $fh = @fopen( 'php://output', 'w' );

              //seleziono il DB
              mysql_select_db('sensors');
              //query per l'header
              $query_header = "SELECT column_name FROM information_schema.columns
                              WHERE  table_name = 'sensors' ORDER  BY ordinal_position ";
              $columns = mysql_query($query_header, $conn);

              //sistemo header del file json
              while ($row = mysql_fetch_array($columns)) {
                  if(($row['column_name'] != $power_not_used ) && ($row['column_name'] != $rssi_not_used ) && ($row['column_name'] != $frequency_not_used ) ){
                    if(($row['column_name'] == 'power') || ($row['column_name'] == 'power_n')){
                        $columnsList[] = 'power';
                    }
                    else if(($row['column_name'] == 'rssi') || ($row['column_name'] == 'rssi_n')){
                        $columnsList[] = 'rssi';
                    }
                    else if(($row['column_name'] == 'frequency') || ($row['column_name'] == 'frequency_n')){
                        $columnsList[] = 'frequency';
                    }
                    else {
                        $columnsList[] = $row['column_name'];}
                  }
              }

              //query per i dati
              $query = "SELECT * FROM sensors.sensors ";

              if(isset($_GET["limit"]) && is_numeric($_GET["limit"])){
                  $limit = " LIMIT ".$_GET["limit"];
                  $query .= $limit;
                  if(isset($_GET["offset"]) && is_numeric($_GET["offset"])){
                      $offset = " OFFSET ".$_GET["offset"];
                      $query .= $offset;
                  }
              }
              else
                $query .= " LIMIT 1000 ";

              $results = mysql_query($query, $conn);
              $datatotal = array();
              $i=1;

              while ($row = mysql_fetch_array($results)) {
                  $row_pure = NULL;
                  foreach ($columnsList as $value){
                    if(($value == 'power') || ($value == 'power_n') ){
                        if($row['power']!=NULL)
                            $row_pure = str_replace(' dB', '', $row['power']);
                        else if($row['power_n'] != NULL)
                            $row_pure = $row['power_n'];

                        $datatotal[$i][$value.' (dB)'] = $row_pure;
                    }
                    else if(($value == 'rssi') || ($value == 'rssi_n')){
                        if($row['rssi']!=NULL)
                            $row_pure = str_replace(' dB', '', $row['rssi']);
                        else if($row['rssi_n'] != NULL)
                            $row_pure = $row['rssi_n'];

                        $datatotal[$i][$value.' (dB)'] = $row_pure;
                    }
                    else if(($value == 'frequency') || ($value == 'frequency_n')) {
                        if($row['frequency']!=NULL)
                            $row_pure = $row_pure = str_replace(' Mhz', '', $row['frequency']);
                        else if($row['frequency_n'] != NULL)
                            $row_pure = $row['frequency_n'];

                        $datatotal[$i][$value.' (Mhz)'] = $row_pure;
                    }
                    else if(($value == 'accuracy') || ($value == 'altitude')){
                        $datatotal[$i][$value.' (m)'] = $row[$value];
                    }
                    else if($value == 'speed'){
                        $datatotal[$i][$value." (m/s)"] = $row[$value];
                    }
                    else if($value == 'heading'){
                        $datatotal[$i][$value.' (degree)'] = $row[$value];
                    }
                    else{
                       $datatotal[$i][$value] = $row[$value];
                    }

                  }
                  $i++;
              }

              //scrivo i dati nel file
              $n = true;
              fwrite($fh, "[ ");
              //NOTE: from php version > 5.2, it is possible to use json_encode($data, JSON_UNESCAPED_SLASHES)
              //istead of str_replace('\/','/',json_encode($data)));

              foreach ($datatotal as $data){
                  if($n){
                      fwrite($fh, str_replace('\/','/',json_encode($data)));
                      $n = false;
                  }
                  else
                      fwrite($fh, ",\n".str_replace('\/','/',json_encode($data)));
              }
              fwrite($fh, " ]");
              // Close the file
              fclose($fh);
              
              //query json andata a buon fine
              $stringData = "\n".$time.' | '. $_SERVER['REMOTE_ADDR'].' | Query managed (type=json): ' .$query;
              fwrite($file_log, $stringData);
              
              
              // Make sure nothing else is sent, our file is done
              exit;

          }
          else{
              //query json NON andata a buon fine
              $stringData = "\n".$time.' | '. $_SERVER['REMOTE_ADDR'].' | Status Code: 200 | Query NOT managed: type is missing ' ;
              fwrite($file_log, $stringData); 
              $message = "Specificare uno dei possibili tipi di output: type ={html, csv, json}";
          }
      }
      //chiusura connessione DB
      mysql_close($conn); 
    }
    else{
        $stringData = "\n".$time.' | ERROR | '. $_SERVER['REMOTE_ADDR']. ' | Query NOT managed: error in username and/or password.'.
        " (Database Credentials: ".$config_filename. ")";
        fwrite($file_log, $stringData); 
        $message = "Inserire correttamente username e password. ";
        http_response_code(400);
    }

    //chiudo il file di log
    fclose($file_log);

    //output
    return $message; 
}


if (!function_exists('http_response_code')){
    function http_response_code($newcode = NULL)
    {
        static $code = 200;
        if($newcode !== NULL)
        {
            header('X-PHP-Response-Code: '.$newcode, true, $newcode);
            if(!headers_sent())
                $code = $newcode;
        }       
        return $code;
    }
}

$possible_url = array("get_sensors");
$value = "Specificare: action(obbligatorio), user(obbligatorio), pwd(obbligatorio), limit e offset<br>";
$value .= "<br>Si noti che i seguenti esempi NON contengono i parametri 'user' e 'pwd', che devono essere aggiunti manualmente.<br>";
$value .= "<br>Esempi di possibili url (con type=html):<br>";
$value .= "Seleziono tutto: http://www.disit.org/sensor/api_select.php?action=get_sensors&type=html <br>";
$value .= "Seleziono i primi '1000' elementi : http://www.disit.org/sensor/api_select.php?action=get_sensors&type=html&limit=1000 <br>";
$value .= "Seleziono '1000' elementi a partire dal 1001esimo : http://www.disit.org/sensor/api_select.php?action=get_sensors&type=html&limit=1000&offset=1000 <br>";
$value .= "<br><br>Esempi di possibili url (con type=csv):<br>";
$value .= "Seleziono tutto: http://www.disit.org/sensor/api_select.php?action=get_sensors&type=csv <br>";
$value .= "Seleziono i primi '1000' elementi : http://www.disit.org/sensor/api_select.php?action=get_sensors&type=csv&limit=1000 <br>";
$value .= "Seleziono '1000' elementi a partire dal 1001esimo : http://www.disit.org/sensor/api_select.php?action=get_sensors&type=csv&limit=1000&offset=1000 <br>";
$value .= "<br><br>Esempi di possibili url (con type=json):<br>";
$value .= "Seleziono tutto: http://www.disit.org/sensor/api_select.php?action=get_sensors&type=json <br>";
$value .= "Seleziono i primi '1000' elementi : http://www.disit.org/sensor/api_select.php?action=get_sensors&type=json&limit=1000 <br>";
$value .= "Seleziono '1000' elementi a partire dal 1001esimo : http://www.disit.org/sensor/api_select.php?action=get_sensors&type=json&limit=1000&offset=1000 <br>";

if (isset($_GET["action"]) && in_array($_GET["action"], $possible_url) )
{
  switch ($_GET["action"])
    {
        case "get_sensors":
            $value = get_sensors();
        break;
    }
}
else {
    $file_log = NULL;
    $file_log = create_file_log();
    //scrittura
    $now   = new DateTime;
    $time = $now->format('H:i:s');
    $operation = "SELECT";
    $error = "\n".$time.' | ERROR | '. $_SERVER['REMOTE_ADDR']. ' | No parameters ';
    fwrite($file_log, $error);

    //chiusura del file di log
    fclose($file_log);
    
    http_response_code(400);
}


function create_file_log(){
    $path = realpath(dirname(__FILE__));
    $log_dir = $path.'/log_api_select_sensors';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $now   = new DateTime;
    $today = $now->format( 'd-m-Y' );
    $filename = $log_dir."/log_api_select_sensors_".$today.".txt";
    $file_log = NULL;
    if (!file_exists($filename)){
       $file_log = fopen($filename, 'a') or die("can't open file");
       $header = "Today LOGs (".$today."):\ntime | ERROR (optional) | client IP  | message \n";
       fwrite($file_log, $header);
    }
    //apertura del file in scrittura
    $file_log = fopen($filename, 'a');
    return $file_log;
}

exit($value);
?>
