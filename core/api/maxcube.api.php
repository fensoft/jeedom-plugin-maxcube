<?php
/* Copyright (C) fensoft <fensoft@gmail.com>
 * You can modify this file but you'll need to send to the author the modified files for future merging (or not, depending on the quality of your modifications)
 * You need to ask the permision of the author for redistribution/copying/forking
 */
 
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

global $jsonrpc;
if (!is_object($jsonrpc)) {
   throw new Exception(__('JSONRPC object not defined', __FILE__), -32699);
}
$params = $jsonrpc->getParams();

throw new Exception(__('Aucune methode correspondante pour le plugin maxcube : ' . $jsonrpc->getMethod(), __FILE__));
?>
