<?php
/*            ___      GOTMLS Anti-Malware Core File Recovery
 *           /  /\     @package GOTMLS
 *          /  /:/     Version: 4.15.27
 *         /__/::\
 Copyright \__\/\:\__  © 2015 Eli Scheetz (email: eli@gotmls.net)
 *            \  \:\/\
 *             \__\::/ This program is free software; you can redistribute it
 *     ___     /__/:/ and/or modify it under the terms of the GNU General Public
 *    /__/\   _\__\/ License as published by the Free Software Foundation;
 *    \  \:\ /  /\  either version 2 of the License, or (at your option) any
 *  ___\  \:\  /:/ later version.
 * /  /\\  \:\/:/
  /  /:/ \  \::/ This program is distributed in the hope that it will be useful,
 /  /:/_  \__\/ but WITHOUT ANY WARRANTY; without even the implied warranty
/__/:/ /\__    of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
\  \:\/:/ /\  See the GNU General Public License for more details.
 \  \::/ /:/
  \  \:\/:/ You should have received a copy of the GNU General Public License
 * \  \::/ with this program; if not, write to the Free Software Foundation,    
 *  \__\/ Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA        */

function GOTMLSRecovery_define($DEF, $val) {
	if (!defined($DEF))
		define($DEF, $val);
}
GOTMLSRecovery_define("GOTMLSRecovery_Version", "4.15.27");
if (isset($_SERVER['HTTP_HOST']))
	$SERVER_HTTP = 'HOST://'.$_SERVER['HTTP_HOST'];
elseif (isset($_SERVER['SERVER_NAME']))
	$SERVER_HTTP = 'NAME://'.$_SERVER['SERVER_NAME'];
else
	$SERVER_HTTP = 'ADDR://'.$_SERVER['SERVER_ADDR'];
