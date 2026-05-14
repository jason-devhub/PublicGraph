# Références Wikidata — organisations (imports)

Ce document relie des **organisations souvent citées** dans le discours public à leurs **items Wikidata** (`Q…`), pour préparer les imports (ex. `app:wikidata:sync-org-members --organization-qid=…`).

**Bonnes pratiques**

- Toujours ouvrir l’item sur Wikidata et vérifier `P31` (nature de l’objet) et les **sitelinks** Wikipédia avant un import en masse.
- Si plusieurs `Q…` portent un libellé proche, **ne retenir pour l’import membres (`P463`) que l’item qui décrit l’organisation**, pas un rapport, une édition d’événement ou une entrée de dictionnaire local.
- Les liens `https://www.wikidata.org/wiki/Q…` sont stables ; les libellés peuvent évoluer.

---

## Dîner du Siècle / Le Siècle (club, France)

| QID | Libellé (indicatif) | Commentaire |
|-----|---------------------|-------------|
| **Q3227220** | Le Siècle | Club parisien ; page fr.wikipedia « Le Siècle » pointe sur cet item. L’expression « dîner du Siècle » désigne en pratique le même club ; **pas d’item Wikidata distinct** trouvé sous ce libellé exact. |

- Wikipédia fr : [Le Siècle](https://fr.wikipedia.org/wiki/Le_Siècle)
- Wikidata : [Q3227220](https://www.wikidata.org/wiki/Q3227220)

---

## Groupe de Bilderberg (Bilderberg Conference / Group)

| QID | Libellé (indicatif) | Commentaire |
|-----|---------------------|-------------|
| **Q184937** | Bilderberg Group | Conférence / réseau informel **principal** (celui visé par la plupart des listes de participants). |
| **Q63344709** | Steering Committee of the Bilderberg Meetings | **Sous-organisation** (comité d’orientation). À utiliser seulement si les données WD ciblent explicitement ce comité (ex. `P463` vers ce QID). |
| *Q59799074, …* | éditions annuelles (ex. « 2018 Bilderberg Conference ») | **Événements** (instances d’une conférence donnée), **pas** l’organisation permanente — à ne pas confondre avec Q184937 pour une fiche « organisation » unique. |

- Wikidata : [Q184937](https://www.wikidata.org/wiki/Q184937), [Q63344709](https://www.wikidata.org/wiki/Q63344709)

---

## Forum économique mondial / World Economic Forum (WEF)

| QID | Libellé (indicatif) | Commentaire |
|-----|---------------------|-------------|
| **Q170418** | World Economic Forum | Organisation **à retenir** pour le WEF / Davos au sens institutionnel. |
| *Q114717230, Q114717231, …* | World Economic Forum Annual Meeting 20xx | **Réunions annuelles** (éditions), pas l’organisation mère. Utiles seulement pour des imports d’**événements** ou de participants liés à une année précise. |

- Wikidata : [Q170418](https://www.wikidata.org/wiki/Q170418)

---

## Franc-maçonnerie

### Concepts et articles-thèmes (hors loges / obédiences)

Il n’existe **pas un seul** item « la franc-maçonnerie mondiale » équivalent à une obédience unique : Wikidata distingue **concept global**, **articles de synthèse par pays**, et **obédiences** (souvent des items séparés).

| QID | Libellé (indicatif) | Commentaire |
|-----|---------------------|-------------|
| **Q41726** | freemasonry | Concept / ensemble d’organisations fraternelles — pertinent pour le **sens large** du terme. |
| **Q2585138** | Freemasonry in France | Article-thème **France** (vue d’ensemble), pas une obédience. |
| **Q2138816** | Regular Masonic jurisdictions | Notion juridictionnelle « maçonnerie régulière ». |
| **Q16238196** | Prince Hall Freemasonry | Branche historique aux États-Unis. |
| *Q3080461, Q3080471, Q3080480, Q3080484, Q3080485, …* | Freemasonry in Canada / Germany / Italy / Belgium / Spain, etc. | **Articles par pays** (synthèses). |
| **Q30025017** | Franc-maçonnerie | Entrée de **dictionnaire patrimonial local** (Rennes) — **ne pas** utiliser comme organisation pour `P463`. |
| **Q60964689** | franc-maçonnerie en Europe | Thème géographique. |

### Classes Wikidata utilisées pour l’inventaire

| Classe | QID | Libellé (fr) | Rôle |
|--------|-----|--------------|------|
| Grand Lodge / obédience maçonnique | **Q1378781** | [obédience maçonnique](https://www.wikidata.org/wiki/Q1378781) | Obédiences, grandes loges symboliques, etc. |
| Loge maçonnique | **Q1454597** | [loge maçonnique](https://www.wikidata.org/wiki/Q1454597) | Unité de base (loge nominative). |

**Méthode (SPARQL, WDQS)** : tous les items dont le `P31` (instance of) est la classe elle-même **ou une sous-classe** (`P279*`) de l’une des deux classes ci-dessus :

```sparql
SELECT DISTINCT ?item WHERE {
  {
    ?item wdt:P31 ?c .
    ?c wdt:P279* wd:Q1454597 .
  }
  UNION
  {
    ?item wdt:P31 ?c .
    ?c wdt:P279* wd:Q1378781 .
  }
}
```

**Exclusions volontaires** : les **temples / bâtiments** (`P31` = [Q1454583](https://www.wikidata.org/wiki/Q1454583) « Masonic building ») ne sont pas dans cette liste ; ce sont des lieux, pas des loges ou obédiences comme organisations.

**Limite** : une obédience ou loge n’apparaît ici **que si** Wikidata la classe (directement ou par sous-classe) en **loge maçonnique** ou **obédience maçonnique**. Des entités réelles mais typées autrement sur WD (ex. seulement « association loi 1901 » sans `P31` vers ces classes) **ne figurent pas** dans cet inventaire.

**Instantané** : **691** items extraits le **2026-05-14** (le nombre peut évoluer sur Wikidata). Copie tabulaire TSV : [`data/masonic_lodges_and_obediences_wikidata.tsv`](data/masonic_lodges_and_obediences_wikidata.tsv).

### Liste des QID — loges (`loge`) et obédiences (`obédience`)

| QID | Type | Libellé (fr ou en) |
| --- | --- | --- |
| [Q100155040](https://www.wikidata.org/wiki/Q100155040) | loge | Loja Maçônica Amor e Concórdia |
| [Q100257027](https://www.wikidata.org/wiki/Q100257027) | loge |  |
| [Q100433461](https://www.wikidata.org/wiki/Q100433461) | obédience | Provinciale Grootloge Suriname |
| [Q100433520](https://www.wikidata.org/wiki/Q100433520) | loge | Loge De Gouden Driehoek |
| [Q100433670](https://www.wikidata.org/wiki/Q100433670) | loge | Loge De Stanfaste |
| [Q100458057](https://www.wikidata.org/wiki/Q100458057) | loge | Loge La Zélée |
| [Q100605053](https://www.wikidata.org/wiki/Q100605053) | loge | Loge L'Union |
| [Q100605070](https://www.wikidata.org/wiki/Q100605070) | loge | Loge Saint Jean de la Réunion |
| [Q100697465](https://www.wikidata.org/wiki/Q100697465) | loge | Loge La Solitaire |
| [Q100698631](https://www.wikidata.org/wiki/Q100698631) | loge | Loge De Standvastigheid |
| [Q100698704](https://www.wikidata.org/wiki/Q100698704) | loge | Loge Cura et Vigilantia |
| [Q100708706](https://www.wikidata.org/wiki/Q100708706) | loge | Lodge Obadiah |
| [Q101444484](https://www.wikidata.org/wiki/Q101444484) | loge | Loge zur Aufgehenden Morgenröthe |
| [Q10285697](https://www.wikidata.org/wiki/Q10285697) | loge | Fraternidade Cearense |
| [Q10290955](https://www.wikidata.org/wiki/Q10290955) | obédience | Grande Loge féminine du Portugal |
| [Q10290983](https://www.wikidata.org/wiki/Q10290983) | loge | Grande Oriente do Brasil do Estado do Ceará |
| [Q10372582](https://www.wikidata.org/wiki/Q10372582) | obédience | Sociedade Amor da Pátria |
| [Q103967318](https://www.wikidata.org/wiki/Q103967318) | loge | Grande Benemérita Loja Simbólica Amazonas N.º 2 |
| [Q104002132](https://www.wikidata.org/wiki/Q104002132) | loge | Loja Esperança e Porvir |
| [Q1041489](https://www.wikidata.org/wiki/Q1041489) | loge | Carl zur Eintracht |
| [Q104154822](https://www.wikidata.org/wiki/Q104154822) | loge | Loja Maçônica Firmeza e Humanidade |
| [Q104162979](https://www.wikidata.org/wiki/Q104162979) | loge | Lodge Zur Weltkugel, Lübeck |
| [Q104891413](https://www.wikidata.org/wiki/Q104891413) | loge |  |
| [Q105208216](https://www.wikidata.org/wiki/Q105208216) | loge | loge Arts et Commerce |
| [Q105530754](https://www.wikidata.org/wiki/Q105530754) | loge | Old Ashville Masonic Lodge and Mattie Lou Teague Crow Museum |
| [Q105750549](https://www.wikidata.org/wiki/Q105750549) | obédience | Grand Lodge of New Jersey |
| [Q10659397](https://www.wikidata.org/wiki/Q10659397) | loge |  |
| [Q106606945](https://www.wikidata.org/wiki/Q106606945) | loge | Loge maçonnique la concorde |
| [Q106766892](https://www.wikidata.org/wiki/Q106766892) | loge |  |
| [Q106798046](https://www.wikidata.org/wiki/Q106798046) | loge | Szczęśliwe Oswobodzenie |
| [Q106803574](https://www.wikidata.org/wiki/Q106803574) | loge |  |
| [Q106804752](https://www.wikidata.org/wiki/Q106804752) | loge |  |
| [Q106843783](https://www.wikidata.org/wiki/Q106843783) | loge |  |
| [Q10685019](https://www.wikidata.org/wiki/Q10685019) | obédience | Svenska frimurare orden |
| [Q106867366](https://www.wikidata.org/wiki/Q106867366) | loge |  |
| [Q107630743](https://www.wikidata.org/wiki/Q107630743) | loge | Jephtha Masonic Lodge No. 494 |
| [Q107671306](https://www.wikidata.org/wiki/Q107671306) | loge |  |
| [Q108001218](https://www.wikidata.org/wiki/Q108001218) | loge | Grand Lodge of Belarus |
| [Q108315988](https://www.wikidata.org/wiki/Q108315988) | loge | Doskonała Jedność |
| [Q108316464](https://www.wikidata.org/wiki/Q108316464) | loge |  |
| [Q108370382](https://www.wikidata.org/wiki/Q108370382) | loge |  |
| [Q108818201](https://www.wikidata.org/wiki/Q108818201) | loge |  |
| [Q108860566](https://www.wikidata.org/wiki/Q108860566) | loge | La Clémente Amitié |
| [Q108934070](https://www.wikidata.org/wiki/Q108934070) | loge | Isaac Newton University Lodge |
| [Q109643890](https://www.wikidata.org/wiki/Q109643890) | loge |  |
| [Q110020679](https://www.wikidata.org/wiki/Q110020679) | loge |  |
| [Q1106935](https://www.wikidata.org/wiki/Q1106935) | obédience | Grand Orient du Brésil |
| [Q110751349](https://www.wikidata.org/wiki/Q110751349) | loge |  |
| [Q11105088](https://www.wikidata.org/wiki/Q11105088) | obédience |  |
| [Q111846323](https://www.wikidata.org/wiki/Q111846323) | loge |  |
| [Q111983615](https://www.wikidata.org/wiki/Q111983615) | loge | Johannis-Freimaurerloge “Zum Morgenstern” e.V. |
| [Q113006040](https://www.wikidata.org/wiki/Q113006040) | loge | Loge Szechwan nº 4 |
| [Q113066202](https://www.wikidata.org/wiki/Q113066202) | loge | Big Bear Masonic Lodge No. 617 |
| [Q113408226](https://www.wikidata.org/wiki/Q113408226) | obédience |  |
| [Q113480343](https://www.wikidata.org/wiki/Q113480343) | obédience | Grand Lodge of Illinois |
| [Q113550464](https://www.wikidata.org/wiki/Q113550464) | loge |  |
| [Q113633473](https://www.wikidata.org/wiki/Q113633473) | loge | Loge Zur Wahrheit und Freundschaft |
| [Q114497822](https://www.wikidata.org/wiki/Q114497822) | loge |  |
| [Q114497922](https://www.wikidata.org/wiki/Q114497922) | loge |  |
| [Q114839735](https://www.wikidata.org/wiki/Q114839735) | loge |  |
| [Q1155329](https://www.wikidata.org/wiki/Q1155329) | obédience | Grande Loge du Chili |
| [Q1155337](https://www.wikidata.org/wiki/Q1155337) | obédience | Grand Orient Ibérique |
| [Q1156404](https://www.wikidata.org/wiki/Q1156404) | obédience | Grande Loge constutionnelle du Pérou |
| [Q115641807](https://www.wikidata.org/wiki/Q115641807) | loge | Bexhill Masonic Temple |
| [Q116185655](https://www.wikidata.org/wiki/Q116185655) | loge |  |
| [Q116245989](https://www.wikidata.org/wiki/Q116245989) | loge | Lodge "To the Three Roses", Hamburg |
| [Q116254166](https://www.wikidata.org/wiki/Q116254166) | loge | Lodge "Apollo", St. Petersburg |
| [Q116259687](https://www.wikidata.org/wiki/Q116259687) | loge |  |
| [Q116622744](https://www.wikidata.org/wiki/Q116622744) | loge | Hiram Abif 80 Lodge |
| [Q116642903](https://www.wikidata.org/wiki/Q116642903) | loge | loge Astrée |
| [Q116875171](https://www.wikidata.org/wiki/Q116875171) | loge |  |
| [Q117039064](https://www.wikidata.org/wiki/Q117039064) | loge | loge Akademos |
| [Q117039202](https://www.wikidata.org/wiki/Q117039202) | loge | loge Athéna |
| [Q117039211](https://www.wikidata.org/wiki/Q117039211) | loge | loge Montmorency-Luxembourg |
| [Q117042549](https://www.wikidata.org/wiki/Q117042549) | loge | L'Arche de paix |
| [Q117047809](https://www.wikidata.org/wiki/Q117047809) | loge | Paix et Liberté |
| [Q117048468](https://www.wikidata.org/wiki/Q117048468) | loge | Bienfaisance |
| [Q117048574](https://www.wikidata.org/wiki/Q117048574) | loge | Justice et Paix |
| [Q117048584](https://www.wikidata.org/wiki/Q117048584) | loge | loge Kaducée |
| [Q117256322](https://www.wikidata.org/wiki/Q117256322) | loge |  |
| [Q11737130](https://www.wikidata.org/wiki/Q11737130) | loge |  |
| [Q11762015](https://www.wikidata.org/wiki/Q11762015) | loge |  |
| [Q117804352](https://www.wikidata.org/wiki/Q117804352) | loge |  |
| [Q117804439](https://www.wikidata.org/wiki/Q117804439) | loge |  |
| [Q117804466](https://www.wikidata.org/wiki/Q117804466) | loge |  |
| [Q117804502](https://www.wikidata.org/wiki/Q117804502) | loge |  |
| [Q117804571](https://www.wikidata.org/wiki/Q117804571) | loge |  |
| [Q11811267](https://www.wikidata.org/wiki/Q11811267) | loge |  |
| [Q11827724](https://www.wikidata.org/wiki/Q11827724) | loge | Prometea |
| [Q11829234](https://www.wikidata.org/wiki/Q11829234) | loge |  |
| [Q118423853](https://www.wikidata.org/wiki/Q118423853) | loge |  |
| [Q118732600](https://www.wikidata.org/wiki/Q118732600) | loge |  |
| [Q11924600](https://www.wikidata.org/wiki/Q11924600) | obédience | Grand Orient of Catalonia |
| [Q119458679](https://www.wikidata.org/wiki/Q119458679) | loge | Urania zur Eintracht |
| [Q12002470](https://www.wikidata.org/wiki/Q12002470) | loge |  |
| [Q12002471](https://www.wikidata.org/wiki/Q12002471) | loge |  |
| [Q12002472](https://www.wikidata.org/wiki/Q12002472) | loge |  |
| [Q12002474](https://www.wikidata.org/wiki/Q12002474) | loge |  |
| [Q12002476](https://www.wikidata.org/wiki/Q12002476) | loge |  |
| [Q12002477](https://www.wikidata.org/wiki/Q12002477) | loge |  |
| [Q12002478](https://www.wikidata.org/wiki/Q12002478) | loge |  |
| [Q12002479](https://www.wikidata.org/wiki/Q12002479) | loge |  |
| [Q12002481](https://www.wikidata.org/wiki/Q12002481) | loge |  |
| [Q12002482](https://www.wikidata.org/wiki/Q12002482) | loge |  |
| [Q12002483](https://www.wikidata.org/wiki/Q12002483) | loge |  |
| [Q12002506](https://www.wikidata.org/wiki/Q12002506) | loge |  |
| [Q12002507](https://www.wikidata.org/wiki/Q12002507) | loge |  |
| [Q12002508](https://www.wikidata.org/wiki/Q12002508) | loge |  |
| [Q12002509](https://www.wikidata.org/wiki/Q12002509) | loge |  |
| [Q12002510](https://www.wikidata.org/wiki/Q12002510) | loge |  |
| [Q12002511](https://www.wikidata.org/wiki/Q12002511) | loge |  |
| [Q12002512](https://www.wikidata.org/wiki/Q12002512) | loge |  |
| [Q12002513](https://www.wikidata.org/wiki/Q12002513) | loge |  |
| [Q12002515](https://www.wikidata.org/wiki/Q12002515) | loge |  |
| [Q12002516](https://www.wikidata.org/wiki/Q12002516) | loge |  |
| [Q12002517](https://www.wikidata.org/wiki/Q12002517) | loge |  |
| [Q12002518](https://www.wikidata.org/wiki/Q12002518) | loge |  |
| [Q12002519](https://www.wikidata.org/wiki/Q12002519) | loge |  |
| [Q12002520](https://www.wikidata.org/wiki/Q12002520) | loge |  |
| [Q12002521](https://www.wikidata.org/wiki/Q12002521) | loge |  |
| [Q12002523](https://www.wikidata.org/wiki/Q12002523) | loge |  |
| [Q12002525](https://www.wikidata.org/wiki/Q12002525) | loge |  |
| [Q12002526](https://www.wikidata.org/wiki/Q12002526) | loge |  |
| [Q12002527](https://www.wikidata.org/wiki/Q12002527) | loge |  |
| [Q12002528](https://www.wikidata.org/wiki/Q12002528) | loge |  |
| [Q12002529](https://www.wikidata.org/wiki/Q12002529) | loge |  |
| [Q12002530](https://www.wikidata.org/wiki/Q12002530) | loge |  |
| [Q12002531](https://www.wikidata.org/wiki/Q12002531) | loge |  |
| [Q12002532](https://www.wikidata.org/wiki/Q12002532) | loge |  |
| [Q12002533](https://www.wikidata.org/wiki/Q12002533) | loge |  |
| [Q12002535](https://www.wikidata.org/wiki/Q12002535) | loge |  |
| [Q12002536](https://www.wikidata.org/wiki/Q12002536) | loge |  |
| [Q12002537](https://www.wikidata.org/wiki/Q12002537) | loge |  |
| [Q12002538](https://www.wikidata.org/wiki/Q12002538) | loge |  |
| [Q12002539](https://www.wikidata.org/wiki/Q12002539) | loge |  |
| [Q12002540](https://www.wikidata.org/wiki/Q12002540) | loge |  |
| [Q12002541](https://www.wikidata.org/wiki/Q12002541) | loge |  |
| [Q12002542](https://www.wikidata.org/wiki/Q12002542) | loge |  |
| [Q12002543](https://www.wikidata.org/wiki/Q12002543) | loge |  |
| [Q12002544](https://www.wikidata.org/wiki/Q12002544) | loge |  |
| [Q12002545](https://www.wikidata.org/wiki/Q12002545) | loge |  |
| [Q12002547](https://www.wikidata.org/wiki/Q12002547) | loge |  |
| [Q120936868](https://www.wikidata.org/wiki/Q120936868) | loge | Loja maçônica de Poços de Caldas |
| [Q122152921](https://www.wikidata.org/wiki/Q122152921) | loge |  |
| [Q12258508](https://www.wikidata.org/wiki/Q12258508) | loge |  |
| [Q12262743](https://www.wikidata.org/wiki/Q12262743) | loge |  |
| [Q12267685](https://www.wikidata.org/wiki/Q12267685) | loge |  |
| [Q122736816](https://www.wikidata.org/wiki/Q122736816) | loge\|obédience | Grand Lodge of Washington |
| [Q123172911](https://www.wikidata.org/wiki/Q123172911) | loge | Druids' Hall, Maylands |
| [Q12334776](https://www.wikidata.org/wiki/Q12334776) | loge |  |
| [Q12336795](https://www.wikidata.org/wiki/Q12336795) | loge | St. Martin |
| [Q123513642](https://www.wikidata.org/wiki/Q123513642) | loge |  |
| [Q123555198](https://www.wikidata.org/wiki/Q123555198) | loge |  |
| [Q12361487](https://www.wikidata.org/wiki/Q12361487) | loge | Grand Lodge of Estonia |
| [Q123680111](https://www.wikidata.org/wiki/Q123680111) | loge |  |
| [Q123692330](https://www.wikidata.org/wiki/Q123692330) | loge | Sirius Lodge |
| [Q123693109](https://www.wikidata.org/wiki/Q123693109) | obédience | Grande Loge de Grèce |
| [Q123735146](https://www.wikidata.org/wiki/Q123735146) | loge | Eastern Star Lodge, No. 227, F. and A. M. |
| [Q124050821](https://www.wikidata.org/wiki/Q124050821) | loge | Vrijmetselaarsloge L'Age d'Or |
| [Q124333849](https://www.wikidata.org/wiki/Q124333849) | loge | Loge Scaldis |
| [Q125176795](https://www.wikidata.org/wiki/Q125176795) | loge | Zion Loge Hannover |
| [Q125398355](https://www.wikidata.org/wiki/Q125398355) | obédience | Le Droit Humain – Fédération croate |
| [Q125449879](https://www.wikidata.org/wiki/Q125449879) | loge | Boyer Lodge No. 1 |
| [Q125997589](https://www.wikidata.org/wiki/Q125997589) | loge | Lodge "Zur Ceder" (Hanover) |
| [Q126172901](https://www.wikidata.org/wiki/Q126172901) | obédience |  |
| [Q126924910](https://www.wikidata.org/wiki/Q126924910) | loge | Les Fils d'Isis |
| [Q126926222](https://www.wikidata.org/wiki/Q126926222) | loge | Argyle Masonic Lodge No. 178 |
| [Q126943904](https://www.wikidata.org/wiki/Q126943904) | loge | White Mountain Lodge No. 3 |
| [Q1270330](https://www.wikidata.org/wiki/Q1270330) | obédience | Ordre danois des francs-maçons |
| [Q12783924](https://www.wikidata.org/wiki/Q12783924) | loge | Absolom |
| [Q127910315](https://www.wikidata.org/wiki/Q127910315) | loge | Lodge "Alma an der Ostsee" |
| [Q12794530](https://www.wikidata.org/wiki/Q12794530) | loge | Savršeni Savez Lodge |
| [Q12795214](https://www.wikidata.org/wiki/Q12795214) | loge | Horn Lodge |
| [Q12799485](https://www.wikidata.org/wiki/Q12799485) | loge |  |
| [Q12799488](https://www.wikidata.org/wiki/Q12799488) | loge | The Goose and Gridiron Ale-House |
| [Q12799493](https://www.wikidata.org/wiki/Q12799493) | loge |  |
| [Q12804399](https://www.wikidata.org/wiki/Q12804399) | loge | Aux trois Coeurs |
| [Q12805364](https://www.wikidata.org/wiki/Q12805364) | loge | Grand Lodge of Argentina |
| [Q12805369](https://www.wikidata.org/wiki/Q12805369) | obédience | Grand Lodge of Croatia |
| [Q12805371](https://www.wikidata.org/wiki/Q12805371) | loge\|obédience | Grand Lodge of Cuba |
| [Q12805374](https://www.wikidata.org/wiki/Q12805374) | obédience | Grande Loge de Chine |
| [Q12805382](https://www.wikidata.org/wiki/Q12805382) | loge\|obédience | Grande Loge du Sénégal |
| [Q12805384](https://www.wikidata.org/wiki/Q12805384) | obédience | Grand Lodge of Slovenia |
| [Q12805491](https://www.wikidata.org/wiki/Q12805491) | loge\|obédience | Symbolic Grand Lodge of Hungary |
| [Q128799639](https://www.wikidata.org/wiki/Q128799639) | loge | Johannis-Loge Ferdinand zur Glückseligkeit |
| [Q128802174](https://www.wikidata.org/wiki/Q128802174) | loge | Friedrich zum Weißen Pferde |
| [Q130002376](https://www.wikidata.org/wiki/Q130002376) | loge |  |
| [Q130354757](https://www.wikidata.org/wiki/Q130354757) | loge | Lodge Marnix van Sint-Aldegonde |
| [Q130394839](https://www.wikidata.org/wiki/Q130394839) | loge | Lodge L'Astre de l'Oriënt |
| [Q130578325](https://www.wikidata.org/wiki/Q130578325) | loge |  |
| [Q130617082](https://www.wikidata.org/wiki/Q130617082) | loge |  |
| [Q130736873](https://www.wikidata.org/wiki/Q130736873) | loge |  |
| [Q131181362](https://www.wikidata.org/wiki/Q131181362) | obédience |  |
| [Q131190632](https://www.wikidata.org/wiki/Q131190632) | loge |  |
| [Q131279045](https://www.wikidata.org/wiki/Q131279045) | loge | Lodge De Ster in 't Oosten |
| [Q131349956](https://www.wikidata.org/wiki/Q131349956) | loge |  |
| [Q131438079](https://www.wikidata.org/wiki/Q131438079) | loge |  |
| [Q131612680](https://www.wikidata.org/wiki/Q131612680) | loge |  |
| [Q131625361](https://www.wikidata.org/wiki/Q131625361) | loge | Scottish Masonic Centre |
| [Q131759456](https://www.wikidata.org/wiki/Q131759456) | loge | Lodge L'Amitié sans Fin |
| [Q131763331](https://www.wikidata.org/wiki/Q131763331) | loge | Former Masonic Hall (Titirangi Lodge) |
| [Q131857941](https://www.wikidata.org/wiki/Q131857941) | loge |  |
| [Q133864041](https://www.wikidata.org/wiki/Q133864041) | loge | Helensville Masonic Lodge |
| [Q133898132](https://www.wikidata.org/wiki/Q133898132) | obédience | Grand Priory of the Netherlands |
| [Q134027970](https://www.wikidata.org/wiki/Q134027970) | obédience | Grand Lodge of Albania |
| [Q13405502](https://www.wikidata.org/wiki/Q13405502) | loge\|obédience | Grand Orient de Luxembourg |
| [Q134277950](https://www.wikidata.org/wiki/Q134277950) | loge | Zum stillen Tempel |
| [Q134550636](https://www.wikidata.org/wiki/Q134550636) | loge | Lodge Salomo |
| [Q134961630](https://www.wikidata.org/wiki/Q134961630) | loge | Matnat Jad |
| [Q135502522](https://www.wikidata.org/wiki/Q135502522) | obédience | Grand Lodge of Peru |
| [Q13554018](https://www.wikidata.org/wiki/Q13554018) | loge |  |
| [Q135658322](https://www.wikidata.org/wiki/Q135658322) | loge |  |
| [Q136236832](https://www.wikidata.org/wiki/Q136236832) | loge | Loge Saint-Jean de Jérusalem (Nancy) |
| [Q136343359](https://www.wikidata.org/wiki/Q136343359) | loge | Ara Lodge |
| [Q137163140](https://www.wikidata.org/wiki/Q137163140) | loge |  |
| [Q137362206](https://www.wikidata.org/wiki/Q137362206) | loge | Post Nubila Lux |
| [Q13746906](https://www.wikidata.org/wiki/Q13746906) | loge | Lodge La Vertu |
| [Q137712711](https://www.wikidata.org/wiki/Q137712711) | loge |  |
| [Q137714641](https://www.wikidata.org/wiki/Q137714641) | loge | Bethesda Lodge |
| [Q137758728](https://www.wikidata.org/wiki/Q137758728) | loge |  |
| [Q137837283](https://www.wikidata.org/wiki/Q137837283) | loge | loge Roger-Leray |
| [Q137837637](https://www.wikidata.org/wiki/Q137837637) | loge | Respectable Loge Jérôme Bonaparte à l'Orient de Rueil-Nanterre |
| [Q137939428](https://www.wikidata.org/wiki/Q137939428) | loge |  |
| [Q138008610](https://www.wikidata.org/wiki/Q138008610) | loge |  |
| [Q138008611](https://www.wikidata.org/wiki/Q138008611) | loge |  |
| [Q138008612](https://www.wikidata.org/wiki/Q138008612) | loge |  |
| [Q138008613](https://www.wikidata.org/wiki/Q138008613) | loge |  |
| [Q138008614](https://www.wikidata.org/wiki/Q138008614) | loge |  |
| [Q138008616](https://www.wikidata.org/wiki/Q138008616) | loge |  |
| [Q138008618](https://www.wikidata.org/wiki/Q138008618) | loge |  |
| [Q138008619](https://www.wikidata.org/wiki/Q138008619) | loge |  |
| [Q138008621](https://www.wikidata.org/wiki/Q138008621) | loge |  |
| [Q138008622](https://www.wikidata.org/wiki/Q138008622) | loge |  |
| [Q138008625](https://www.wikidata.org/wiki/Q138008625) | loge |  |
| [Q138008627](https://www.wikidata.org/wiki/Q138008627) | loge |  |
| [Q138307388](https://www.wikidata.org/wiki/Q138307388) | loge | Loge Kosmopolis |
| [Q138432707](https://www.wikidata.org/wiki/Q138432707) | obédience |  |
| [Q138601373](https://www.wikidata.org/wiki/Q138601373) | loge | Freemasonic association "Star on the Ribnitz Lagoon" |
| [Q138601386](https://www.wikidata.org/wiki/Q138601386) | loge | Loge „Recht und Freiheit“ in Gotha |
| [Q138602012](https://www.wikidata.org/wiki/Q138602012) | loge | Loge „Zur Bruderkette von Thüringen“ in Gotha |
| [Q138714570](https://www.wikidata.org/wiki/Q138714570) | loge |  |
| [Q138800262](https://www.wikidata.org/wiki/Q138800262) | loge | Johannis-Loge Zum Füllhorn zu Lübeck |
| [Q138840528](https://www.wikidata.org/wiki/Q138840528) | loge | loge Athanor |
| [Q138920941](https://www.wikidata.org/wiki/Q138920941) | loge |  |
| [Q139558411](https://www.wikidata.org/wiki/Q139558411) | loge |  |
| [Q139798587](https://www.wikidata.org/wiki/Q139798587) | loge | La Loge de Juste |
| [Q1472942](https://www.wikidata.org/wiki/Q1472942) | obédience | Grande Loge régulière du Portugal |
| [Q1478100](https://www.wikidata.org/wiki/Q1478100) | loge | Reuchlin |
| [Q1492464](https://www.wikidata.org/wiki/Q1492464) | loge |  |
| [Q14942093](https://www.wikidata.org/wiki/Q14942093) | loge |  |
| [Q14942116](https://www.wikidata.org/wiki/Q14942116) | loge | L'Union Royale |
| [Q15269246](https://www.wikidata.org/wiki/Q15269246) | loge | Lodge Obreros de Hiram, no. 29 |
| [Q1542667](https://www.wikidata.org/wiki/Q1542667) | obédience | Grand Orient de France |
| [Q1542968](https://www.wikidata.org/wiki/Q1542968) | obédience | Grande Loge nationale française |
| [Q1543757](https://www.wikidata.org/wiki/Q1543757) | loge\|obédience | Grande Loge unie d'Angleterre |
| [Q1548315](https://www.wikidata.org/wiki/Q1548315) | obédience | Große National-Mutterloge „Zu den drei Weltkugeln“ |
| [Q1549438](https://www.wikidata.org/wiki/Q1549438) | obédience | Grand Lodge of Turkey |
| [Q15855840](https://www.wikidata.org/wiki/Q15855840) | loge | Pour une vue noble |
| [Q15875233](https://www.wikidata.org/wiki/Q15875233) | loge |  |
| [Q16069325](https://www.wikidata.org/wiki/Q16069325) | loge | Loge Sint Lodewijk |
| [Q16237593](https://www.wikidata.org/wiki/Q16237593) | obédience | Grande loge d'Alabama |
| [Q16237595](https://www.wikidata.org/wiki/Q16237595) | obédience | Grande Loge de Chypre |
| [Q16237597](https://www.wikidata.org/wiki/Q16237597) | obédience | Grande Loge d'Indiana |
| [Q16237599](https://www.wikidata.org/wiki/Q16237599) | obédience | Grande Loge du Missouri |
| [Q16237601](https://www.wikidata.org/wiki/Q16237601) | obédience | Grande Loge de Terre-Neuve et Labrador |
| [Q16237603](https://www.wikidata.org/wiki/Q16237603) | obédience | Grand Orient lusitanien |
| [Q16238090](https://www.wikidata.org/wiki/Q16238090) | loge | New Welcome Lodge |
| [Q16238198](https://www.wikidata.org/wiki/Q16238198) | obédience | Prince Hall National Grand Lodge |
| [Q16350591](https://www.wikidata.org/wiki/Q16350591) | loge |  |
| [Q16351523](https://www.wikidata.org/wiki/Q16351523) | loge |  |
| [Q16351525](https://www.wikidata.org/wiki/Q16351525) | loge |  |
| [Q16351529](https://www.wikidata.org/wiki/Q16351529) | loge |  |
| [Q16351534](https://www.wikidata.org/wiki/Q16351534) | loge |  |
| [Q16351538](https://www.wikidata.org/wiki/Q16351538) | loge |  |
| [Q16351544](https://www.wikidata.org/wiki/Q16351544) | loge |  |
| [Q16351548](https://www.wikidata.org/wiki/Q16351548) | loge |  |
| [Q16351551](https://www.wikidata.org/wiki/Q16351551) | loge | Zum Nordstern lodge in Riga |
| [Q16355774](https://www.wikidata.org/wiki/Q16355774) | loge |  |
| [Q16358498](https://www.wikidata.org/wiki/Q16358498) | loge |  |
| [Q16358502](https://www.wikidata.org/wiki/Q16358502) | loge |  |
| [Q16358504](https://www.wikidata.org/wiki/Q16358504) | loge |  |
| [Q16360603](https://www.wikidata.org/wiki/Q16360603) | loge |  |
| [Q16362486](https://www.wikidata.org/wiki/Q16362486) | loge |  |
| [Q16362489](https://www.wikidata.org/wiki/Q16362489) | loge |  |
| [Q1637868](https://www.wikidata.org/wiki/Q1637868) | obédience | Première Grande Loge d'Angleterre |
| [Q16639831](https://www.wikidata.org/wiki/Q16639831) | obédience | Grand Directoire des Gaules |
| [Q16639990](https://www.wikidata.org/wiki/Q16639990) | obédience | Grande Loge unie de France |
| [Q1665103](https://www.wikidata.org/wiki/Q1665103) | obédience | Liberal Grand Lodge of Austria |
| [Q16657638](https://www.wikidata.org/wiki/Q16657638) | loge | Loge Spartacus |
| [Q16657641](https://www.wikidata.org/wiki/Q16657641) | loge | Loge Volney |
| [Q1689090](https://www.wikidata.org/wiki/Q1689090) | loge | Ruprecht zu den fünf Rosen |
| [Q1698895](https://www.wikidata.org/wiki/Q1698895) | loge | Johannis-Freimaurerloge Im Quadrat |
| [Q17355439](https://www.wikidata.org/wiki/Q17355439) | loge | Loge La Parfaite Union |
| [Q17492828](https://www.wikidata.org/wiki/Q17492828) | loge |  |
| [Q17521342](https://www.wikidata.org/wiki/Q17521342) | loge | Archimedes zu den 3 Reissbretern |
| [Q17521888](https://www.wikidata.org/wiki/Q17521888) | loge |  |
| [Q17540286](https://www.wikidata.org/wiki/Q17540286) | loge | Ernst zum Compass |
| [Q17743093](https://www.wikidata.org/wiki/Q17743093) | loge | St John's Church |
| [Q17749350](https://www.wikidata.org/wiki/Q17749350) | loge | United Friends |
| [Q18008318](https://www.wikidata.org/wiki/Q18008318) | loge | Boizenburg temple |
| [Q18030055](https://www.wikidata.org/wiki/Q18030055) | loge |  |
| [Q18061577](https://www.wikidata.org/wiki/Q18061577) | loge |  |
| [Q1818244](https://www.wikidata.org/wiki/Q1818244) | obédience | Grande Loge Mixte des Pays-Bas |
| [Q18286131](https://www.wikidata.org/wiki/Q18286131) | loge | Le Droit Humain – Fédération néerlandaise |
| [Q18331924](https://www.wikidata.org/wiki/Q18331924) | obédience | Grande Loge du Congo |
| [Q18332979](https://www.wikidata.org/wiki/Q18332979) | obédience | Grand Lodge of Finland |
| [Q1835402](https://www.wikidata.org/wiki/Q1835402) | loge | Frédéric Royal |
| [Q1867973](https://www.wikidata.org/wiki/Q1867973) | loge |  |
| [Q1867979](https://www.wikidata.org/wiki/Q1867979) | loge |  |
| [Q18888636](https://www.wikidata.org/wiki/Q18888636) | loge |  |
| [Q1891317](https://www.wikidata.org/wiki/Q1891317) | loge | Les Amis Philanthropes nº 3 |
| [Q19363327](https://www.wikidata.org/wiki/Q19363327) | obédience | Grand Orient de Roumanie |
| [Q1936631](https://www.wikidata.org/wiki/Q1936631) | loge | Minerva zu den drei Palmen |
| [Q19390077](https://www.wikidata.org/wiki/Q19390077) | loge |  |
| [Q19390079](https://www.wikidata.org/wiki/Q19390079) | loge |  |
| [Q19390080](https://www.wikidata.org/wiki/Q19390080) | loge |  |
| [Q19390081](https://www.wikidata.org/wiki/Q19390081) | loge |  |
| [Q19390083](https://www.wikidata.org/wiki/Q19390083) | loge |  |
| [Q19390084](https://www.wikidata.org/wiki/Q19390084) | loge |  |
| [Q19390085](https://www.wikidata.org/wiki/Q19390085) | loge |  |
| [Q19390102](https://www.wikidata.org/wiki/Q19390102) | loge |  |
| [Q19390103](https://www.wikidata.org/wiki/Q19390103) | loge |  |
| [Q19390105](https://www.wikidata.org/wiki/Q19390105) | loge |  |
| [Q19390106](https://www.wikidata.org/wiki/Q19390106) | loge |  |
| [Q19390107](https://www.wikidata.org/wiki/Q19390107) | loge |  |
| [Q19390110](https://www.wikidata.org/wiki/Q19390110) | loge |  |
| [Q19390111](https://www.wikidata.org/wiki/Q19390111) | loge |  |
| [Q19390112](https://www.wikidata.org/wiki/Q19390112) | loge |  |
| [Q19390114](https://www.wikidata.org/wiki/Q19390114) | loge |  |
| [Q19390115](https://www.wikidata.org/wiki/Q19390115) | loge |  |
| [Q19390120](https://www.wikidata.org/wiki/Q19390120) | loge |  |
| [Q19390121](https://www.wikidata.org/wiki/Q19390121) | loge | Lodge "Regulus", Olesun |
| [Q19390122](https://www.wikidata.org/wiki/Q19390122) | loge |  |
| [Q19390123](https://www.wikidata.org/wiki/Q19390123) | loge |  |
| [Q19390124](https://www.wikidata.org/wiki/Q19390124) | loge |  |
| [Q19390127](https://www.wikidata.org/wiki/Q19390127) | loge |  |
| [Q19390128](https://www.wikidata.org/wiki/Q19390128) | loge |  |
| [Q19390129](https://www.wikidata.org/wiki/Q19390129) | loge |  |
| [Q19390130](https://www.wikidata.org/wiki/Q19390130) | loge |  |
| [Q19390131](https://www.wikidata.org/wiki/Q19390131) | loge |  |
| [Q19390133](https://www.wikidata.org/wiki/Q19390133) | loge |  |
| [Q19390134](https://www.wikidata.org/wiki/Q19390134) | loge |  |
| [Q19390135](https://www.wikidata.org/wiki/Q19390135) | loge |  |
| [Q19461505](https://www.wikidata.org/wiki/Q19461505) | loge | Eastern Star Lodge, No. 207, F. and A. M. |
| [Q19709086](https://www.wikidata.org/wiki/Q19709086) | loge | Grande Loja Maçônica do Amazonas |
| [Q1972213](https://www.wikidata.org/wiki/Q1972213) | loge |  |
| [Q1983867](https://www.wikidata.org/wiki/Q1983867) | loge\|obédience | Ancient and Accepted Scottish Rite for the Kingdom of the Netherlands |
| [Q19915255](https://www.wikidata.org/wiki/Q19915255) | loge | freemasonry in Belgium during French ruling |
| [Q19937294](https://www.wikidata.org/wiki/Q19937294) | loge |  |
| [Q19937295](https://www.wikidata.org/wiki/Q19937295) | loge | Aux Trois Canons |
| [Q19937578](https://www.wikidata.org/wiki/Q19937578) | obédience | Grand Lodge of Bolivia |
| [Q19937586](https://www.wikidata.org/wiki/Q19937586) | loge | Grand Lodge of Puerto Rico |
| [Q19962593](https://www.wikidata.org/wiki/Q19962593) | loge | Johannisloge Zur Eintracht |
| [Q19974011](https://www.wikidata.org/wiki/Q19974011) | loge |  |
| [Q2001496](https://www.wikidata.org/wiki/Q2001496) | obédience | Ordre norvégien des francs-maçons |
| [Q2021137](https://www.wikidata.org/wiki/Q2021137) | loge |  |
| [Q2042994](https://www.wikidata.org/wiki/Q2042994) | loge |  |
| [Q2049535](https://www.wikidata.org/wiki/Q2049535) | loge\|obédience | Grande Loge d’Écosse |
| [Q20564746](https://www.wikidata.org/wiki/Q20564746) | loge |  |
| [Q20667680](https://www.wikidata.org/wiki/Q20667680) | loge | Zum großen Licht im Norden |
| [Q20669800](https://www.wikidata.org/wiki/Q20669800) | loge | Loge Alexandria-Washington n°22 |
| [Q20709915](https://www.wikidata.org/wiki/Q20709915) | obédience | Grand Lodge of Wisconsin |
| [Q2081970](https://www.wikidata.org/wiki/Q2081970) | loge |  |
| [Q2084178](https://www.wikidata.org/wiki/Q2084178) | loge |  |
| [Q2100219](https://www.wikidata.org/wiki/Q2100219) | loge |  |
| [Q21004421](https://www.wikidata.org/wiki/Q21004421) | obédience |  |
| [Q21017096](https://www.wikidata.org/wiki/Q21017096) | obédience | Grand Lodge of Macedonia |
| [Q2118625](https://www.wikidata.org/wiki/Q2118625) | obédience | Grande Loge féminine de Belgique |
| [Q21282853](https://www.wikidata.org/wiki/Q21282853) | obédience | Grand Lodge of Turkey |
| [Q2142137](https://www.wikidata.org/wiki/Q2142137) | loge | Le Droit Humain – Fédération luxembourgeoise |
| [Q2193044](https://www.wikidata.org/wiki/Q2193044) | loge |  |
| [Q22086205](https://www.wikidata.org/wiki/Q22086205) | loge | La Fidélité |
| [Q2221631](https://www.wikidata.org/wiki/Q2221631) | obédience | Grand Orient des Pays-Bas |
| [Q2242614](https://www.wikidata.org/wiki/Q2242614) | obédience |  |
| [Q2248662](https://www.wikidata.org/wiki/Q2248662) | loge |  |
| [Q225503](https://www.wikidata.org/wiki/Q225503) | obédience | Grand Orient de Pologne |
| [Q2263862](https://www.wikidata.org/wiki/Q2263862) | loge |  |
| [Q227451](https://www.wikidata.org/wiki/Q227451) | loge | Aux trois épées et Astrée au diamant vert |
| [Q229563](https://www.wikidata.org/wiki/Q229563) | loge | Zum Todtenkopf und Phoenix |
| [Q229597](https://www.wikidata.org/wiki/Q229597) | loge |  |
| [Q230281](https://www.wikidata.org/wiki/Q230281) | loge |  |
| [Q230381](https://www.wikidata.org/wiki/Q230381) | loge | À la vraie concorde |
| [Q2319455](https://www.wikidata.org/wiki/Q2319455) | loge |  |
| [Q23368474](https://www.wikidata.org/wiki/Q23368474) | loge |  |
| [Q2355596](https://www.wikidata.org/wiki/Q2355596) | loge\|obédience | Grande Loge d'Irlande |
| [Q2360107](https://www.wikidata.org/wiki/Q2360107) | loge | Les Vrais Amis de l'Union et du Progrès Réunis |
| [Q2365799](https://www.wikidata.org/wiki/Q2365799) | obédience | Grande Loge traditionnelle et symbolique Opéra |
| [Q23785392](https://www.wikidata.org/wiki/Q23785392) | loge | Libanon zu den drei Cedern |
| [Q23785393](https://www.wikidata.org/wiki/Q23785393) | loge |  |
| [Q2385972](https://www.wikidata.org/wiki/Q2385972) | loge | Le Droit Humain – Fédération belge |
| [Q2391011](https://www.wikidata.org/wiki/Q2391011) | loge |  |
| [Q2398411](https://www.wikidata.org/wiki/Q2398411) | obédience | Grand Orient des États-Unis d'Amérique |
| [Q2408568](https://www.wikidata.org/wiki/Q2408568) | obédience | Grande Loge mixte universelle |
| [Q2411715](https://www.wikidata.org/wiki/Q2411715) | obédience | Grand Lodge of British Freemasons in Germany |
| [Q24273542](https://www.wikidata.org/wiki/Q24273542) | loge | Hercynia zum flammenden Stern |
| [Q2449095](https://www.wikidata.org/wiki/Q2449095) | loge |  |
| [Q2480906](https://www.wikidata.org/wiki/Q2480906) | obédience | Grand Orient de Belgique |
| [Q2495542](https://www.wikidata.org/wiki/Q2495542) | obédience |  |
| [Q2503311](https://www.wikidata.org/wiki/Q2503311) | loge |  |
| [Q2511149](https://www.wikidata.org/wiki/Q2511149) | loge |  |
| [Q25378930](https://www.wikidata.org/wiki/Q25378930) | obédience | Souverain Collège du rite écossais pour la Belgique |
| [Q2554832](https://www.wikidata.org/wiki/Q2554832) | loge |  |
| [Q2603342](https://www.wikidata.org/wiki/Q2603342) | obédience | Grande Loge de Belgique |
| [Q2665331](https://www.wikidata.org/wiki/Q2665331) | loge |  |
| [Q2666396](https://www.wikidata.org/wiki/Q2666396) | loge | Loge L'Union Provinciale |
| [Q26721363](https://www.wikidata.org/wiki/Q26721363) | loge\|obédience | Grande Loge du Japon |
| [Q2676101](https://www.wikidata.org/wiki/Q2676101) | loge |  |
| [Q2683798](https://www.wikidata.org/wiki/Q2683798) | loge | Ordre des maîtres maçons de la marque |
| [Q2705082](https://www.wikidata.org/wiki/Q2705082) | loge |  |
| [Q2741067](https://www.wikidata.org/wiki/Q2741067) | loge |  |
| [Q2780200](https://www.wikidata.org/wiki/Q2780200) | obédience |  |
| [Q2801775](https://www.wikidata.org/wiki/Q2801775) | loge | Simon Stevin |
| [Q28057761](https://www.wikidata.org/wiki/Q28057761) | loge |  |
| [Q28104589](https://www.wikidata.org/wiki/Q28104589) | obédience | Suprême Conseil mixte de France |
| [Q28456657](https://www.wikidata.org/wiki/Q28456657) | obédience | Scottish Rite Supreme Council for the Northern Masonic Jurisdiction |
| [Q28499147](https://www.wikidata.org/wiki/Q28499147) | obédience | Grand Orient de Suisse |
| [Q28667774](https://www.wikidata.org/wiki/Q28667774) | loge |  |
| [Q2901304](https://www.wikidata.org/wiki/Q2901304) | loge |  |
| [Q2918845](https://www.wikidata.org/wiki/Q2918845) | loge | Altuna |
| [Q2920894](https://www.wikidata.org/wiki/Q2920894) | loge |  |
| [Q29210142](https://www.wikidata.org/wiki/Q29210142) | loge | Groot-Nederland |
| [Q3012601](https://www.wikidata.org/wiki/Q3012601) | loge | L'Obstinée |
| [Q3019116](https://www.wikidata.org/wiki/Q3019116) | loge | Illuminés d'Avignon |
| [Q30239159](https://www.wikidata.org/wiki/Q30239159) | obédience |  |
| [Q30241501](https://www.wikidata.org/wiki/Q30241501) | loge |  |
| [Q30264727](https://www.wikidata.org/wiki/Q30264727) | loge\|obédience | Grand Lodge of Western Australia |
| [Q3051103](https://www.wikidata.org/wiki/Q3051103) | obédience | Lithos - Confédération de loges |
| [Q30587889](https://www.wikidata.org/wiki/Q30587889) | obédience | Grand Lodge of the Philippines |
| [Q3086702](https://www.wikidata.org/wiki/Q3086702) | loge | Fraternité bugeysienne |
| [Q30907258](https://www.wikidata.org/wiki/Q30907258) | loge | Obradoiro-Keltoy |
| [Q3113684](https://www.wikidata.org/wiki/Q3113684) | obédience | Grand Orient |
| [Q3113688](https://www.wikidata.org/wiki/Q3113688) | obédience | Grand Orient latino-américain |
| [Q3113712](https://www.wikidata.org/wiki/Q3113712) | obédience | Grand Prieuré des Gaules |
| [Q3114959](https://www.wikidata.org/wiki/Q3114959) | obédience | Grande Loge de l'Alliance maçonnique française |
| [Q3114962](https://www.wikidata.org/wiki/Q3114962) | obédience | Grande Loge de Russie |
| [Q3114963](https://www.wikidata.org/wiki/Q3114963) | obédience | Grande Loge féminine de Memphis-Misraïm |
| [Q3114964](https://www.wikidata.org/wiki/Q3114964) | obédience | Grande Loge du Québec |
| [Q3114965](https://www.wikidata.org/wiki/Q3114965) | obédience | Grande Loge de Pennsylvanie |
| [Q3114968](https://www.wikidata.org/wiki/Q3114968) | obédience | Grande Loge provinciale des Pays-Bas autrichiens |
| [Q3114970](https://www.wikidata.org/wiki/Q3114970) | loge\|obédience | Grande Loge régulière de Belgique |
| [Q3114971](https://www.wikidata.org/wiki/Q3114971) | obédience | Grande Loge nationale polonaise |
| [Q3114972](https://www.wikidata.org/wiki/Q3114972) | obédience | Grande Loge mixte de France |
| [Q3114973](https://www.wikidata.org/wiki/Q3114973) | obédience | Grande Loge symbolique écossaise |
| [Q3114979](https://www.wikidata.org/wiki/Q3114979) | obédience | Grande Loge suisse Alpina |
| [Q3230700](https://www.wikidata.org/wiki/Q3230700) | loge | Les Arts et l'Amitié |
| [Q3257869](https://www.wikidata.org/wiki/Q3257869) | loge | Loge Alsace-Lorraine |
| [Q3257873](https://www.wikidata.org/wiki/Q3257873) | loge | Loge Coustos-Villeroy |
| [Q3257874](https://www.wikidata.org/wiki/Q3257874) | loge | Loge Constante Alona |
| [Q3257878](https://www.wikidata.org/wiki/Q3257878) | loge | Loge de la Vraie Fraternité |
| [Q3257879](https://www.wikidata.org/wiki/Q3257879) | loge | Loge d'Edimbourg n°1 |
| [Q3257880](https://www.wikidata.org/wiki/Q3257880) | loge | Loge des Cœurs Fidèles |
| [Q3257881](https://www.wikidata.org/wiki/Q3257881) | loge | Loge des Frères Réunis |
| [Q3257884](https://www.wikidata.org/wiki/Q3257884) | loge | Loge militaire L'Union indissoluble |
| [Q3257889](https://www.wikidata.org/wiki/Q3257889) | obédience | Loge nationale française |
| [Q33219545](https://www.wikidata.org/wiki/Q33219545) | loge | Athanor |
| [Q3345274](https://www.wikidata.org/wiki/Q3345274) | obédience | Nouvelles obédiences maçonniques françaises |
| [Q3355745](https://www.wikidata.org/wiki/Q3355745) | obédience | Ordre initiatique et traditionnel de l'Art royal |
| [Q3394832](https://www.wikidata.org/wiki/Q3394832) | loge |  |
| [Q3463885](https://www.wikidata.org/wiki/Q3463885) | loge | Saint Jean d'Écosse de Marseille |
| [Q3463889](https://www.wikidata.org/wiki/Q3463889) | loge | Saint Jean d'Écosse du Contrat social |
| [Q3504956](https://www.wikidata.org/wiki/Q3504956) | obédience | Suprême Conseil de la Juridiction Sud |
| [Q3504958](https://www.wikidata.org/wiki/Q3504958) | loge\|obédience | Suprême Conseil de France |
| [Q3618677](https://www.wikidata.org/wiki/Q3618677) | obédience | Antient Grand Lodge of England |
| [Q36595112](https://www.wikidata.org/wiki/Q36595112) | loge | Aomori Lodge |
| [Q36595459](https://www.wikidata.org/wiki/Q36595459) | loge | DeMolay-Land Lodge |
| [Q36595500](https://www.wikidata.org/wiki/Q36595500) | loge | Research Lodge of Japan |
| [Q36595561](https://www.wikidata.org/wiki/Q36595561) | loge | Square and Compass Lodge |
| [Q36595590](https://www.wikidata.org/wiki/Q36595590) | loge | Kokusai Lodge |
| [Q36595610](https://www.wikidata.org/wiki/Q36595610) | loge | Far East Lodge |
| [Q36595643](https://www.wikidata.org/wiki/Q36595643) | loge | Yokosuka Lodge |
| [Q36595670](https://www.wikidata.org/wiki/Q36595670) | loge | Sagamihara Masonic Lodge |
| [Q36595689](https://www.wikidata.org/wiki/Q36595689) | loge | Torii Masonic Lodge |
| [Q36595712](https://www.wikidata.org/wiki/Q36595712) | loge | Kyoto Mikado Lodge |
| [Q36595733](https://www.wikidata.org/wiki/Q36595733) | loge | Kintai Lodge |
| [Q36595755](https://www.wikidata.org/wiki/Q36595755) | loge | Himiko Lodge |
| [Q36595775](https://www.wikidata.org/wiki/Q36595775) | loge | Nippon Lodge |
| [Q36595800](https://www.wikidata.org/wiki/Q36595800) | loge | Teikoku Lodge |
| [Q3774329](https://www.wikidata.org/wiki/Q3774329) | obédience | Grande Loge d'Italie |
| [Q38554912](https://www.wikidata.org/wiki/Q38554912) | loge |  |
| [Q3919361](https://www.wikidata.org/wiki/Q3919361) | obédience | Grande Loge unie de Russie |
| [Q4284136](https://www.wikidata.org/wiki/Q4284136) | obédience | Ordre maçonique du Libéria |
| [Q44635837](https://www.wikidata.org/wiki/Q44635837) | loge |  |
| [Q4467995](https://www.wikidata.org/wiki/Q4467995) | loge | Lodge Willem Fredrik |
| [Q451117](https://www.wikidata.org/wiki/Q451117) | loge\|obédience | Grandes Loges unies d'Allemagne |
| [Q46994918](https://www.wikidata.org/wiki/Q46994918) | loge |  |
| [Q471345](https://www.wikidata.org/wiki/Q471345) | obédience | Grande Loge d'Allemagne |
| [Q4733709](https://www.wikidata.org/wiki/Q4733709) | loge | Almas Temple |
| [Q4791902](https://www.wikidata.org/wiki/Q4791902) | loge | Arkansas Valley Lodge No. 21, Prince Hall Masons |
| [Q48204303](https://www.wikidata.org/wiki/Q48204303) | loge | Loge Nature et Philanthropie |
| [Q48828342](https://www.wikidata.org/wiki/Q48828342) | loge | Les Amis de Sully |
| [Q48840575](https://www.wikidata.org/wiki/Q48840575) | loge |  |
| [Q48898276](https://www.wikidata.org/wiki/Q48898276) | loge | L'heureuse rencontre |
| [Q49580640](https://www.wikidata.org/wiki/Q49580640) | loge | Vrijmetselaarsloge La Liberté Gent |
| [Q4988758](https://www.wikidata.org/wiki/Q4988758) | loge |  |
| [Q49968848](https://www.wikidata.org/wiki/Q49968848) | loge | Vrijmetselaarsloge L'Aurore Oudenaarde |
| [Q49969314](https://www.wikidata.org/wiki/Q49969314) | loge | J. Speliers |
| [Q49969846](https://www.wikidata.org/wiki/Q49969846) | loge | Jan De Grande |
| [Q50326744](https://www.wikidata.org/wiki/Q50326744) | loge | La Parfaite Union |
| [Q52183132](https://www.wikidata.org/wiki/Q52183132) | loge | Rising Sun Lodge No. 1401 |
| [Q52183215](https://www.wikidata.org/wiki/Q52183215) | loge | Lodge Star in the East No. 640 |
| [Q52183255](https://www.wikidata.org/wiki/Q52183255) | loge | Lodge Hiogo and Osaka No. 498 |
| [Q52183313](https://www.wikidata.org/wiki/Q52183313) | loge | Sinim Lodge |
| [Q52183371](https://www.wikidata.org/wiki/Q52183371) | loge | Soleil Levant |
| [Q52183462](https://www.wikidata.org/wiki/Q52183462) | loge | Grand Orient arabe œcuménique |
| [Q52183503](https://www.wikidata.org/wiki/Q52183503) | loge | Loge la lumière du soleil levant |
| [Q52183615](https://www.wikidata.org/wiki/Q52183615) | loge | O'Misawa Lodge No. 54 |
| [Q52183730](https://www.wikidata.org/wiki/Q52183730) | loge | Tomodachi Lodge No. 111 |
| [Q52183901](https://www.wikidata.org/wiki/Q52183901) | loge | Pride of the Orient Lodge No. 55 |
| [Q52184009](https://www.wikidata.org/wiki/Q52184009) | loge | Touchon Lodge No. 106 |
| [Q52184049](https://www.wikidata.org/wiki/Q52184049) | loge | Genesis Lodge No. 89 |
| [Q52184092](https://www.wikidata.org/wiki/Q52184092) | loge | Revelation Lodge No. 97 |
| [Q52184282](https://www.wikidata.org/wiki/Q52184282) | loge | Cherry Blossom Lodge No. 42 |
| [Q52184327](https://www.wikidata.org/wiki/Q52184327) | loge | Okinawa Lodge No. 118 |
| [Q52184373](https://www.wikidata.org/wiki/Q52184373) | loge | Rising Sun Lodge No. 151 |
| [Q52279256](https://www.wikidata.org/wiki/Q52279256) | loge |  |
| [Q528770](https://www.wikidata.org/wiki/Q528770) | loge | Liberté chérie |
| [Q5361097](https://www.wikidata.org/wiki/Q5361097) | loge | Tanchelijn |
| [Q5552214](https://www.wikidata.org/wiki/Q5552214) | loge |  |
| [Q55603297](https://www.wikidata.org/wiki/Q55603297) | loge | African Lodge No. 459 |
| [Q55658013](https://www.wikidata.org/wiki/Q55658013) | loge |  |
| [Q5587234](https://www.wikidata.org/wiki/Q5587234) | loge | Goshamahal Baradari |
| [Q5594751](https://www.wikidata.org/wiki/Q5594751) | obédience | Grande Loge du Canada de la province d'Ontario |
| [Q5594752](https://www.wikidata.org/wiki/Q5594752) | obédience | Grande Loge du Connecticut |
| [Q5594753](https://www.wikidata.org/wiki/Q5594753) | obédience | Grande Loge d'Idaho |
| [Q5594755](https://www.wikidata.org/wiki/Q5594755) | obédience | Grande Loge de Colombie |
| [Q5594757](https://www.wikidata.org/wiki/Q5594757) | obédience | Grande Loge de Kansas |
| [Q5594758](https://www.wikidata.org/wiki/Q5594758) | obédience | Grande Loge du Kentucky |
| [Q5594760](https://www.wikidata.org/wiki/Q5594760) | obédience | Grande Loge de l'Inde |
| [Q5594761](https://www.wikidata.org/wiki/Q5594761) | obédience | Grande Loge du Manitoba |
| [Q5594762](https://www.wikidata.org/wiki/Q5594762) | obédience | Grande Loge du Massachusetts |
| [Q5594763](https://www.wikidata.org/wiki/Q5594763) | obédience | Grande Loge du Minnesota |
| [Q5594764](https://www.wikidata.org/wiki/Q5594764) | obédience | Grande Loge du Michigan |
| [Q5594765](https://www.wikidata.org/wiki/Q5594765) | obédience | Grande Loge du Nebraska |
| [Q5594766](https://www.wikidata.org/wiki/Q5594766) | obédience | Grande Loge de New-York |
| [Q5594768](https://www.wikidata.org/wiki/Q5594768) | obédience | Grande Loge du Dakota du nord |
| [Q5594770](https://www.wikidata.org/wiki/Q5594770) | obédience | Grande Loge de l'Ohio |
| [Q5594771](https://www.wikidata.org/wiki/Q5594771) | obédience | Grande Loge du Texas |
| [Q5594772](https://www.wikidata.org/wiki/Q5594772) | obédience | Grande Loge de Virginie |
| [Q5594773](https://www.wikidata.org/wiki/Q5594773) | obédience | Grande Loge du Virginie occidentale |
| [Q5594774](https://www.wikidata.org/wiki/Q5594774) | obédience | Grande Loge de république Dominicaine |
| [Q5594778](https://www.wikidata.org/wiki/Q5594778) | obédience | Grande Loge d'Espagne |
| [Q5595325](https://www.wikidata.org/wiki/Q5595325) | obédience | Grande Oriente Español |
| [Q5802757](https://www.wikidata.org/wiki/Q5802757) | loge\|obédience | Grande Loge Symbolique Espagnole |
| [Q5881011](https://www.wikidata.org/wiki/Q5881011) | loge | Holland Lodge |
| [Q5884373](https://www.wikidata.org/wiki/Q5884373) | loge\|obédience | Grand Lodge of Uruguay |
| [Q5884395](https://www.wikidata.org/wiki/Q5884395) | obédience |  |
| [Q5884494](https://www.wikidata.org/wiki/Q5884494) | obédience |  |
| [Q592847](https://www.wikidata.org/wiki/Q592847) | loge | Souveräner GrossOrient von Deutschland |
| [Q5952893](https://www.wikidata.org/wiki/Q5952893) | loge | Le Reveile de l'Iran |
| [Q5978346](https://www.wikidata.org/wiki/Q5978346) | loge |  |
| [Q60609170](https://www.wikidata.org/wiki/Q60609170) | loge |  |
| [Q61051531](https://www.wikidata.org/wiki/Q61051531) | loge |  |
| [Q61104977](https://www.wikidata.org/wiki/Q61104977) | obédience | Grand Lodge of Armenia |
| [Q62609453](https://www.wikidata.org/wiki/Q62609453) | loge |  |
| [Q62804810](https://www.wikidata.org/wiki/Q62804810) | loge | Carl zur gekrönten Säule |
| [Q63174255](https://www.wikidata.org/wiki/Q63174255) | loge | Pilar Lodge No. 3 |
| [Q64005968](https://www.wikidata.org/wiki/Q64005968) | loge | Zum flammenden Schwert |
| [Q64168109](https://www.wikidata.org/wiki/Q64168109) | loge | Hamilton Memorial Masonic Lodge |
| [Q64762664](https://www.wikidata.org/wiki/Q64762664) | loge | Loge Les enfants de la concorde fortifiée |
| [Q64966925](https://www.wikidata.org/wiki/Q64966925) | loge | Suffolk Lodge No. 60 |
| [Q650732](https://www.wikidata.org/wiki/Q650732) | loge | Neuf Sœurs |
| [Q65557950](https://www.wikidata.org/wiki/Q65557950) | loge | Rapid City Masonic Lodge |
| [Q65617534](https://www.wikidata.org/wiki/Q65617534) | loge | L'Écho du Grand Orient |
| [Q65617695](https://www.wikidata.org/wiki/Q65617695) | loge | Tolérance et Union |
| [Q65618015](https://www.wikidata.org/wiki/Q65618015) | loge | Le Progrès |
| [Q65618288](https://www.wikidata.org/wiki/Q65618288) | loge | La Ligne droite |
| [Q65618903](https://www.wikidata.org/wiki/Q65618903) | loge | La Philanthropique |
| [Q65618929](https://www.wikidata.org/wiki/Q65618929) | loge | L'Avenir cévenol |
| [Q65618985](https://www.wikidata.org/wiki/Q65618985) | loge | La Réunion |
| [Q65619021](https://www.wikidata.org/wiki/Q65619021) | loge | L'Étoile des Cévennes |
| [Q65619754](https://www.wikidata.org/wiki/Q65619754) | loge | La Parfaite Union |
| [Q65619783](https://www.wikidata.org/wiki/Q65619783) | loge | La Philanthropie |
| [Q65619818](https://www.wikidata.org/wiki/Q65619818) | loge | loge Écho 1 |
| [Q65619830](https://www.wikidata.org/wiki/Q65619830) | loge | Le Bienfait anonyme |
| [Q65643729](https://www.wikidata.org/wiki/Q65643729) | loge | Progrès-Humanité |
| [Q65643756](https://www.wikidata.org/wiki/Q65643756) | loge | La Marche en avant |
| [Q65643812](https://www.wikidata.org/wiki/Q65643812) | loge | Le Réveil des Cévennes |
| [Q65643816](https://www.wikidata.org/wiki/Q65643816) | loge | Le Réveil cévenol |
| [Q65643830](https://www.wikidata.org/wiki/Q65643830) | loge | Acacia du Gard |
| [Q65643866](https://www.wikidata.org/wiki/Q65643866) | loge | Progrès et Humanité |
| [Q65645061](https://www.wikidata.org/wiki/Q65645061) | loge | Les Philanthropes réunis |
| [Q65645838](https://www.wikidata.org/wiki/Q65645838) | loge | Les Amis rassemblés par la vertu |
| [Q65646490](https://www.wikidata.org/wiki/Q65646490) | loge | Les Cœurs réunis |
| [Q65647378](https://www.wikidata.org/wiki/Q65647378) | loge | L'Inaltérable Amitié |
| [Q65770969](https://www.wikidata.org/wiki/Q65770969) | loge | loge Villard de Honnecourt |
| [Q6666326](https://www.wikidata.org/wiki/Q6666326) | loge | Lodge St. Olaus to the white Leopard |
| [Q68487729](https://www.wikidata.org/wiki/Q68487729) | loge |  |
| [Q68902806](https://www.wikidata.org/wiki/Q68902806) | loge | loge Fraternité |
| [Q69795698](https://www.wikidata.org/wiki/Q69795698) | loge | L'Étoile occidentale |
| [Q7023677](https://www.wikidata.org/wiki/Q7023677) | loge |  |
| [Q7100564](https://www.wikidata.org/wiki/Q7100564) | obédience | Ordre des femmes franc-maçons |
| [Q7100685](https://www.wikidata.org/wiki/Q7100685) | obédience | Order of the Secret Monitor |
| [Q72187547](https://www.wikidata.org/wiki/Q72187547) | loge | Loge l'Amitié de Bordeaux |
| [Q726678](https://www.wikidata.org/wiki/Q726678) | loge | Les Amis philanthropes |
| [Q731738](https://www.wikidata.org/wiki/Q731738) | obédience | Le Droit humain |
| [Q7374651](https://www.wikidata.org/wiki/Q7374651) | obédience | ordre royal d'Écosse |
| [Q7558531](https://www.wikidata.org/wiki/Q7558531) | loge | Solomon's Lodge, Savannah |
| [Q7588765](https://www.wikidata.org/wiki/Q7588765) | loge | St. John's Lodge, Portsmouth, New Hampshire |
| [Q7603370](https://www.wikidata.org/wiki/Q7603370) | loge | Old State House |
| [Q76356831](https://www.wikidata.org/wiki/Q76356831) | loge | Les Arts réunis |
| [Q7654098](https://www.wikidata.org/wiki/Q7654098) | loge | E. S. Swayze Drugstore |
| [Q769231](https://www.wikidata.org/wiki/Q769231) | loge | Loge Lautaro |
| [Q7766139](https://www.wikidata.org/wiki/Q7766139) | loge | The St. Andrew lodge Oscar to the burning star |
| [Q7777087](https://www.wikidata.org/wiki/Q7777087) | loge | The old Masonic Lodge in Lębork |
| [Q7886023](https://www.wikidata.org/wiki/Q7886023) | obédience | George Washington Union |
| [Q7887828](https://www.wikidata.org/wiki/Q7887828) | obédience | United Grand Lodge of New South Wales and the Australian Capital Territory |
| [Q79300870](https://www.wikidata.org/wiki/Q79300870) | loge | Cromwell Kilwinning Lodge No 98 |
| [Q79302436](https://www.wikidata.org/wiki/Q79302436) | loge | Lake Lodge of Ophir |
| [Q79309424](https://www.wikidata.org/wiki/Q79309424) | loge | Lodge of Unanimity No 3 |
| [Q798379](https://www.wikidata.org/wiki/Q798379) | loge\|obédience | Frères asiatiques |
| [Q81216177](https://www.wikidata.org/wiki/Q81216177) | loge | Loge De Ster in het Oosten (Bilthoven) |
| [Q81216535](https://www.wikidata.org/wiki/Q81216535) | loge | Loge L’Inséparable |
| [Q81216554](https://www.wikidata.org/wiki/Q81216554) | loge | Loge De Drie Kolommen |
| [Q81216956](https://www.wikidata.org/wiki/Q81216956) | loge | Loge Le Profond Silence |
| [Q81322699](https://www.wikidata.org/wiki/Q81322699) | loge | Loge De Waare Broedertrouw |
| [Q81322707](https://www.wikidata.org/wiki/Q81322707) | loge | Loge De Noordstar |
| [Q81323037](https://www.wikidata.org/wiki/Q81323037) | loge | Loge Deugd en IJver |
| [Q81323610](https://www.wikidata.org/wiki/Q81323610) | loge | Loge De Geldersche Broederschap |
| [Q81323886](https://www.wikidata.org/wiki/Q81323886) | loge | Loge Vicit Vim Virtus |
| [Q81324156](https://www.wikidata.org/wiki/Q81324156) | loge | Loge Le Préjugé Vaincu |
| [Q81324356](https://www.wikidata.org/wiki/Q81324356) | loge | Loge De Friesche Trouw |
| [Q81324606](https://www.wikidata.org/wiki/Q81324606) | loge | Loge La Compagnie Durable |
| [Q81324974](https://www.wikidata.org/wiki/Q81324974) | loge | Loge De Edelmoedigheid |
| [Q81325406](https://www.wikidata.org/wiki/Q81325406) | loge | Loge De Vergenoeging |
| [Q81326010](https://www.wikidata.org/wiki/Q81326010) | loge | Loge Fides Mutua |
| [Q81373798](https://www.wikidata.org/wiki/Q81373798) | loge | Loge Orde en Vlijt |
| [Q81391381](https://www.wikidata.org/wiki/Q81391381) | loge | Loge L’Aurore |
| [Q81393845](https://www.wikidata.org/wiki/Q81393845) | loge | Loge La Persévérance (Maastricht) |
| [Q81394658](https://www.wikidata.org/wiki/Q81394658) | loge | Loge La Charité |
| [Q81395115](https://www.wikidata.org/wiki/Q81395115) | loge | Lodge Concordia (Paramaribo) |
| [Q81402717](https://www.wikidata.org/wiki/Q81402717) | loge | Loge Concordia Vincit Animos |
| [Q81407214](https://www.wikidata.org/wiki/Q81407214) | loge | Loge La Bien Aimée |
| [Q81409946](https://www.wikidata.org/wiki/Q81409946) | loge | Loge La Paix |
| [Q82805135](https://www.wikidata.org/wiki/Q82805135) | loge | Loge West Friesland |
| [Q82810612](https://www.wikidata.org/wiki/Q82810612) | loge | Lodge Karel van Zweden |
| [Q82896661](https://www.wikidata.org/wiki/Q82896661) | loge | Torche du Nord |
| [Q83154119](https://www.wikidata.org/wiki/Q83154119) | loge |  |
| [Q83177762](https://www.wikidata.org/wiki/Q83177762) | loge | Loge Acacia |
| [Q83178547](https://www.wikidata.org/wiki/Q83178547) | loge | Loge Jacob van Campen |
| [Q83185513](https://www.wikidata.org/wiki/Q83185513) | loge | Loge Hiram Abiff |
| [Q83186385](https://www.wikidata.org/wiki/Q83186385) | loge | Loge Broedertrouw |
| [Q83335423](https://www.wikidata.org/wiki/Q83335423) | loge | Loge De Achterhoek |
| [Q83367497](https://www.wikidata.org/wiki/Q83367497) | loge | Loge Moed en Volharding |
| [Q83367715](https://www.wikidata.org/wiki/Q83367715) | loge | Loge Tubantia |
| [Q83368342](https://www.wikidata.org/wiki/Q83368342) | loge | Loge De Veluwe |
| [Q83368825](https://www.wikidata.org/wiki/Q83368825) | loge | Loge Het Noorderlicht |
| [Q83630385](https://www.wikidata.org/wiki/Q83630385) | loge | Union and Progress Masonic Lodge |
| [Q84399729](https://www.wikidata.org/wiki/Q84399729) | loge |  |
| [Q84571836](https://www.wikidata.org/wiki/Q84571836) | loge | Loge Ad Lucem et Pacem |
| [Q84571921](https://www.wikidata.org/wiki/Q84571921) | loge | Loge Kennemerland |
| [Q84572469](https://www.wikidata.org/wiki/Q84572469) | loge | Loge De Vriendschap |
| [Q84572492](https://www.wikidata.org/wiki/Q84572492) | loge | Loge In Vrijheid Eén |
| [Q84750349](https://www.wikidata.org/wiki/Q84750349) | loge | Loge Ken U Zelven |
| [Q84755630](https://www.wikidata.org/wiki/Q84755630) | loge | Loge De Gooische Broederschap |
| [Q85081439](https://www.wikidata.org/wiki/Q85081439) | loge | Lumière et Justice |
| [Q85802888](https://www.wikidata.org/wiki/Q85802888) | loge | St. John's Lodge |
| [Q86667326](https://www.wikidata.org/wiki/Q86667326) | loge |  |
| [Q86674619](https://www.wikidata.org/wiki/Q86674619) | loge |  |
| [Q87037948](https://www.wikidata.org/wiki/Q87037948) | loge | Réunion des amis choisis |
| [Q87336981](https://www.wikidata.org/wiki/Q87336981) | loge |  |
| [Q87996852](https://www.wikidata.org/wiki/Q87996852) | obédience | Grand Lodge of North Carolina |
| [Q882995](https://www.wikidata.org/wiki/Q882995) | obédience | Grande Loge des anciens maçons libres et acceptés d'Allemagne |
| [Q88463644](https://www.wikidata.org/wiki/Q88463644) | loge | loge Indépendance et Progrès |
| [Q887508](https://www.wikidata.org/wiki/Q887508) | loge |  |
| [Q8964885](https://www.wikidata.org/wiki/Q8964885) | obédience | Grand lodge of Catalonia |
| [Q9068290](https://www.wikidata.org/wiki/Q9068290) | loge | Manuel Iradier |
| [Q9178198](https://www.wikidata.org/wiki/Q9178198) | loge |  |
| [Q9195128](https://www.wikidata.org/wiki/Q9195128) | loge |  |
| [Q9198801](https://www.wikidata.org/wiki/Q9198801) | loge |  |
| [Q9210709](https://www.wikidata.org/wiki/Q9210709) | loge | Masonic Lodge in Kętrzyn |
| [Q92811346](https://www.wikidata.org/wiki/Q92811346) | loge | Freemason's lodge "Friend of Humanity" |
| [Q932593](https://www.wikidata.org/wiki/Q932593) | obédience | Grande Loge féminine de France |
| [Q9336899](https://www.wikidata.org/wiki/Q9336899) | loge |  |
| [Q9343576](https://www.wikidata.org/wiki/Q9343576) | loge |  |
| [Q9351617](https://www.wikidata.org/wiki/Q9351617) | loge | Szkoła Sokratesa |
| [Q9362201](https://www.wikidata.org/wiki/Q9362201) | loge | Trois Frères |
| [Q9368729](https://www.wikidata.org/wiki/Q9368729) | loge |  |
| [Q9372990](https://www.wikidata.org/wiki/Q9372990) | loge |  |
| [Q9396537](https://www.wikidata.org/wiki/Q9396537) | loge |  |
| [Q954078](https://www.wikidata.org/wiki/Q954078) | loge | Propaganda Due |
| [Q959344](https://www.wikidata.org/wiki/Q959344) | loge\|obédience | Grande Loge de France |
| [Q96016145](https://www.wikidata.org/wiki/Q96016145) | loge | Le Liban (loge maçonnique) |
| [Q96606139](https://www.wikidata.org/wiki/Q96606139) | loge | loge maçonnique de Rueil-Malmaison |
| [Q96984455](https://www.wikidata.org/wiki/Q96984455) | loge | Kayssoun |
| [Q97208448](https://www.wikidata.org/wiki/Q97208448) | loge |  |
| [Q97231607](https://www.wikidata.org/wiki/Q97231607) | obédience | Grand Symbolic Lodge of Croatia |
| [Q97231623](https://www.wikidata.org/wiki/Q97231623) | loge | Grand Regular Lodge of Croatia |
| [Q97924205](https://www.wikidata.org/wiki/Q97924205) | loge | La Concorde |
| [Q97924511](https://www.wikidata.org/wiki/Q97924511) | loge | L'Aurore sociale |
| [Q98959812](https://www.wikidata.org/wiki/Q98959812) | loge | Fidélité et Travail |
| [Q99469793](https://www.wikidata.org/wiki/Q99469793) | loge | Les Droits de l'Homme |

---

## Commission trilatérale (Trilateral Commission)

| QID | Libellé (indicatif) | Commentaire |
|-----|---------------------|-------------|
| **Q218868** | Commission trilatérale / Trilateral Commission | Organisation **internationale** attendue pour les listes de membres. |
| **Q59700287** | « Trilateral Commission » | Homonyme : item classé comme **rapport du Congressional Research Service** (`P31` : rapport), **pas** la commission — **à exclure** pour un import d’organisation. |
| *Q66847285, Q63903116, …* | archives / réunions NAID | Fonds ou événements documentaires — **pas** l’organisation. |

- Wikidata : [Q218868](https://www.wikidata.org/wiki/Q218868)

---

## Council on Foreign Relations (CFR)

| QID | Libellé (indicatif) | Commentaire |
|-----|---------------------|-------------|
| **Q594712** | Council on Foreign Relations | Think tank / organisation **principale** (États-Unis). |
| **Q11964146** | Council on Foreign Relations’ historie | Ouvrage / notice liée à l’**histoire** du CFR (libellé norvégien « historie ») — **pas** l’organisation CFR elle-même. |

- Wikidata : [Q594712](https://www.wikidata.org/wiki/Q594712)

---

## Rappel commande d’import (membres `P463`)

```bash
php bin/console app:wikidata:sync-org-members --organization-qid=Q3227220 --nationality-iso=FR
# ou résolution par titre Wikipédia français :
php bin/console app:wikidata:sync-org-members --fr-wiki-title=Le_Siècle --nationality-iso=FR
```

Remplacer le `Q…` par l’item retenu dans le tableau ci-dessus après vérification sur Wikidata.

---

*Document généré pour faciliter les imports ; les QID homonymes ou annexes sont listés volontairement pour éviter les erreurs de fusion.*
