<?php

function fetchUrl($endpoint) {
    $options = array(
        'http' => array('method' => 'GET'),
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false
        )
    );
    $context = stream_context_create($options);
    $data = @file_get_contents($endpoint, false, $context);
    if ($data === false) {
        return null;
    }
    return $data;
}

$userIp = $_SERVER['REMOTE_ADDR'] ?? "193.50.135.204";

if ($userIp == "127.0.0.1") {
    $userIp = "193.50.135.204";
}

$geoResponse = fetchUrl("http://ip-api.com/json/{$userIp}");
$geoInfo = json_decode($geoResponse);

if ($geoInfo->status === "fail") {
    $fallbackResponse = fetchUrl("http://api-adresse.data.gouv.fr/search/?q=IUT%20Nancy%20Charlemagne");
    $fallbackData = json_decode($fallbackResponse);

    $latitude = $fallbackData->features[0]->geometry->coordinates[1];
    $longitude = $fallbackData->features[0]->geometry->coordinates[0];
} else {
    $latitude = $geoInfo->lat;
    $longitude = $geoInfo->lon;
}

$trafficResponse = fetchUrl("https://carto.g-ny.org/data/cifs/cifs_waze_v2.json");
$trafficData = json_decode($trafficResponse, true);
$trafficIncidents = json_encode($trafficData['incidents']);

$weatherResponse = fetchUrl("https://www.infoclimat.fr/public-api/gfs/xml?_ll={$latitude},{$longitude}&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1QyAD4PZA%3D%3D&_c=19f3aa7d766b6ba91191c8be71dd1ab2");

$pollutionResponse = fetchUrl("https://api.waqi.info/feed/here/?token=6b2bcfeae5cf939d58d60c5e4617879531a8e65d");
$pollutionData = json_decode($pollutionResponse, true);

$pollutionQuality = "Indisponible";
if ($pollutionData['status'] === 'ok') {
    $aqi = $pollutionData['data']['aqi'];
    if ($aqi <= 50) {
        $pollutionQuality = "Bonne";
    } elseif ($aqi <= 100) {
        $pollutionQuality = "Moyenne";
    } else {
        $pollutionQuality = "Mauvaise";
    }
}

$domXml = new DOMDocument();
$domXml->loadXML($weatherResponse);

$xslTemplate = new DOMDocument();
$xslTemplate->load("style.xsl");

$transformer = new XSLTProcessor();
$transformer->importStylesheet($xslTemplate);
$transformedHtml = $transformer->transformToXML($domXml);

echo <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infos Météo, Trafic & Pollution</title>
    <link href="styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    
</head>
<body>
    <div class="container">
        <h1>Informations Locales</h1>
        <div id="map"></div>
        <div class="pollution">
            Niveau de pollution : <strong>$pollutionQuality</strong>
        </div>
        $transformedHtml
        <div class="resources">
            <h3>Ressources Utiles</h3>
            <ul>
                <li>GitHub : 
                    <a target="_blank" href="https://github.com/KiSsWave/Atmosphere">Projet GitHub</a>
                </li>
                <li>API Météo : 
                    <a target="_blank" href="https://www.infoclimat.fr/public-api/gfs/xml?_ll={$latitude},{$longitude}&_auth=...">Infoclimat</a>
                </li>
                <li>API Géolocalisation : 
                    <a target="_blank" href="http://ip-api.com/json/{$userIp}">IP API</a>
                </li>
                <li>API Trafic : 
                    <a target="_blank" href="https://carto.g-ny.org/data/cifs/cifs_waze_v2.json">Trafic CIFS</a>
                </li>
                <li>API Pollution : 
                    <a target="_blank" href="https://api.waqi.info/feed/here/?token=6b2bcfeae5cf939d58d60c5e4617879531a8e65d">WAQI API</a>
                </li>
            </ul>
        </div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        var map = L.map('map').setView([$latitude, $longitude], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

        var incidents = $trafficIncidents;

        incidents.forEach(incident => {
            var coordinates = incident.location.polyline.split(' ');
            var incidentLat = parseFloat(coordinates[0]);
            var incidentLon = parseFloat(coordinates[1]);

            L.marker([incidentLat, incidentLon]).addTo(map)
                .bindPopup(incident.short_description)
                .openPopup();
        });

        L.marker([$latitude, $longitude]).addTo(map)
            .bindPopup('Localisation actuelle')
            .openPopup();
    </script>
</body>
</html>
HTML;

?>
