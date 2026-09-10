# AVG-afwegingen

Dit document beschrijft welke persoonsgegevens de flow raakt en hoe daarmee wordt omgegaan. Het is een geheugensteun en een verantwoording van keuzes — **geen juridisch advies en geen garantie dat de flow "AVG-proof" is**. Geen enkele opzet kan dat hard garanderen. Wat dit document wél doet: de afwegingen expliciet maken en aangeven waar een jurist, DPO of functionaris gegevensbescherming een keuze moet toetsen.

## Uitgangspunt: dataminimalisatie

Het leidende principe is dat bij elke stap alleen die gegevens worden verwerkt die strikt nodig zijn, en dat elke ontvanger alleen krijgt wat bij zijn rol past. Twee partijen in dezelfde flow kunnen bewust verschillende informatieniveaus krijgen.

## Welke gegevens raakt de flow?

| Gegeven | Waar | Bijzonder persoonsgegeven? |
|---|---|---|
| Werknemersnummer | overal | nee, maar wel herleidbaar tot een persoon |
| Datum van afwezigheid | ziekmelding, HR, dossier | nee |
| Dienstgegevens (tijden) | rooster, HR | nee |
| Naam medewerker | HR-berichten, kandidaatkeuze | nee (gewoon persoonsgegeven) |
| E-mailadres | bij benaderen kandidaat | nee (gewoon persoonsgegeven) |
| **Reden van het verzuim** | **nergens** | **ja — gezondheidsgegeven** |

De laatste rij is de belangrijkste. De reden of aard van een ziekmelding is een **gezondheidsgegeven** en daarmee een bijzonder persoonsgegeven, dat extra bescherming geniet. Zulke gegevens horen in de regel niet in een automation of een extern kanaal thuis.

## Kernkeuze: de reden blijft buiten de flow

De medewerker geeft bij een ziekmelding **alleen** een werknemersnummer en een datum door. Geen reden, geen aard, geen klachten. De flow werkt uitsluitend met "afwezig", niet met "waarom".

Dit is bewust en op meerdere plekken afgedwongen:

- Het meldformaat (`ziek <werknr> <datum>`) laat geen ruimte voor een reden.
- Waar de flow een reden zou kunnen tegenkomen (bijvoorbeeld in vrije tekst), wordt die niet uitgelezen of doorgegeven.
- Berichten aan HR, de collega en het uitzendbureau bevatten uitsluitend procesinformatie — nooit iets over de gezondheid van de medewerker.

## Informatie per ontvanger

Elke ontvanger krijgt bewust een ander informatieniveau:

| Ontvanger | Krijgt wel | Krijgt niet |
|---|---|---|
| HR | werknemersnummer, naam, dienst, datum, status vervanging | reden van verzuim |
| Gevraagde collega | datum, dienst, tijden | wie ziek is, waarom, welk nummer |
| Uitzendbureau | datum, dienst, uren, functie | namen, werknemersnummers, reden |

De gevraagde collega krijgt zelfs **minder** dan strikt nodig lijkt: hij hoeft niet te weten wie ziek is om te beslissen of hij een dienst wil draaien. Het uitzendbureau krijgt het absolute minimum om een aanvraag te kunnen beoordelen.

## Externe kanalen

Berichten lopen in de simulatie via e-mail; in productie is een dienst als WhatsApp denkbaar. Zulke kanalen lopen via derde partijen. Daar mogen **geen bijzondere persoonsgegevens doorheen** — wat hier gewaarborgd is doordat de reden van verzuim sowieso nooit in de flow zit.

Bij een productiekanaal via een externe partij hoort een verwerkersovereenkomst en een afweging over waar die partij data verwerkt en opslaat. Dat is een punt om met een jurist of DPO af te stemmen.

## Bewaren en verwijderen

Het uitgangspunt is dat de automatisering zelf zo min mogelijk bewaart. De afwezigheid en de status van de vervanging horen thuis in het medewerkersdossier (de bron), niet in tussenopslag van de flow. Tijdelijke gegevens — zoals een beschikbaarheidsmelding in de pool — horen te verlopen zodra ze niet meer relevant zijn.

> **Status in dit project:** de pool verwijdert verlopen beschikbaarheden (datums in het verleden) automatisch bij het uitlezen. Het wegschrijven van de uitkomst naar een dossier en het opschonen van afgeronde meldingen is in deze proof-of-concept nog niet volledig uitgewerkt en zou in productie een expliciet onderdeel van het ontwerp moeten zijn.

## Sectorspecifieke wetgeving

Naast de AVG spelen afhankelijk van de casus andere wetten mee. Bij het inplannen van vervangers is dat vooral de **Arbeidstijdenwet** (maximum uren, verplichte rusttijden). De flow signaleert dit met een controle op uren en rusttijd, maar die controle is een praktische benadering en geen volledige wettelijke toetsing — zie het beslissingenlog.

## Waar een mens of specialist moet toetsen

Deze flow neemt routinewerk over, maar vervangt geen juridische toetsing. Punten die door een jurist, DPO of functionaris gegevensbescherming bekeken zouden moeten worden:

- de grondslag en het bewaarbeleid voor de verwerkte gegevens;
- de verwerkersovereenkomst en dataverwerking bij een extern meldkanaal;
- of de kandidaatselectie voldoet aan de volledige Arbeidstijdenwet;
- de beveiliging van de opslag en de API in een productieomgeving.