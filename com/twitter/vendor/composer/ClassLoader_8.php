<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
    ini_set("memory_limit",-1);
	date_default_timezone_set("America/New_York");

    //Include GuzzleHttp library
    require "vendor/autoload.php";

    //  Get the passed arguments
    $arguments = getopt("a:");
    $arguments = explode("|",$arguments['a']);

    $temp_ep_files_folder = $arguments[0];
    $server_to_move_files_to_1 = $arguments[1];
	
	echo "Scanning directory ".$temp_ep_files_folder."...\n";
	
    //Get the files to process
    $directory_listing = scandir($temp_ep_files_folder,SCANDIR_SORT_ASCENDING);
	
	echo "(".Date("Y-m-d H:i:s").") "."Found ".sizeof($directory_listing)." files, processing...\n";
	
    //Define the files that are going to be ignored
    $ignore = array('.', '..','ready_to_send');

    //Define the do-while flag
    $scan_again = false;
	
    do {
		
		$file_counetr = 0;
		
        //Loop through the directory
        foreach ($directory_listing as $file) {

            //Check if the current file is not in the ignored array
            if (!in_array($file, $ignore)) {

                //Verify that the file is not a directory nor an invalid file
                if (!is_dir($temp_ep_files_folder.$file) && is_file($temp_ep_files_folder.$file)) {
					
					echo "(".Date("Y-m-d H:i:s").") "."Opening file $file...\n";
					
                    //Open the file
                    $file_handle = fopen($temp_ep_files_folder.$file,'r');
					
					//Check for error
                    if ($file_handle === false) {
                        $error_message = "Anpro file save exception: Can not read/write file '".$file."' on path '".$temp_ep_files_folder."' with error message 'No such file or directory'";
                        file_put_contents("/web/eprensa/logs/anpro/".Date("Y-m-d")."_error_log.log","(".Date("Y-m-d H:i:s").") append_html_to_ep_file.php > ERR_CANT_READ_OR_WRITE_FILE ".$error_message."\r",FILE_APPEND);
                        trigger_error($error_message,E_USER_ERROR);
						echo "\n";
						continue;
                    }
					
					echo "(".Date("Y-m-d H:i:s").") "."Getting contents of file $file... ";
					
                    //Read the contents of the file
                    //Get the URL contents from the EP file
                    $ep_file_url = preg_replace("/[URL:][:URL]/","",explode("\n",fread($file_handle, filesize($temp_ep_files_folder."/".$file)))[1]);
					
					echo "found URL $ep_file_url \n";
					echo "(".Date("Y-m-d H:i:s").") "."Closing file $file... ";
					
                    if (fclose($file_handle) === false) {
                        $error_message = "Anpro file save exception: Can not read/write file '" .$file. "' on path '" .$temp_ep_files_folder. "' with error message 'The file is in use by another program";
                        file_put_contents("/web/eprensa/logs/anpro/".Date("Y-m-d")."_error_log.log","(".Date("Y-m-d H:i:s").") append_html_to_ep_file.php > ERR_CANT_READ_OR_WRITE_FILE ".$error_message."\r",FILE_APPEND);
                        trigger_error($error_message,E_USER_ERROR);
						echo "\n";
						continue;
                    }
					echo "Done\n";
					echo "(".Date("Y-m-d H:i:s").") "."processing HTML content... ";
					
                    try {
                        //Get the HTML content from the URL
                        $site_html_content = guzzleHttpSendPetition(array("url" => $ep_file_url, "file" => $temp_ep_files_folder.$file));
                    } catch(Exception $e) {
                        $site_html_content = "";
                        $error_message = "Anpro EP file save exception: Can not connect to site '".$ep_file_url."' from file '" .$file. "' on path '" .$temp_ep_files_folder. "' with error message '" . $e->getMessage() . "'";
                        file_put_contents("/web/eprensa/logs/anpro/".Date("Y-m-d")."_error_log.log","(".Date("Y-m-d H:i:s").") append_html_to_ep_file.php > ERR_CANT_CONNECT_TO_SITE ".$error_message."\r",FILE_APPEND);
                        trigger_error($error_message,E_USER_NOTICE);
						echo "\n";
                    }
					
					echo "Done\n";
					echo "(".Date("Y-m-d H:i:s").") "."Writing HTML content into file $file... ";
                    
                    //Write the HTML content of the url to the EP file
                    if (file_put_contents($temp_ep_files_folder.$file,"\n".$site_html_content,FILE_APPEND) === false) {
                        $error_message = "Anpro file save exception: Can not read/write file '" .$file. "' on path '" .$temp_ep_files_folder. "' with error message 'No such file or directory'";
                        file_put_contents("/web/eprensa/logs/anpro/".Date("Y-m-d")."_error_log.log","(".Date("Y-m-d H:i:s").") append_html_to_ep_file.php > ERR_CANT_READ_OR_WRITE_FILE ".$error_message."\r",FILE_APPEND);
                        trigger_error($error_message,E_USER_ERROR);
						echo "\n";
						continue;
                    }

					echo "Done\n";
					echo "(".Date("Y-m-d H:i:s").") "."Compressing file $file into gzip format... ";
					
                    try {
                        //Compress the EP file and remove the original one
                        shell_exec("gzip -f ".$temp_ep_files_folder.$file);

                        //Check if the ready_to_send directory exists
                        if (!file_exists($temp_ep_files_folder."ready_to_send")) {
                            //Create the ready_to_send directory
                            mkdir($temp_ep_files_folder."ready_to_send",0777,true);
                        }

                    } catch(Exception $e) {
                        $error_message = "Anpro file save exception: Can not read/write/move file '" .$file. "' on path '" .$temp_ep_files_folder. "' with error message '" . $e->getMessage() . "'";
                        file_put_contents("/web/eprensa/logs/anpro/".Date("Y-m-d")."_error_log.log","(".Date("Y-m-d H:i:s").") append_html_to_ep_file.php > ERR_CANT_READ_OR_WRITE_FILE ".$error_message."\r",FILE_APPEND);
                        trigger_error($error_message,E_USER_NOTICE);
						echo "\n";
                    }

					echo "Done\n";
					echo "(".Date("Y-m-d H:i:s").") "."Moving file $file.gz to folder $temp_ep_files_folder... ";
					
                    //Move the file the ready to send folder
                    if (rename($temp_ep_files_folder.$file.".gz",$temp_ep_files_folder."ready_to_send/".$file.".gz") === false) {
                        $error_message = "Anpro file save exception: Can not move file '" .$file. "' on path '" .$temp_ep_files_folder. "' with error message 'No such file or directory'";
                        file_put_contents("/web/eprensa/logs/anpro/".Date("Y-m-d")."_error_log.log","(".Date("Y-m-d H:i:s").") append_html_to_ep_file.php > ERR_CANT_READ_OR_WRITE_FILE ".$error_message."\r",FILE_APPEND);
                        trigger_error($error_message,E_USER_NOTICE);
						echo "\n";
                    }
					
					echo "Done\n";
					echo "(".Date("Y-m-d H:i:s").") "."Finished processing file $file\n";
					
					$file_counetr++;
					
					echo "(".Date("Y-m-d H:i:s").") "."Processed files counter: ".$file_counetr."\n\n";

                } else {
                    continue;
                }

                clearstatcache();
            }
        }
		
		echo "(".Date("Y-m-d H:i:s").") "."Scanning directory ".$temp_ep_files_folder."...\n";
		
        //Check again the directory for any new files
        $directory_files_counter = 0;
        foreach (scandir($temp_ep_files_folder,SCANDIR_SORT_ASCENDING) as $file) {
            if (!in_array($file, $ignore)) {
                if (!is_dir($temp_ep_files_folder.$file) && is_file($temp_ep_files_folder.$file)) {
                    $directory_files_counter++;
                }
            }
        }
		
		echo "(".Date("Y-m-d H:i:s").") "."Found $directory_files_counter files, processing...\n";

        if ($directory_files_counter > 0) {
            //Get the new directory listing
            $directory_listing = scandir($temp_ep_files_folder,SCANDIR_SORT_ASCENDING);

            //Reset the flags to start processing the files again
            $scan_again = true;
        } else {
            $scan_again = false;
        }

    } while($scan_again);

    //Check whether the ep file mover script is already running
    if (shell_exec("ps -ef | grep 'move_ep_files_to_server.php' | wc -l") <= 2) {
		
		echo "(".Date("Y-m-d H:i:s").") "."Moving compressed files in folder $temp_ep_files_folder to server $server_to_move_files_to_1 ...";
		
        shell_exec("php -q ".realpath(__DIR__)."/move_ep_files_to_server.php -a='".$temp_ep_files_folder."|".$server_to_move_files_to_1."' &> /dev/null &");
		
		echo "Done\n";
    }
	
	echo "(".Date("Y-m-d H:i:s").") "."Process finished\n";
	
    function guzzleHttpSendPetition($args) {

        //Set the flag to repeat the do-while loop in case of error
        $repeat = true;

        //Set the proxy values
        $proxy_url = "http://epimente051.eprensa.com";
        $proxy_parameters = "/proxy/direct.php?url=";
        $proxy_active = false;
        $proxy_do_while_counter = 0;

        do {

            //Get the ULR domain
            $url_value = implode("/",array_slice(explode("/",$args['url']),0,3));
            //Get the URL parameters
            $url_parameters = "/".preg_replace('/[:]/', '%3A', implode("/", array_slice(explode("/", $args['url']), 3)));
            $url_parameters = preg_replace("/^\/{2,}/","/",$url_parameters);

            //Create the anpro HTTP object
            $guzzleHttp_object = new GuzzleHttp\Client([
                "base_uri" => ($proxy_active == true ? $proxy_url : $url_value)
            ]);

            //Send the HTTP GET request
            $guzzleHttp_response = $guzzleHttp_object->request("GET", ($proxy_active == true ? $proxy_parameters.$url_value.$url_parameters : $url_parameters), ['verify' => false, 'http_errors' => false, 'allow_redirects' => ['max' => 5, 'referer' => true], 'connect_timeout' => 15, 'timeout' => 15]);

            //Check whether the petition got any error message
            switch ($guzzleHttp_response->getStatusCode()) {
                case 200:

                    //Get the response
                    $guzzleHttp_response_content = $guzzleHttp_response->getBody();

                    $proxy_active = false;
                    $repeat = false;
                    break;
                case 403:
                case 451:
                case 508:
					
					echo "using proxy to get HTML content from site ".$args['url']."... ";
					
                    //Check whether the do-while loop attempted to connect too many times
                    if ($proxy_do_while_counter < 10) {
                        $proxy_do_while_counter++;
                        $proxy_active = true;
                        $repeat = true;
                    } else {
                        $guzzleHttp_response_content = "";

                        $error_message = "Anpro petition exception: The proxy could not connect to URL '" . $args['url'] . "'";
                        file_put_contents("/web/eprensa/logs/anpro/".Date("Y-m-d")."_error_log.log", "(" . Date("Y-m-d H:i:s") . ") append_html_to_ep_file.php > ERR_PROXY_CONNECTION " . $error_message . "\r", FILE_APPEND);
                        trigger_error($error_message,E_USER_NOTICE);

                        $proxy_do_while_counter = 0;
                        $proxy_active = false;
                        $repeat = false;
                    }

                    break;
                default:

                    $guzzleHttp_response_content = "";
                    $error_message = "Anpro petition exception: The server returned code '" . $guzzleHttp_response->getStatusCode() . "' on petition '" . $args['url'] . "'";
                    file_put_contents("/web/eprensa/logs/anpro/".Date("Y-m-d")."_error_log.log", "(" . Date("Y-m-d H:i:s") . ") append_html_to_ep_file.php > ERR_CODE_" . $guzzleHttp_response->getStatusCode() . " " . $error_message . "\r", FILE_APPEND);
                    trigger_error($error_message,E_USER_NOTICE);

                    $proxy_active = false;
                    $repeat = false;
                    break;
            }

        } while($repeat);

        return $guzzleHttp_response_content;

    }

?>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     