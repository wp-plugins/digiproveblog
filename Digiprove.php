<?php
define("DPRV_SDK_VERSION", '0.98');
define("DPRV_HOST", "api.digiprove.com");                // you may use digiprove1.dyndns.ws for testing
define("DPRV_VERIFY_HOST", "verify.digiprove.com");                // you may use digiprove1.dyndns.ws for testing
define("DPRV_SSL", "No");
define("DPRV_Log", "No");                                // Set this to "Yes" to generate local log-file for debug purposes (needs write permissions)

if (intval(substr(PHP_VERSION,0,1)) > 4)
{
	include_once('Digiprove_http_functions.php');        // Functions for HTTP
}
else
{
	include_once('Digiprove_http_functions_basic.php');  // Functions for HTTP
}

class Digiprove
{
	// This function Digiproves a piece of content
	// $error_message               a string - indirect reference - will contain error message if something went wrong
	// $credentials                 an object containing EITHER "user_id" and "password" properties (with optional "domain_name"),
	//													 OR "user_id", "domain_name", and "api_key" properties (recommended).  "alt_domain_name" property is optional. 
	//                              indirect reference - on successful return, will also contain up to date "subscription_type" and "subscription_expiry_date" properties (also included in the return array);
	//                              if "api_key" was not supplied but "domain_name" was, a new api key will be generated on the server and will be returned as a property of credentials and in the return array
	// $content                     Optional (but either this or $content_files must be supplied) - content to be digiproved; can be a string, array, or object
	// $digiproved_content          indirect reference - if $content was not a string, it will be serialized into a string and that is what will be Digiproved; $digiproved_content contains this string value
	//                              Note it is recommended that this is saved for future reference along with $content itself
	// $content_files               An array of files (full path names) to be Digiproved as part of this transaction
	// $content_type                Optional = a string describing the content, e.g. "Medical Record"
	// $metadata					Optional = an array containing metadata elements.  For now, supports finite values:
	//                                                                                 ['content_title'] = string
	//                                                                                 ['abstract'] = string describing the content
	//                                                                                 ['authors'] = string naming author or authors
	// $document_tracking			Optional = an array with up to 3 elements:
	//                                                             ['original_document_id'] (mandatory)
	//                                                             ['document_title'] (optional)
	//                                                             ['version'] (optional)
	// $user_agent                  Optional = a string describing your software and its version e.g. "Bank Software 1.1" to aid in debugging etc.
	// $content_url                 Optional = a string with the url of this content if it is published on the internet
    // $linkback                    Optional = boolean value, only matters if $content_url is supplied, indicates whether the certifying page should have a link back to the url, default is no link (false)
	// $obscure_url                 Optional = boolean value indicating whether to obscure the link to the certifying page with a guid; default is false
	// $return_dp_cert				Optional = boolean value indicating whether to return the digitally-signed Digiprove certificate (not available to Basic subscribers) default is false 
	// $email_confirmation			Optional = boolean value indicating whether to email a confirmation (including the digitally-signed Digiprove certificate as an attachment) default is false.
	// $save_content                Optional = boolean value indicating whether to save the content at digiprove.com (NOTE only the content variable, not the content in content_files); default is false
	//
	// Returns an array:
	// ['result_code']				Will be 0 if everything worked OK
	// ['result']					Will be "Success" if everything worked OK, otherwise contains an error message
	// ['certificate_id']			Will be present if everything worked ok: id of Digiprove certificate
	// ['digital_fingerprint']		Will be present if everything worked ok: digital fingerprint of content
	// ['utc_date_and_time']		Will be present if everything worked ok: timestamp of certificate
	// ['certificate_url']			Will be present if everything worked ok: a url where the certificate details can be viewed in a browser
	// ['subscription_type']		Will be present if everything worked ok: the up-to-date subscription type of the user (to enable you to synch your local record of this)
	// ['subscription_expiry']		Will be present if everything worked ok: the up-to-date subscription expiry date (to enable you to synch your local record of this)
	// ['api_key']					Will be present if everything worked ok and there was no api key supplied but a domain: a newly created api key for this domain
	// ['certificate_file']			Will be present if everything worked ok and $return_dp_cert parameter was set to true: BASE64-encoded certificate file
	// ['certificate_filename']		Will be present if everything worked ok and $return_dp_cert parameter was set to true: suggested name for certificate file (includes the p7s extension)
	// ['content_files']			Will be present if everything worked ok and �content_files array was included as input parameter: an array where keys = filenames and values = digital fingerprints

