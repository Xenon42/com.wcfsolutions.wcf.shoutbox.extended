<?php
// wcf imports
require_once (WCF_DIR . 'lib/data/shoutbox/ShoutboxEntryFactory.class.php');
require_once (WCF_DIR . 'lib/page/AbstractPage.class.php');
require_once(WBB_DIR.'lib/data/user/usersOnline/CompactUsersOnlineList.class.php');
/**
 * Outputs an XML document with a list of shoutbox bans.
 * 
 * @author		Thomas Wegner
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @package		com.wcfsolutions.wcf.shoutbox.fork.i2c
 * @subpackage	page
 * @category	Community Framework
 */
class ShoutboxUserOnlineXMLListPage extends AbstractPage {
	
	protected static $usersOnlineList = array();
	
	/**
	 * @see Page::readParameters()
	 */
	public function readParameters() {
		parent::readParameters ();
	}
	
	/**
	 * Returns a compact list of all online users
	 *
	 * @param	boolean	$returnData
	 * @return	array<array>
	 */
	public static function getUsersOnlineList($returnData = true) {
	
		if (empty(self::$usersOnlineList)) {
			$cuol = new CompactUsersOnlineList();
			self::$usersOnlineList = $cuol->getUsersOnline();
		}

		if ($returnData) return self::$usersOnlineList;
	}

	/**
	 * Verifies if a user is online and return formatted username if requested. This
	 * method will always return bool(false) if user is offline
	 *
	 * @param	integer	$userID
	 * @param	boolean	$returnFormattedUsername
	 * @return	mixed
	 */
	public static function getUserOnlineStatus($userID, $returnFormattedUsername = false) {
		if (empty(self::$usersOnlineList)) self::getUsersOnlineList(false);
		// check if user is listed as online
		if (array_key_exists(intval($userID), self::$usersOnlineList)) {
			if ($returnFormattedUsername) {
				return self::$usersOnlineList[intval($userID)];
			}
			return true;
		}
		return false;
	}
	
	/**
	 * @see Page::show()
	 */
	public function show() {
		parent::show ();
		if (WCF::getSession ()->lastRequestURI) {
			WCF::getSession ()->setRequestURI ( WCF::getSession ()->lastRequestURI );
		}
		self::getUsersOnlineList();
		
		header ( 'Content-type: text/xml; charset=' . CHARSET );
		echo "<?xml version=\"1.0\" encoding=\"" . CHARSET . "\"?>";
		echo "<onlinelist>";
		foreach ( self::$usersOnlineList as $value ) {
				echo "<username><![CDATA[" . StringUtil::escapeCDATA ( $value ) . "]]></username>";
		}
		echo "</onlinelist>";
	
		exit ();
	}
}
?>
