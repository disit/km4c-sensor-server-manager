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

function insert_sensors(){

    //DATABASE variables
    //leggo le variabili $DB_username  $DB_password dal file di configurazione
    $path = realpath(dirname(__FILE__));
    //chiamata REST a engager 
    $url_engager = "http://192.168.0.19:8080/engager-api/engager";//TODO mettere nel file di configurazione
    $config_filename = $path. "/api_config.php";
    include($config_filename);

    //inizializzo file di log
    $file_log = NULL;
    $file_log = create_file_log();



    //inizializzo il solo file di errore
    //scrivo l'errore in due punti, sia nel file generale di log che in quello conntenente i SOLI errori
    $file_log_error = NULL;
    $file_log_error = create_file_error_log();

    //scrittura
    $now   = new DateTime;
    $time = $now->format('H:i:s');
    $day = $now->format('d-m-Y');

    $message_array = array('insert_result' => '', 'engager_result' => array());

    $message = '';
   
    $json = file_get_contents('php://input');//$_POST["json"];
	//$_SERVER['SERVER_NAME'] = 'localhost'; //non ha senso
    $obj = json_decode($json, true);

    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && $_SERVER["HTTP_X_FORWARDED_FOR"] != ""){
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    }else{
        $ip = $_SERVER["REMOTE_ADDR"];
    }

    $uid_error = null;

    if($obj){//fai tutto, il json e quindi l'array sono ben formati 
        if(is_array($obj)){
            $i = 0;
            $t = 1;
            $array_query = array();
            $single_query ='';
            $primo = true;
            $array_query_user = array();
            $last_date = null;
            foreach ($obj as $key => $value) {
                
                //se è ben formato lo metto da qualche parte
                //se non è scritto bene come array devo dare errore ed uscire

                //se è scritto bene faccio i controlli sui singoli campi
                // se trovo un campo NON ben formato devo dare errore ed uscire

                //se alla fine sono tutti ben formati li registro

                if(count($value)!=1){//sensori multipli
                    $valid_response = valid_sensor($value);
                    if($valid_response['error_message'] != ""){
                        //se ho uno dei sensori scritto male esco!
                        //var_dump('array multiplo numero: '.$i.' errato. NON analizzo gli altri');
                        $stringData = $time.' | ERROR | '. $ip.' | Could not enter data. '.'Error in sensor number '.$t . ': '. $valid_response['error_message'];
                        fwrite($file_log, "\n".$stringData);
                        //scrivo nel file di errore
                        fwrite($file_log_error,  "\n".$day.' | '.$stringData);
                        header('Access-Control-Allow-Origin: *');
                        http_response_code(400);
                        die('Could not enter data. '.'Error in sensor number '.$t . ': '. $valid_response['error_message'] );
                        
                        $message .= $valid_response['error_message'];
                        $message_array['insert_result'] = $message;
                    }
                    else{
                        //metto in array
                        //var_dump('array multiplo numero: '.$i.' corretto... salvo da qualche parte');
                        $array_query[$t] = $valid_response['query'];
                        
                        //$single_query = $valid_response['query2']; 
                        //Questo single query NON sarà più un sensore singolo MA un array
                        //perchè io avro' come chiamata un gruppo composto da gruppi di sensori con
                        //date diverse e DEVO inserire un sensore per ogni data diversa

                        //SE è un sensore valido, faccio il controllo sulla data
                         //controllo:
                        //SE E' il primo sensore, lo metto nell'array
                        
                        if($t==1){
                            $array_query_user[$t] = $valid_response['query2']; //metto direttamente
                            $last_date = $value['date'];
                        }
                        else{  //per gli altri controllo
                            if($last_date != $value['date']) {//se la data è diversa da quella dell'ultimo inserimento
                               
                                //lo metto in user_eval
                                $array_query_user[$t] = $valid_response['query2']; 
                                $last_date = $value['date'];//aggiorno il last_date
                            }
                            //se la data è la stessa NON faccio niente
                        }
                        
                        if($primo){//primo elemento qui faccio inserimento in user_eval e chiamata REST a servizio engager
                            //inserimento tabella user_eval
                            //$single_query = $valid_response['query2']; 

                            //chiamata REST a engager 
                            if(!isset($value['uid']))
                                $uid = '';
                            else 
                                $uid = $value['uid'];

                            $data = array("uid" => "$uid");
                            $method = 'GET';    
                            $result = CallAPI($method, $url_engager, $data);

                            if($result){//se è diverso da NULL, restituisco il messaggio
                                $array_result = json_decode($result);
                                $message_array['engager_result'] = $array_result;
                            }
                     
                            $primo = false;    
                        }
                        
                    }
                    //$message .= $message_error.' '.$i ;
                    //$message .= $error_message.' '.$i ;
                }
                else{//---------------------------------------- invio di un sensore singolo
                    //var_dump('sensore singolo');
                    $valid_response = valid_sensor($obj);
                    if( $valid_response['error_message'] != ""){
                        //se ho un sensore scritto male mando errore
                        $stringData = $time.' | ERROR | '. $ip.' | Could not enter data: '.$valid_response['error_message'];
                        fwrite($file_log, "\n".$stringData);
                        //scrivo nel file di errore
                        fwrite($file_log_error, "\n".$day.' | '.$stringData);
                        header('Access-Control-Allow-Origin: *');
                        http_response_code(400);
                        die('Could not enter data: '.$valid_response['error_message'] );

                        $message .= $valid_response['error_message'];
                        $message_array['insert_result'] = $message;
                    }       
                    else{
                        //var_dump('sensore singolo corretto'. $valid_response['error_message'] ); 
                        $conn = mysql_connect($DB_host, $DB_username, $DB_password);//prendere da file
                        if (!$conn) {
                            //connessione al DB NON stabilita
                            $stringData = $time.' | ERROR | '. $ip.' | Could not connect: ' . mysql_error().
                                    " (Database Credentials: ".$config_filename. ")";
                            fwrite($file_log, "\n".$stringData);
                            //scrivo nel file di errore
                            fwrite($file_log_error, "\n".$day.' | '.$stringData);
                            header('Access-Control-Allow-Origin: *');
                            http_response_code(500);
                            die('Could not connect: ' . mysql_error());
                        }
                        else{
                            mysql_select_db('sensors');
                            $result = mysql_query($valid_response['query'], $conn);
                            $result2 = mysql_query($valid_response['query2'], $conn);
                            $message = '';
                            if(!$result ){
                                //errore di inserimento
                                $stringData = $time." | ERROR | ". $ip.' | Could not enter data (1 sensor): '.$valid_response['query']. ' sql error'.mysql_error();
                                fwrite($file_log, "\n".$stringData);
                                //scrivo nel file di errore
                                fwrite($file_log_error, "\n".$day.' | '.$stringData);

                                die('Could not enter data: '.$valid_response['query']. ' sql error'.mysql_error() );
                            }
                            else{
                                //Inserimento andato a buon fine   
                                $stringData = "\n".$time.' | '. $_SERVER['REMOTE_ADDR'].' | '. "1 Sensor stored! ";
                                fwrite($file_log, $stringData);
                                    
                                //chiamata REST a engager 
                                if(!isset($value['uid']))
                                    $uid = '';
                                else 
                                    $uid = $value['uid'];

                                $data = array("uid" => "$uid");
                                $method = 'GET';    
                                $result = CallAPI($method, $url_engager, $data);

                                if($result){//se è diverso da NULL, restituisco il messaggio
                                    $array_result = json_decode($result);
                                    $message_array['engager_result'] = $array_result;
                                }


                                $message .= "1 Sensor stored!";
                                $message_array['insert_result'] = $message; 
                            }
                        }
                        mysql_close($conn);
                        }
                        break;//esco dal ciclo, se il primo elemento NON ha sottoelementi so già che si tratta di un sensore singolo
                    }
                    $i++;
                    $t++;
            }
            $multiple_insert = false;
            $multiple_query = '';
            foreach ($array_query as $key => $value) {
                
                $multiple_insert = true;                
                //var_dump ('MULTIPLE QUERY inserisco la query per il sensore '.$key);
                //fwrite($file_log_test,  "\n".$day.' | '.$key .' | '.$value);
                //devo fare una query sola
                if($key == 1)
                    $multiple_query = $value;
                else{
                    $query_part = explode("VALUES", $value);
                    $add_sensor = ','. $query_part[1];
                    $multiple_query .= $add_sensor;
                }
            }
            
            $multiple_insert_user = false;
            $multiple_query_user = '';
            foreach ($array_query_user as $key => $value) {
                
                $multiple_insert_user = true;                
                //devo fare una query sola
                if($key == 1)
                    $multiple_query_user = $value;
                else{
                    $query_part_user = explode("VALUES", $value);
                    $add_sensor_user = ','. $query_part_user[1];
                    $multiple_query_user .= $add_sensor_user;
                }
            }

            //fwrite($file_log_test,  "\n-----".$day.' | '. $ip.' | UID: '. $uid_error);



            if($multiple_insert){
                //faccio la query multipla
                $conn = mysql_connect($DB_host, $DB_username, $DB_password);//prendere da file
                if (!$conn) {
                    $stringData = $time.' | ERROR | '. $ip.' | Could not connect: ' . mysql_error().
                            " (Database Credentials: ".$config_filename. ")";
                    fwrite($file_log, "\n".$stringData);
                    //scrivo nel file di errore
                    fwrite($file_log_error, "\n".$day.' | '.$stringData);
                    header('Access-Control-Allow-Origin: *');
                    http_response_code(500);
                    die('Could not connect: ' . mysql_error());
                    }
                    else{
                        mysql_select_db('sensors');
                        $result = mysql_query($multiple_query, $conn);
                        //$result2 = mysql_query($single_query, $conn); 
                        $result2 = mysql_query($multiple_query_user, $conn); 

                        $message = '';
                        if(!$result ){
                            //errore di inserimento
                            $stringData = $time.' | ERROR | '. $ip.' | Could not enter data (multiple sensors): '.$multiple_query. ' sql error '.mysql_error();
                            fwrite($file_log, "\n".$stringData);
                            //scrivo nel file di errore
                            fwrite($file_log_error, "\n".$day." | ".$stringData);
                            die('Could not enter data: '.$multiple_query. ' sql error'.mysql_error() );
                        }
                        else{
                            //$message = "Sensor stored!".' ('.$json.")";
                            //Inserimento andato a buon fine
                            $stringData = "\n".$time.' | '. $ip.' | '. count($array_query). " sensors stored!";
                            fwrite($file_log, $stringData);
                            $message .= count($array_query). " Sensors stored!";
                            $message_array['insert_result'] = $message;
                        }
                    }
                mysql_close($conn);
            }
        }
        else{
             //var_dump ('SINGLE INSERT QUERY inserisco la query per il sensore '.$key);
            //potrebbe segnare errore quando un numero è scritto male sotto forma di stringa, ad esempio senza le virgolette 
            $stringData = $time.' | ERROR | '. $ip.' | Error in the Json schema format';
            fwrite($file_log, "\n".$stringData);
            //scrivo nel file di errore
            fwrite($file_log_error, "\n".$day." | ".$stringData);
            header('Access-Control-Allow-Origin: *');
            http_response_code(400);
            die('Please, send a Json as input.' );
        }
    }
    else{
        //segnala errore di json scritto male
        $stringData = $time.' | ERROR | '. $ip.' | Error in the Json schema format';
        fwrite($file_log, "\n".$stringData);
        //scrivo nel file di errore
        fwrite($file_log_error, "\n".$day." | ".$stringData);
        header('Access-Control-Allow-Origin: *');
        http_response_code(400);
        die('Error in the Json schema format.' );
    }
    
    //return $message; 
    
    //$message_json = json_encode(array_map('utf8_encode', $message_array));
    //json_encode($message_array);//json_encode($message_array, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    
    //fwrite($file_log_test,  "\n".$message_array['engager_result']); 

    return $message_array;
}

