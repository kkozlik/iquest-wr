<?php

require dirname(__FILE__)."/../prepend.php";

unset($GLOBALS['page_attributes']['giveitup_url']);


$GLOBALS['page_attributes']['css_file'][] = $GLOBALS['config']->style_src_path."bootstrap-datetimepicker.min.css";
$GLOBALS['page_attributes']['required_javascript'][] = "bootstrap-datetimepicker.min.js";


?>