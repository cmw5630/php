<?php
	function encodingReplace($data) {
		$data = str_replace('  ', '<br />', $data);
		$data = str_replace('   ', '<br /> <br />', $data);
		return $data;
	}


?>