if (!function_exists('http_response_code')){
    function http_response_code($newcode = NULL)
    {
        static $code = 200;
        if($newcode !== NULL)
        {
            header('Access-Control-Allow-Origin: *');
            header('X-PHP-Response-Code: '.$newcode, true, $newcode);
            if(!headers_sent())
                $code = $newcode;
        }       
        return $code;
    }
}

//valida il formato datetime
function isValidDateTime($dateTime){

    //$file_log_test = create_file_log_test();

    //var_dump($dateTime);
    //$now = date("Y-m-d H:i:s");
    $delay = 4500;//adesso 1 ora e un quarto prima era 15 minuti 900;//in seconds
    $now = date("Y-m-d H:i:s", time() + $delay);
    //fwrite($file_log_test,  "\nTEST 90 | sensore: ".$dateTime. ' confronto: '. $now );


    if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $dateTime, $matches)) {
        if (checkdate($matches[2], $matches[3], $matches[1]) && ($dateTime <= $now) ) {
            return true;//formato corretto
        }
        else
            return false;
    }
    return false;//formato errato
}

function isValidLatitude($latitude){
    // /^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/
    //OLD: "/^-?([1-8]?[1-9]|[1-9]0)\.{1}\d{1,6}$/"
    if (preg_match("/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/", $latitude) &&
        is_numeric($latitude)) {
        return true;
    } else {
        return false;
    }

}

