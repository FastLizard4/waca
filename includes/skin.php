<?php

/**************************************************************
** English Wikipedia Account Request Interface               **
** Wikipedia Account Request Graphic Design by               **
** Charles Melbye is licensed under a Creative               **
** Commons Attribution-Noncommercial-Share Alike             **
** 3.0 United States License. All other code                 **
** released under Public Domain by the ACC                   **
** Development Team.                                         **
**             Developers:                                   **
**  SQL ( http://en.wikipedia.org/User:SQL )                 **
**  Cobi ( http://en.wikipedia.org/User:Cobi )               **
** Cmelbye ( http://en.wikipedia.org/User:cmelbye )          **
**FastLizard4 ( http://en.wikipedia.org/User:FastLizard4 )   **
**Stwalkerster ( http://en.wikipedia.org/User:Stwalkerster ) **
**Soxred93 ( http://en.wikipedia.org/User:Soxred93)          **
**Alexfusco5 ( http://en.wikipedia.org/User:Alexfusco5)      **
**OverlordQ ( http://en.wikipedia.org/wiki/User:OverlordQ )  **
**Prodego    ( http://en.wikipedia.org/wiki/User:Prodego )   **
**Chris G ( http://en.wikipedia.org/wiki/User:Chris_G )      **
**                                                           **
**************************************************************/

if ($ACC != "1") {
	header("Location: $tsurl/");
	die();
} //Re-route, if you're a web client.

class skin {
	public function displayheader() {
		global $tsSQL;
		$result = $tsSQL->query("SELECT * FROM acc_emails WHERE mail_id = '8';");
		if (!$result) {
			// TODO: Nice error message
			die("ERROR: No result returned.");
		}
		$row = mysql_fetch_assoc($result);
		echo $row['mail_text'];
	}
	
	public function displayfooter() {
		global $tsSQL;
		$result = $tsSQL->query("SELECT * FROM acc_emails WHERE mail_id = '22';");
		if (!$result) {
			// TODO: Nice error message
			die("ERROR: No result returned.");
		}
		$row = mysql_fetch_assoc($result);
		echo $row['mail_text'];
	}
}

?>