	static public function certify(&$error_message, &$credentials, $content, &$digiproved_content, $content_files = null, $content_type="", $metadata = null, $document_tracking = null, $user_agent = "", $content_url = "", $linkback = false, $obscure_url = false, $return_dp_cert = false, $email_confirmation = false, $save_content = false)
	{
		$log = new DPLog();
		$log->lwrite("Digiprove::certify starts");
		$error_message = "";
		//if (!self::is_valid($credentials, &$error_message))
		if (!self::is_valid($credentials, $error_message))
		{
			return false;
		}
		if (!is_string($content))
		{
			$digiproved_content = serialize($content);
		}
		else
		{
			$content = stripslashes($content);
			$digiproved_content = $content;
		}
		$content = self::getRawContent($content, $digital_fingerprint);
		$log->lwrite("certify content = string of " . strlen($content) . " characters, fingerprint is " . $digital_fingerprint);

		if ($content == "" && ($content_files == null || !is_array($content_files) || count($content_files) == 0))
		{
			$error_message = "No content supplied";
			return false;
		}
		$XML_string = self::prepareCertifyXML($error_message, $credentials, $content, $digital_fingerprint, $content_files, $content_file_table, $content_type,  $metadata, $document_tracking, $user_agent, $content_url, $linkback, $obscure_url, $return_dp_cert, $email_confirmation);
		$log->lwrite("request: $XML_string");

		if ($XML_string === false)
		{
			// Error while creating the XML
			return false;
		}

		$data = Digiprove_HTTP::post($XML_string, DPRV_HOST, "/secure/service.asmx/", "DigiproveContent");
		$pos = strpos($data, "Error:");
		if ($pos !== false)
		{
			$log->lwrite("There was a problem in Digiprove_HTTP::post");
			$error_message = $data;
			return false;
		}
		$pos = stripos($data, "<result_code>0");
		if ($pos === false)
		{
			$error_message = self::getTag($data,"result");
			if ($error_message == false)
			{
				$error_message = $data;
			}
			return false;
		}
		$pos = strpos($data, "<?xml ");
		$pos2 = strpos($data, "<digiprove_certify_response>", $pos); 
		$pos3 = strpos($data, "</digiprove_certify_response>", $pos2+28);
		$return = self::parseResponse(substr($data,$pos2+28,$pos3-$pos2-28));
		if ($content_files != null && count($content_files) > 0)
		{
			$return['content_files'] = $content_file_table;
		}
		if (!isset($return['digital_fingerprint']))
		{
			$return['digital_fingerprint'] = $digital_fingerprint;
		}
		if (isset($return['api_key']) && $return['api_key'] != "")
		{
			$credentials['api_key'] = $return['api_key'];
		}
		if (isset($return['subscription_type']) && $return['subscription_type'] != "")
		{
			$credentials['subscription_type'] = $return['subscription_type'];
			if (isset($return['subscription_expiry']) && $return['subscription_expiry'] != "")
			{
				$credentials['subscription_expiry'] = $return['subscription_expiry'];
			}
			else
			{
				$credentials['subscription_expiry'] = null;
			}
		}
		$log->lwrite("finishing Digiprove::certify");
		return $return;
	}

