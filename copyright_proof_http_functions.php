<?php

// THIS VERSION USES TRY/CATCH BLOCKS AND NEEDS PHP5 OR LATER

/* this function based on that from akismet.php by Matt Mullenweg.  */
function dprv_http_post($request, $host, $path, $service, $ip=null) 
{
	//global $dprv_port, $dprv_ssl;
	global $dprv_port;
	$log = new Logging();  
	$request = "xml_string=" . urlencode($request);
	$http_request  = "POST " . $path . $service . " HTTP/1.1\r\n";
	$http_request .= "Host: $host\r\n";
	$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
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
		//$http_host = akismet_get_host($host);  //TODO: implement this akismet resilience code
		$http_host = $host;
	}
	$log->lwrite("http_post of " . $http_request);

	$response = '';                 
	if (DPRV_SSL == "Yes")
	{
		$t = stream_get_transports();
		for ($i=0; $i<count($t); $i++)
		{
			if (stripos($t[$i], "ssl") !== false)
			{
				$http_host = "ssl://" . $http_host;
				break;
			}
		}
		if (strpos($http_host, "ssl://") === false)
		{
			$dprv_port = 80;
		} 
	}
	$log->lwrite("http_host " . $http_host);
	try																	// try/catch block not supported in php4
	{
		$errno = -1;
		$errstr = "Unknown";
		if( false != ( $fs = @fsockopen($http_host, $dprv_port, $errno, $errstr, 10) ) ) 
		{                 
			$log->lwrite("socket open, errno = " . $errno);
			if ($errno == 0)
			{
				fwrite($fs, $http_request);
				stream_set_timeout($fs, 50);
				$get_count = 0;
				while ( !feof($fs) )
				{
					error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);	// Suppress Warning errors just for this due to intermittent bug (in IIS?)
					$temp = fgets($fs);
					error_reporting(E_ALL & ~E_NOTICE);					// Reset error reporting back to default
					$info = stream_get_meta_data($fs);
					if ($info['timed_out'])
					{
						$log->lwrite("timed out waiting for response");
						return "Error: connection timed out, server may be offline";
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
				return "Error: Could not open socket to " . $http_host . ".  Error = " . $errno . "/" . $errstr;
			}
		}
		else
		{
			if ($errno ==0)
			{
				$log->lwrite("Could not initialise socket");
				return "Error: Could not initialise socket to " . $http_host . ", server may be offline";
			}
			$log->lwrite("Could not open socket, error = " . $errno . "/" . $errstr);
			return "Error: Could not open socket to " . $http_host . ", server may be offline.  Error = " . $errno . "/" . $errstr;
		}
		$log->lwrite("Got response ok: " . $response);
		// TODO: Trap HTTP errors such as 403 here (happens if you try to connect to live server w/o ssl)
		if (substr($response,0,4) == "HTTP")			// error starts like this: HTTP/1.1 404 Not Found
		{	
			$pos = strpos($response, " ");
			if (substr($response, $pos+1, 3) != "200" && strpos(strtolower($response), "<title>digiprove service temporarily offline</title>") != false)
			{
				return "The Digiprove service is temporarily offline for maintenance, please try again in a few minutes";
			}
		}
		return $response;
	}
	catch (Exception $e)
	{
		$log->lwrite("Exception : " . $e->getMessage());
		return 'Error: ' . $e->getMessage();
	}
}
?>