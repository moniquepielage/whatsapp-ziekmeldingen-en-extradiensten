# Beslissingenlog

Dit document legt de belangrijkste ontwerpkeuzes vast: wat is besloten, waarom, en welke alternatieven zijn afgewogen. Onderaan staan de technische lessen die tijdens het bouwen zijn opgedaan.

## Ontwerpkeuzes

### Bron los van logica, via één vervangbaar punt

**Keuze:** de roostergegevens worden via een API-aanroep opgehaald, niet in de flow zelf hardgecodeerd.

**Waarom:** het doel is dat de flow later gekoppeld wordt aan een echte roostertool. Zo'n tool bevraag je via een API die JSON teruggeeft. Door de databron nu al als een aparte, vervangbare stap te bouwen, verandert er bij de overstap naar een echte tool alleen het adres en de authenticatie — de rest van de flow blijft gelijk.

**Alternatieven afgewogen:** de data in de code zetten (simpelst, maar minst realistisch en niet onderhoudbaar door een niet-programmeur), of een plat bestand inlezen (CSV/Excel — makkelijk, maar bootst geen API-koppeling na). Gekozen is voor een zelfgebouwde mock-API, omdat die het echte gebruik het dichtst benadert.

### E-mail als simulatiekanaal, WhatsApp als productiekanaal

**Keuze:** de simulatie draait op e-mail; WhatsApp is gedocumenteerd als productiekanaal maar niet gebouwd.

**Waarom:** e-mail is meteen testbaar, zonder account of goedgekeurde berichtsjablonen. Doordat de verwerkingslogica los staat van het kanaal, is de overstap naar WhatsApp een dun laagje aan de rand van de flow. WhatsApp Business vereist bovendien vooraf goedgekeurde templates voor zelf-gestarte berichten en heeft een venster-regel voor wanneer je iemand mag benaderen — relevant om te documenteren, maar niet nodig om de logica te bewijzen.

### HR-akkoord alleen bij de uitzendaanvraag

**Keuze:** de collega wordt automatisch gevraagd; alleen de uitzendaanvraag krijgt een menselijk akkoord vooraf.

**Waarom:** het human-in-the-loop-principe is bedoeld voor wat geld kost, onomkeerbaar is of gevoelige gegevens raakt. Een collega vragen of hij wil bijspringen is geen van drie — het kost niets en deelt geen gevoelige informatie. De uitzendaanvraag kost wél geld, dus daar beslist een mens vooraf. Dit wijkt bewust af van een variant waarin ook het collega-voorstel eerst langs HR gaat; die is strenger dan de principes vereisen.

### Flexkrachten eerst

**Keuze:** bij het kiezen van een vervanger worden flexkrachten vóór vaste medewerkers gevraagd.

**Waarom:** een bedrijfsregel — flexkrachten zijn er juist om pieken en gaten op te vangen. De regel leeft in de sorteerstap van de kandidaatselectie, niet in de data, zodat hij makkelijk aan te passen is.

### Twee faalpaden samenvoegen zonder Merge-node

**Keuze:** waar "geen kandidaat gevonden" en "collega zei nee" samenkomen, lopen beide takken rechtstreeks naar dezelfde vervolgnode.

**Waarom:** de standaard Merge-node wachtte (in deze n8n-versie) op alle ingangen, terwijl er per uitvoering maar één tak data levert — waardoor de flow bleef hangen. Twee verbindingen naar één node lost dat eenvoudiger op. De vervolgnode haalt zijn gegevens sowieso rechtstreeks uit de roosterstap, dus samenvoegen van data was niet nodig.

## Bewuste vereenvoudigingen

Omdat dit een proof-of-concept is, zijn enkele zaken bewust vereenvoudigd. Ze staan hier zodat duidelijk is dat het keuzes zijn, geen omissies:

- **Mock-API met fictieve data** in plaats van een echte roostertool en database.
- **Vereenvoudigde ATW-check:** toetst de 11-uursrustregel en een weekgrens, niet de volledige wettelijke nuance.
- **Token in de URL** in plaats van in een header; voor productie hoort dit in een header.
- **Schrijf-endpoint alleen met token** beveiligd; productie vraagt sterkere authenticatie.