	static private function is_valid($credentials, &$error_message)
	{
		if (!isset($credentials['user_id']) || $credentials['user_id'] == "")
		{
			$error_message = "User id not supplied";
			return false;
		}
		if (!isset($credentials['password']) || $credentials['password'] == "")
		{
			if (!isset($credentials['api_key']) || $credentials['api_key'] == "")
			{
				$error_message = "Api key not supplied";
				return false;
			}
			if (!isset($credentials['domain_name']) || $credentials['domain_name'] == "")
			{
				$error_message = "Domain name not supplied";
				return false;
			}
		}
		return true;
	}
	static private function prepareCertifyXML(&$error_message, $credentials,$content, $digital_fingerprint, $content_files = null, &$content_file_table = null, $content_type="", $metadata = null, $document_tracking = null, $user_agent = "", $content_url = "", $linkback = false,  $obscure_url = false,  $return_dp_cert = false, $email_confirmation = false, $save_content = false)
	{
		$log = new DPLog();  
		$log->lwrite("prepareXML starts");
		// TODO: get rid of the clunky twin arrays and just use the $content_file_table which has key=filename and value=digital fingerprint
		//$content_file_names = array();
		//$content_file_fingerprints = array();
		$content_file_table = array();
		if (function_exists("hash") && $content_files !== null)
		{
			//self::parseContentFiles($error_message, $content_files, $content_file_names, $content_file_fingerprints, $content_file_table);
			self::parseContentFiles($error_message, $content_files, $content_file_table);
		}
		
		$content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
		
		// Statement below inserted as vertical tabs not converted and cause a problem in XML .net server process
		// TODO: there are probably other characters that will trip it up - review whole XML-encoding to create more systemic solution
		$content = str_replace("\v", " ", $content);			// vertical tab           11 0013 0x0b
		$content = str_replace(chr(1), '&#x1;', $content);	// soh - start of header   1 0001 0x01
		$content = str_replace(chr(22), ' ', $content);		// SYN - Synchronous Idle 22  026 0x16

		

		// following instruction inserted to prevent problems with unescaped character '&' causing server-side XML parsing error
		$content_type = trim(htmlspecialchars(stripslashes($content_type), ENT_QUOTES, 'UTF-8'));
		
		$postText = "<digiprove_certify_request>";
		$postText .= "<user_id>" . $credentials['user_id'] . "</user_id>";
		if (isset($credentials['domain_name']) && $credentials['domain_name'] != "")
		{
			$postText .= '<domain_name>' . $credentials['domain_name'] . '</domain_name>';
		}
		if (isset($credentials['alt_domain_name']) && $credentials['alt_domain_name'] != "")
		{
			$postText .= '<alt_domain_name>' . $credentials['alt_domain_name'] . '</alt_domain_name>';
		}

		if (isset($credentials['api_key']) && $credentials['api_key'] != "")
		{
			$postText .= '<api_key>' . $credentials['api_key'] . '</api_key>';
		}
		else
		{
			if (!isset($credentials['password']) || $credentials['password'] == "")
			{
				$error_message = "No api key or password";
				return false;
			}
			$dprv_password = htmlspecialchars(stripslashes($credentials['password']), ENT_QUOTES, 'UTF-8');	// Now encode the characters necessary for XML (Note this may not be necessary if using SOAP)
			$postText .= '<password>' . $dprv_password . '</password>';
			$postText .= '<request_api_key>Yes</request_api_key>';
		}
		if 	(isset($credentials['dprv_event']) && $credentials['dprv_event'] != "")
		{
			//$postText .= "<dprv_event>" . $credentials['dprv_event'] . "</dprv_event>";
			$postText .= "<dprv_event>" . htmlspecialchars($credentials['dprv_event']) . "</dprv_event>";
		}

		$postText .= '<user_agent>PHP ' . PHP_VERSION . ' / Digiprove SDK ' . DPRV_SDK_VERSION;
		if ($user_agent != "")
		{
			$postText .= ' / ' . $user_agent;
		}
		$postText .= '</user_agent>';

		if (is_array($metadata))
		{
			foreach ($metadata as $k=>$v)
			{
				// Prepare value for XML transmission
				if (intval(substr(PHP_VERSION,0,1)) > 4)	// Skip this step if before PHP 5 as PHP4 cannot cope with it - not the end of the world in this case
				{
					$v = html_entity_decode($v, ENT_QUOTES, 'UTF-8');   // first go back to basic string (have seen WLW-sourced titles with html-encoding embedded)
				}
				$v = htmlspecialchars(stripslashes($v), ENT_QUOTES, 'UTF-8');	// Now encode the characters necessary for XML (Note this may not be necessary if using SOAP)
				$postText .= "<$k>$v</$k>";
			}
		}
		//$postText .= '<content_title>' . $title . '</content_title>';

		//if (count($content_file_names) > 0)
		if (count($content_file_table) > 0)
		{
			$postText .= "<content_wrapper>";
		}
		$postText .= '<content_type>' . $content_type . '</content_type>';

		// if digital fingerprint could not be calculated (PHP4), or if requested, send content as well as fingerprint
		if ($digital_fingerprint == "" || $save_content == true)
		{
			$postText .= '<content_data>' . $content . '</content_data>';
		}
		else
		{
			$postText .= '<content_fingerprint>' . $digital_fingerprint . '</content_fingerprint>';
		}
		if ($content_url != "")
		{
			$postText .= '<content_url>' . $content_url . '</content_url>';
			$postText .= "<linkback>";
			if ($linkback == true)
			{
				$postText .= "Linkback";
			}
			else
			{
				$postText .= "Nolink";
			}
			$postText .= "</linkback>";

		}
		//if (count($content_file_names) > 0)
		if (count($content_file_table) > 0)
		{
			$postText .= "</content_wrapper>";
		}

		//for ($t = 0; $t <count($content_file_names); $t++)
		$t=0;
		foreach ($content_file_table as $f_name=>$f_fingerprint)
		{
			//$log->lwrite("doing xml for file " . $t . ": " .  $content_file_names[$t]);
			$log->lwrite("doing xml for file " . $t . ": " .  $f_name);
			$postText .= "<content_wrapper>";
			$postText .= '<content_type>File</content_type>';
			//$postText .= '<content_filename>' . $content_file_names[$t] . '</content_filename>';
			$postText .= '<content_filename>' . $f_name . '</content_filename>';
			//$postText .= '<content_fingerprint>' . $content_file_fingerprints[$t] . '</content_fingerprint>';
			$postText .= '<content_fingerprint>' . $f_fingerprint . '</content_fingerprint>';
			$postText .= "</content_wrapper>";
			$t++;
		}

		if (is_array($document_tracking))
		{
			foreach ($document_tracking as $k=>$v)
			{
				// Prepare value for XML transmission
				if (intval(substr(PHP_VERSION,0,1)) > 4)	// Skip this step if before PHP 5 as PHP4 cannot cope with it - not the end of the world in this case
				{
					$v = html_entity_decode($v, ENT_QUOTES, 'UTF-8');   // first go back to basic string (have seen WLW-sourced titles with html-encoding embedded)
				}
				$v = htmlspecialchars(stripslashes($v), ENT_QUOTES, 'UTF-8');	// Now encode the characters necessary for XML (Note this may not be necessary if using SOAP)
				$postText .= "<$k>$v</$k>";
			}
		}

		if ($obscure_url == false)
		{
			$postText .= '<obscure_certificate_url>No</obscure_certificate_url>';
		}
		else
		{
			$postText .= '<obscure_certificate_url>Yes</obscure_certificate_url>';
		}
		if ($return_dp_cert === true)
		{
			$postText .= '<return_dp_cert>Yes</return_dp_cert>';
		}
		if ($email_confirmation === true)
		{
			$postText .= '<email_confirmation>Yes</email_confirmation>';
		}
		else
		{
			$postText .= '<email_confirmation>No</email_confirmation>';
		}
		
		$postText .= '</digiprove_certify_request>';
		return $postText;
	}


