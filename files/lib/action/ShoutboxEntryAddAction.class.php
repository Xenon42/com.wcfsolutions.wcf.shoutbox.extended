<?php
// wcf imports
require_once (WCF_DIR . 'lib/action/AbstractAction.class.php');
require_once (WCF_DIR . 'lib/data/shoutbox/ShoutboxEntryEditor.class.php');

/**
 * Adds a new shoutbox entry.
 * 
 * @author	Sebastian Oettl
 * @copyright	2009-2011 WCF Solutions <http://www.wcfsolutions.com/index.html>
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.wcfsolutions.wcf.shoutbox
 * @subpackage	action
 * @category	Community Framework
 */
class ShoutboxEntryAddAction extends AbstractAction {
	/**
	 * username
	 * 
	 * @var	string
	 */
	public $username = '';
	
	/**
	 * new message
	 * 
	 * @var	string
	 */
	public $message = '';
	
	/**
	 * me
	 * 
	 * @var	integer
	 */
	public $me = 0;
	
	/**
	 * command
	 * 
	 * @var	string
	 */
	public $command = '';
	
	/**
	 * whisper to userID
	 * 
	 * @var	integer
	 */
	public $toUserID = 0;
	
	/**
	 * whisper to username
	 * 
	 * @var	string
	 */
	public $toUserName = '';
	
	/**
	 * new shoutbox entry editor object
	 * 
	 * @var	ShoutboxEntryEditor
	 */
	public $entry = null;
	
	/**
	 * @see Action::readParameters()
	 */
	public function readParameters() {
		parent::readParameters ();
		
		try {
			// check permissions
			WCF::getUser ()->checkPermission ( 'user.shoutbox.canAddEntry' );
			
			// do flood control	
			if (WCF::getUser ()->getPermission ( 'user.shoutbox.floodControlTime' )) {
				$sql = "SELECT		time
					FROM		wcf" . WCF_N . "_shoutbox_entry
					WHERE		" . (WCF::getUser ()->userID ? "userID = " . WCF::getUser ()->userID : "ipAddress = '" . escapeString ( WCF::getSession ()->ipAddress ) . "'") . "
							AND time > " . (TIME_NOW - WCF::getUser ()->getPermission ( 'user.shoutbox.floodControlTime' )) . "
					ORDER BY	time DESC";
				$row = WCF::getDB ()->getFirstRow ( $sql );
				if (isset ( $row ['time'] )) {
					throw new NamedUserException ( WCF::getLanguage ()->getDynamicVariable ( 'wcf.shoutbox.entry.error.floodControl', array ('waitingTime' => $row ['time'] - (TIME_NOW - WCF::getUser ()->getPermission ( 'user.shoutbox.floodControlTime' )), 'floodControlTime' => WCF::getUser ()->getPermission ( 'user.shoutbox.floodControlTime' ) ) ) );
				}
			}
			
			// get username
			if (isset ( $_POST ['username'] )) {
				$this->username = StringUtil::trim ( $_POST ['username'] );
				if (CHARSET != 'UTF-8')
					$this->username = StringUtil::convertEncoding ( 'UTF-8', CHARSET, $this->username );
			}
			if (WCF::getUser ()->userID == 0) {
				if (empty ( $this->username )) {
					throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.entry.error.username.empty' ) );
				}
				if (! UserUtil::isValidUsername ( $this->username )) {
					throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.user.error.username.notValid' ) );
				}
				if (! UserUtil::isAvailableUsername ( $this->username )) {
					throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.user.error.username.notUnique' ) );
				}
				
				WCF::getSession ()->setUsername ( $this->username );
			} else {
				$this->username = WCF::getUser ()->username;
			}
			
			$this->checkBans ();
			
			// get message
			if (isset ( $_POST ['message'] )) {
				$this->message = StringUtil::trim ( $_POST ['message'] );
				//$this->message = preg_replace('/[^\x9\xA\xD\x20-\xD7FF\xE000-\xFFFD]\x10000-\x10FFFF\x0000F/', '', $this->message);
				$this->message = preg_replace ( '/[\x00-\x1F\x80-\xFF]/', '', $this->message );
				if (CHARSET != 'UTF-8') {
					$this->message = StringUtil::convertEncoding ( 'UTF-8', CHARSET, $this->message );
				}
			}
			if (empty ( $this->message )) {
				throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.entry.error.message.empty' ) );
			}
			if (StringUtil::length ( $this->message ) > WCF::getUser ()->getPermission ( 'user.shoutbox.maxEntryLength' )) {
				throw new NamedUserException ( WCF::getLanguage ()->getDynamicVariable ( 'wcf.message.error.tooLong', array ('maxTextLength' => WCF::getUser ()->getPermission ( 'user.shoutbox.maxEntryLength' ) ) ) );
			}
			// check for commands
			if (preg_match ( "/^\/.*?\s/", $this->message, $match )) {
				$this->command = StringUtil::trim ( StringUtil::replace ( '/', '', $match [0] ) );
				$this->handleCommands ();
			}
			$this->message = StringUtil::replace ( "\n", '', StringUtil::unifyNewlines ( $this->message ) );
			
			if (preg_match ( "/\[.*?\]/", $this->message, $match )) {
				$this->handleBBCodes ();
			}
		} catch ( UserException $e ) {
			// show errors in a readable way
			if (empty ( $_REQUEST ['ajax'] )) {
				throw $e;
			} else {
				@header ( 'HTTP/1.0 403 Forbidden' );
				echo $e->getMessage ();
				exit ();
			}
		}
	}
	
