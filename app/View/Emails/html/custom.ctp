<?php

$order        = array("\\r\n", "\\n", "\\r");
$replace      = '<br />';
$formated_txt = str_replace($order, $replace, filter_var($this->viewVars['text'], FILTER_SANITIZE_STRING));

print $formated_txt;