<?php

// THIS VERSION USES TRY/CATCH BLOCKS AND NEEDS PHP5 OR LATER

/*  This is not used at present - re-introduce later
function dprv_soap_post($request, $method) 
{
	global $dprv_host, $dprv_port, $dprv_ssl;
	$log = new Logging();
	try								// Does not work in PHP4 - use different approach
	{
		$log->lwrite("dprv_soap_post starts, method=" . $method);
		$dprv_WSDL_url = "";
		if ($dprv_ssl == "Yes")
		{
			$dprv_WSDL_url = "https://" . $dprv_host;
			if ($dprv_port != 443)
			{
				$dprv_WSDL_url .= ":" . $dprv_port;
			}
		}
		else
		{
			$dprv_WSDL_url = "http://" . $dprv_host;
			if ($dprv_port != 80)
			{
				$dprv_WSDL_url .= ":" . $dprv_port;
			}
		}
		$dprv_WSDL_url .= "/secure/Service.asmx?WSDL";
		//$client = new SoapClient("https://www.digiprove.com/secure/Service.asmx?WSDL",  
		$client = new SoapClient($dprv_WSDL_url,  
			array(	'soap_version' => SOAP_1_2,
					'trace' => true,
					'encoding'=>'utf-8'));
		$dprv_result = $client->$method(array('xml_string' => $request));
		$result_vars    = get_object_vars($dprv_result);
		return $result_vars[$method . "Result"];
	}
	catch(SoapFault $ex)
	{
		$dprv_error = $ex->getMessage();
		$log->lwrite("Soap error in dprv_soap_post: " . $dprv_error);
		$return_value = $dprv_error;
		$pos = strpos($dprv_error, "\n");
		if ($pos != false && $pos > 0)
		{
			$return_value = substr($dprv_error, 0, $pos);
		}
		return "Error: " . $return_value;
	}
}

*/

/* this function based on that from akismet.php by Matt Mullenweg.  */
function dprv_http_post($request, $host, $path, $service, $ip=null) 
{
	global $dprv_port, $dprv_ssl;
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
	if ($dprv_ssl == "Yes")
	{
		$http_host = "ssl://" . $http_host;
	}
	
	try																	// try/catch block not supported in php4
	{
		if( false != ( $fs = @fsockopen($http_host, $dprv_port, $errno, $errstr, 10) ) ) 
		{                 
			$log->lwrite("socket open, errno = " . $errno);
			if ($errno == 0)
			{
				fwrite($fs, $http_request);
				//$log->lwrite("fwrite done, now get response when it comes");
				stream_set_timeout($fs, 40);
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
						//$log->lwrite("got this: " . $temp);
						$response .= $temp;
						//$log->lwrite("get " . $get_count . " done, response length = " . strlen($response));
						$get_count = $get_count + 1;
					}
				}
				$log->lwrite("finished getting, about to close socket");
				fclose($fs);
				//TODO: check that response is complete (ends with </string>)
				$response = htmlspecialchars_decode($response, ENT_QUOTES);
				//$log->lwrite("response=$response, length=" . strlen($response));
				$log->lwrite("response length=" . strlen($response));
				if (strlen($response) == 0)
				{
					$log->lwrite("Empty response from server");
					return "Error: " . $http_host . " returned empty response, server may be offline";
				}
			}
			else
			{
				$log->lwrite("Socket may be open, but error code = " . $errno);
				return "Error: Could not open socket to " . $http_host . ".  Error code = " . $errno;
			}
		}
		else
		{
			if ($errno ==0)
			{
				$log->lwrite("Could not initialise socket");
				return "Error: Could not initialise socket to " . $http_host . ", server may be offline";
			}
			$log->lwrite("Could not open socket, error = " . $errno);
			return "Error: Could not open socket to " . $http_host . ", server may be offline.  Error code = " . $errno;
		}
		$log->lwrite("Got response ok: " . $response);
		return $response;
	}
	catch (Exception $e)
	{
		$log->lwrite("Exception : " . $e->getMessage());
		return 'Error: ' . $e->getMessage();
	}
}
?>