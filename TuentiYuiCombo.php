<?php
/**
 * This file wraps the functionality built-in the combo service to adjust it to
 * our specific needs
 *
 * LICENSE: This file can only be stored on servers belonging to Tuenti Technologies S.L.
 *
 * @copyright 2011, (c) Tuenti Technologies S.L.
 * @author Guillermo Pérez <bisho@tuenti.com>
 */

class TuentiYuiCombo {
	const REQUIRE_VERSIONS_FILE = '.require-versions';

	private $scripts = array();
	private $yuiPath;
	private $enableCache = FALSE;
	private $requireVersions = FALSE;

	/**
	 * Generates a query string for the given set of scripts
	 * 
	 * @param array $scripts
	 */
	public static function generateQueryString(array $scripts) {
		return implode('&', $scripts);
	}
	
	/**
	 * Parses a query string as generated by generateQueryString()
	 * 
	 * @param string $queryString
	 * @param string $yuiPath Yui path
	 * @return self 
	 */
	public static function parse($queryString, $yuiPath) {
		// Get a cleanarray free from empty strings caused by URL rewrite
		$scripts = array_filter(explode('&', $queryString));
		return new self($scripts, $yuiPath);
	}

	/**
	 * @param array $scripts
	 * @param string $yuiPath 
	 */
	public function __construct(array $scripts, $yuiPath) {
		$this->yuiPath = $yuiPath;
		$this->addScripts($scripts);
	}

	/**
	 * Add (and validate) the given list of scripts
	 * 
	 * @param array $scripts 
	 * @author Nuria Ruiz <nruiz@tuenti.com>
	 */
	public function addScripts(array $scripts) {
		$this->requireVersions = file_exists($this->yuiPath . self::REQUIRE_VERSIONS_FILE);
		
		foreach ($scripts as $script) {
			$name = basename($script);
			$dir = dirname($script);
			
			// Dir should only contain given chars
			if (! preg_match('/^[a-z0-9-][a-z0-9-\/]*$/', $dir)) { continue; }
			
			// Maximum 2 . one for extension and one for version
			$dotCount = substr_count($name, '.');
			if ($dotCount > 2) { continue; }

			// Check if file exists:
			$file = $this->yuiPath . $dir . '/'. $name;			
			if (!file_exists($file)) {
			    // mm.. something is happening here, 
			    // the combo needs to find all files you are asking for, either the filename is wrong 
			    // or there is some issue with the versioning
			    // for sure this is a faulty request, should not be cached by the http cache so
			    // do not send back a successfull http code
			    // sending a 400 but continuing retrieving files
			    header("Status: 400 Bad Request");
			    
			    continue; 
			}
			
			// Check if it's using versions, to enable cache headers
			if ($dotCount == 2) { $this->enableCache = TRUE; }
			elseif ($this->requireVersions) { continue; }

			$this->scripts[] = $file;
		}
	}

	/**
	 * Render the combo
	 * 
	 * @return void
	 */
	public function render() {
		if ($this->enableCache) {
			// Cache control ~ 10 years
			header('Cache-Control: max-age=315360000');
			header('Expires: ' . gmdate('D, j M Y H:i:s', time() + 315360000) .' GMT');
		}
		
		header('Content-Type: application/x-javascript');

		foreach ($this->scripts as $script) {
			readfile($script);
		}
	}
}