	/**
	 * handles possible commands
	 */
	public function handleCommands() {
		switch ($this->command) {
			case 'w' :
				If (! WCF::getUser ()->getPermission ( 'user.shoutbox.canWhisper' )) {
					throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.whisper.noWhisper' ) );
				} else if (! preg_match ( '/\/w \"(.+?)\"/', $this->message, $match )) {
					throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.whisper.wrongWhisper' ) );
				} else {
					$toUser = new User ( null, null, $match [1], null );
					if ($toUser->userID == WCF::getUser ()->userID) {
						throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.whisper.noSelfWhisper' ) );
					} else if ($match [1] == WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' )) {
						throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.answer' ) );
					}
					if ($toUser->userID != 0) {
						$this->message = StringUtil::trim ( preg_replace ( '/\/w \"' . $toUser->username . '\"/', '', $this->message ) );
						$this->toUserID = $toUser->userID;
						$this->toUserName = $toUser->username;
						if (empty ( $this->message )) {
							throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.entry.error.message.empty' ) );
						}
					} else {
						throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.user.error.username.notValid' ) );
					}
				}
				break;
			case 'me' :
				$this->message = '[i]' . str_replace ( '/me ', '', $this->message ) . '[/i]';
				$this->me = 1;
			case 'del' :
				if (WCF::getUser ()->getPermission ( 'mod.shoutbox.canDeleteEntry' ) || WCF::getUser ()->getPermission ( 'user.shoutbox.canDeleteOwnEntry' )) {
					if (! preg_match ( '/\/del\s(\d+)/', $this->message, $match )) {
						return;
					}
					$entry = new ShoutboxEntryEditor ( $match [1] );
					if (! $entry->isDeletable ()) {
						return;
					} else {
						$entry->delete ();
						throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.entry.del.message.ok', array ('entryID' => $match [1] ) ) );
						return;
					}
				} else {
					return;
				}
				break;
			case 'ignore' :
				If (! WCF::getUser ()->getPermission ( 'user.shoutbox.canIgnoreUser' )) {
					throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.ignore.noIgnore' ) );
				} else if (! preg_match ( '/\/ignore \"(.+?)\"/', $this->message, $match )) {
					throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.ignore.wrongIgnore' ) );
				} else {
					$igUser = new User ( null, null, $match [1], null );
					if ($igUser->userID == WCF::getUser ()->userID) {
						throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.ignore.noSelfIgnore' ) );
					} else if ($match [1] == WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' )) {
						throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.answer' ) );
					}
					if ($igUser->userID != 0) {
						$sql = "SELECT	COUNT(*) AS count
								FROM wcf" . WCF_N . "_shoutbox_blacklist
								WHERE userID=" . WCF::getUser ()->userID . "
									AND blackUserID =" . $igUser->userID;
						$row = WCF::getDB ()->getFirstRow ( $sql );
						if ($row ['count'] == 0) {
							$session = new UserSession ( $igUser->userID, null, null, null );
							if ($session->getPermission ( 'user.shoutbox.canBeIgnored' )) {
								$sql = "INSERT INTO	wcf" . WCF_N . "_shoutbox_blacklist
									(userID, blackUserID)
									VALUES		(" . WCF::getUser ()->userID . ", $igUser->userID)";
								WCF::getDB ()->sendQuery ( $sql );
								$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
								$this->message = 'Der Benutzer [i]' . $igUser->username . '[/i] wurde deiner Ignorierliste hinzugefügt.[i]';
								$this->toUserID = WCF::getUser ()->userID;
								$this->toUserName = WCF::getUser ()->username;
							} else {
								throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.ignore.canBeIgnored' ) );
							}
						} else {
							$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
							$this->message = 'Der Benutzer [i]' . $igUser->username . '[/i] befindet sich bereits auf deiner Ignorierliste.[i]';
							$this->toUserID = WCF::getUser ()->userID;
							$this->toUserName = WCF::getUser ()->username;
						}
					} else {
						throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.user.error.username.notValid' ) );
					}
				}
				break;
			case 'unignore' :
				If (! WCF::getUser ()->getPermission ( 'user.shoutbox.canIgnoreUser' )) {
					return;
				} else if (! preg_match ( '/\/unignore \"(.+?)\"/', $this->message, $match )) {
					throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.ignore.wrongUnignore' ) );
				} else {
					$unigUser = new User ( null, null, $match [1], null );
					if ($unigUser->userID == WCF::getUser ()->userID) {
						throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.ignore.noSelfIgnore' ) );
					} else if ($match [1] == WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' )) {
						throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.answer' ) );
					}
					if ($unigUser->userID != 0) {
						$sql = "SELECT	COUNT(*) AS count
								FROM wcf" . WCF_N . "_shoutbox_blacklist
								WHERE userID=" . WCF::getUser ()->userID . "
									AND blackUserID =" . $unigUser->userID;
						$row = WCF::getDB ()->getFirstRow ( $sql );
						if ($row ['count'] >= 1) {
							$sql = "DELETE FROM wcf" . WCF_N . "_shoutbox_blacklist
									WHERE userID=" . WCF::getUser ()->userID . "
										AND blackUserID=" . $unigUser->userID;
							WCF::getDB ()->sendQuery ( $sql );
							$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
							$this->message = 'Der Benutzer [i]' . $unigUser->username . '[/i] wurde von deiner Ignorierliste entfernt.[i]';
							$this->toUserID = WCF::getUser ()->userID;
							$this->toUserName = WCF::getUser ()->username;
						} else {
							$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
							$this->message = 'Der Benutzer [i]' . $unigUser->username . '[/i] befindet sich nicht auf deiner Ignorierliste.[i]';
							$this->toUserID = WCF::getUser ()->userID;
							$this->toUserName = WCF::getUser ()->username;
						}
					} else {
						throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.user.error.username.notValid' ) );
					}
				}
				break;
			case 'blacklist' :
				$this->message = str_replace ( '/blacklist ', '', $this->message );
				if ($this->message == 'show') {
					if (! WCF::getUser ()->getPermission ( 'user.shoutbox.canIgnoreUser' )) {
						exit ();
					}
					$sql = "SELECT *  
							FROM wcf" . WCF_N . "_shoutbox_blacklist
							WHERE userID=" . WCF::getUser ()->userID . "
							ORDER BY blackUserID ASC";
					$result = WCF::getDB ()->getResultList ( $sql );
					if (! empty ( $result )) {
						$list = array ();
						foreach ( $result as $key => $value ) {
							$user = new User ( $value ['blackUserID'], null, null, null );
							array_push ( $list, $user->username );
						}
						$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
						$this->message = "[i]" . implode ( ", ", $list ) . "[/i]";
						$this->toUserID = WCF::getUser ()->userID;
						$this->toUserName = WCF::getUser ()->username;
						$this->entry = ShoutboxEntryEditor::create ( WCF::getUser ()->userID, $this->username, $this->message, $this->toUserID, $this->toUserName, $this->me );
						$this->executed ();
						exit ();
					} else {
						$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
						$this->message = 'Deine Ignorierliste ist leer.';
						$this->toUserID = WCF::getUser ()->userID;
						$this->toUserName = WCF::getUser ()->username;
					}
				} else {
					$this->message = '/blacklist ' . $this->message;
					return;
				}
				break;
			case 'ban' :
				if (! WCF::getUser ()->getPermission ( 'mod.shoutbox.canBanUser' )) {
					return;
				}
				if (preg_match ( '/\/ban\s\"(.+?)\"\s(\d+)\s(.*)/', $this->message, $match )) {
					$reason = '[b]Grund:[/b] ' . $match [3];
				} elseif (preg_match ( '/\/ban\s\"(.+?)\"\s(\d+)/', $this->message, $match )) {
					$reason = '';
				} else {
					return;
				}
				$banUser = new User ( null, null, $match [1], null );
				$banUsername = $banUser->username;
				$banUserID = $banUser->userID;
				$banTime = $match [2];
				$until = TIME_NOW + ($match [2] * 60);
				if ($banUserID == WCF::getUser ()->userID) {
					throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.ban.noSelfBan' ) );
				}
				if ($banUserID != 0) {
					$session = new UserSession ( $banUserID, null, null, null );
					If (! $session->getPermission ( 'mod.shoutbox.noBan' ) || (WCF::getUser ()->getPermission ( 'mod.shoutbox.canBanEveryUser' ))) {
						$sql = "INSERT INTO	wcf" . WCF_N . "_shoutbox_banlist
									(userID, until)
									VALUES		($banUserID, $until)
									ON DUPLICATE KEY UPDATE until = " . $until;
						WCF::getDB ()->sendQuery ( $sql );
						$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
						$this->message = 'Der Benutzer [i]' . $banUsername . '[/i] wurde von [i]' . WCF::getUser ()->username . '[/i] fuer ' . $banTime . ' Minuten vom Shoutboxbetrieb ausgeschlossen. ' . $reason;
					} else {
						throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.ban.noBan' ) );
					}
				} else {
					throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.user.error.username.notValid' ) );
				}
				break;
			case 'unban' :
				if (! WCF::getUser ()->getPermission ( 'mod.shoutbox.canUnbanUser' )) {
					return;
				}
				if (! preg_match ( '/\/unban\s\"(.+?)\"/', $this->message, $match )) {
					return;
				}
				$unbanUser = new User ( null, null, $match [1], null );
				$unbanUserID = $unbanUser->userID;
				$unbanUsername = $unbanUser->username;
				if ($unbanUserID == 0) {
					throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.user.error.username.notValid' ) );
				}
				$sql = "SELECT	COUNT(*) AS count
						FROM wcf" . WCF_N . "_shoutbox_banlist
						WHERE userID=" . $unbanUserID;
				$row = WCF::getDB ()->getFirstRow ( $sql );
				if ($row ['count'] != 1) {
					$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
					$this->message = 'Es liegt keine Sperre gegen den Benutzer [i]' . $unbanUsername . '[/i] vor.';
					$this->toUserID = WCF::getUser ()->userID;
					$this->toUserName = WCF::getUser ()->username;
				} else {
					if ($unbanUserID != 0) {
						$sql = "DELETE FROM wcf" . WCF_N . "_shoutbox_banlist
						WHERE userID=" . $unbanUserID;
						WCF::getDB ()->sendQuery ( $sql );
						$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
						$this->message = 'Die Sperre gegen den Benutzer [i]' . $unbanUsername . '[/i] wurde von ' . WCF::getUser ()->username . ' aufgehoben.';
					} else {
						throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.user.error.username.notValid' ) );
					}
				}
				break;
			case 'banlist' :
				$this->message = str_replace ( '/banlist ', '', $this->message );
				if ($this->message == 'show') {
					if (! WCF::getUser ()->getPermission ( 'mod.shoutbox.canBanUser' )) {
						$this->message = '/banlist ' . $this->message;
						return;
					}
					$sql = "SELECT *  
							FROM wcf" . WCF_N . "_shoutbox_banlist
							WHERE userID<>0 
							ORDER BY until ASC";
					$result = WCF::getDB ()->getResultList ( $sql );
					if (! empty ( $result )) {
						foreach ( $result as $key => $value ) {
							if ($value ['until'] > TIME_NOW) {
								$user = new User ( $value ['userID'], null, null, null );
								$username = $user->username;
								$until = DateUtil::formatShortTime ( null, $value ['until'], null, null );
								$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
								$this->message = '[b]Benutzer:[/b] ' . $username . ' [b]Gesperrt bis:[/b] ' . $until;
								$this->toUserID = WCF::getUser ()->userID;
								$this->toUserName = WCF::getUser ()->username;
								$this->entry = ShoutboxEntryEditor::create ( WCF::getUser ()->userID, $this->username, $this->message, $this->toUserID, $this->toUserName, $this->me );
							
							}
						}
						$this->executed ();
						exit ();
					} else {
						$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
						$this->message = 'Es liegen im moment keine Sperren vor.';
						$this->toUserID = WCF::getUser ()->userID;
						$this->toUserName = WCF::getUser ()->username;
					}
				} elseif ($this->message == 'pub') {
					if (! WCF::getUser ()->getPermission ( 'mod.shoutbox.canBanUser' )) {
						$this->message = '/banlist ' . $this->message;
						return;
					}
					$sql = "SELECT *  
							FROM wcf" . WCF_N . "_shoutbox_banlist
							WHERE userID<>0 
							ORDER BY until ASC";
					$result = WCF::getDB ()->getResultList ( $sql );
					if (! empty ( $result )) {
						foreach ( $result as $key => $value ) {
							if ($value ['until'] > TIME_NOW) {
								$user = new User ( $value ['userID'], null, null, null );
								$username = $user->username;
								$until = DateUtil::formatShortTime ( null, $value ['until'], null, null );
								$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
								$this->message = '[b]Benutzer:[/b] ' . $username . ' [b]Gesperrt bis:[/b] ' . $until;
								$this->entry = ShoutboxEntryEditor::create ( WCF::getUser ()->userID, $this->username, $this->message, $this->toUserID, $this->toUserName, $this->me );
							}
						}
						$this->executed ();
						exit ();
					} else {
						$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
						$this->message = 'Es liegen im Moment keine Sperren vor.';
					}
				} else {
					$this->message = '/banlist ' . $this->message;
					return;
				}
				break;
			case 'system' :
				if (! WCF::getUser ()->getPermission ( 'mod.shoutbox.canBanUser' )) {
					return;
				}
				$this->message = str_replace ( '/system ', '', $this->message );
				$this->username = WCF::getLanguage ()->get ( 'wcf.shoutbox.bot.name' );
				break;
			default :
				return;
				break;
		}
	}
	