function isValidLongitude($longitude){
    //new /^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/
    //old /^-?([1]?[1-7][1-9]|[1]?[1-8][0]|[1-9]?[0-9])\.{1}\d{1,6}$/
    if(preg_match("/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/",$longitude)
        && is_numeric($longitude) ) {
        return true;
    } else {
        return false;
    }
}

function isValid_MAC($mac)
{
  return (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $mac) == 1);
}
/*
function isValidPowerFrequency($power){
    $power_no_space = str_replace(' ', '', $power);  
    if(preg_match('#[0-9]#',$power_no_space)){
        return (preg_match('^\d*[a-zA-Z][a-zA-Z\d]*$^', $power_no_space));
    }
    else 
        return false;
}*/

function isValidFrequency($frequency){
    $frequency_no_space = str_replace(' ', '', $frequency); 
    $frequency_value = str_replace('Mhz', '', $frequency_no_space); 
    if(is_numeric($frequency_value)){
        return $frequency_value;
    }
    else
        return false;
}

function isValidPower($power){
    $power_no_space = str_replace(' ', '', $power); 
    $power_value = str_replace('dB', '', $power_no_space); 
    if(is_numeric($power_value)){
        return $power_value;
    }
    else
        return false;
}

function isValidInt($string){ 
    return is_numeric($string);
}

$possible_url = array("insert_sensors");
$value = "An error has occurred";

if(isset($_REQUEST)){
    $value = insert_sensors();
}

