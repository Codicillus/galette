<? 

/* gestion_adherents.php
 * - R�capitulatif des adh�rents
 * Copyright (c) 2003 Fr�d�ric Jaqcuot
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
 
	include("includes/config.inc.php"); 
	include(WEB_ROOT."includes/database.inc.php");
	include(WEB_ROOT."includes/session.inc.php");
	include(WEB_ROOT."includes/functions.inc.php"); 
        include(WEB_ROOT."includes/i18n.inc.php");
	include(WEB_ROOT."includes/smarty.inc.php");
	
	if ($_SESSION["logged_status"]==0) 
		header("location: index.php");
	if ($_SESSION["admin_status"]==0) 
		header("location: voir_adherent.php");
	
	// Filters
	$page = 1;
	if (isset($_GET["page"]))
		$page = $_GET["page"];

	if (isset($_GET["filtre"]))
		if (is_numeric($_GET["filtre"]))
			$_SESSION["filtre_adh"]=$_GET["filtre"];

	if (isset($_GET["filtre_2"]))
		if (is_numeric($_GET["filtre_2"]))
			$_SESSION["filtre_adh_2"]=$_GET["filtre_2"];
	
	// Sorting
	if (isset($_GET["tri"]))
		if (is_numeric($_GET["tri"]))
		{
			if ($_SESSION["tri_adh"]==$_GET["tri"])
				$_SESSION["tri_adh_sens"]=($_SESSION["tri_adh_sens"]+1)%2;
			else
			{
				$_SESSION["tri_adh"]=$_GET["tri"];
				$_SESSION["tri_adh_sens"]=0;
			}
		}
	
	if (isset($_GET["sup"]))
	{
		if (is_numeric($_GET["sup"]))
		{
			$requetesup = "SELECT nom_adh, prenom_adh FROM ".PREFIX_DB."adherents WHERE id_adh=".$DB->qstr($_GET["sup"]);
			$resultat = $DB->Execute($requetesup);
			if (!$resultat->EOF)
			{
				// supression record adh�rent
				$requetesup = "DELETE FROM ".PREFIX_DB."adherents 
						WHERE id_adh=".$DB->qstr($_GET["sup"]); 
				$DB->Execute($requetesup); 		
	
				// suppression de l'eventuelle photo
				@unlink(WEB_ROOT . "photos/".$id_adh.".jpg");
				@unlink(WEB_ROOT . "photos/".$id_adh.".gif");
				@unlink(WEB_ROOT . "photos/".$id_adh.".jpg");
				@unlink(WEB_ROOT . "photos/tn_".$id_adh.".jpg");
				@unlink(WEB_ROOT . "photos/tn_".$id_adh.".gif");
				@unlink(WEB_ROOT . "photos/tn_".$id_adh.".jpg");
			
				// suppression records cotisations
				$requetesup = "DELETE FROM ".PREFIX_DB."cotisations 
						WHERE id_adh=" . $DB->qstr($_GET["sup"]); 
				$DB->Execute($requetesup);

				// erase custom fields
				$requetesup = "DELETE FROM ".PREFIX_DB."adh_info
						WHERE id_adh=".$DB->qstr($_GET["sup"]);
				$DB->Execute($requetesup);
				
				dblog(_T("Delete the member card (and dues)")." ".strtoupper($resultat->fields[0])." ".$resultat->fields[1], $requetesup);
			}
			$resultat->Close();
 		}
	}

	// selection des adherents et application filtre / tri
	$requete[0] = "SELECT id_adh, nom_adh, prenom_adh, pseudo_adh, activite_adh,
		       libelle_statut, bool_exempt_adh, titre_adh, email_adh, bool_admin_adh, date_echeance
		       FROM ".PREFIX_DB."adherents, ".PREFIX_DB."statuts
		       WHERE ".PREFIX_DB."adherents.id_statut=".PREFIX_DB."statuts.id_statut ";
	$requete[1] = "SELECT count(id_adh)
		       FROM ".PREFIX_DB."adherents 
		       WHERE 1=1 ";
								
	// filtre d'affichage des adherents activ�s/desactiv�s
	if ($_SESSION["filtre_adh_2"]=="1")
	{
		$requete[0] .= "AND ".PREFIX_DB."adherents.activite_adh='1' ";
		$requete[1] .= "AND ".PREFIX_DB."adherents.activite_adh='1' ";
	}
	elseif ($_SESSION["filtre_adh_2"]=="2")
	{
		$requete[0] .= "AND ".PREFIX_DB."adherents.activite_adh='0' ";
		$requete[1] .= "AND ".PREFIX_DB."adherents.activite_adh='0' ";
	}

	// filtre d'affichage des adherents retardataires
	if ($_SESSION["filtre_adh"]=="2")
	{
		$requete[0] .= "AND date_echeance < ".$DB->DBDate(time())." ";
		$requete[1] .= "AND date_echeance < ".$DB->DBDate(time())." ";
	}

	// filtre d'affichage des adherents � jour
	if ($_SESSION["filtre_adh"]=="3")
	{
		$requete[0] .= "AND (date_echeance > ".$DB->DBDate(time())." OR bool_exempt_adh='1') ";
		$requete[1] .= "AND (date_echeance > ".$DB->DBDate(time())." OR bool_exempt_adh='1') ";
	}

	// filtre d'affichage des adherents bientot a echeance
	if ($_SESSION["filtre_adh"]=="1")
	{
		$requete[0] .= "AND date_echeance > ".$DB->DBDate(time())."
			        AND date_echeance < ".$DB->OffsetDate(30)." ";
		$requete[1] .= "AND date_echeance > ".$DB->DBDate(time())."
			        AND date_echeance < ".$DB->OffsetDate(30)." ";
	}
	
	// phase de tri	
	if ($_SESSION["tri_adh_sens"]=="0")
		$tri_adh_sens_txt="ASC";
	else
		$tri_adh_sens_txt="DESC";

	$requete[0] .= "ORDER BY ";
	
	// tri par pseudo
	if ($_SESSION["tri_adh"]=="1")
		$requete[0] .= "pseudo_adh ".$tri_adh_sens_txt.",";
		
	// tri par statut
	elseif ($_SESSION["tri_adh"]=="2")
		$requete[0] .= "priorite_statut ".$tri_adh_sens_txt.",";

	// tri par echeance
	elseif ($_SESSION["tri_adh"]=="3")
		$requete[0] .= "bool_exempt_adh ".$tri_adh_sens_txt.", date_echeance ".$tri_adh_sens_txt.",";

	// defaut : tri par nom, prenom
	$requete[0] .= "nom_adh ".$tri_adh_sens_txt.", prenom_adh ".$tri_adh_sens_txt; 
	
	$resultat = &$DB->SelectLimit($requete[0],PREF_NUMROWS,($page-1)*PREF_NUMROWS);
	$nbadh = &$DB->Execute($requete[1]);

	if ($nbadh->fields[0]%PREF_NUMROWS==0) 
		$nbpages = intval($nbadh->fields[0]/PREF_NUMROWS);
	else 
		$nbpages = intval($nbadh->fields[0]/PREF_NUMROWS)+1;

	$compteur = 1+($page-1)*PREF_NUMROWS;
	if ($resultat->EOF)
	{
?>	
		<TR><TD colspan="6" class="emptylist"><? echo _T("no member"); ?></TD></TR>
<?
	}
	else while (!$resultat->EOF) 
	{ 
		// d�finition CSS pour adherent d�sactiv�
		if ($resultat->fields[4]=="1")
			$row_class = "actif";
		else
			$row_class = "inactif";
			
		// temps d'adh�sion
		if($resultat->fields[6])
		{
			$statut_cotis = _T("Freed of dues");
			$row_class .= " cotis-exempt";
		}
		else
		{
			if ($resultat->fields[10]=="")
			{
				$statut_cotis = _T("Never contributed");
				$row_class .= " cotis-never";
			}
			else
			{
				$date_fin = split("-",$resultat->fields[10]);
				$ts_date_fin = mktime(0,0,0,$date_fin[1],$date_fin[2],$date_fin[0]);
				$aujourdhui = time();
				
				$difference = intval(($ts_date_fin - $aujourdhui)/(3600*24));
				if ($difference==0)
				{
					$statut_cotis = _T("Last day!");
					$row_class .= " cotis-lastday";
				}
				elseif ($difference<0)
				{
					$statut_cotis = _T("Late of ").-$difference." "._T("days")." ("._T("since")." ".$date_fin[2]."/".$date_fin[1]."/".$date_fin[0].")";
					$row_class .= " cotis-late";
				}
				else
				{
					if ($difference!=1)
						$statut_cotis = $difference." "._T("days remaining")." ("._T("ending on")." ".$date_fin[2]."/".$date_fin[1]."/".$date_fin[0].")";
					else
						$statut_cotis = $difference." "._T("day remaining")." ("._T("ending on")." ".$date_fin[2]."/".$date_fin[1]."/".$date_fin[0].")";
					if ($difference < 30)
						$row_class .= " cotis-soon";
					else
						$row_class .= " cotis-ok";	
				}				
			}
		}
		$members[$compteur]["class"]=$row_class;
		$members[$compteur]["genre"]=$resultat->fields[7];
		$members[$compteur]["email"]=$resultat->fields[8];
		$members[$compteur]["admin"]=$resultat->fields[9];
		$members[$compteur]["nom"]=htmlentities(strtoupper($resultat->fields[1]),ENT_QUOTES);
		$members[$compteur]["prenom"]=htmlentities($resultat->fields[2], ENT_QUOTES);
		$members[$compteur]["id_adh"]=$resultat->fields[0];
		$members[$compteur]["pseudo"]=htmlentities($resultat->fields[3], ENT_QUOTES);
		$members[$compteur]["statut"]=_T($resultat->fields[5]);
		$members[$compteur]["statut_cotis"]=$statut_cotis;
		$compteur++;
		$resultat->MoveNext();
	} 
	$resultat->Close();
	
	$tpl->assign("members",$members);
	$tpl->assign("nb_members",count($members));
	$tpl->assign("nb_pages",$nbpages);
	$tpl->assign("page",$page);
	$tpl->assign('filtre_options', array(
			0 => _T("All members"),
			3 => _T("Members up to date"),
			1 => _T("Close expiries"),
			2 => _T("Latecomers")));
	$tpl->assign('filtre_2_options', array(
			1 => _T("All the accounts"),
			2 => _T("Inactive accounts")));
										
	$content = $tpl->fetch("gestion_adherents.tpl");
	$tpl->assign("content",$content);
	$tpl->display("page.tpl");
?>
