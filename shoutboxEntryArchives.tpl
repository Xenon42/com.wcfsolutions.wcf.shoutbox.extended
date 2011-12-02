{include file="documentHeader"}<head><title>{lang}wcf.shoutbox.entry.archives{/lang} -{lang}{PAGE_TITLE}{/lang}</title>{include file='headInclude' sandbox=false}<script type="text/javascript"	src="{@RELATIVE_WCF_DIR}js/MultiPagesLinks.class.js"></script></head><body {if $templateName|isset} id="tpl{$templateName|ucfirst}"{/if}>{include file='header' sandbox=false}<div id="main"><ul class="breadCrumbs">	<li><a href="index.php?page=Index{@SID_ARG_2ND}"><img		src="{icon}indexS.png{/icon}" alt="" /> <span>{lang}{PAGE_TITLE}{/lang}</span></a>	&raquo;</li></ul><div class="mainHeadline"><img src="{icon}shoutboxL.png{/icon}" alt="" /><div class="headlineContainer"><h2><a href="index.php?page=ShoutboxEntryArchives{@SID_ARG_2ND}">{lang}wcf.shoutbox.entry.archives{/lang}</a></h2></div></div>{if $userMessages|isset}{@$userMessages}{/if} {if $entries|count} {if$this->user->getPermission('mod.shoutbox.canDeleteEntry')} <script	type="text/javascript"	src="{@RELATIVE_WCF_DIR}js/InlineListEdit.class.js"></script> <script	type="text/javascript"	src="{@RELATIVE_WCF_DIR}js/ShoutboxEntryListEdit.class.js"></script> <script	type="text/javascript">				//<![CDATA[							// data array				var shoutboxEntryData = new Hash();								// language				var language = new Object();				language['wcf.global.button.mark']			= '{lang}wcf.global.button.mark{/lang}';				language['wcf.global.button.unmark']			= '{lang}wcf.global.button.unmark{/lang}';				language['wcf.global.button.delete']			= '{lang}wcf.global.button.delete{/lang}';				language['wcf.shoutbox.entry.deleteMarked.sure']	= '{lang}wcf.shoutbox.entry.deleteMarked.sure{/lang}';				language['wcf.shoutbox.markedEntries']			= '{lang}wcf.shoutbox.markedEntries{/lang}';								onloadEvents.push(function() { shoutboxEntryListEdit = new ShoutboxEntryListEdit(shoutboxEntryData, {@$markedEntries}); });				//]]>			</script> {/if}<div class="contentHeader">{pages print=true assign=pagesOutputlink="index.php?page=ShoutboxEntryArchives&pageNo=%d&sortField=$sortField&sortOrder=$sortOrder"|concat:SID_ARG_2ND_NOT_ENCODED}</div><div class="border titleBarPanel"><div class="containerHead"><h3>{lang}wcf.shoutbox.entry.archives.count{/lang}</h3></div></div><div class="border borderMarginRemove"><table class="tableList">	<thead>		<tr class="tableHead">			{if $this->user->getPermission('mod.shoutbox.canDeleteEntry')}			<th class="columnMark">			<div><label class="emptyHead"><input name="shoutboxEntryMarkAll"				type="checkbox" /></label></div>			</th>			{/if}			<th class="columnEntryID{if $sortField == 'entryID'} active{/if}"				colspan="2">			<div><a				href="index.php?page=ShoutboxEntryArchives&amp;pageNo={@$pageNo}&amp;sortField=entryID&amp;sortOrder={if $sortField == 'entryID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@SID_ARG_2ND}">{lang}wcf.shoutbox.entry.entryID{/lang}{if			$sortField == 'entryID'} <img				src="{@RELATIVE_WCF_DIR}icon/sort{@$sortOrder}S.png" alt="" />{/if}</a></div>			</th>			<th class="columnUsername{if $sortField == 'username'} active{/if}">			<div><a				href="index.php?page=ShoutboxEntryArchives&amp;pageNo={@$pageNo}&amp;sortField=username&amp;sortOrder={if $sortField == 'username' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@SID_ARG_2ND}">{lang}wcf.shoutbox.entry.username{/lang}{if			$sortField == 'username'} <img				src="{@RELATIVE_WCF_DIR}icon/sort{@$sortOrder}S.png" alt="" />{/if}</a></div>			</th>			<th class="columnMessage{if $sortField == 'message'} active{/if}">			<div><a				href="index.php?page=ShoutboxEntryArchives&amp;pageNo={@$pageNo}&amp;sortField=message&amp;sortOrder={if $sortField == 'message' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@SID_ARG_2ND}">{lang}wcf.shoutbox.entry.message{/lang}{if			$sortField == 'message'} <img				src="{@RELATIVE_WCF_DIR}icon/sort{@$sortOrder}S.png" alt="" />{/if}</a></div>			</th>			<th class="columnTime{if $sortField == 'time'} active{/if}">			<div><a				href="index.php?page=ShoutboxEntryArchives&amp;pageNo={@$pageNo}&amp;sortField=time&amp;sortOrder={if $sortField == 'time' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{@SID_ARG_2ND}">{lang}wcf.shoutbox.entry.time{/lang}{if			$sortField == 'time'} <img				src="{@RELATIVE_WCF_DIR}icon/sort{@$sortOrder}S.png" alt="" />{/if}</a></div>			</th>			{if $additionalColumnHeads|isset}{@$additionalColumnHeads}{/if}		</tr>	</thead>	<tbody>		{foreach from=$entries item=entry}		<tr class="{cycle values=" container-1,container-2" advance=false}">			{if $this->user->getPermission('mod.shoutbox.canDeleteEntry')}			<td class="columnMark"><input name="shoutboxEntryMark"				id="shoutboxEntryMark{@$entry->entryID}" type="checkbox"				value="{@$entry->entryID}" /></td>			{/if}			<td class="columnIcon"><script type="text/javascript">									//<![CDATA[									shoutboxEntryData.set({@$entry->entryID}, {										'isMarked': {@$entry->isMarked()}									});									//]]>								</script> {if $entry->isDeletable()} <a				onclick="return confirm('{lang}wcf.shoutbox.entry.delete.sure{/lang}')"				href="index.php?action=ShoutboxEntryDelete&amp;entryID={@$entry->entryID}{@SID_ARG_2ND}"><img				src="{@RELATIVE_WCF_DIR}icon/deleteS.png" alt=""				title="{lang}wcf.shoutbox.entry.delete{/lang}" /></a> {else} <img				src="{@RELATIVE_WCF_DIR}icon/deleteDisabledS.png" alt=""				title="{lang}wcf.shoutbox.entry.delete{/lang}" /> {/if} {if			$additionalButtons[$entry->entryID]|isset}{@$additionalButtons[$entry->entryID]}{/if}			</td>			<td class="columnEntryID columnID">{@$entry->entryID}</td>			<td class="columnUsername columnText">			{if $entry->toUserID != 0} 				{if $entry->toUserID != $this->user->userID && $entry->toUserID != 0} 					<span style="font-weight:bold;">An&nbsp;</span><a href="index.php?page=User&amp;userID={@$entry->toUserID}{@SID_ARG_2ND}">{$entry->toUserName}</a>				{elseif $entry->toUserID = $this->user->userID || $entry->toUserID = 0}							<span style="font-weight:bold;">Von&nbsp;</span><a href="index.php?page=User&amp;userID={@$entry->userID}{@SID_ARG_2ND}">{$entry->username}</a>						{/if}			{else}				<a href="index.php?page=User&amp;userID={@$entry->userID}{@SID_ARG_2ND}">{$entry->username}</a></td>			{/if}			<td class="columnMessage columnText">{@$entry->getFormattedMessage()}</td>			<td class="columnTime columnText">{@$entry->time|shorttime}</td>			{if			$additionalColumns[$entry->entryID]|isset}{@$additionalColumns[$entry->entryID]}{/if}		</tr>		{/foreach}	</tbody></table></div><div class="contentFooter">{@$pagesOutput} {if$this->user->getPermission('mod.shoutbox.canDeleteEntry') && $pages > 1|| $additionalLargeButtons|isset}<div class="largeButtons"><ul>	{if $this->user->getPermission('mod.shoutbox.canDeleteEntry') && $pages	> 1}	<li><a		href="index.php?action=ShoutboxEntryMarkAll&amp;t={@SECURITY_TOKEN}{@SID_ARG_2ND}"><img		src="{icon}shoutboxEntryMarkAllM.png{/icon}" alt="" /> <span>{lang}wcf.shoutbox.entry.button.markAll{/lang}</span></a></li>	{/if} {if $additionalLargeButtons|isset}{@$additionalLargeButtons}{/if}</ul></div>{/if}<div id="shoutboxEntryEditMarked" class="optionButtons"></div></div>{else}<div class="border content"><div class="container-1">{lang}wcf.shoutbox.entry.archives.count.noEntries{/lang}</div></div>{/if}</div>{include file='footer' sandbox=false}</body></html>