function valid_sensor($obj){
    
    /*
     * WIFI e status:
     * required fields: date, place (latitude & longitude), type ('beacon' or 'wifi' ), MAC, network_name
     * not required fields: power, rssi, frequency, capabilities, date_pre_scan, lat_pre_scan, long_pre_scan, device_model, uid (device_id nelle nuove versioni),
     *                      speed, altitude, provider, accuracy, heading, date_pre_scan, place_pre_scan, status, prev_status, appID,
                            version, lang, uid2, profile
     * not admitted fields:  UUID, major, minor, sensor_name, id
     * automatically added: idsensors (primary key), sensor_IP
     */
    
    /*
     * BEACON:
     * required fields: date, place (latitude & longitude), type ('beacon' or 'wifi' ), MAC
     * not required fields: UUID, id, sensor_name, power, rssi, major, minor, speed, altitude, provider, accuracy, heading, date_pre_scan, place_pre_scan, status, prev_status, appID,
                            version, lang, uid2, profile
     * not admitted fields: network_name, frequency, capabilities
     * automatically added: idsensors (primary key), sensor_IP
     */
    
    //inizializzo le variabili
    $date_is_valid = false;//All sensors & required
    $place_is_valid = false;//All sensors & required
    $type_is_valid = false;//All sensors & required
    $network_name_is_valid = false;//Only wifi ----ora anche BEACON
    $sensor_name_is_valid = false;//Only Beacon 
    $mac_address_is_valid = false;//All sensors & required
    $power_is_valid = false;//All sensors & not required
    $rssi_is_valid = false;//All sensors & not required
    $minor_is_valid = false;//Only Beacon 
    $major_is_valid = false;//Only Beacon
    $UUID_is_valid = false; //Only Beacon 
    $frequency_is_valid = false;//Only wifi 
    $capabilities_is_valid = false;//Only wifi
    //----------------------------------------------intermediate data 
    $speed_is_valid = false;
    $altitude_is_valid = false;
    $provider_is_valid = false;
    $accuracy_is_valid = false; 
    $heading_is_valid = false; 
    //----------------------------------------------new data (from 09/03/2016)
    $date_pre_scan_is_valid = false;//Only wifi 
    $place_pre_scan_is_valid = false;//Only wifi 
    
    if(!isset($obj['lat_pre_scan']))
        $obj['lat_pre_scan'] = NULL;
    if(!isset($obj['long_pre_scan']))
        $obj['long_pre_scan'] = NULL;
    if(!isset($obj['date_pre_scan']))
        $obj['date_pre_scan'] = NULL;
    if(!isset($obj['device_model']))
        $obj['device_model'] = NULL;
    if(!isset($obj['profile']))
        $obj['profile'] = NULL;
//------------------ init new data 14/04 
/*lin_acc_x    lin_acc_y    lin_acc_z   avg_lin_acc_magn  avg_speed */
    if(!isset($obj['lin_acc_x']))
        $obj['lin_acc_x'] = 'NULL';
    if(!isset($obj['lin_acc_y']))
        $obj['lin_acc_y'] = 'NULL';
    if(!isset($obj['lin_acc_z']))
        $obj['lin_acc_z'] = 'NULL';
    if(!isset($obj['avg_lin_acc_magn']))
        $obj['avg_lin_acc_magn'] = 'NULL';
    if(!isset($obj['avg_speed']))
        $obj['avg_speed'] = 'NULL';

//necessario gestire i due parametri 'device_id' e 'uid' che contengono la stessa infomrazione
//in base alle versioni della App io ricevo uno dei due parametri
//a livello di DB deve finire TUTTO nel field 'device_id'.
    if(!isset($obj['device_id']))
        $obj['device_id'] = NULL;
    if(!isset($obj['uid']))
        $obj['uid'] = NULL;
//fine gestione 'uid'

    if(!isset($obj['prev_status']))
        $obj['prev_status'] = NULL;
    if(!isset($obj['status']))
        $obj['status'] = NULL;
    if(!isset($obj['capabilities']))
        $obj['capabilities'] = NULL;

    if(!isset($obj['appID']))
        $obj['appID'] = NULL;
    if(!isset($obj['version']))
        $obj['version'] = NULL;
    if(!isset($obj['lang']))
        $obj['lang'] = NULL;
    if(!isset($obj['uid']))
        $obj['uid'] = NULL;
    if(!isset($obj['uid2']))
        $obj['uid2'] = NULL;


    // prendo l'id dell'utente per metterlo nei LOG
/*
    if(isset($obj['uid']) || isset($obj['device_id'])){
        if(isset($obj['uid']))
            $uid_error = $obj['uid']; // ne prende uno, e lo mette nella variabile globale
        else
            $uid_error = $value['device_id']; //compatibilità con le vecchie versioni
    }
*/

    //tolgo gli spazi dove necessario 
    $latitude_no_spaces = str_replace(' ', '', $obj['latitude']); 
    $longitude_no_spaces = str_replace(' ', '', $obj['longitude']); 
    $lat_pre_scan_no_spaces = str_replace(' ', '', $obj['lat_pre_scan']); 
    $long_pre_scan_no_spaces = str_replace(' ', '', $obj['long_pre_scan']);

    if(isset($obj['minor']))
        $minor_no_space = str_replace(' ', '', $obj['minor']); 
    else
        $minor_no_space = '';
    if(isset($obj['major']))    
        $major_no_space = str_replace(' ', '', $obj['major']);
    else
        $major_no_space = '';
    if(isset($obj['UUID']))
        $UUID_no_spaces = str_replace(' ', '', $obj['UUID']); 
    else
        $UUID_no_spaces = '';
    if(isset($obj['id']))
        $id_no_spaces = str_replace(' ', '', $obj['id']); 
    else
        $id_no_spaces = '';
    
    $date_is_valid = isValidDateTime($obj['date']);

    if(!$date_is_valid){
        $obj['date'] = '0000-00-00 00:00:00';
        $date_is_valid = true;
    }    

    if(isValidLatitude($latitude_no_spaces) && isValidLongitude($longitude_no_spaces))//All sensors
        $place_is_valid = true; 

    $type = strtolower($obj['type']);
    $device_model = 'NULL';




    if(is_string($obj['type'])){

// INIZIO ---------- type = wifi, beacon, status -------------------------------
        //------------------provider check INIZIO
        $provider_is_valid = true; 



        if(!isset($obj['provider'])){//SE non è settato metto tutti NULL
            $speed_is_valid = true; 
            $altitude_is_valid = true;
            $provider_is_valid = true; 
            $accuracy_is_valid = true; 
            $heading_is_valid = true; 
            //default values
            $obj['speed'] = 'NULL';
            $obj['heading'] = 'NULL';
            $obj['altitude'] = 'NULL';
            $obj['provider'] = '';
            $obj['accuracy'] = 'NULL';
        }
        else{//altrimenti salvo in modo guidato per i vari casi

            //metto NULL se NON viene passato speed
            $speed_is_valid = true;
            if((!is_numeric($obj['speed']) && $obj['speed']=='') || !isset($obj['speed']))
                $obj['speed'] = 'NULL';


            if(strtolower($obj['provider'] == 'network' )){
                //--------------------------inizio-------------- caso network 
                //heading NON ha senso uguale a 0 in questo caso, quindi metto il default value
                $heading_is_valid = true;
                $obj['heading'] = 'NULL';
                //altitude NON ha senso uguale a 0 in questo caso, quindi metto il default value
                $altitude_is_valid = true;
                $obj['altitude'] = 'NULL';

                //metto NULL se NON viene passato accuracy
                if(isset($obj['accuracy']) && $obj['accuracy'] != NULL)
                    $accuracy_is_valid = is_numeric($obj['accuracy']);
                else{
                    $accuracy_is_valid = true;
                    $obj['accuracy'] = 'NULL';
                }
                //--------------------------fine-------------- caso network  
            }
            else{
                //------------------------ inizio ----------------- CASO GPS, fused
                //----caso GPS o altro, in questo caso il valore 0 ha senso e lo metto 
                //heading
                $heading_is_valid = true;
                if(is_numeric($obj['heading']))//se è un numero lo metto
                    $heading_is_valid = true;
                else{//se NON è un numero metto NULL
                    $heading_is_valid = true;
                    $obj['heading'] = 'NULL';
                }

                //altitude
                $altitude_is_valid = true;
                if(is_numeric($obj['altitude']))//se è un numero lo metto
                    $altitude_is_valid = true;
                else{//se NON è un numero metto NULL
                    $altitude_is_valid = true;
                    $obj['altitude'] = 'NULL';
                }

               //accuracy
                $accuracy_is_valid = true;
                if(is_numeric($obj['accuracy']))//se è un numero lo metto
                    $accuracy_is_valid = true;
                else{//se NON è un numero metto NULL
                    $accuracy_is_valid = true;
                    $obj['accuracy'] = 'NULL';
                }

                //----------------------------------fine----------------- CASO GPS, fused
            }
        }
        //FINE PROVIDER check

        //latitudine, longitudine, date
        if(($lat_pre_scan_no_spaces != '') && ($long_pre_scan_no_spaces != '') ){//TODO: capire meglio quando deve dare errore
            //per ora: SE ho le una delle due coordinate diverse da NULL, le valido e in caso di errore lo scrivo nel messaggio
            //inserisco lo stesso nel DB poch+ non è required
            if(!(isValidLatitude($lat_pre_scan_no_spaces) && isValidLongitude($lat_pre_scan_no_spaces))){
                //se NN è una coordinata valida, metto NULL
                $lat_pre_scan_no_spaces = '0';//'NULL';
                $long_pre_scan_no_spaces = '0';//'NULL';
            }
        }
        else{
            //se NN è una coordinata valida, metto NULL
            $lat_pre_scan_no_spaces = '0';//'NULL';
            $long_pre_scan_no_spaces = '0';//'NULL';
        }
        if($obj['date_pre_scan'] == '')
                $obj['date_pre_scan'] = 'NULL';
        else{
            if(isValidDateTime($obj['date_pre_scan']))
                $obj['date_pre_scan'] = "'".$obj['date_pre_scan']."'";
            else
                $obj['date_pre_scan'] = "'0000-00-00 00:00:00'"; 
        }


// FINE ---------- type = wifi, beacon, status -------------------------------

        if($type == 'wifi' || $type == 'beacon'){
            $type_is_valid = true;

            if($type == 'wifi' ){//Only wifi
                
                //TODO check del valore... fare escape: mysql_escape_string()
                $obj['network_name'] = mysql_escape_string($obj['network_name']);
                $network_name_is_valid = true;
                
                
                if(isset($obj['device_model']) && $obj['device_model']!='')
                    $device_model = "'".mysql_escape_string($obj['device_model'])."'"; 
                else
                    $device_model = 'NULL';
                
//inizio gestione uid e device_id
                if(isset($obj['device_id']) && $obj['device_id']!='')
                    $device_id = "'".$obj['device_id']."'"; 
                else
                    $device_id = 'NULL';

                if(isset($obj['uid']) && $obj['uid']!='')
                    $uid = "'".$obj['uid']."'"; 
                else
                    $uid = 'NULL';
//fine gestione uid e device_id

                if($UUID_no_spaces == ''){
                    $UUID_is_valid = true;
                }

                if($id_no_spaces == ''){
                    $id_is_valid = true;
                }

                if(isset($obj['sensor_name']) && ($obj['sensor_name'] == '')
                || !isset($obj['sensor_name'])){
                    $sensor_name_is_valid = true;
                }
                
                if($minor_no_space == ""){
                    $minor_is_valid = true;
                    $minor_no_space = 'NULL';
                }
                if($major_no_space == ""){
                    $major_is_valid = true; 
                    $major_no_space = 'NULL';
                }

                if(isset($obj['frequency']) && $obj['frequency'] !=''){
                    $result = false;
                    $result = isValidFrequency($obj['frequency']); //isValidPowerFrequency($obj['power']);
                    if($result){
                        $frequency_is_valid = true;
                        $obj['frequency'] = $result;
                    }
                }
                else
                    $frequency_is_valid = true;
                    
                $obj['capabilities'] = mysql_escape_string($obj['capabilities']);
                $capabilities_is_valid = true;

//check su PROVIDER, prima era QUI

            }
            else {//Only Beacon
                if((isset($obj['network_name']) && $obj['network_name'] == '') ||
                    !isset($obj['network_name'])){
                    $network_name_is_valid = true;
                }
                $UUID_is_valid = true;//not required
                $id_is_valid = true;
                $sensor_name_is_valid = true;
                $frequency_is_valid = true;
                $capabilities_is_valid = true;

                $obj['frequency'] = 'NULL';
                //$lat_pre_scan_no_spaces = 'NULL';
                //$long_pre_scan_no_spaces = 'NULL';          
                //$obj['date_pre_scan'] = 'NULL';

                if($obj['device_model']!='')
                        $device_model = "'".mysql_escape_string($obj['device_model'])."'"; 
                    else
                        $device_model = 'NULL';    

//inizio gestione uid e device_id
                if($obj['device_id']!='')
                    $device_id = "'".$obj['device_id']."'"; 
                else
                    $device_id = 'NULL';
                if($obj['uid']!='')
                    $uid = "'".$obj['uid']."'"; 
                else
                    $uid = 'NULL';
//fine gestione uid e device_id

                if($minor_no_space == ""){
                    $minor_is_valid = true;
                    $minor_no_space = 'NULL';
                }
                else
                    $minor_is_valid = isValidInt($minor_no_space);//deve essere un intero
                if($major_no_space == ""){
                    $major_is_valid = true; 
                    $major_no_space = 'NULL';
                }
                else
                    $major_is_valid = isValidInt($major_no_space);//deve essere un intero
            }
            $mac_address_is_valid = isValid_MAC($obj['MAC_address']);
        }
        else if($type == 'status'){//gestisco come caso a se'
            $mac_address_is_valid = true;
            $type_is_valid = true; 
            $network_name_is_valid = true;
            $date_is_valid = true;
            $place_is_valid = true;
            $sensor_name_is_valid = true;
            $power_is_valid = true;
            $rssi_is_valid = true;
            $minor_is_valid = true;
            $major_is_valid = true;
            $UUID_is_valid = true;
            $id_is_valid = true;
            $frequency_is_valid = true;
            $capabilities_is_valid = true;

            //aggiungo condizione su latitudine e longitudine
            if($obj['latitude'] == "0.00000")
                $latitude_no_spaces = '0';//'NULL'; 
            if($obj['longitude'] == "0.00000")
                $longitude_no_spaces = '0';//'NULL'; 

            if($obj['device_model']!='')
                    $device_model = "'".mysql_escape_string($obj['device_model'])."'"; 
                else
                    $device_model = 'NULL';    

//inizio gestione uid e device_id
            if($obj['device_id']!='')
                $device_id = "'".$obj['device_id']."'"; 
            else
                $device_id = 'NULL';
            if($obj['uid']!='')
                $uid = "'".$obj['uid']."'"; 
            else
                $uid = 'NULL';
//fine gestione uid e device_id
           
                if($minor_no_space == ""){
                    $minor_is_valid = true;
                    $minor_no_space = 'NULL';
                }
                if($major_no_space == ""){
                    $major_is_valid = true; 
                    $major_no_space = 'NULL';
                }
        }//fine type 'status'
    }
    
    
    if( isset($obj['power']) && $obj['power'] !=''){
        $result = false;
        $result = isValidPower($obj['power']); //isValidPowerFrequency($obj['power']);
        if($result){
            $power_is_valid = true;
            $obj['power'] = $result;
        }
    }
    else
        $power_is_valid = true;

    if(isset($obj['rssi']) && $obj['rssi'] !=''){
        $result = false;
        $result = isValidPower($obj['rssi']); //isValidPowerFrequency($obj['rssi']);
        if($result){
            $rssi_is_valid = true;
            $obj['rssi'] = $result;
            }
        }
    else 
        $rssi_is_valid = true;
    
    //--------------------------fine validazione
                
    //inizio stampa messaggio errore                         
    $error_message = '';
    if(!$date_is_valid)
        $error_message .= ' | Invalid date format. Wrong data: "'.$obj['date'].'"';

    if(!$place_is_valid)
        $error_message .= ' | Invalid coordinates. Wrong data: latitude = "'.$obj['latitude'].
                                '" ; longitude = "'.$obj['longitude'].'"';
    
    if($type == 'wifi'){

        if(!$UUID_is_valid)
            $error_message .= ' | Invalid UUID value (NO UUDI value is admitted for a wifi sensor). Wrong data: "'.$obj['UUID'].'"';
        if(!$id_is_valid)
            $error_message .= ' | Invalid id value (NO id value is admitted for a wifi sensor). Wrong data: "'.$obj['id'].'"';
        if(!$minor_is_valid)
            $error_message .= ' | Invalid minor value (NO minor value is admitted for a wifi sensor). Wrong data: "'.$obj['minor'].'"';
        if(!$major_is_valid)
            $error_message .= ' | Invalid major value (NO major value is admitted for a wifi sensor). Wrong data: "'.$obj['major'].'"';
        if(!$rssi_is_valid)
                $error_message .= ' | Invalid rssi. Wrong data: "'.$obj['rssi'].'"';
        if(!$sensor_name_is_valid)
            $error_message .= ' | Invalid sensor name (NO sensor name is admitted for a wifi sensor). Wrong data: "'.$obj['sensor_name'].'"';
        if(!$frequency_is_valid)
            $error_message .= ' | Invalid frequency. Wrong data: '.$obj['frequency'];
        
        //------------- from 02/11/2015
        /* 
       if(!$speed_is_valid) 
            $error_message .= ' | Invalid speed. Please insert a number (meters/second). Wrong data: "'.$obj['speed'].'"';
 
       if(!$heading_is_valid) 
            $error_message .= ' | Invalid heading. Please insert a number (degree). Wrong data: "'.$obj['heading'].'"';
   
        if(!$altitude_is_valid) 
            $error_message .= ' | Invalid altitude. Please insert a number (meters). Wrong data: "'.$obj['altitude'].'"';

        if(!$accuracy_is_valid) 
            $error_message .= ' | Invalid accuracy. Please insert a number (meters). Wrong data: "'.$obj['accuracy'].'"';
      */  
 
        

    }
    else if($type == 'beacon'){ //beacon
        if(!$network_name_is_valid)   
            $error_message .=  ' | Invalid network_name. A beacon could not have a network name. Wrong data: "'.$obj['network_name'].'"'; 
        if(!$minor_is_valid)
            $error_message .= ' | Invalid minor format. Wrong data: "'.$obj['minor'].'"'; 
        if(!$major_is_valid)
            $error_message .= ' | Invalid major format. Wrong data: "'.$obj['major'].'"';
        if(!$rssi_is_valid)
                $error_message .= ' | Invalid rssi. Wrong data: "'.$obj['rssi'].'"';
        if(!$capabilities_is_valid)
            $error_message .= ' | Invalid capabilities (NO capabilities is admitted for a beacon sensor). Wrong data: "'.$obj['capabilities'].'"';
        if(!$frequency_is_valid)
            $error_message .= ' | Invalid frequency (NO frequency is admitted for a beacon sensor). Wrong data: "'.$obj['frequency'].'"';
    }
    else if(!$type_is_valid)
        $error_message .= ' | Invalid type, please specify: "wifi" or "beacon", no other "type" values are admitted. Wrong data: "'.$obj['type'].'"';

    if(!$mac_address_is_valid)
        $error_message .= ' | Invalid MAC address. Wrong data: "'.$obj['mac_address'].'"';
    if(!$power_is_valid)
        $error_message .= ' | Invalid power format: please specify value and unit of measurement. Wrong data: "'.$obj['power'].'"';

    //die('Could not enter data: '.$error_message );

//inizializzo le stringhe
    if(!isset($obj['rssi']))
        $obj['rssi'] = 'NULL';
    if(!isset($obj['frequency']))
        $obj['frequency'] = 'NULL';
    if(!isset($obj['capabilities']))
        $obj['capabilities'] = '';
    if(!isset($obj['provider']))
        $obj['provider'] = '';
    if(!isset($obj['sensor_name']))
        $obj['sensor_name'] = '';
    if(!isset($obj['power']))
        $obj['power'] = 'NULL';
    if(!isset($obj['network_name']))
        $obj['network_name'] = '';
//new

    if(!isset($obj['lat_pre_scan']))
        $obj['lat_pre_scan'] = NULL;
    if(!isset($obj['long_pre_scan']))
        $obj['long_pre_scan'] = NULL;

    $query = '';
    $query2 = ''; //MICHELA errore undefined variable
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && $_SERVER["HTTP_X_FORWARDED_FOR"] != ""){
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    }else{
        $ip = $_SERVER["REMOTE_ADDR"];
    }

        if($date_is_valid && $place_is_valid && $type_is_valid && $network_name_is_valid && $sensor_name_is_valid 
            && $mac_address_is_valid && $power_is_valid &&  $rssi_is_valid && $minor_is_valid && 
            $major_is_valid && $UUID_is_valid && $id_is_valid && $frequency_is_valid && $capabilities_is_valid &&
            $speed_is_valid && $altitude_is_valid && $provider_is_valid  && $accuracy_is_valid  && $heading_is_valid)
        {//i dati del json sono tutti scritti in modo corretto 
    
//inizio gestione paramtri uidi e device_id
if($uid != 'NULL')
    $uid_insert = $uid;
else 
    $uid_insert = $device_id;
//fine gestione
 
            $query = "INSERT INTO sensors.sensors (date, sender_IP, latitude, longitude, type, network_name, sensor_name, MAC_address, power, rssi, minor, major, UUID, id, frequency, capabilities, speed, altitude, provider, accuracy, heading, frequency_n, power_n, rssi_n, device_model, device_id, date_pre_scan, lat_pre_scan, long_pre_scan, status, prev_status, appID, version, lang, uid2, profile) VALUES ('".
                    $obj['date']."', '".$ip."' , ".$latitude_no_spaces." , ".$longitude_no_spaces.",'".$type."', '".$obj['network_name'].
                    "' , '". $obj['sensor_name']."' , '". $obj['MAC_address']."',NULL,NULL, ".$minor_no_space.
                    ", ".$major_no_space." ,'". $UUID_no_spaces ."', '". $id_no_spaces ."',NULL, '". $obj['capabilities'].
                    "' , ".$obj['speed']." , ".$obj['altitude']." , '".$obj['provider']."' , ". $obj['accuracy']." , ".$obj['heading'].
                    " , ".$obj['frequency']. " , ". $obj['power']." , ".$obj['rssi']." , ".$device_model. " ,".$uid_insert .
                    " , ".$obj['date_pre_scan']." , ".$lat_pre_scan_no_spaces." , ".$long_pre_scan_no_spaces.
                    ",'".$obj['status']."' , '".$obj['prev_status']."', '".$obj['appID']."' , '".$obj['version'].
                    "','".$obj['lang']."', '".$obj['uid2']."','".$obj['profile'].
                    "')";

            if($latitude_no_spaces == 'NULL')  
                $cc_x = 'NULL';//se type='status'
            else
                $cc_x = round($longitude_no_spaces/180*pi()*6371000/69)*69;

            if($longitude_no_spaces == 'NULL') 
                $cc_y = 'NULL';//se type='status'
            else 
                $cc_y = round(6371000 * log(tan(pi()/4+$latitude_no_spaces/180*pi()/2))/69)*69; 



            $query2 = "INSERT INTO sensors.user_eval (date, device_id, latitude, longitude, cc_x, cc_y, speed, altitude, provider, accuracy, heading, lat_pre_scan, long_pre_scan, date_pre_scan, prev_status, curr_status, appID, version, lang, uid2, profile, lin_acc_x, lin_acc_y, lin_acc_z, avg_lin_acc_magn, avg_speed) VALUES ( '".
                        $obj['date']."' , ".$uid_insert.
                        " , ".$latitude_no_spaces." , ".$longitude_no_spaces." , $cc_x, $cc_y, ".$obj['speed']." , ".$obj['altitude'].
                        " , '". $obj['provider']."' , ". $obj['accuracy']." , ".$obj['heading']. " , ".$lat_pre_scan_no_spaces.
                        " , ".$long_pre_scan_no_spaces." , " .$obj['date_pre_scan']." , '".$obj['prev_status']."' , '".$obj['status'].
                        "', '".$obj['appID']."' , '".$obj['version']."', '".$obj['lang']."', '".$obj['uid2']."','".$obj['profile'].
                        "', ".$obj['lin_acc_x']." , ".$obj['lin_acc_y'].", ".$obj['lin_acc_z'].", ".$obj['avg_lin_acc_magn'].", ".$obj['avg_speed'].
                        ")";
        //}
    }
    

    if($error_message !='')
        $error_message = $type. $error_message;
    $response['error_message'] = $error_message;

    $query = str_replace(array("\n", "\r"), ' ', $query);
    $query2 = str_replace(array("\n", "\r"), ' ', $query2);
    
    $response['query'] = $query; 
    $response['query2'] = $query2; 
    return $response;
}

