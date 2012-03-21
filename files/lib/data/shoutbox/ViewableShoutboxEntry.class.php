<?php
// wcf imports
require_once (WCF_DIR . 'lib/data/message/bbcode/MessageParser.class.php');
require_once (WCF_DIR . 'lib/data/shoutbox/ShoutboxEntry.class.php');
require_once (WCF_DIR . 'lib/data/message/bbcode/URLParser.class.php');
/**
 * Represents a viewable shoutbox entry.
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
class ViewableShoutboxEntry extends ShoutboxEntry {
	/**
	 * whisper prefix
	 * 
	 * @var string
	 */
	public $prefix = '';
	
	/**
	 * message username
	 * 
	 * @var string
	 */
	public $messageUsername = '';
	
	/**
	 * Returns styled username.
	 * 
	 * @return	string
	 */
	public function getStyledUsername() {
		
		if ($this->username == StringUtil::encodeHTML ( WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.neme' ) )) {
			return sprintf ( WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.style' ), StringUtil::encodeHTML ( $this->username ) );
		} else {
			if (!$this->usernameStyle || $this->usernameStyle == '%s') {
				$this->usernameStyle = '<span style="font-weight:bold;color:#666;">%s</span>';
			}
			 if ($this->toUserID == 0 || ($this->toUserID <> 0 && $this->toUserID == WCF::getUser()->userID)){
				return sprintf ( $this->usernameStyle, StringUtil::encodeHTML ( $this->username ) );
			}	elseif ($this->toUserID <> 0 && $this->toUserID <> WCF::getUser()->userID) {
				return sprintf ( $this->usernameStyle, StringUtil::encodeHTML ( $this->toUserName ) );
			}	else {
				return sprintf ( $this->usernameStyle, StringUtil::encodeHTML ( $this->username ) );
			} 
		}
	}
	
	/**
	 * Returns the whisper prefix.
	 * 
	 * @return	string
	 */
	public function getWhisperPrefix() {
		if ($this->toUserID != 0) {
			if ($this->toUserID != WCF::getUser ()->userID) {
				$this->prefix = 'An ';
			} else if ($this->toUserID == WCF::getUser ()->userID) {
				$this->prefix = 'Von ';
			} else {
				$this->prefix = '';
			}
		}
		return $this->prefix;
	}
	
	/**
	 * Returns the formatted message.
	 * 
	 * @return 	string
	 */
	public function getFormattedMessage() {
		$sql = "SELECT	COUNT(*) AS count
				FROM wcf" . WCF_N . "_shoutbox_blacklist
				WHERE userID=" . WCF::getUser ()->userID . "
				AND blackUserID =" . $this->userID;
		$row = WCF::getDB ()->getFirstRow ( $sql );
		if ($row ['count'] != 0) {
			$this->message = "~ignoriert~";
		}
		$parser = MessageParser::getInstance ();
		$parser->setOutputType ( 'text/html' );
		return $parser->parse ( URLParser::parse ( $this->message ), 1, 0, 1, false );
	}
}
?>
