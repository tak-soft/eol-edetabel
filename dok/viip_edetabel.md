# EOL Edetabel 2026

EOL edetabeli eesmärk 

EOLi orienteerumise edetabeli eesmärgiks Eesti parimate MN21 orienteerujate reastamine individuaalsete 
võistlustulemuste põhjal järgmistel aladel: 
• Orienteerumisjooks 
• Orienteerumisjooks- Sprint 
• Rattaorienteerumine 
• Suusaorienteerumine 

EOL edetabelisse pääsevad orienteerujad, kes on Eesti Vabariigi kodanikud ja kellel on olemas vastav märge 
EST, Rahvusvahelise Orienteerumisliidu (IOF) andmebaasis Eventor.


Eesmärk on teha uus Eesti Orienteerumisliidu põhiklasside edetabeli rakendus.

Rakendus impordib andmed IOF Ranking süsteemi (WRS) API kaudu võistlustulemused koos arvutatud edetabeli punktiga.

Rakendus arvutab jooksva edetabeli punktid vastavalt juhendile:

Võistlejale arvutatakse võistluse edetabelipunktid IOF edetabeli reeglite kohaselt.  
EOLi jooksvasse edetabelisse arvestatakse IOF edetabelist viimase 12 kuu tulemused. 
Võistlusalade kaupa on arvesse minevate tulemuste arv: 

• Orienteerumisjooks – 4  
• Orienteerumisjooks Sprint -3 
• Rattaorienteerumine - 3 
• Suusaorienteerumine -3  


## Mittefunktsionaalsed nõuded:


* andmebaas mySQL
* raamistik PHP 8.x
* Stiili failina kasutaks https://orienteerumine.ee/wp-content/themes/eol/assets/dist/css/pp-app-theme.css
* Väljanägemine sarnane vanale edetabelile: https://orienteerumine.ee/edetabel/
* Edetabel hakkab olema alamdomeenil edetabel.orienteerumine.ee

## Andmete laadimine

IOF API kaudu laetakse kord päevas automaatselt viimase kuu aja WRE punktid kõigi 4 ala kohta. Andmete laadimiseks teha API. Cron töö regulaarselt kutsub välja API otspunkti.

Andmed salvestatakse lokaalsesse andmebaasi

* uuendatakse isikute tabelit
* lisatakse uued võistlused
* uuendatakse tulemused


#### Andmebaasi tabelid:

Tuleb luua mySQL andmebaas järgmiste tabelitega:

Võistluste tabel : iofevents

- eventorId - Unique, Index
- kuupäev - date
- nimetus - string
- distants - string
- riik - string[3]
- alatunnus -- F|FS|M|S|T

Isikute tabel : iofrunners

- iofId - Unique, Index
- firstname - string
- lastname - string
 

Tulemused : iofresults

- id - Index
- eventorId -- iofevents.eventorId
- iofId -- iofrunners.iofId
- tulemus -- numbri kujul aeg sekundites
- koht võistlusel -- number
- WRE punktid -- number
 

Alade seadistused : edetabli_seaded

- id - idx
- aasta -number
- nimetus - string
- alakood - string
- periood_lopp - date
- periood_kuud - number - kui palju kuid võetakse arvesse
- arvesse - number - kui palju parimaid punkte läheb arvesse


### Seadistused

Teenused seadistused (konfiguratsioonis failist):

- URL
- IOF API Key hoidmine (RankingAPIKeyForFederation)
- alade loetelu ja järjekord
- mySQL ühenduse andmed


## Kuvamine


### Avalehekülg, edetabeli ülevaade


| Koht | Eesnimi Perenimi | Punktid |


* tulemuste kuvamine: Valida saab perioodi, vaikimis käesolev aasta.
* Kuvatakse edetabeli seis 2 veergu,kui ekraan on kitsam kui 600px, siis ühes veerus -(naised, mehed) vastavalt reeglile (periood, arvesse minevad punktid),
* Edetabeli liider eraldi välja tooduna
* Järgmised kohad 2-10 Nimekirjana
* Viide detailsemale leheküljele (ala ja perioodi põhine)

Alade jaotus:

 || -----------|--------- |
 || Orienteerumisjooks    | 
 || Naised    |  Mehed    | 
 || Orienteerumisjooks Sprint  | 
 || Naised    |  Mehed    | 
 || Rattaorienteerumine   | 
 || Naised    |  Mehed    | 
 || Suusaorienteerumine   | 
 || Naised    |  Mehed    | 


### Punktide detailsem vaade

| Koht| Eesnimi Perenimi | iofId | Punktid kokku| \[arvesse minevad võistluste punktid

* Punktid on sorteeritult kahanevalt
* Punktide peal tooltip kuvab võistluse nime ja klikides punktidel avaneb viide IOF Rankingu lehele https://ranking.orienteering.org/ResultsView?event=\[eventorId]\&person=\[iofId]\&ohow=\[alatunnus]


* Viide IOF Eventori/Rankingu lehele https://ranking.orienteering.org/ResultsView?event=[eventorId]&person=[iofId]&ohow=F
* Viide võistleja vaatele


### Võistleja tulemuste vaade


* Kuvatakse tema kõik punktid valitud alal, sorteeritud toimumise järjekorras
* Päises võistleja nimi


| Kuupäev | Võistlus | Tulemus | Koht | Punktid


### Edetabeli API

* API andmete laadimiseks IOF Rankingust
* API ala põhiselt EOLi edetabeli tabeli eksportimine json ja csv formaadis
* API võistleja (iofId põhiselt) edetabeli punktide ja koha pärimiseks


## IOF API kirjeldus


server: ranking.orienteering.org



WRS punktide päring riigi põhiselt ajavahemiku kohta

Vaikimisi tulemused viimase 30p kohta, päringuga parameetritega saab määrata soovitu ajavahemiku

```
GET /api/exports/federationrankings/EST?fromDate={now-1 month}&toDate={now}
X-API-Key: {RankingAPIKeyForFederation}
```

Vastus massiiv järgmiste elementidega:

```javascript

interface ApiFederationRanks {
    EventId:         number;
    EventName:       string;
    EventDate:       Date;
    EventCountry:    string;
    Distance:        string;
    Discipline:      Discipline;
    Group:           Group;
    IofId:           number;
    FirstName:       string;
    LastName:        string;
    BirthYear:       number;
    Sex:             string;
    Country:         string;
    Position:        number;
    RaceTimeSeconds: number;
    RankPoints:      number;
}

enum Discipline {
    F = "F", // Foot orienteering = Orienteerumisjooks
    FS = "FS", // Foor sprint orienteering = Orienteerumisjooks-sprint
    M = "M", // MoutainBike orienteering = Rattaorienteerumine
    S = "S", // Ski orienteering = Suusaorienteerumine
    T = "T"  // Trail orienteering = Teeraja orienteerumine
}

enum Group {
    Men = "MEN",
    Women = "WOMEN",
}