function create_file_log(){
    $path = realpath(dirname(__FILE__));
    $log_dir = $path.'/log_api_insert_sensors';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $now   = new DateTime;
    $today = $now->format( 'd-m-Y' );
    $filename = $log_dir."/log_api_insert_sensors_".$today.".txt";
    $file_log = NULL;
    if (!file_exists($filename)){
       $file_log = fopen($filename, 'a') or die("can't open file");
       $header = "Today LOGs (".$today."):\ntimem | ERROR (optional) | client IP | message \n";
       fwrite($file_log, $header);
    }
    //apertura del file in scrittura
    $file_log = fopen($filename, 'a');
    return $file_log;
}


function create_file_log_test(){
    $path = realpath(dirname(__FILE__));
    $log_dir = $path.'/log_api_insert_sensors';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $now   = new DateTime;
    $today = $now->format( 'd-m-Y' );
    $filename = $log_dir."/log_test_".$today.".txt";
    $file_log_test = NULL;
    if (!file_exists($filename)){
       $file_log_test = fopen($filename, 'a') or die("can't open file");
       $header = "Today LOGs (".$today."):\ntimem | ERROR (optional) | client IP | message \n";
       fwrite($file_log_test, $header);
    }
    //apertura del file in scrittura
    $file_log_test = fopen($filename, 'a');
    return $file_log_test;
}

