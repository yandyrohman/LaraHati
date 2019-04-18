<?php

class UserConfig {

protected static $config = [

	# Your app info
	'app' => [
		
		'name' => 'Your Amazing Project',
		'owner' => 'Someone'

	],

	# MySql database connection (multiple)
	'connection' => [

		'default_connection' => 'connection_1',

		[


			# =================================
			# First Connection
			'connection_1' => [

				'database_host' => 'localhost',
				'database_user' => 'root',
				'database_pass' => '',
				'database_name' => 'tokobuku',

			],
			# =================================


			# =================================
			# Second Connection
			'connection_2' => [

				'database_host' => '',
				'database_user' => '',
				'database_pass' => '',
				'database_name' => '',
				
			],
			# =================================


		]

	]

];

}

?>