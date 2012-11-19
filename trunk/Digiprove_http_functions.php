<?php

// THIS VERSION USES TRY/CATCH BLOCKS AND NEEDS PHP5 OR LATER
class Digiprove_HTTP
{
	function makePost($request, $host, $path, $service, $ip=null)
	{
		// In this primitive version, just handles a single level of redirect...
		$dprv_response = post($request, $host, $path, $service, $ip);
		if (substr($dprv_response,0,8) == "Redirect")
		{	
			$pos = strpos($dprv_response, " ");
			if (substr($dprv_response, $pos+1, 3) != "301" && substr($dprv_response, $pos+1, 3) != "302" && substr($dprv_response, $pos+1, 3) != "307")
			{
				return $dprv_response;
			}
			$host = substr($dprv_response, $pos+1, 3);	// change this to find new hostname
			return post($request, $host, $path, $service, $ip);
		}
	}

	function post($request, $host, $path, $service, $ip=null) 
	{
		$log = new DPLog();  
		$request = "xml_string=" . urlencode($request);
		$http_request  = "POST " . $path . $service . " HTTP/1.1\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\n";
		$http_request .= "Content-Length: " . strlen($request) . "\r\n";
		$http_request .= "Connection: close\r\n\r\n";
		$http_request .= $request;  

		// use a specific IP if provided
		if ( $ip && long2ip(ip2long($ip)) )
		{
			$http_host = $ip;
		}
		else
		{
			$http_host = $host;
		}
		$dprv_port = 80;
		$response = '';
		if (DPRV_SSL == "Yes")
		{
			$t = stream_get_transports();
			for ($i=0; $i<count($t); $i++)
			{
				if (stripos($t[$i], "ssl") !== false)
				{
					// TODO: Test to see if the ssl:// prefix is actually required
					$http_host = "ssl://" . $http_host;
					$dprv_port = 443;
					break;
				}
			}
		}

		try																	// try/catch block not supported in php4
		{
			$errno = -1;
			$errstr = "Unknown";
			// TODO - Somehow incorporate this error-handling logic into Digiprove SDK
			// Trap errors (as alternative to suppressing)
			global $dprv_last_error;
			$dprv_last_error = "";
			if (class_exists('dprvErrors'))
			{
				set_error_handler(array("dprvErrors", "catch_error"));     // Note that before php 4.3 this array reference will not work
			}
			// Test Instructions:
			//$fs = fsockopen($http_host, $dprv_port, $errno, $errstr, 10, 20, 30, 100);		// warning error
			//$fs = fsockopen($http_host, 89, $errno, $errstr, 10);								// time-out
			//$fs = fsockopensesame($http_host, $dprv_port, $errno, $errstr, 10);				// fatal error
			//$fs = fsockopen("www.digiprovedoesnotexist.com", $dprv_port, $errno, $errstr, 10);// dns error

			// Correct instruction
			$fs = fsockopen($http_host, $dprv_port, $errno, $errstr, 10);						
			if (class_exists('dprvErrors'))
			{
				restore_error_handler();
			}

			if ($fs !== false) 
			{                 
				if ($errno == 0)
				{
					fwrite($fs, $http_request);
					stream_set_timeout($fs, 50);
					$get_count = 0;
					$err_level = error_reporting();							// Save current error reporting level
					while ( !feof($fs) )
					{
						error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);	// Suppress Warning errors just for this due to intermittent bug (in IIS?)
						$temp = fgets($fs);
						error_reporting($err_level);						// Reset error reporting back to previous value
						$info = stream_get_meta_data($fs);
						if ($info['timed_out'])
						{
							$log->lwrite("timed out waiting for response");
							return "Error: connection to " . $http_host . " timed out";
						}
						else
						{
							$response .= $temp;
							$get_count = $get_count + 1;
						}
					}
					$log->lwrite("finished getting, about to close socket");
					fclose($fs);
					//TODO: check that response is complete (ends with </string>)
					$response = htmlspecialchars_decode($response, ENT_QUOTES);
					$log->lwrite("response length=" . strlen($response));
					if (strlen($response) == 0)
					{
						$log->lwrite("Empty response from server");
						return "Error: " . $http_host . " returned empty response, server may be offline";
					}
				}
				else
				{
					$log->lwrite("Socket may be open, but error = " . $errno . "/" . $errstr);
					return "Error: Problem while opening socket to " . $http_host . ".  Error = " . $errno . "/" . $errstr;
				}
			}
			else
			{
				$e_message = "Error: ";
				// if error is -1, indicates a PHP error rather than a communications error
				if ($errno == -1)
				{
					$e_message .= "Could not open socket to " . $http_host;
				}
				else
				{
					if ($errno ==0)
					{
						$e_message .= "Could not initialise socket to " . $http_host;
					}
					else
					{
						// Note error 110-Timeout (and possibly others) generate a PHP warning at the fsockopen instruction as well
						//      error 110-Timeout
						//      error 111-Connection Refused (e.g. web-server stopped)
						$e_message .=  "Communications error with " . $http_host . ": " . $errno . "/" . $errstr;
					}
				}
				if ($dprv_last_error != "")
				{
					$e_message .= " PHP error " . $dprv_last_error;
				}
				$log->lwrite($e_message);
				return $e_message;
			}

			// TODO: Trap HTTP errors such as 403 here (happens if you try to connect to live server w/o ssl)
			if (substr($response,0,4) == "HTTP")			// error starts like this: HTTP/1.1 404 Not Found
			{	
				$pos = strpos($response, " ");
				if (substr($response, $pos+1, 3) == "301" || substr($response, $pos+1, 3) == "302" || substr($response, $pos+1, 3) == "307")
				{
					return "Redirect " . substr($response, $pos+1, 3);
				}
				// TODO - return the actual message if one supplied
				if (substr($response, $pos+1, 3) != "200" && strpos(strtolower($response), "<title>digiprove service temporarily offline</title>") != false)
				{
					return "The Digiprove service is temporarily offline for maintenance, please try again in a few minutes";
				}
			}
			$log->lwrite("Digiprove_HTTP::post response:");
			$log->lwrite($response);
			return $response;
		}
		catch (Exception $e)
		{
			$log->lwrite("Exception : " . $e->getMessage());
			return 'Error: ' . $e->getMessage();
		}
	}
}
?>