if (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"])
	$SERVER_HTTP .= ":".$_SERVER["SERVER_PORT"];
$SERVER_parts = explode(":", $SERVER_HTTP);
if ((isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on" || $_SERVER["HTTPS"] == 1)) || (count($SERVER_parts) > 2 && $SERVER_parts[2] == '443'))
	$GLOBALS["GOTMLS"]["tmp"]["protocol"] = "https:";
else
	$GLOBALS["GOTMLS"]["tmp"]["protocol"] = "http:";
GOTMLSRecovery_define("GOTMLSRecovery_siteurl", $GLOBALS["GOTMLS"]["tmp"]["protocol"].$SERVER_parts[1].((count($SERVER_parts) > 2 && ($SERVER_parts[2] == '80' || $SERVER_parts[2] == '443'))?"":":".$SERVER_parts[2]));//."/"
GOTMLSRecovery_define("GOTMLSRecovery_installation_key", md5(GOTMLSRecovery_siteurl));
GOTMLSRecovery_define("GOTMLSRecovery_update_home", "http://updates.gotmls.net/".GOTMLSRecovery_installation_key.'/');
function GOTMLSRecovery_decode($encoded_string) {
	$tail = 0;
	if (strlen($encoded_string) > 1 && is_numeric(substr($encoded_string, -1)) && substr($encoded_string, -1) > 0)
		$tail = substr($encoded_string, -1) - 1;
	else
		$encoded_string .= "$tail";
	$encoded_string = strtr(substr($encoded_string, 0, -1), "-_=", "+/0").str_repeat("=", $tail);
	if (function_exists("base64_decode"))
		return base64_decode($encoded_string);
	elseif (function_exists("mb_convert_encoding"))
		return mb_convert_encoding($encoded_string, "UTF-8", "BASE64");
	else
		return "Cannot decode: $encoded_string";
}
function GOTMLSRecovery_get_URL($URL) {
	$ReadFile = '';
	if (function_exists('curl_init')) {
		$curl_hndl = curl_init();
		curl_setopt($curl_hndl, CURLOPT_URL, $URL);
		curl_setopt($curl_hndl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl_hndl, CURLOPT_REFERER, GOTMLSRecovery_siteurl);
		if (isset($_SERVER['HTTP_USER_AGENT']))
			curl_setopt($curl_hndl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($curl_hndl, CURLOPT_HEADER, 0);
		curl_setopt($curl_hndl, CURLOPT_RETURNTRANSFER, TRUE);
		$ReadFile = curl_exec($curl_hndl);
		curl_close($curl_hndl);
	}
	if (strlen($ReadFile) == 0 && function_exists('file_get_contents'))
		$ReadFile = @file_get_contents($URL).'';
	return $ReadFile;
}
$root_path = dirname(__FILE__);
$readme_version = '';
$includes_version = '';
while (strlen($root_path) > 1 && (!$readme_version || !$includes_version)) {
	if (!$readme_version && is_file($root_path."/readme.html")) {
		$readme = file_get_contents($root_path."/readme.html");
		if (preg_match('/Version\s*([0-9\.]+)/', $readme, $match))
			$readme_version = $match[1];
	}
	if (!$includes_version && is_file($root_path."/wp-includes/version.php")) {
		$includes = file_get_contents($root_path."/wp-includes/version.php");
		if (preg_match('/\$wp_version\s*=\s*[\'"]([0-9\.]+)["\']/', $includes, $match))
			$includes_version = $match[1];
	}
	if (!$readme_version || !$includes_version)
		$root_path = dirname($root_path);
}
echo "<h2>Core Files Integrity Check</h2><h3>";
if ($readme_version && $includes_version && ($readme_version == $includes_version)) {
	echo "Found WordPress $readme_version (WP $includes_version) in $root_path</h3><li>Downloading Definitions for ".GOTMLSRecovery_siteurl." (".GOTMLSRecovery_installation_key.") ... ";
	$URL = GOTMLSRecovery_update_home.'definitions.php?ver='.GOTMLSRecovery_Version.'&wp='.$includes_version.'&ts='.date("YmdHis").'&d='.urlencode(GOTMLSRecovery_siteurl);
	if ($DEF = GOTMLSRecovery_get_URL($URL)) {
		if (is_array($GOTnew_definitions = unserialize(GOTMLSRecovery_decode($DEF)))) {
			if (isset($GOTnew_definitions["wp_core"]["$includes_version"])) {
				echo count($GOTnew_definitions["wp_core"]["$includes_version"])." Core files found :-)</li>";
			} else	die("Core files NOT found in ".print_r(array_keys($GOTnew_definitions))."</li>");
		} else	die("ERROR: Could NOT decode BLOB: $DEF</li>");
	} else	die("Could NOT download definitions from ".GOTMLSRecovery_update_home."</li>");
} else	die("Failed to find WordPress $readme_version (WP $includes_version) in $root_path</h3>");
echo "<h3>Checking ".count($GOTnew_definitions["wp_core"]["$includes_version"])." Core Files</h3>";
$link = "recheck=".date("YmdHis");
foreach ($GOTnew_definitions["wp_core"]["$includes_version"] as $file => $hash) {
	$ok = false;
	if (is_file("$root_path$file")) {
		if ($contents = file_get_contents("$root_path$file")) {
			if ($hash != md5($contents)."O".strlen($contents))
				echo "\n<li style=\"color: red;\">$file: Invalid checksum! ($hash != ".md5($contents)."O".strlen($contents).")</li>";
			else	$ok = true;
		} else	echo "\n<li style=\"color: orange;\">$file: EMPTY!</li>";
	} else	echo "\n<li style=\"color: grey;\">$file: MISSING!</li>";
	if (!$ok) {
		if (isset($_GET["fix"])) {
			$URL = "http://core.svn.wordpress.org/tags/$includes_version$file";
			if ($contents = GOTMLSRecovery_get_URL($URL)) {
				if ($hash == md5($contents)."O".strlen($contents)) {
					if (file_put_contents("$root_path$file", $contents)) {
						echo "\n<li style=\"color: green;\">$file: Core file RESTORED :-)</li>";
					} else	echo "\n<li style=\"color: red;\">$file: Core file READ-ONLY!</li>";
				} else	echo "\n<li style=\"color: orange;\">$file: Invalid checksum! ($hash != ".md5($contents)."O".strlen($contents).")</li>";
			} else	echo "\n<li style=\"color: grey;\">Could NOT download Core File from $URL</li>";
		} else	$link = "fix=now";
	}
}
echo "<h2>DONE!</h2><a href=\"?$link\">$link</a>";