	// This function verifies a previously Digiproved piece of content
	// $error_message               a string - indirect reference - will contain error message if something went wrong
	// $credentials                 Optional - an object containing either "user_id", "domain_name", and "api_key" properties (recommended) or "user_id" and "password" properties
	//								If null is supplied or an object that does not contain "user_id" property, will be treated as an anonymous verify request.
	//								NOTE: anonymous verify requests return only the essential results (as described below) and are less processor intensive
	//
	// $certificate_id				Optional: The original Digiprove certificate id
	//
	// Supply one or other of these 2 parameters:
	// $content                     content to be verified; can be a string, array, or object
	// $digiproved_content          A string containing the serialised value of content (will have been provided as a return value from original Digiprove transaction)
	//
	// $content_files               Optional - an array of files (full path names) referring to files which were Digiproved as part of this transaction
	// $user_agent                  Optional = a string describing your software and its version e.g. "Bank Software 1.1" to aid in debugging etc.
	//
	// Returns an array:
	// ["result_code"]				A string with values:
	//              				200 - Document is authentic and all requested checks were successful
	//								201 - Qualified Success (qualification described in result)
	//								202 - Document is authentic but out of date (latest version number in result)
	//								210 - Digiprove has no record of this document
	//								211 - Digiprove has no record of this instance of this document (but is aware of the original document id)
	//								220 - Possible Tamper Alert! The supplied certificate id is valid, but the content fingerprint does not match any of the Digiproved files/documents
    //                              101 - Credentials incomplete (see error message) 
    //                              102 - Raw content (i.e. after trimming) is empty 
	//								110 - Internal error
	//								111 - Error while attempting to contact server
	//								112 - Could not decipher server response
	//								120 - XML validation error (as described in <result> tag)
	//								130 - Other Error (as described in <result> tag)
	// ['result']					a string containing description of result (e.g. "Document is Authentic", "Digiprove has no record of this document")
	// ['notes']					a string (may be present) containing further information
	// ['instance_count']			an integer - only supplied if searching on fingerprint only (certificate id not supplied): the number of times this exact content has been Digiproved
	// ['content_fingerprint']		a string containing digital fingerprint of supplied content
	//
	// If not an anonymous request (a set of credentials was supplied), and the verification was successful, serially-named array(s) (beginning with document_0)
	// one for every Digiproved document that contains the specified content (and was issued to that user)
	// Note if certificate id was supplied there will not be more than one of these:
	// ['document_n']				An array containing document information:
	//								['certificate_id']				id of Digiprove certificate
	//								['digital_fingerprint']			digital fingerprint of primary content in the certificate (not necessarily the content that you submitted)
	//								['utc_date_and_time']			timestamp of certificate
	//								['certificate_url']				a url where the certificate details can be viewed in a browser (except where certificate is private)
	//								['published_url']				if present, is the internet location where the content has been published
	//								['original_document_id']		if present, is the id of the original document (of which this instance is a version)
	//								['version'"]					if present, is the version number/code of this instance of the document
	//								['title']						if present, is Document title
	//								['authors']						if present, is author or authors names
	//								["file_n"]						An array, one for each file referenecd in this Digiproved document, containing:
	//																["filename"]
	//																["digital_fingerprint"]

