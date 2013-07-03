<?php
	// FUNCTIONS CALLED WHEN CHECKING INTEGRITY OF POSTS OR PAGES
	// BUT ONE BELOW NOT CALLED!!
	function dprv_verifyContentFiles(&$error_message, $dprv_post_id, $content_file_table, &$match_results)
	{
		global $wpdb;
		$log = new DPLog();  
		$log->lwrite("verifyContentFiles begins");
		//$log->lwrite(dprv_eval($content_file_table));

		$success=true;				// default value 

		$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_post_content_files WHERE post_id = %d";
		$dprv_post_files = dprv_wpdb("get_results", $sql, $dprv_post_id);
		$match_results = array();
		if (!is_null($dprv_post_files))
		{
			for ($i=0; $i<count($dprv_post_files); $i++)
			{
				$file = $dprv_post_files[$i];
				$filename=$file['filename'];
				$fingerprint = $file['digital_fingerprint'];
				$log->lwrite("i=$i, filename=$filename, fingerprint=$fingerprint");
				if (isset($content_file_table[$filename]))
				{
					if ($content_file_table[$filename] == $fingerprint)
					{
						$match_results[$filename] = __("Matched", "dprv_cp");
					}
					else
					{
						if ($content_file_table[$filename] == "Processed")
						{
							// Must be a duplicate filename, ignore
						}
						else
						{
							$match_results[$filename] = __("File contents changed", "dprv_cp");
							$success=false;
						}
					}
					$content_file_table[$filename] = "Processed";
				}
				else
				{
					$match_results[$filename] = __("No longer there", "dprv_cp");
					$success=false;
				}

			}
		}
		foreach ($content_file_table as $filename => $fingerprint)
		{
			if ($fingerprint != "Processed")
			{
				$match_results[$filename] = __("Was not there before", "dprv_cp");
				$success=false;
			}
		}
 		return $success;
	}
	function dprv_verify_callback()
	{
		global $dprv_blog_host;
		$log = new DPLog();  
		$log->lwrite("Ajax verify");
		$error_message = "";
		$certificate_id =  $_POST['certificate_id'];
		$digital_fingerprint =  $_POST['digital_fingerprint'];
		$content = null;
		$digiproved_content = null;
		$content_files = null;
		$user_agent = "Copyright Proof " . DPRV_VERSION;
		$credentials = array("user_id" => get_option('dprv_user_id'), "domain_name" => $dprv_blog_host, "api_key" => get_option('dprv_api_key'));
		$response = Digiprove::verify_fingerprint($error_message, $credentials, $certificate_id, $digital_fingerprint, $content, $digiproved_content, $content_files, $user_agent);
		echo "Response from Digiprove: " . $response["result"];
		die();
	}


?>