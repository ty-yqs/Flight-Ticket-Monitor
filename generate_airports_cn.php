<?php
// Downloads OpenFlights airports.dat and generates airports_cn.json
$url = 'https://raw.githubusercontent.com/jpatokal/openflights/master/data/airports.dat';
echo "Downloading airports.dat...\n";
$data = @file_get_contents($url);
if ($data === false) {
    echo "Failed to download $url\n";
    exit(1);
}

$lines = preg_split('/\r?\n/', $data);
$out = [];
$countries = ["China", "Hong Kong", "Macau", "Taiwan"];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '') continue;
    $cols = str_getcsv($line);
    // OpenFlights columns: 0:id 1:name 2:city 3:country 4:IATA 5:ICAO 6:lat 7:lon
    if (!isset($cols[3])) continue;
    $country = $cols[3];
    if (!in_array($country, $countries, true)) continue;
    $iata = isset($cols[4]) ? $cols[4] : '\\N';
    if ($iata === '\\N' || $iata === '' ) continue;
    $name = isset($cols[1]) ? $cols[1] : '';
    $city = isset($cols[2]) ? $cols[2] : '';
    $lat = isset($cols[6]) ? (float)$cols[6] : null;
    $lon = isset($cols[7]) ? (float)$cols[7] : null;
    $out[$iata] = [
        'iata' => $iata,
        'name' => $name,
        'city' => $city,
        'country' => $country,
        'lat' => $lat,
        'lon' => $lon,
    ];
}

// Sort by IATA
ksort($out);
$json = json_encode(array_values($out), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    echo "Failed to encode JSON\n";
    exit(1);
}
$path = __DIR__ . '/airports_cn.json';
file_put_contents($path, $json);
echo "Wrote " . $path . " (" . count($out) . " airports)\n";
echo "Done.\n";