	// TODO: Return complete certificate detail (i.e. all associated files)
	function verify(&$error_message, $credentials, $certificate_id, $content, &$digiproved_content = null, $content_files = null, $user_agent = "")
	{
		$log = new DPLog();
		$log->lwrite("Digiprove::verify starts");
		$error_message = "";
		$return_table = array();
		//if (isset($credentials) && isset($credentials['user_id']) && !self::is_valid($credentials, &$error_message))
		if (isset($credentials) && isset($credentials['user_id']) && !self::is_valid($credentials, $error_message))
		{
            $return_table["result_code"] = "101";
            $return_table["result"] = "Credentials incomplete";
            return $return_table;
		}
		if (!is_string($content))
		{
			if ($digiproved_content == null)
			{
				$digiproved_content = serialize($content);
			}
		}
		else
		{
			$content = stripslashes($content);
			if ($digiproved_content == null)
			{
				$digiproved_content = $content;
			}
		}
		$content = self::getRawContent($content, $digital_fingerprint);
		$log->lwrite("verify content = string of " . strlen($content) . " characters, fingerprint is " . $digital_fingerprint);
		if ($content == "")
		{
			$error_message = "Raw content is empty";
            $return_table["result_code"] = "101";
            $return_table["result"] =  "Raw content is empty";
			return $return_table;
		}
		$XML_string = self::prepareVerifyXML($error_message, $credentials, $certificate_id, $content, $digital_fingerprint, $content_files, $content_type, $user_agent);
		$log->lwrite("request: $XML_string");

		if ($XML_string === false)
		{
			// Error while creating the XML
            $return_table["result_code"] = "110";
            $return_table["result"] =  "Failed to create XML";
			return $return_table;
		}

		$data = Digiprove_HTTP::post($XML_string, DPRV_VERIFY_HOST, "/secure/service.asmx/", "DigiproveVerify");
		$pos = strpos($data, "Error:");
		if ($pos !== false)
		{
			$log->lwrite("There was a problem in Digiprove_HTTP::post");
            $return_table["result_code"] = "111";
            $return_table["result"] =  "Error while attempting to contact server";
			$error_message = $data;
			return $return_table;
		}
		$pos = stripos($data, "<result_code>2");	// Return codes in the 200-299 range indicate verification process completed without error (although not necessarily verified)
		if ($pos === false)
		{
			$error_message = self::getTag($data,"result");
			if ($error_message == false)
			{
				$error_message = $data;
			}
			$return_table["result_code"] = "112";
            $return_table["result"] =  "Could not decipher server response";
			return $return_table;
		}
		$pos = strpos($data, "<?xml ");
		$pos2 = strpos($data, "<digiprove_verify_response>", $pos); 
		$pos3 = strpos($data, "</digiprove_verify_response>", $pos2+27);
		$return_table = self::parseResponse(substr($data,$pos2+27,$pos3-$pos2-27));
		if (!isset($return_table['content_fingerprint']))
		{
			$return_table['content_fingerprint'] = $digital_fingerprint;
		}
		$log->lwrite("finishing Digiprove::verify");
		return $return_table;
	}


