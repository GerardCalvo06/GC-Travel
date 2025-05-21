<?php
// 1. Crear/obrir la base de dades SQLite
$db = new SQLite3('GCTravel.db');

// 2. Crear taules: paisos i ciutats
$db->exec("CREATE TABLE IF NOT EXISTS paisos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT UNIQUE
)");

$db->exec("CREATE TABLE IF NOT EXISTS ciutats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT,
    pais_id INTEGER,
    FOREIGN KEY(pais_id) REFERENCES paisos(id)
)");

// 3. Descarregar dades de l'API
$url = "https://countriesnow.space/api/v0.1/countries";
$json = file_get_contents($url);

if (!$json) {
    die();
}

$response = json_decode($json, true);

if (!isset($response['data'])) {
    die();
}

// 4. Inserir dades
foreach ($response['data'] as $pais) {
    $nomPais = $pais['country'];

    // Inserir país
    $stmtPais = $db->prepare("INSERT OR IGNORE INTO paisos (nom) VALUES (:nom)");
    $stmtPais->bindValue(':nom', $nomPais, SQLITE3_TEXT);
    $stmtPais->execute();

    // Obtenir l'ID del país inserit
    $stmtSelect = $db->prepare("SELECT id FROM paisos WHERE nom = :nom");
    $stmtSelect->bindValue(':nom', $nomPais, SQLITE3_TEXT);
    $result = $stmtSelect->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    $paisId = $row['id'] ?? null;

    if (!$paisId) {
        continue;
    }

    // Inserir ciutats associades
    if (isset($pais['cities'])) {
        foreach ($pais['cities'] as $nomCiutat) {
            $nomCiutatNetejat = iconv('UTF-8', 'UTF-8//IGNORE', $nomCiutat);

            if (empty($nomCiutatNetejat)) continue;

            $stmtCiutat = $db->prepare("INSERT INTO ciutats (nom, pais_id) VALUES (:nom, :pais_id)");
            $stmtCiutat->bindValue(':nom', $nomCiutatNetejat, SQLITE3_TEXT);
            $stmtCiutat->bindValue(':pais_id', $paisId, SQLITE3_INTEGER);
        }
    }
}

$db->close();
?>
