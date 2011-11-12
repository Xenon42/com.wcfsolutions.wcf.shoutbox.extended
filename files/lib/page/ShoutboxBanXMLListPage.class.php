<?php
// wcf imports
require_once (WCF_DIR . 'lib/data/shoutbox/ShoutboxEntryFactory.class.php');
require_once (WCF_DIR . 'lib/page/AbstractPage.class.php');
/**
 * Outputs an XML document with a list of shoutbox bans.
 * 
 * @original author	Thomas Wegner
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @package		com.wcfsolutions.wcf.shoutbox.fork.i2c
 * @subpackage		page
 * @category		Community Framework
 */
class ShoutboxBanXMLListPage extends AbstractPage {
	/**
	 * @see Page::readParameters()
	 */
	public function readParameters() {
		parent::readParameters ();
		// check permission
		WCF::getUser ()->checkPermission ( 'mod.shoutbox.canBanUser' );
	}
	/**
	 * @see Page::readData()
	 */
	
	/**
	 * @see Page::show()
	 */
	public function show() {
		parent::show ();
		if (WCF::getSession ()->lastRequestURI) {
			WCF::getSession ()->setRequestURI ( WCF::getSession ()->lastRequestURI );
		}
		$sql = "SELECT *  
			FROM wcf" . WCF_N . "_shoutbox_banlist
			WHERE userID<>0 
			ORDER BY until ASC";
		$result = WCF::getDB ()->getResultList ( $sql );
		header ( 'Content-type: text/xml; charset=' . CHARSET );
		echo "<?xml version=\"1.0\" encoding=\"" . CHARSET . "\"?>";
		echo "<banlist>";
		foreach ( $result as $key => $value ) {
			if ($value ['until'] > TIME_NOW) {
				$user = new User ( $value ['userID'], null, null, null );
				echo "<ban>";
				echo "<username><![CDATA[" . StringUtil::escapeCDATA ( $user->username ) . "]]></username>";
				echo "<until>" . $value ['until'] . "</until>";
				echo "</ban>";
			}
		}
		echo "</banlist>";
		exit ();
	}
}
?>
