<?php
// rooster.php — MOCK roostertool-API voor de ziekmelding/vervangingsflow.
// SIMULATIE met fictieve testdata. In een echte tool zit dit in een database
// met sterkere authenticatie; het schrijf-endpoint zou dan extra beveiligd zijn.

header('Content-Type: application/json; charset=utf-8');

// --- 1. Token-beveiliging ---
$GEHEIME_TOKEN = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';  // vul hier een geheim token in
$token = $_GET['token'] ?? '';
if (!hash_equals($GEHEIME_TOKEN, $token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Ongeldige of ontbrekende token']);
    exit;
}

// --- 2. Medewerkers (fictief). in_pool is verwijderd: beschikbaarheid komt
//        nu uit de meldingen in pool.json, niet uit een vast veld. ---
$medewerkers = [
    ['werknemersnummer' => '12345', 'naam' => 'Willem de Vries',   'email' => 'willem@example.com',       'contractsoort' => 'fulltime', 'contracturen_per_week' => 36],
    ['werknemersnummer' => '23456', 'naam' => 'Sanne Bakker',      'email' => 'sanne@example.com',        'contractsoort' => 'fulltime', 'contracturen_per_week' => 36],
    ['werknemersnummer' => '34567', 'naam' => 'Ahmed El Idrissi',  'email' => 'ahmed@example.com',        'contractsoort' => 'fulltime', 'contracturen_per_week' => 36],
    ['werknemersnummer' => '45678', 'naam' => 'Bram Jansen',       'email' => 'bram@example.com',         'contractsoort' => 'fulltime', 'contracturen_per_week' => 36],
    ['werknemersnummer' => '20001', 'naam' => 'Julia Smit',        'email' => 'julia@example.com',        'contractsoort' => 'parttime', 'contracturen_per_week' => 20],
    ['werknemersnummer' => '20002', 'naam' => 'Noor Visser',       'email' => 'noor@example.com',         'contractsoort' => 'parttime', 'contracturen_per_week' => 20],
    ['werknemersnummer' => '20003', 'naam' => 'Daan Mulder',       'email' => 'daan@example.com',         'contractsoort' => 'parttime', 'contracturen_per_week' => 20],
    ['werknemersnummer' => '20004', 'naam' => 'Lisa Peters',       'email' => 'lisa@example.com',         'contractsoort' => 'parttime', 'contracturen_per_week' => 20],
    ['werknemersnummer' => '90001', 'naam' => 'Youssef Haddad',    'email' => 'pielagemonique@gmail.com', 'contractsoort' => 'flex',     'contracturen_per_week' => 0],
    ['werknemersnummer' => '90002', 'naam' => 'Yara de Boer',      'email' => 'pielagemonique@gmail.com', 'contractsoort' => 'flex',     'contracturen_per_week' => 0],
];

$naamVan = [];
foreach ($medewerkers as $m) { $naamVan[$m['werknemersnummer']] = $m['naam']; }
$bestaatWerknr = fn($nr) => isset($naamVan[$nr]);

// --- 3. Rooster genereren (ongewijzigd) ---
$patronen = [
    '12345' => [1=>['09:00','18:00'], 2=>['09:00','17:00'], 3=>['09:00','18:00'], 4=>['09:00','18:00']],
    '23456' => [2=>['09:00','18:00'], 3=>['09:00','18:00'], 4=>['09:00','18:00'], 5=>['09:00','18:00']],
    '34567' => [1=>['09:00','18:00'], 3=>['09:00','18:00'], 4=>['09:00','18:00'], 5=>['09:00','18:00']],
    '45678' => [1=>['08:00','17:00'], 2=>['08:00','17:00'], 3=>['08:00','17:00'], 4=>['08:00','17:00']],
    '20001' => [1=>['09:00','14:00'], 3=>['09:00','14:00'], 5=>['09:00','14:00']],
    '20002' => [2=>['09:00','16:00'], 4=>['09:00','16:00']],
    '20003' => [6=>['10:00','18:00'], 7=>['10:00','18:00']],
    '20004' => [1=>['12:00','20:00'], 5=>['12:00','20:00']],
    '90001' => [4=>['18:00','23:00'], 7=>['10:00','16:00']],
    '90002' => [2=>['12:00','20:00'], 6=>['12:00','20:00']],
];

function urenTussen($start, $eind) {
    [$sh, $sm] = array_map('intval', explode(':', $start));
    [$eh, $em] = array_map('intval', explode(':', $eind));
    return round((($eh * 60 + $em) - ($sh * 60 + $sm)) / 60, 2);
}

$rooster = [];
$begin = new DateTime('2026-09-01');
$eindDatum = new DateTime('2026-09-30');
for ($d = clone $begin; $d <= $eindDatum; $d->modify('+1 day')) {
    $n = (int)$d->format('N');
    $datum = $d->format('Y-m-d');
    foreach ($patronen as $nummer => $pat) {
        if (isset($pat[$n])) {
            [$s, $e] = $pat[$n];
            $rooster[] = [
                'werknemersnummer' => $nummer,
                'naam'  => $naamVan[$nummer],
                'datum' => $datum,
                'start' => $s,
                'eind'  => $e,
                'uren'  => urenTussen($s, $e),
            ];
        }
    }
}

// --- 4. Pool-opslag helpers ---
$POOL_BESTAND = __DIR__ . '/pool.json';

function leesPool($bestand) {
    if (!file_exists($bestand)) return [];
    $inhoud = file_get_contents($bestand);
    $data = json_decode($inhoud, true);
    return is_array($data) ? $data : [];
}

function schrijfPool($bestand, $pool) {
    file_put_contents($bestand, json_encode($pool, JSON_PRETTY_PRINT), LOCK_EX);
}

// --- 5. Afhandelen op basis van 'action' ---
$action = $_GET['action'] ?? '';

// 5a. Beschikbaarheid wegschrijven
if ($action === 'beschikbaar') {
    $werknr = $_GET['werknr'] ?? '';
    $datum  = $_GET['datum'] ?? '';

    if (!$bestaatWerknr($werknr)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'reden' => 'werknemersnummer onbekend']);
        exit;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'reden' => 'datum ontbreekt of verkeerd formaat (verwacht jjjj-mm-dd)']);
        exit;
    }

    $pool = leesPool($POOL_BESTAND);

    // dubbele melding (zelfde werknr + datum) niet nog eens toevoegen
    foreach ($pool as $p) {
        if ((string)$p['werknemersnummer'] === (string)$werknr && $p['datum'] === $datum) {
            echo json_encode(['ok' => true, 'reden' => 'stond al in de pool', 'werknemersnummer' => $werknr, 'datum' => $datum]);
            exit;
        }
    }

    $pool[] = [
        'werknemersnummer' => (string)$werknr,
        'datum'            => $datum,
        'gemeld_op'        => (new DateTime())->format('Y-m-d H:i:s'),
    ];
    schrijfPool($POOL_BESTAND, $pool);

    echo json_encode(['ok' => true, 'werknemersnummer' => $werknr, 'datum' => $datum]);
    exit;
}

// 5b. Pool + medewerkers + rooster teruggeven
if ($action === 'pool') {
    $vandaag = (new DateTime('today'))->format('Y-m-d');
    $ruwePool = leesPool($POOL_BESTAND);

    // alleen beschikbaarheden voor vandaag of later
    $pool = array_values(array_filter($ruwePool, fn($p) => $p['datum'] >= $vandaag));

    echo json_encode([
        'medewerkers' => $medewerkers,
        'rooster'     => $rooster,
        'pool'        => $pool,
    ]);
    exit;
}

// 5c. Dienst opzoeken (ongewijzigd)
if ($action === 'dienst') {
    $werknr = $_GET['werknr'] ?? '';
    $datum  = $_GET['datum'] ?? '';

    $nummerBekend = $bestaatWerknr($werknr);
    $dienst = null;
    foreach ($rooster as $r) {
        if ((string)$r['werknemersnummer'] === (string)$werknr && $r['datum'] === $datum) {
            $dienst = $r;
            break;
        }
    }

    echo json_encode([
        'nummerBekend'   => $nummerBekend,
        'dienstGevonden' => $dienst !== null,
        'dienst'         => $dienst,
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Onbekende action. Gebruik ?action=pool, ?action=dienst of ?action=beschikbaar']);