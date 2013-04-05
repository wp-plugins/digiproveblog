<?php
	// FUNCTIONS CALLED WHEN CHECKING INTEGRITY OF POSTS OR PAGES
	function dprv_verifyContentFiles(&$error_message, $dprv_post_id, $content_file_table, &$match_results)
	{
		global $wpdb;
		$log = new DPLog();  
		$log->lwrite("verifyContentFiles begins");
		//$log->lwrite(dprv_eval($content_file_table));

		$success=true;				// default value 

		$sql="SELECT * FROM " . get_option('dprv_prefix') . "dprv_post_content_files WHERE post_id = $dprv_post_id";
		$wpdb->show_errors();
		$dprv_post_files = $wpdb->get_results($sql, ARRAY_A);
		$match_results = array();
		if (!is_null($dprv_post_files))
		{
			for ($i=0; $i<count($dprv_post_files); $i++)
			{
				$file = $dprv_post_files[$i];
				$filename=$file['filename'];
				$fingerprint = $file['digital_fingerprint'];
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
					$match_results[$filename] = __("File no longer there", "dprv_cp");
					$success=false;
				}

			}
		}
		foreach ($content_file_table as $filename => $fingerprint)
		{
			if ($fingerprint != "Processed")
			{
				$match_results[$filename] = __("File $filename ($fingerprint) was not there before", "dprv_cp");
				$success=false;
			}
		}
 		return $success;
	}
?>