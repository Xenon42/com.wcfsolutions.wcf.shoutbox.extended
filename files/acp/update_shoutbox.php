<?php
/**
 * @midified by 	Thomas Wegner
 * @copyright		2009-2011 WCF Solutions <http://www.wcfsolutions.com/index.html>
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */

$packageID = $this->installation->getPackageID ();

// user and mod options
$sql = "UPDATE 	wcf" . WCF_N . "_cronjobs
	SET	classPath = 'lib/system/cronjob/EmptyShoutboxCronjob.class.php'
	WHERE	classPath REGEXP '.*EmptyShoutboxCronjob.class.php'";
WCF::getDB ()->sendQuery ( $sql );
//guest options
$sql = "TRUNCATE wcf" . WCF_N. "_shoutbox_entry";
WCF::getDB ()->sendQuery ( $sql );
?>
