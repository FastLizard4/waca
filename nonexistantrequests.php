<?php
// taken, or created requests which are not existant

// for taken,
// log_action == "Closed 3"
// for created,
// log_action == "Closed 1"

/*

SELECT acc_pend.pend_id, acc_pend.pend_name, acc_log.log_action
FROM acc_pend
INNER JOIN acc_log ON acc_pend.pend_id = acc_log.log_pend
WHERE acc_pend.pend_status = "Closed"
AND ( acc_log.log_action = "Closed 1"
      OR acc_log.log_action = "Closed 3" )
;


api.php?action=query&list=users&ususers=Stwalkerster|Stwalkersock2&usprop=groups|editcount&format=php

*/

// check to see if the database is unavailable
require_once('config.inc.php');
require_once('functions.php');

readOnlyMessage();
ifWikiDbDisabledDie();

die('not implemented yet (requests marked "done" or "taken" on the tool, which actually don\'t exist on enwiki)');
?>