		<h1 id="titre">{_T string="Logs"}</h1>
		<div class="button-container">
			<div class="button-link button-flush-logs">
				<a href="history.php?reset=1">{_T string="Flush the logs"}</a>
			</div>
		</div>
		<table id="listing">
			<thead>
				<tr>
					<td colspan="6" class="right">
						<form action="history.php" method="get" id="historyform">
							<span>
								<label for="nbshow">{_T string="Records per page:"}</label>
								<select name="nbshow" id="nbshow">
									{html_options options=$nbshow_options selected=$numrows}
								</select>
								<noscript> <span><input type="submit" value="{_T string="Change"}" /></span></noscript>
							</span>
						</form>
					</td>
				</tr>
				<tr>
					<th class="listing">#</th>
					<th class="listing left">
						<a href="history.php?tri=date_log" class="listing">
							{_T string="Date"}
							{if $history->orderby eq "date_log"}
								{if $history->getDirection() eq "DESC"}
							<img src="{$template_subdir}images/down.png" width="10" height="6" alt="{_T string="Ascendent"}"/>
								{else}
							<img src="{$template_subdir}images/up.png" width="10" height="6" alt="{_T string="Descendant"}"/>
								{/if}
							{/if}
						</a>
					</th>
					<th class="listing left">
						<a href="history.php?tri=ip_log" class="listing">
							{_T string="IP"}
							{if $history->orderby eq "ip_log"}
								{if $history->getDirection() eq "DESC"}
							<img src="{$template_subdir}images/down.png" width="10" height="6" alt="{_T string="Ascendent"}"/>
								{else}
							<img src="{$template_subdir}images/up.png" width="10" height="6" alt="{_T string="Descendant"}"/>
								{/if}
							{/if}
						</a>
					</th>
					<th class="listing left">
						<a href="history.php?tri=adh_log" class="listing">
							{_T string="User"}
							{if $history->orderby eq "adh_log"}
								{if $history->getDirection() eq "DESC"}
							<img src="{$template_subdir}images/down.png" width="10" height="6" alt="{_T string="Ascendent"}"/>
								{else}
							<img src="{$template_subdir}images/up.png" width="10" height="6" alt="{_T string="Descendant"}"/>
								{/if}
							{/if}
						</a>
					</th>
					<th class="listing left">
						<a href="history.php?tri=action_log" class="listing">
							{_T string="Action"}
							{if $history->orderby eq "action_log"}
								{if $history->getDirection() eq "DESC"}
							<img src="{$template_subdir}images/down.png" width="10" height="6" alt="{_T string="Ascendent"}"/>
								{else}
							<img src="{$template_subdir}images/up.png" width="10" height="6" alt="{_T string="Descendant"}"/>
								{/if}
							{/if}
						</a>
					</th>
					<th class="listing left">
						<a href="history.php?tri=text_log" class="listing">
							{_T string="Description"}
							{if $history->orderby eq "text_log"}
								{if $history->getDirection() eq "DESC"}
							<img src="{$template_subdir}images/down.png" width="10" height="6" alt="{_T string="Ascendent"}"/>
								{else}
							<img src="{$template_subdir}images/up.png" width="10" height="6" alt="{_T string="Descendant"}"/>
								{/if}
							{/if}
						</a>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="6" class="center">
						{_T string="Pages:"}<br/>
						<ul class="pages">{$pagination}</ul>
					</td>
				</tr>
			</tfoot>
			<tbody>
{foreach from=$logs item=log name=eachlog}
				<tr class="cotis-never">
					<td width="15">{$smarty.foreach.eachlog.iteration}</td>
					<td valign="top" class="nowrap">{$log.date_log|date_format:"%a %d/%m/%Y - %R"}</td>
					<td valign="top" class="nowrap">{$log.ip_log}</td>
					<td valign="top">{$log.adh_log}</td>
					<td valign="top">{_T string=$log.action_log}</td>
					<td valign="top">{$log.text_log}<br/>{$log.sql_log|escape:"htmlall"}</td>
				</tr>
{foreachelse}
				<tr><td colspan="6" class="emptylist">{_T string="logs are empty"}</td></tr>
{/foreach}
			</tbody>
		</table>
		{literal}
		<script type="text/javascript">
			//<![CDATA[
				$('#nbshow').change(function() {
					this.form.submit();
				});
			//]]>
		</script>
		{/literal}