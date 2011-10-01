<?php
// wcf imports
require_once (WCF_DIR . 'lib/data/DatabaseObjectList.class.php');
require_once (WCF_DIR . 'lib/data/shoutbox/ViewableShoutboxEntry.class.php');

/**
 * Represents a list of shoutbox entries.
 * 
 * @midified by 	Thomas Wegner
 * @original author	Sebastian Oettl
 * @copyright		2009-2011 WCF Solutions <http://www.wcfsolutions.com/index.html>
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * @package		com.wcfsolutions.wcf.shoutbox.fork.i2c
 * @original package	com.wcfsolutions.wcf.shoutbox
 * @subpackage		data.shoutbox
 * @category		Community Framework
 */
class ShoutboxEntryList extends DatabaseObjectList {
	/**
	 * list of shoutbox entries
	 * 
	 * @var array<ViewableShoutboxEntry>
	 */
	public $entries = array ();
	
	/**
	 * sql order by statement
	 * 
	 * @var	string
	 */
	public $sqlOrderBy = 'shoutbox_entry.time DESC';
	
	/**
	 * @see DatabaseObjectList::countObjects()
	 */
	public function countObjects() {
		$sql = "SELECT	COUNT(*) AS count
			FROM	wcf" . WCF_N . "_shoutbox_entry shoutbox_entry
			" . (! empty ( $this->sqlConditions ) ? "WHERE " . $this->sqlConditions : '');
		$row = WCF::getDB ()->getFirstRow ( $sql );
		return $row ['count'];
	}
	
	/**
	 * @see DatabaseObjectList::readObjects()
	 */
	public function readObjects() {
		$sql = "SELECT		" . (! empty ( $this->sqlSelects ) ? $this->sqlSelects . ',' : '') . "
					shoutbox_entry.*
				FROM		wcf" . WCF_N . "_shoutbox_entry shoutbox_entry
				" . $this->sqlJoins . "
				WHERE " . (! empty ( $this->sqlConditions ) ? $this->sqlConditions . " AND " : '') . "
					   (shoutbox_entry.userID = " . WCF::getUser ()->userID . " 
					OR 	shoutbox_entry.toUserID = 0
					OR 	shoutbox_entry.toUserID = " . WCF::getUser ()->userID . ")
				" . (! empty ( $this->sqlOrderBy ) ? "ORDER BY " . $this->sqlOrderBy : '');
		$result = WCF::getDB ()->sendQuery ( $sql, $this->sqlLimit, $this->sqlOffset );
		while ( $row = WCF::getDB ()->fetchArray () ) {
			
			$this->entries [] = new ViewableShoutboxEntry ( null, $row );
		}
	}
	
	/**
	 * @see DatabaseObjectList::getObjects()
	 */
	public function getObjects() {
		return $this->entries;
	}
}
?>
