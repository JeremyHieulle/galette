		<h1 id="titre">{_T("Management of contributions")}</h1>
		<form action="gestion_contributions.php" method="get" id="filtre">
		<div id="listfilter">
			<label for="contrib_filter_1">{_T("Show contributions since")}</label>&nbsp;
			<input type="text" name="contrib_filter_1" id="contrib_filter_1" maxlength="10" size="10" value="{$smarty.session.filtre_date_cotis_1}"/>
			<label for="contrib_filter_2">{_T("until")}</label>&nbsp;
			<input type="text" name="contrib_filter_2" id="contrib_filter_2" maxlength="10" size="10" value="{$smarty.session.filtre_date_cotis_2}"/>
			<input type="submit" class="submit inline" value="{_T("Filter")}"/>
		</div>
		<table class="infoline">
			<tr>
				<td class="left">{$nb_contributions} {if $nb_contributions != 1}{_T("contributions")}{else}{_T("contribution")}{/if}</td>
                                <td class="center">
					<label for="nbshow">{_T("Show:")}</label>
					<select name="nbshow" id="nbshow">
						{html_options options=$nbshow_options selected=$numrows}
					</select>
					<noscript> <span><input type="submit" value="{_T("Change")}" /></span></noscript>
				</td>
				<td class="right">{_T("Pages:")}
					<span class="pagelink">
					{section name="pageLoop" start=1 loop=$nb_pages+1}
						{if $smarty.section.pageLoop.index eq $page}
							{$smarty.section.pageLoop.index}
						{else}
							<a href="gestion_contributions.php?nbshow={$smarty.get.nbshow}&amp;page={$smarty.section.pageLoop.index}">{$smarty.section.pageLoop.index}</a>
						{/if}
					{/section}
					</span>
				</td>
			</tr>
		</table>
		</form>
		<table id="listing">
			<thead>
				<tr>
					<th class="listing" id="id_row">#</th>
					<th class="listing left date_row">
						<a href="gestion_contributions.php?tri=0" class="listing">{_T("Date")}
						{if $smarty.session.tri_cotis eq 0}
						{if $smarty.session.tri_cotis_sens eq 0}
						<img src="{$template_subdir}images/down.png" width="10" height="6" alt=""/>
						{else}
						<img src="{$template_subdir}images/up.png" width="10" height="6" alt=""/>
						{/if}
						{/if}
						</a>
					</th>
					<th class="listing left date_row"> {_T("Begin.")}</th>
					<th class="listing left date_row"> {_T("End")}</th>
{if $smarty.session.admin_status eq 1}
					<th class="listing left">
						<a href="gestion_contributions.php?tri=1" class="listing">{_T("Member")}
						{if $smarty.session.tri_cotis eq 1}
						{if $smarty.session.tri_cotis_sens eq 0}
						<img src="{$template_subdir}images/down.png" width="10" height="6" alt=""/>
						{else}
						<img src="{$template_subdir}images/up.png" width="10" height="6" alt=""/>
						{/if}
						{/if}
						</a>
					</th>
{/if}
					<th class="listing left">
						<a href="gestion_contributions.php?tri=2" class="listing">{_T("Type")}
						{if $smarty.session.tri_cotis eq 2}
						{if $smarty.session.tri_cotis_sens eq 0}
						<img src="{$template_subdir}images/down.png" width="10" height="6" alt=""/>
						{else}
						<img src="{$template_subdir}images/up.png" width="10" height="6" alt=""/>
						{/if}
						{/if}
						</a>
					</th>
					<th class="listing left">
						<a href="gestion_contributions.php?tri=3" class="listing">{_T("Amount")}
						{if $smarty.session.tri_cotis eq 3}
						{if $smarty.session.tri_cotis_sens eq 0}
						<img src="{$template_subdir}images/down.png" width="10" height="6" alt=""/>
						{else}
						<img src="{$template_subdir}images/up.png" width="10" height="6" alt=""/>
						{/if}
						{/if}
						</a>
					</th>
					<th class="listing left">
						<a href="gestion_contributions.php?tri=4" class="listing">{_T("Duration")}
						{if $smarty.session.tri_cotis eq 4}
						{if $smarty.session.tri_cotis_sens eq 0}
						<img src="{$template_subdir}images/down.png" width="10" height="6" alt=""/>
						{else}
						<img src="{$template_subdir}images/up.png" width="10" height="6" alt=""/>
						{/if}
						{/if}
						</a>
					</th>
{if $smarty.session.admin_status eq 1}
					<th class="listing nowrap actions_row">{_T("Actions")}</th>
{/if}
				</tr>
			</thead>
			<tbody>
{foreach from=$contributions item=contribution key=ordre}
				<tr>
					<td class="{$contribution.class} center nowrap">{$ordre}</td>
					<td class="{$contribution.class} nowrap">{$contribution.date_enreg}</td>
					<td class="{$contribution.class} nowrap">{$contribution.date_debut}</td>
					<td class="{$contribution.class} nowrap">{$contribution.date_fin}</td>
{if $smarty.session.admin_status eq 1}
					<td class="{$contribution.class}">
{if $smarty.session.filtre_cotis_adh eq ""}
						<a href="gestion_contributions.php?id_adh={$contribution.id_adh}">
							{$contribution.nom} {$contribution.prenom}
						</a>
{else}
						<a href="voir_adherent.php?id_adh={$contribution.id_adh}">
							{$contribution.nom} {$contribution.prenom}
						</a>
{/if}
					</td>
{/if}
					<td class="{$contribution.class}">{$contribution.libelle_type_cotis}</td>
					<td class="{$contribution.class} nowrap">{$contribution.montant_cotis}</td>
					<td class="{$contribution.class} nowrap">{$contribution.duree_mois_cotis}</td>
{if $smarty.session.admin_status eq 1}
					<td class="{$contribution.class} center nowrap">
						<a href="ajouter_contribution.php?id_cotis={$contribution.id_cotis}"><img src="{$template_subdir}images/icon-edit.png" alt="{_T("[mod]")}" width="16" height="16"/></a>
						<a onclick="return confirm('{_T("Do you really want to delete this contribution of the database ?")|escape:"javascript"}')" href="gestion_contributions.php?sup={$contribution.id_cotis}"><img src="{$template_subdir}images/icon-trash.png" alt="{_T("[del]")}" width="16" height="16"/></a>
					</td>
{/if}
				</tr>
{foreachelse}
{if $smarty.session.admin_status eq 1}
				<tr><td colspan="9" class="emptylist">{_T("no contribution")}</td></tr>
{else}
				<tr><td colspan="7" class="emptylist">{_T("no contribution")}</td></tr>
{/if}
{/foreach}
			</tbody>
		</table>
		<div class="infoline2 right">
			{_T("Pages:")}
			<span class="pagelink">
			{section name="pageLoop" start=1 loop=$nb_pages+1}
			{if $smarty.section.pageLoop.index eq $page}
			{$smarty.section.pageLoop.index}
			{else}
			<a href="gestion_contributions.php?nbshow={$smarty.get.nbshow}&amp;page={$smarty.section.pageLoop.index}">{$smarty.section.pageLoop.index}</a>
			{/if}
			{/section}
			</span>
		</div>
{if $smarty.session.filtre_cotis_adh!=""}
		<br/>
		<div align="center">
			<table class="{$statut_class}">
				<tr>
					<td>{$statut_cotis}</td>
				</tr>
			</table>
		<br/>
{if $smarty.session.admin_status eq 1}
		<br/>
			<a href="voir_adherent.php?id_adh={$smarty.session.filtre_cotis_adh}">{_T("[ See member profile ]")}</a>
			&nbsp;&nbsp;&nbsp;
			<a href="ajouter_contribution.php?&amp;id_adh={$smarty.session.filtre_cotis_adh}">{_T("[ Add a contribution ]")}</a>
{/if}
		</div>
{/if}
		{literal}
		<script type="text/javascript">
			//<![CDATA[
				$('#nbshow').change(function() {
					this.form.submit();
				});
			//]]>
		</script>
		{/literal}