	static private function prepareVerifyXML(&$error_message, $credentials, $certificate_id, $content, $digital_fingerprint, $content_files = null, $content_type="", $user_agent = "")
	{
		$log = new DPLog();  
		$log->lwrite("prepareVerifyXML starts");
		$content_file_table = array();
		if (function_exists("hash") && $content_files !== null)
		{
			self::parseContentFiles($error_message, $content_files, $content_file_table);
		}

		$content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
		
		// Statement below inserted at 0.75 as vertical tabs not converted and cause a problem in XML .net server process
		// TODO: there are probably other characters that will trip it up - review whole XML-encoding to create more systemic solution
		$content = str_replace("\v", " ", $content);			// vertical tab           11 0013 0x0b
		$content = str_replace(chr(1), '&#x1;', $content);	// soh - start of header   1 0001 0x01
		$content = str_replace(chr(22), ' ', $content);		// SYN - Synchronous Idle 22  026 0x16

		$postText = "<digiprove_verify_request>";

		if ($credentials != null && isset($credentials) && isset($credentials['user_id']) && $credentials['user_id'] != "")
		{
			$postText .= "<user_id>" . $credentials['user_id'] . "</user_id>";
			if (isset($credentials['domain_name']) && $credentials['domain_name'] != "")
			{
				$postText .= '<domain_name>' . $credentials['domain_name'] . '</domain_name>';
			}

			if (isset($credentials['api_key']) && $credentials['api_key'] != "")
			{
				$postText .= '<api_key>' . $credentials['api_key'] . '</api_key>';
			}
			else
			{
				if (!isset($credentials['password']) || $credentials['password'] == "")
				{
					$error_message = "No api key or password";
					return false;
				}
				$dprv_password = htmlspecialchars(stripslashes($credentials['password']), ENT_QUOTES, 'UTF-8');	// Now encode the characters necessary for XML (Note this may not be necessary if using SOAP)
				$postText .= '<password>' . $dprv_password . '</password>';
			}
		}
		$postText .= '<user_agent>PHP ' . PHP_VERSION . ' / Digiprove SDK ' . DPRV_SDK_VERSION;
		if ($user_agent != "")
		{
			$postText .= ' / ' . $user_agent;
		}
		$postText .= '</user_agent>';
		if ($certificate_id != null && $certificate_id != "")
		{
			$postText .= '<certificate_id>' . $certificate_id . '</certificate_id>';
		}

		//if (count($content_file_names) > 0)
		if (count($content_file_table) > 0)
		{
			$postText .= "<content_wrapper>";
		}

		// if digital fingerprint could not be calculated (PHP4), send content as well as fingerprint (undocumented feature)
		if ($digital_fingerprint == "")
		{
			$postText .= '<content_data>' . $content . '</content_data>';
		}
		else
		{
			$postText .= '<content_fingerprint>' . $digital_fingerprint . '</content_fingerprint>';
		}
		//if (count($content_file_names) > 0)
		if (count($content_file_table) > 0)
		{
			$postText .= "</content_wrapper>";
		}

		//for ($t = 0; $t <count($content_file_names); $t++)
		$t=0;
		foreach ($content_file_table as $f_name=>$f_fingerprint)
		{
			//$log->lwrite("doing xml for file " . $t . ": " .  $content_file_names[$t]);
			$log->lwrite("doing xml for file " . $t . ": " .  $f_name);
			$postText .= "<content_wrapper>";
			$postText .= '<content_type>File</content_type>';
			//$postText .= '<content_filename>' . $content_file_names[$t] . '</content_filename>';
			$postText .= '<content_filename>' . $f_name . '</content_filename>';
			//$postText .= '<content_fingerprint>' . $content_file_fingerprints[$t] . '</content_fingerprint>';
			$postText .= '<content_fingerprint>' . $f_fingerprint . '</content_fingerprint>';
			$postText .= "</content_wrapper>";
			$t++;
		}
		$postText .= '</digiprove_verify_request>';
		return $postText;
	}


