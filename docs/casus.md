# Casus: ziekmelding → vervanging

Dit document beschrijft de uitgewerkte casus achter de flow: wat er gebeurt, welke scenario's er zijn, en waarom het zo is ontworpen.

## De situatie

Een bedrijf met wisseldiensten (denk aan horeca of retail) heeft dagelijks te maken met ziekmeldingen die snel opgevangen moeten worden. Het proces is tijdrovend en foutgevoelig: iemand meldt zich ziek, HR moet de dienst opzoeken, kijken wie kan invallen, collega's bellen, en als niemand kan een uitzendbureau inschakelen — vaak onder tijdsdruk.

Deze flow neemt het routinewerk over, maar laat de beslissingen die ertoe doen bij een mens.

## De twee ingangen

Medewerkers gebruiken één meldkanaal voor twee soorten berichten, elk met een vast formaat:

- **Ziekmelding:** `ziek <werknemersnummer> <datum>`
- **Beschikbaarheid:** `beschikbaar <werknemersnummer> <datum>`

Een medewerker geeft dus alleen een nummer en een datum door — nooit een naam of een reden. Berichten die geen van beide zijn, worden herkend als "onbekend" en naar een beheerder gestuurd; de flow doet er verder niets mee.

## Wat er bij een ziekmelding gebeurt

1. **Parsen en controleren.** De flow leest het werknemersnummer en de datum uit het bericht en zet de datum om naar een intern formaat.
2. **Dienst opzoeken.** In de roostertool wordt gekeken of dit nummer op die datum een dienst heeft.
3. **Beslispunt — mag dit door?** Klopt het nummer niet of staat er geen dienst gepland, dan stopt de flow en wordt HR gewaarschuwd. Er wordt niets aangevraagd. Klopt het wel, dan gaat de flow verder.
4. **HR informeren.** HR krijgt een melding met de details: wie, welke dienst, en de status van de vervanging.
5. **Kandidaat zoeken.** De flow zoekt in de pool van beschikbare medewerkers naar iemand die kan invallen — flexkrachten eerst, en alleen wie binnen de Arbeidstijdenwet past.
6. **Collega vragen.** Is er een geschikte kandidaat, dan krijgt die een bericht met alleen de dienst en datum (niet wie ziek is of waarom), en de flow wacht op een ja of nee — of tot een tijdslimiet verloopt.
7. **Uitkomst.**
   - Zegt de collega ja, dan hoort HR dat de vervanging geregeld is. Klaar.
   - Zegt niemand ja (of is er geen kandidaat), dan wordt een concept-aanvraag voor het uitzendbureau klaargezet.
8. **Uitzendaanvraag met akkoord.** De aanvraag bevat alleen datum, dienst, uren en functie — geen namen, geen reden. Hij gaat pas de deur uit nadat HR akkoord heeft gegeven.

## Wat er bij een beschikbaarheidsmelding gebeurt

De medewerker geeft door dat hij op een bepaalde datum extra kan werken. De flow schrijft dat weg naar de pool (met het tijdstip van melden) en bevestigt aan de medewerker dat hij genoteerd staat. Bij een latere ziekmelding voor die datum wordt hij als mogelijke kandidaat meegenomen.

## Hoe een kandidaat wordt gekozen

Niet iedereen in de pool is geschikt. Per kandidaat wordt gecontroleerd:

- **Werkt hij die dag al?** Dan valt hij af.
- **Komt hij die week boven de urengrens?** Dan valt hij af.
- **Houdt hij genoeg rust?** Minder dan 11 uur tussen twee diensten mag niet — dan valt hij af.

Wie overblijft, wordt gesorteerd: **flexkrachten eerst** (bedrijfsregel), daarna wie de minste uren heeft (om het werk eerlijk te verdelen). De eerste op die lijst wordt als eerste gevraagd.

## Testscenario's

De testdata is zo opgezet dat alle uitkomsten voorkomen bij een ziekmelding voor dezelfde datum:

| Kandidaat | Uitkomst | Reden |
|---|---|---|
| Flexkracht, beschikbaar, weinig uren | gekozen | flex + ruimte → als eerste gevraagd |
| Parttimer, beschikbaar, ruimte | geschikt | komt na de flexkracht |
| Collega die die dag al werkt | valt af | werkt die dag al |
| Fulltimer die al aan zijn uren zit | valt af | te veel uren deze week |

## Waarom het zo is ontworpen

De rode draad door de hele casus:

- **De reden van het verzuim komt nergens voor.** Dat is een bijzonder persoonsgegeven en hoort niet in een automation of een extern kanaal thuis. De flow werkt met "afwezig", niet met een reden.
- **Het uitzendbureau krijgt minder dan HR.** Dataminimalisatie: elke ontvanger krijgt alleen wat bij zijn rol past.
- **Bij onzekerheid of kosten beslist een mens.** Onbekend nummer, geen dienst, of een uitzendaanvraag: op die punten stopt de automatisering en neemt een mens het over.