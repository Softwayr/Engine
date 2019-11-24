<?php
// AutoLoad classes when needed.
spl_autoload_register(function ($class_name) {
	// Convert the back slashes used for namespacing to the directory separator used by the system.
	$class_name = str_replace("\\", DIRECTORY_SEPARATOR, $class_name);
	// Check if requested class has a file with the same name.
	if(file_exists($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . $class_name . '.php'))
		// Load the file as this would most likely have the requested class.
		require_once $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . $class_name . '.php';
});
