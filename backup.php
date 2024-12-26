<?php

// cron job 2 min

error_reporting(0);
date_default_timezone_set('Asia/Tehran');

$token_bot = ""; // Your bot token telegram
$id_channel = "-100111111"; // Telegram channel ID

$_SERVER['SERVER_NAME'] = 'Domain.com'; // your Domain host

$array_database = [
    "Domain.com" =>[
        [
            'db_user' => '', // user database
            'db_name' => '', // name database
            'db_pass' => '', // password database
            'filename_database' => '', // Any name for name file
        ],
        [
            'db_user' => '',
            'db_name' => '',
            'db_pass' => '',
            'filename_database' => '',
        ],
        [
            'db_user' => '',
            'db_name' => '',
            'db_pass' => '',
            'filename_database' => '',
        ],
    ],
];






function bot($method,$datas=[]){
    global $token_bot;
    $multi1 = curl_multi_init();
    $curl1 = curl_init();
    curl_setopt_array($curl1,array(
        CURLOPT_URL => "https://api.telegram.org/bot{$token_bot}/{$method}",
        CURLOPT_TIMEOUT => 90,
        CURLOPT_CONNECTTIMEOUT => 60,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_WHATEVER,
        CURLOPT_SSL_VERIFYHOST =>false,
        CURLOPT_SSL_VERIFYPEER =>false,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $datas
    ));
    curl_multi_add_handle($multi1, $curl1);
    $curlHandl1 = $curl1;
    $running = null;
    do {
        curl_multi_exec($multi1, $running);
        curl_multi_select($multi1);
        $status = curl_multi_exec($multi1, $running);
    if ($status === CURLM_CALL_MULTI_PERFORM) {
        curl_multi_select($multi1);
    }
    } while ($status === CURLM_CALL_MULTI_PERFORM || $running);
    $resp = curl_multi_getcontent($curlHandl1);
    $resp1 = json_decode($resp);
    curl_multi_remove_handle($multi1, $curlHandl1);
    curl_close($curlHandl1);
    curl_multi_close($multi1);
    return $resp1;
}


function backup_database($user_database = null , $password_database = null , $name_database = null , $namefile_database = 'backup' , $directory = null , $localhost = 'localhost')
{
    $connection = new mysqli($localhost,$user_database,$password_database,$name_database);
    $connection->query("SET NAMES 'utf8mb4'");

    if($connection){
        $tables = array();
        $result = mysqli_query($connection,"SHOW TABLES");
    
        while($row = mysqli_fetch_row($result)){
            $tables[] = $row[0];
        }
    
        $return = '';
    
        foreach($tables as $table){
            $result = mysqli_query($connection,"SELECT * FROM ".$table);
            $num_fields = mysqli_num_fields($result);
            
            $row2 = mysqli_fetch_row(mysqli_query($connection,"SHOW CREATE TABLE ".$table));
            $return .= "\n\n".$row2[1].";\n\n";
            
            for($i=0;$i<$num_fields;$i++){
                while($row = mysqli_fetch_row($result)){
                    $return .= "INSERT INTO ".$table." VALUES(";
                    for($j=0;$j<$num_fields;$j++){
                        $row[$j] = addslashes($row[$j]);
                        if(isset($row[$j])){ $return .= '"'.$row[$j].'"';}
                        else{ $return .= '""';}
                        if($j<$num_fields-1){ $return .= ',';}
                    }
                    $return .= ");\n";
                }
            }
            $return .= "\n\n\n";
        }



        if(!is_dir($directory) and $directory != null){
            mkdir($directory);
            $namefile = trim($directory,"/ ") . '/' . $namefile_database . ".sql";
        }else{
            if($directory == null){
                $namefile = $namefile_database . ".sql";
            }else{
                $namefile = trim($directory,"/ ") . '/' . $namefile_database . ".sql";
            }
        }

            
            

        $handle = fopen($namefile,"w+");
        fwrite($handle,$return);
        fclose($handle);

        return $namefile;
    }else{
        return false;
    }
}


function zipCreate($nameFile = 'myzip1' , $directoryZip = './' , $directoryCreate = null ){

    $zip = new ZipArchive;

    $directoryCreate = ($directoryCreate == null) ? null : trim($directoryZip,"/ ") . '/';

    if ($zip->open( $directoryCreate . $nameFile . '.zip' , ZipArchive::CREATE) === TRUE)
    {
        if ($handle = opendir($directoryZip))
        {
   
            while (false !== ($entry = readdir($handle)))
            {
                if ($entry != "." && $entry != ".." && !is_dir(trim($directoryZip,"/ ") . '/' . $entry))
                {
                    $zip->addFile(trim($directoryZip,"/ ") . '/' . $entry , $entry);
                }
            }
            closedir($handle);
        }
     
        $zip->close();

        return true;
    }else{
        return false;
    }
}


function removeFolder(string $dir , $isremoveFolder = false){

    $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it,
                 RecursiveIteratorIterator::CHILD_FIRST);

    foreach($files as $file) {
        if ($file->isDir()){
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }

    if($isremoveFolder)rmdir($dir);

    return true;
}




$todaydate = date("l");
$datetimedate = date("Y-m-d");
$timesdate = date("H-i-s");

$time = date("H:i");

if($time == "00:00"){ // At 00:00 AM, the file will be sent to your Telegram channel where the bot is the admin.

    $name_server = $_SERVER['SERVER_NAME'];
    $arrays = $array_database[$name_server];

    $dirctor = $name_server;

    foreach($arrays as $property){

        $filename_database = "Database-{$property['filename_database']}_{$datetimedate}_{$timesdate}";

        backup_database($property['db_user'],$property['db_pass'],$property['db_name'],$filename_database,$dirctor);
        
    }


    $name_zip = "Database-" . $dirctor . '_' . $datetimedate;

    zipCreate($name_zip,$dirctor,$dirctor);


    bot('sendDocument', [
        'chat_id' => $id_channel,
        'document'=> new CURLFile($dirctor ."/". $name_zip . ".zip"),
        'caption' => "با موفقیت از دیتابیس {$dirctor} بکاپ گرفته شد ✅\n\n🕚 در تاریخ : {$todaydate} | {$datetimedate} | {$timesdate}\n\n➖➖➖➖➖➖➖➖",
        'parse_mode'=>"HTML"
    ]);



    removeFolder($dirctor);

}