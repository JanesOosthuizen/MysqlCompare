<?php
/**
 * Copyright (c) Janes Oosthuizen (hello@janes.co.za)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     (c) Janes Oosthuizen (hello@janes.co.za)
 * @since         1.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

include("mysql_compare.php");

$comp = new DatabaseCompare();
$comp->SelectDatabaseOne("database_name_1");
$comp->SelectDatabaseTwo("database_name_2");
$comp->ConnectDatabaseOne("localhost","user","password");
$comp->ConnectDatabaseTwo("localhost","user","password");
$comp->displayMatches = false; 
$comp->DoComparison();