// Method: POST, PUT, GET etc
// Data: array("param" => "value") ==> index.php?param=value

function CallAPI($method, $url, $data = false)
{
    $curl = curl_init();
    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data){
                
                $url = sprintf("%s?%s", $url, http_build_query($data));
            }
    }

    // Optional Authentication:
    //curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    //curl_setopt($curl, CURLOPT_USERPWD, "username:password");

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 5);//fino al 26gennaio era a 2
   
    $result = curl_exec($curl);
    //inizializzo file per stampe di test-- DA RIMUOVERE o COMMENTARE
    //$file_log_test = create_file_log_test();
    
    if(curl_errno($curl))//CURLE_OPERATION_TIMEOUTED => 28
    {
        //fwrite($file_log_test,  "\nERRR ".curl_errno($curl));
        $result = NULL;
    }
    
    curl_close($curl);

    return $result;
}

function create_file_error_log(){
    $path = realpath(dirname(__FILE__));
    $log_dir = $path.'/log_api_insert_sensors';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $filename = $log_dir."/log_api_insert_ERROR_sensors.txt";
    $file_log_error = NULL;
    if (!file_exists($filename)){
       $file_log_error = fopen($filename, 'a') or die("can't open file");
       $header = "INSERT ERRORS registered:\nday | time | ERROR | client IP | message \n";
       fwrite($file_log_error, $header);
    }
    //apertura del file in scrittura
    $file_log_error = fopen($filename, 'a');
    return $file_log_error;
}

//return JSON array
header('Access-Control-Allow-Origin: *');
exit(json_encode($value));
?>



