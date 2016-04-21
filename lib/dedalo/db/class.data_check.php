<?php





class data_check {
	



	/**
	* CHECK_SEQUENCES
	* @return stdClass object $response
	*/
	public function check_sequences() {		
		
		$response = new stdClass();
			$response->result 	= true;
			$response->msg 	 	= '';


		$response->msg .= "TEST ALL SEQUENCES IN DATABASE: ".DEDALO_DATABASE_CONN;

		# Find and iterate all db tables
		$sql 	= " SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name ASC ";
		$result = JSON_RecordObj_matrix::search_free($sql);
		while ($rows = pg_fetch_assoc($result)) {
				
			$table_name = $rows['table_name'];		

			# Find last id in table
			$sql 	 = " SELECT id FROM $table_name ORDER BY id DESC LIMIT 1 ";
			$result2 = JSON_RecordObj_matrix::search_free($sql);
			if (pg_num_rows($result2) == 0) {
				continue;	// Skip empty tables
			}
			$last_id = pg_fetch_result($result2, 0, 'id');

			# Find vars in current sequence
			$sql = " SELECT last_value, start_value, increment_by FROM {$table_name}_id_seq ; ";
			$result3 = JSON_RecordObj_matrix::search_free($sql);

			$last_value 	= pg_fetch_result($result3, 0, 'last_value');
			$start_value 	= pg_fetch_result($result3, 0, 'start_value');
			$increment_by 	= pg_fetch_result($result3, 0, 'increment_by');


			$response->msg .= "<hr><b>$table_name</b> - start_value: $start_value - seq last_value: $last_value [ last id: $last_id]";

			if ($last_id>$last_value) {
				$response->msg .= "<br><pre>   WARNING: seq last_id > last_value [$last_id > $last_value]</pre>";
				$response->msg .= "<br>FIX AUTOMATIC TO $last_id start</pre>";
				#$response->msg .= "Use: <pre>SELECT setval('public.{$table_name}_id_seq', $last_id, true);</pre>";

				$sql2 	 = "SELECT setval('public.{$table_name}_id_seq', $last_id, true);";
				$result2 = JSON_RecordObj_matrix::search_free($sql2);
				if (!$result2) {
					$response->msg .= "Use: <pre>SELECT setval('public.{$table_name}_id_seq', $last_id, true);</pre>";
				}

				$response->result = false;
			}

			if ($start_value!=1) {
				$response->msg .= "<br><pre>   WARNING: seq start_value != 1</pre>";
				$response->msg .= "Use: <pre>ALTER SEQUENCE {$table_name}_id_seq START WITH 1 ;</pre>";

				$response->result = false;
			}

		}//end while ($rows = pg_fetch_assoc($result)) {


		return (object)$response;

	}#end check_sequences






}
?>