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

Il n’existe **pas un seul** item « la franc-maçonnerie mondiale » équivalent à une obédience unique : Wikidata distingue **concept global**, **articles de synthèse par pays**, et **obédiences** (souvent des items séparés).

| QID | Libellé (indicatif) | Commentaire |
|-----|---------------------|-------------|
| **Q41726** | freemasonry | Concept / ensemble d’organisations fraternelles — pertinent pour le **sens large** du terme. |
| **Q2585138** | Freemasonry in France | Article-thème **France** (vue d’ensemble), pas une obédience. |
| **Q2138816** | Regular Masonic jurisdictions | Notion juridictionnelle « maçonnerie régulière ». |
| **Q16238196** | Prince Hall Freemasonry | Branche historique aux États-Unis. |
| *Q3080461, Q3080471, Q3080480, Q3080484, Q3080485, …* | Freemasonry in Canada / Germany / Italy / Belgium / Spain, etc. | **Articles par pays** (synthèses). |
| **Q30025017** | Franc-maçonnerie | Entrée de **dictionnaire patrimonial local** (Rennes) — **ne pas** utiliser comme organisation nationale ou internationale pour `P463`. |
| **Q60964689** | franc-maçonnerie en Europe | Thème géographique. |

Pour des imports **membres d’une obédience précise** (ex. Grand Orient de France, Grande Loge nationale française, etc.), il faut en général **un QID dédié** à cette obédience : les rechercher sur Wikidata par **nom officiel exact**.

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
