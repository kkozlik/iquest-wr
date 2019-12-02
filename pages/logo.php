<?php
$_data_layer_required_methods=array();
$_phplib_page_open = array("sess" => "iquest_session");
$_required_modules = array('iquest');

require dirname(__FILE__)."/prepend.php";

$logo_file = Iquest_Options::get(Iquest_Options::LOGO);

if ($logo_file) $logo_file = $config->iquest_data_dir.$logo_file;

if (!$logo_file or !file_exists($logo_file)) $logo_file="img/logo.png";

$offset = 60 * 60 * 24;

header('Content-Description: File Transfer');
header('Content-Type: '.Iquest_file::get_mime_type($logo_file));
header('Content-Disposition: inline; filename='.basename($logo_file));
header('Content-Transfer-Encoding: binary');
header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");
header("Cache-Control: max-age=$offset, must-revalidate");
header('Pragma: public');
header('Content-Length: ' . filesize($logo_file));

readfile($logo_file);

