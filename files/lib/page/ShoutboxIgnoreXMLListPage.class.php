<?php
// wcf imports
require_once (WCF_DIR . 'lib/data/shoutbox/ShoutboxEntryFactory.class.php');
require_once (WCF_DIR . 'lib/page/AbstractPage.class.php');
/**
 * Outputs an XML document with a list of ignored users.
 * 
 * @original author	Thomas Wegner
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @package		com.wcfsolutions.wcf.shoutbox.fork.i2c
 * @subpackage		page
 * @category		Community Framework
 */
class ShoutboxIgnoreXMLListPage extends AbstractPage
{
    /**
     * @see Page::readParameters()
     */
    public function readParameters ()
    {
        parent::readParameters();
        // check permission
        WCF::getUser()->checkPermission('user.shoutbox.canIgnoreUser');
    }
    /**
     * @see Page::readData()
     */
    /**
     * @see Page::show()
     */
    public function show ()
    {
        parent::show();
        if (WCF::getSession()->lastRequestURI) {
            WCF::getSession()->setRequestURI(WCF::getSession()->lastRequestURI);
        }
        $sql = "SELECT *  
							FROM wcf" . WCF_N . "_shoutbox_blacklist
							WHERE userID=" . WCF::getUser()->userID . "
							ORDER BY blackUserID ASC";
        $result = WCF::getDB()->getResultList($sql);
        header('Content-type: text/xml; charset=' . CHARSET);
        echo "<?xml version=\"1.0\" encoding=\"" . CHARSET . "\"?>";
        echo "<blacklist>";
        $list = array();
        foreach ($result as $key => $value) {
            $user = new User($value['blackUserID'], null, null, null);
            array_push($list, $user->username);
        }
        echo "<usernames>";
        echo "<list><![CDATA[" . implode("|", $list) . "]]></list>";
        echo "</usernames>";
        echo "</blacklist>";
        exit();
    }
}
?>