	//function parseContentfiles(&$error_message, $content_files, &$content_file_names, &$content_file_fingerprints, &$content_file_table=null)
	static public function parseContentFiles(&$error_message, $content_files, &$content_file_table=null)
	{
		$log = new DPLog();
		$t = 0;
		if (!is_array($content_file_table))
		{
			$content_file_table = array();
		}
		//if (!is_array($content_file_names))
		//{
		//	$content_file_names = array();
		//}
		//if (!is_array($content_file_fingerprints))
		//{
		//	$content_file_fingerprints = array();
		//}

		foreach($content_files as $full_path)
		{
			$file_name = basename($full_path);
			//$ext = pathinfo($file_name, PATHINFO_EXTENSION);
			$file_data = @file_get_contents($full_path);
			if ($file_data != false)
			{
				$file_fingerprint = strtoupper(hash("sha256", $file_data));
				//if (array_search($file_name,$content_file_names) === false || array_search($file_fingerprint,$content_file_fingerprints) === false)  // prevent duplicate references
				if (array_key_exists($file_name,$content_file_table) === false || array_search($file_fingerprint, $content_file_table) === false)  // prevent duplicate references
				{
					$f = 1;
					$f_suffix = "";
					while (array_key_exists($file_name . $f_suffix,$content_file_table))
					{
						$f++;
						$f_suffix = "(" . $f . ")";
					}
					//$content_file_names[$t] = $file_name;
					//$content_file_fingerprints[$t] = $file_fingerprint;
					//$content_file_table[$file_name] = $file_fingerprint;
					$content_file_table[$file_name . $f_suffix] = $file_fingerprint;
					$log->lwrite("content_file_table[" . $file_name . $f_suffix . "]=" . $content_file_table[$file_name . $f_suffix]);
					$t++;
				}
				else
				{
					$log->lwrite("ignoring - " . $file_name . " - encountered earlier)");
				}
			}
			else
			{
				$error = error_get_last();
				if ($error !== null)
				{
					$error_message = "Error " . $error["type"] . " " . $error["message"] . " at line " . $error["line"] . " trying to read $full_path";
				}
				else
				{
					$error_message = "error trying to read $full_path";
				}
				break;
			}
		}
		if ($error_message != "")
		{
			return false;
		}
		return true;
	}

