<?php

	// redirect
	function redirect($route) {

		// for localhost
		if ($_SERVER['HTTP_HOST'] == 'localhost') {
			$host = $_SERVER['REQUEST_URI'];
			$host = explode('/', $host);
			$host = '/'.$host[1];
		} else {
			$host = '';
		}

		return header('Location:'.$host.$route);
	}

	// view
	function view($page, $variable=false) {

		// get file from deep folder configuration
		$page = preg_replace('/\./', '/', $page);

		// extract variable if exist (array)
		if ($variable) {
			extract($variable);
		}
		
		// get ultra template
		$ultraFile = file_get_contents("../view/$page.ultra.php");

		// ULTRA FILE CONFIGURATION (static)
		$ultraConfigStc = [
			'\{\{' => '<?php echo ',
			'\}\}' => ' ?>',
			'@else' => '<?php } else { ?>',
			'@endif' => '<?php } ?>',
			'@endforeach' => '<?php } ?>'
		];
		foreach ($ultraConfigStc as $def => $changer) {
			$ultraFile = preg_replace('/'.$def.'/', "$changer", $ultraFile);
		}

		// ULTRA FILE CONFIGURATION (dynamic)

		// @foreach(..)
		$ultraConfigDyc['foreach'] = '@foreach[\s]*\([a-z0-9$=>"\'\s]*\)';
		if (preg_match('/'.$ultraConfigDyc['foreach'].'/', $ultraFile)) {
			preg_match_all(
				'/'.$ultraConfigDyc['foreach'].'/',
				$ultraFile,
				$foreach_matches
			);
			$ultraConfigDyc['foreachValue'] = preg_replace(
				'/@foreach[\s]*/',
				'',
				$foreach_matches[0][0]
			);
			$ultraFile = preg_replace(
				'/'.$ultraConfigDyc['foreach'].'/',
				'<?php foreach'.$ultraConfigDyc['foreachValue'].' { ?>',
				$ultraFile	
			);
		}

		// create signature from ultra file (programmer wrote)
		$timeStamp = $_SERVER['REQUEST_TIME'];
		$signature = md5_file("../view/$page.ultra.php").$timeStamp;

		// write temporay file with real php function
		$temp = fopen("temp/$signature.php", 'w');
		fwrite($temp, $ultraFile);
		fclose($temp);

		// get real php file and run on temporary
		ob_start();
		include "temp/$signature.php";
		$ultraString = ob_get_contents();
		ob_end_clean();

		// delete temporary file
		unlink("temp/$signature.php");

		// check cache file existable
		$ultraSign = md5($ultraString);
		$ultraCache = '';
		if (!file_exists("cache/$ultraSign.ultra")) {

			// write cache file
			$cache = fopen("cache/$ultraSign.ultra", 'w');
			fwrite($cache, $ultraString);
			fclose($cache);

			// get cache file
			$ultraCache = file_get_contents("cache/$ultraSign.ultra");

		} else {

			// get cache file
			$ultraCache = file_get_contents("cache/$ultraSign.ultra");
		}

		// view cache on client
		echo $ultraCache;

	}

	// route on html and javascript
	function route($value) {
		
		// for localhost
		if ($_SERVER['HTTP_HOST'] == 'localhost') {
			$host = $_SERVER['REQUEST_URI'];
			$host = explode('/', $host);
			$host = '/'.$host[1];
		} else {
			$host = '';
		}

		return $host.$value;
	}

	// error
	function error($type) {
		switch ($type) {
			case '403':
				include 'error/403.php';
				break;
			case '404':
				include 'error/404.php';
				break;
			case '500':
				include 'error/500.php';
				break;
			
			default:
				include 'error/200.php';
				break;
		}
	}

?>