	/**
	 * Handles BBCodes
	 */
	protected function handleBBCodes() {
		// @TODO: pattern also deletes bbcodes in bbcode, find a better one
		if (preg_match ( '/^\[.*?\]\s*\[\/.*?\]$/', $this->message, $match )) {
			throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.entry.error.message.empty' ) );
		}
		$this->message = preg_replace ( '/\[spoiler.*\](.+)\[\/spoiler\]/Ui', '$1', $this->message );
		$this->message = preg_replace ( '/\[youtube.*\](.+)\[\/youtube\]/Ui', '$1', $this->message );
		$this->message = preg_replace ( '/\[clipfish.*\](.+)\[\/clipfish\]/Ui', '$1', $this->message );
		$this->message = preg_replace ( '/\[googlevideo.*\](.+)\[\/googlevideo\]/Ui', '$1', $this->message );
		$this->message = preg_replace ( '/\[myspace.*\](.+)\[\/myspace\]/Ui', '$1', $this->message );
		$this->message = preg_replace ( '/\[myvideo.*\](.+)\[\/myvideo\]/Ui', '$1', $this->message );
		$this->message = preg_replace ( '/\[sevenload.*\](.+)\[\/sevenload\]/Ui', '$1', $this->message );
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.color" )) {
			$this->message = preg_replace ( '/\[color.*\](.+)\[\/color\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.img" )) {
			$this->message = preg_replace ( '/\[img\](.*)\[\/img\]/Ui', '$1', $this->message );
			$this->message = preg_replace ( '/\[img=(.*)\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.size" )) {
			$this->message = preg_replace ( '/\[size.*\](.+)\[\/size\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.font" )) {
			$this->message = preg_replace ( '/\[font.*\](.+)\[\/yfont\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.b" )) {
			$this->message = preg_replace ( '/\[b\](.+)\[\/b\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.i" )) {
			$this->message = preg_replace ( '/\[i\](.+)\[\/i\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.u" )) {
			$this->message = preg_replace ( '/\[u\](.+)\[\/u\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.s" )) {
			$this->message = preg_replace ( '/\[s\](.+)\[\/s\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.align" )) {
			$this->message = preg_replace ( '/\[align.*\](.+)\[\/align\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.list" )) {
			$this->message = preg_replace ( '/\[list.*\](.+)\[\/list\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.quote" )) {
			$this->message = preg_replace ( '/\[quote.*\](.+)\[\/quote\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.code" )) {
			$this->message = preg_replace ( '/\[code.*\](.+)\[\/code\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.php" )) {
			$this->message = preg_replace ( '/\[php.*\](.+)\[\/php\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.mysql" )) {
			$this->message = preg_replace ( '/\[mysql.*\](.+)\[\/mysql\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.cpp" )) {
			$this->message = preg_replace ( '/\[cpp.*\](.+)\[\/cpp\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.c" )) {
			$this->message = preg_replace ( '/\[c.*\](.+)\[\/c\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.attach" )) {
			$this->message = preg_replace ( '/\[attach.*\].+\[\/attach\]/Ui', '', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.email" )) {
			$this->message = preg_replace ( '/\[email.*\](.+)\[\/email\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.css" )) {
			$this->message = preg_replace ( '/\[css.*\](.+)\[\/css\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.html" )) {
			$this->message = preg_replace ( '/\[html.*\](.+)\[\/html\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.java" )) {
			$this->message = preg_replace ( '/\[java.*\](.+)\[\/java\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.js" )) {
			$this->message = preg_replace ( '/\[js.*\](.+)\[\/js\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.sub" )) {
			$this->message = preg_replace ( '/\[sub.*\](.+)\[\/sub\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.sup" )) {
			$this->message = preg_replace ( '/\[sup.*\](.+)\[\/sup\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.tpl" )) {
			$this->message = preg_replace ( '/\[tpl.*\](.+)\[\/tpl\]/Ui', '$1', $this->message );
		}
		if (! WCF::getUser ()->getPermission ( "user.shoutbox.bbcode.xml" )) {
			$this->message = preg_replace ( '/\[xml.*\](.+)\[\/xml\]/Ui', '$1', $this->message );
		}
		// autoit-shouts are different!
		if (preg_match ( '/^\/w\s\".*?\"\s\[autoit\](.*)\[\/autoit\]$|^\[autoit\](.*)\[\/autoit\]$/Uism', $this->message )) {
		} else {
			// non-autoit shout
			$this->message = preg_replace ( '/\\n([^$])/Ui', '↵ $1', $this->message );
			$this->message = preg_replace ( '/\[autoit.*\](.+)\[\/autoit\]/Uims', '[AutoIt-Code bitte einzeln posten!]', $this->message );
		}
	}
	
	/**
	 * Check if userID is currently banned
	 */
	protected function checkBans() {
		$sql = "SELECT * FROM wcf" . WCF_N . "_shoutbox_banlist
				WHERE userID=" . WCF::getUser ()->userID . "
				LIMIT 1";
		$result = WCF::getDB ()->getFirstRow ( $sql );
		if (is_array ( $result ) && $result ['until'] > TIME_NOW) {
			throw new NamedUserException ( WCF::getLanguage ()->get ( 'wcf.shoutbox.ban.isBanned' ) );
		} elseif (is_array ( $result ) && $result ['until'] <= TIME_NOW) {
			$sql = "DELETE FROM wcf" . WCF_N . "_shoutbox_banlist
					WHERE userID=" . WCF::getUser ()->userID;
			WCF::getDB ()->sendQuery ( $sql );
		}
	}
	
	/**
	 * @see Action::execute()
	 */
	public function execute() {
		parent::execute ();
		// add shoutbox entry
		$this->entry = ShoutboxEntryEditor::create ( WCF::getUser ()->userID, $this->username, $this->message, $this->toUserID, $this->toUserName, $this->me );
		
		$this->executed ();
		
		// forward
		if (WCF::getSession ()->lastRequestURI && empty ( $_REQUEST ['ajax'] )) {
			HeaderUtil::redirect ( WCF::getSession ()->lastRequestURI, false );
		}
		exit ();
	}
}

?>

