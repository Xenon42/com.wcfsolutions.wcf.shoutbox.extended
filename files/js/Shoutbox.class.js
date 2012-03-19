/**
 * @midified by Thomas Wegner
 * @original author Sebastian Oettl
 * @copyright 2009-2011 WCF Solutions <http://www.wcfsolutions.com/index.html>
 * @license GNU Lesser General Public License
 *          <http://opensource.org/licenses/lgpl-license.php>
 */
var Shoutbox = Class
		.create({
			/**
			 * Inits Shoutbox.
			 * 
			 * @param string
			 *            shoutboxID
			 * @param Hash
			 *            entries
			 */
			initialize : function(shoutboxID, entries) {
				this.shoutboxID = shoutboxID;
				this.lastEntryID = 0;
				this.standby = false;
				this.options = Object.extend({
					langDeleteEntry : '',
					langDeleteEntrySure : '',
					imgDeleteEntrySrc : '',
					entryReloadInterval : 0,
					entrySortOrder : 'ASC',
					unneededUpdateLimit : 1
				}, arguments[3] || {});
				this.unneededUpdates = 0;

				// remove entries
				var shoutboxContentDiv = $(this.shoutboxID + 'Content');
				if (shoutboxContentDiv) {
					shoutboxContentDiv.update();
					shoutboxContentDiv.observe('mouseover', function() {
						if (this.standby) {
							this.stopStandby();
							this.startEntryUpdate();
						}
					}.bind(this));
				}

				// show smileys
				var smileyContainerDiv = $(this.shoutboxID + 'SmileyContainer');
				if (smileyContainerDiv) {
					smileyContainerDiv.removeClassName('hidden');
				}

				// add event listener
				var entryAddForm = $(this.shoutboxID + 'EntryAddForm');
				if (entryAddForm) {
					entryAddForm.observe('submit', function(event) {
						this.addEntry();
						event.stop();
					}.bind(this));
				}

				// insert entries
				this.insertEntries(entries, false);

				// start entry update
				this.startEntryUpdate();
			},

			/**
			 * Starts the entry update.
			 */
			startEntryUpdate : function() {
				if (this.options.entryReloadInterval != 0) {
					this.executer = new PeriodicalExecuter(function() {
						this.loadEntries();
					}.bind(this), this.options.entryReloadInterval);
				}
			},

			/**
			 * Stops the entry update.
			 */
			stopEntryUpdate : function() {
				if (this.options.entryReloadInterval != 0) {
					this.executer.stop();
				}
			},

			/**
			 * Starts the standby mode.
			 */
			startStandby : function() {
				if (!this.standby) {
					this.standby = true;

					// change opacity
					new Effect.Opacity(this.shoutboxID + 'Content', {
						from : 1,
						to : 0.5
					});
				}
			},

			/**
			 * Stops the standby mode.
			 */
			stopStandby : function() {
				if (this.standby) {
					this.standby = false;
					this.unneededUpdates = 0;

					// change opacity
					new Effect.Opacity(this.shoutboxID + 'Content', {
						from : 0.5,
						to : 1
					});
				}
			},

			/**
			 * Inserts a smiley with the given code.
			 * 
			 * @param string
			 *            code
			 */
			insertSmiley : function(code) {
				var messageInputField = $(this.shoutboxID + 'Message');
				if (messageInputField) {
					messageInputField.value = messageInputField.value + ' '
							+ code + ' ';
					messageInputField.focus();
				}
			},
			
			insertBBCode : function(aTag, eTag, id) {
				var input = $(this.shoutboxID + 'Message');
				input.focus();
				if (id != null) {
				var selectElm = $(id);
				selectElm.selectedIndex = '0';
				}
				
				/* für Internet Explorer */
				if (typeof document.selection != 'undefined') {
					/* Einfügen des Formatierungscodes */
					var range = document.selection.createRange();
					var insText = range.text;
					range.text = aTag + insText + eTag;
					/* Anpassen der Cursorposition */
					range = document.selection.createRange();
					if (insText.length == 0) {
						range.move('character', -eTag.length);
					} else {
						range.moveStart('character', aTag.length
								+ insText.length + eTag.length);
					}
					range.select();
				}
				/* für neuere auf Gecko basierende Browser */
				else if (typeof input.selectionStart != 'undefined') {
					/* Einfügen des Formatierungscodes */
					var start = input.selectionStart;
					var end = input.selectionEnd;
					var insText = input.value.substring(start, end);
					input.value = input.value.substr(0, start) + aTag + insText
							+ eTag + input.value.substr(end);
					/* Anpassen der Cursorposition */
					var pos;
					if (insText.length == 0) {
						pos = start + aTag.length;
					} else {
						pos = start + aTag.length + insText.length
								+ eTag.length;
					}
					input.selectionStart = pos;
					input.selectionEnd = pos;
				}
				/* für die übrigen Browser */
				else {
					/* Abfrage der Einfügeposition */
					var pos;
					var re = new RegExp('^[0-9]{0,3}$');
					while (!re.test(pos)) {
						pos = prompt("Einfügen an Position (0.."
								+ input.value.length + "):", "0");
					}
					if (pos > input.value.length) {
						pos = input.value.length;
					}
					/* Einfügen des Formatierungscodes */
					var insText = prompt("Bitte geben Sie den zu formatierenden Text ein:");
					input.value = input.value.substr(0, pos) + aTag + insText
							+ eTag + input.value.substr(pos);
				}
			},

			/**
			 * Adds a new entry.
			 */
			addEntry : function() {
				// get message
				var message = '';
				var messageInputField = $(this.shoutboxID + 'Message');
				if (messageInputField) {
					message = messageInputField.value;
				}

				// get username
				var username = '';
				var usernameInputField = $(this.shoutboxID + 'Username');
				if (usernameInputField) {
					username = usernameInputField.value;
				}

				// stop entry update
				this.stopEntryUpdate();

				// add entry
				new Ajax.Request('index.php?action=ShoutboxEntryAdd'
						+ SID_ARG_2ND, {
					method : 'post',
					parameters : {
						message : message,
						username : username,
						ajax : 1
					},
					onSuccess : function(messageInputField) {
						// reset message
						if (messageInputField) {
							messageInputField.value = '';
							messageInputField.focus();
						}

						// update entries
						this.loadEntries();

						// stop standby
						this.stopStandby();
					}.bind(this, messageInputField),
					onFailure : function(response) {
						// workaround: js does not support html-entities
						var error = new Element('textarea').update(
								response.responseText).getValue();
						alert(error);
					}
				});
			},

			/**
			 * Deletes an entry.
			 * 
			 * @param integer
			 *            id
			 */
			deleteEntry : function(id) {
				new Ajax.Request('index.php?action=ShoutboxEntryDelete&t='
						+ SECURITY_TOKEN + SID_ARG_2ND, {
					method : 'post',
					parameters : {
						entryID : id,
						ajax : 1
					},
					onSuccess : function() {
						// remove entry row
						var row = $(this.shoutboxID + 'Entry' + id);
						if (row) {
							new Effect.Parallel([ new Effect.BlindUp(row),
									new Effect.Fade(row) ], {
								duration : 0.3
							});
						}
					}.bind(this)
				});
			},

			/**
			 * Loads the new entries and inserts them.
			 */
			loadEntries : function() {
				// stop entry update
				this.stopEntryUpdate();

				// start request
				new Ajax.Request(
						'index.php?page=ShoutboxEntryXMLList&t='
								+ SECURITY_TOKEN + SID_ARG_2ND,
						{
							method : 'post',
							parameters : {
								entryID : this.lastEntryID
							},
							onSuccess : function(response) {
								// get entries
								var entries = response.responseXML
										.getElementsByTagName('entries');
								if (entries.length > 0) {
									if (entries[0].childNodes.length == 0) {
										this.unneededUpdates++;
										if (this.options.unneededUpdateLimit != 0
												&& this.unneededUpdates >= this.options.unneededUpdateLimit) {
											this.startStandby();
											return;
										}
									} else {

										var newEntries = new Hash();
										for ( var i = 0; i < entries[0].childNodes.length; i++) {
											newEntries
													.set(
															entries[0].childNodes[i].childNodes[0].childNodes[0].nodeValue,
															{
																userID : entries[0].childNodes[i].childNodes[1].childNodes[0].nodeValue,
																styledUsername : entries[0].childNodes[i].childNodes[2].childNodes[0].nodeValue,
																username : entries[0].childNodes[i].childNodes[3].childNodes[0].nodeValue,
																time : entries[0].childNodes[i].childNodes[5].childNodes[0].nodeValue,
																message : entries[0].childNodes[i].childNodes[6].childNodes[0].nodeValue,
																me : entries[0].childNodes[i].childNodes[7].childNodes[0].nodeValue,
																isDeletable : entries[0].childNodes[i].childNodes[8].childNodes[0].nodeValue,
																toUserID : entries[0].childNodes[i].childNodes[9].childNodes[0].nodeValue,
																toUserName : entries[0].childNodes[i].childNodes[10].childNodes[0].nodeValue,
																thisUserID : entries[0].childNodes[i].childNodes[11].childNodes[0].nodeValue,
																prefix : entries[0].childNodes[i].childNodes[12].childNodes[0].nodeValue
															});
										}
										this.unneededUpdates = 0;
										this.insertEntries(newEntries, true);
									}
								}

								// restart entry update
								this.startEntryUpdate();
							}.bind(this)
						});
			},

			/**
			 * Inserts the given entries into the shoutbox.
			 * 
			 * @param Hash
			 *            entries
			 * @param boolean
			 *            animate
			 */

			insertEntries : function(entries, animate) {
				var shoutboxMessageDiv = $(this.shoutboxID + 'Content');
				if (shoutboxMessageDiv) {
					// update shoutbox content
					var idArray = entries.keys();
					if (idArray.length > 0) {
						for ( var i = 0; i < idArray.length; i++) {
							var id = idArray[i];
							var entry = entries.get(id);

							// check if entry already exists
							// find a better solution for this
							if ($(this.shoutboxID + 'Entry' + id))
								continue;

							// create entry row
							var time = new Element('span')
									.addClassName('light').update(
											'[' + entry.time + ']');
							var entryRow = new Element('p', {
								id : this.shoutboxID + 'Entry' + id
							}).hide().insert(time);
							if (entry.isDeletable == 1) {
								var removeImage = new Element('img', {
									src : this.options.imgDeleteEntrySrc,
									alt : ''
								});
								var removeLink = new Element('a', {
									title : this.options.langDeleteEntry
								})
										.observe(
												'click',
												function(id, event) {
													if (confirm(this.options.langDeleteEntrySure)) {
														this.deleteEntry(id);
													}
													event.stop();
												}.bind(this, id)).insert(
												removeImage);
								entryRow.insert(' ').insert(removeLink);
							}
							entryRow.insert(' ');

							var prefix = new Element('span', {
								style : 'font-weight:bold;'
							}).insert(entry.prefix);
							entryRow.insert(prefix);

							if (entry.userID != 0) {
								if (entry.prefix == ''
										|| entry.prefix == 'Von ') {
									var userLink = new Element(
											'a',
											{
												style : 'text-decoration:none;',
												onClick : '$(\'shoutboxMessage\').value=\'/w \"'
														+ entry.username
														+ '\"  \'; $(\'shoutboxMessage\').focus();'
											}).insert(entry.styledUsername);
									entryRow.insert(userLink);
								} else {
									var userLink = new Element(
											'a',
											{
												style : 'text-decoration:none;',
												onClick : '$(\'shoutboxMessage\').value=\'/w \"'
														+ entry.toUserName
														+ '\"  \'; $(\'shoutboxMessage\').focus();'
											}).insert(entry.styledUsername);
									entryRow.insert(userLink);
								}

							} else {
								entryRow.insert(entry.username);
							}
							if (entry.me == '0') {
								entryRow.insert(': ');
							} else if (entry.me == '1') {
								entryRow.insert(' ');
							}
							entryRow.insert(entry.message);

							// insert new entry
							if (this.options.entrySortOrder == 'ASC') {
								shoutboxMessageDiv.insert({
									bottom : entryRow
								});
							} else {
								shoutboxMessageDiv.insert({
									top : entryRow
								});
							}

							var shoutboxEntryDiv = $(this.shoutboxID + 'Entry'
									+ id);
							if (shoutboxEntryDiv) {
								if (animate) {
									new Effect.Parallel(
											[
													new Effect.BlindDown(
															shoutboxEntryDiv),
													new Effect.Appear(
															shoutboxEntryDiv) ],
											{
												duration : 0.3
											});
								} else {
									shoutboxEntryDiv.show();
								}
							}

							// set last entry id
							this.lastEntryID = id;

						}

						// focus last entry
						if (animate) {
							new PeriodicalExecuter(function(executer) {
								this.focusLastEntry();
								executer.stop();
							}.bind(this), 0.3);
						} else {
							this.focusLastEntry();
						}
					}
				}
			},

			/**
			 * Focuses the last shoutbox entry.
			 */
			focusLastEntry : function() {
				if (this.options.entrySortOrder == 'ASC') {
					var shoutboxMessageDiv = $(this.shoutboxID + 'Content');
					if (shoutboxMessageDiv) {
						shoutboxMessageDiv.scrollTop = shoutboxMessageDiv.scrollHeight
								- shoutboxMessageDiv.offsetHeight + 100;
					}
				}
			}
		});