	static private function getTag($xmlString, $tagName)
	{
		$start_contents = stripos($xmlString, "<" . $tagName . ">") + strlen($tagName) + 2;
		$end_tag = stripos($xmlString, "</" . $tagName . ">");
		if ($start_contents === false || $end_tag === false || $end_tag <= $start_contents)
		{
			return false;
		}
		return substr($xmlString, $start_contents, $end_tag - $start_contents);
	}

	// Extract raw content to be Digiproved and calculate digital fingerprint
	static private function getRawContent($content, &$raw_content_hash)
	{
		$raw_content = trim($content);
		$raw_content = htmlspecialchars_decode($raw_content, ENT_QUOTES);  		// decode any encoded XML-incompatible characters now to ensure match with post-xml decoded string on server
		$raw_content_hash = "";

		if (function_exists("hash"))											// Before 5.1.2, the hash() function did not exist, calling it gives a fatal error
		{
			$raw_content_hash = strtoupper(hash("sha256", $raw_content));
		}
		return $raw_content;
	}

	static private function parseResponse($data)
	{
		return self::xml2array($data);
	}

	// This function works with XML strings supplied from Digiprove
	static private function xml2array($xml_string)
	{
		$return = array();
		$param = $xml_string;
		while ($xml_string != "")
		{
			$pos = strpos($xml_string, "<");
			if ($pos === false)
			{
				break;
			}
			$pos2 = strpos($xml_string, ">");
			if ($xml_string[$pos+1] =="/")
			{
				if ($pos2 !== false && strlen($xml_string) > ($pos2+1))
				{
					$xml_string = substr($xml_string, $pos2 + 1);
					continue;
				}
				else
				{
					break;
				}
			}
			$tag = trim(substr($xml_string, $pos+1, ($pos2-$pos)-1));
			$pos = strpos($xml_string, "</" . $tag . ">", $pos2+1);
			if ($pos === false)
			{
				break;
			}
			$return[$tag] = self::xml2array(substr($xml_string, $pos2+1, ($pos-$pos2)-1));
			if (strlen($xml_string) > ($pos + strlen($tag) + 3))
			{
				$xml_string = substr($xml_string, $pos + strlen($tag) + 3);
			}
			else
			{
				break;
			}
		}
		if (count($return)==0)
		{
			return $param;
		}
		return $return;
	}
}

class DPLog
{
	// write message to the log file  
	function lwrite($message)
	{  
		if (DPRV_Log == "Yes")
		{
			// if file pointer doesn't exist, then open log file  
			if (!isset($this->fp) || !$this->fp) $this->lopen();
			if (!$this->fp) return;														// if cannot open/create logfile, just return

			$script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
			$posDot = strrpos($script_name,'.');
			if ($posDot != false)
			{
				$script_name = substr($script_name, 0, $posDot);
			}

			$time = date('H:i:s');  
			// write current time, script name and message to the log file  
			@fwrite($this->fp, "$time ($script_name) $message\n") or $this->logwarning('(note - could not write to log-file)');
		}
	}  

	// open log file  
	function lopen()
	{  
		// define log file path and name  
		$lfile='';
		$current_dir = getcwd();
		$pos = strpos($current_dir, "wp-");
		if ($pos != false)
		{
			$lfile = substr($current_dir, 0, $pos);
		}
		$lfile .= 'digiprove_log' . date('Y-m-d') . '.log';

		// open log file for writing only; place the file pointer at the end of the file  
		// if the file does not exist, attempt to create it  
		$this->fp = @fopen($lfile, 'a') or  $this->logwarning('(note - could not open or create log-file ' . $lfile . ')');  
	} 

	function logwarning($dprv_warning)
	{
		// what to do?
	}  
}

?>