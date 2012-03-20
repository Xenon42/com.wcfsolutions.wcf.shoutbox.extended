<?php
// wcf imports
require_once (WCF_DIR . 'lib/data/shoutbox/ShoutboxEntryFactory.class.php');
require_once (WCF_DIR . 'lib/system/event/EventListener.class.php');

/**
 * Removes deleted users from banlist and blacklist
 *
 * @author Thomas Wegner
 * @copyright 2009-2011 WCF Solutions <http://www.wcfsolutions.com/index.html>
 * @license GNU Lesser General Public License
 *          <http://opensource.org/licenses/lgpl-license.php>
 * @package com.wcfsolutions.wcf.shoutbox.fork.i2c
 * @subpackage system.event.listener
 * @category Burning Board
 */
class UserDeleteActionShoutboxListener implements EventListener {
	/**
	 *
	 * @see EventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		if (MODULE_SHOUTBOX) {
			$sql = "DELETE FROM wcf" . WCF_N . "_shoutbox_banlist
				WHERE userID =" . $eventObj->userID;
			WCF::getDB ()->sendQuery ( $sql );
			$sql = "DELETE FROM wcf" . WCF_N . "_shoutbox_blacklist
		WHERE blackUserID =" . $eventObj->userID;
			WCF::getDB ()->sendQuery ( $sql );
		}
	
	}
}
?>
