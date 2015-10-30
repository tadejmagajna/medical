<?php
$resource = array_shift($paths);

if ($resource == 'clients') {
	$name = array_shift($paths);

	if (empty($name)) {
		$this->handle_base($method);
	} else {
		$this->handle_name($method, $name);
	}

} else {
	// We only handle resources under 'clients'
	header('HTTP/1.1 404 Not Found');
}