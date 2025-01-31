<?php
function get_domainbulma_tables($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.212 Safari/537.36');

    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($httpcode == 200) {
        return $output;
    } else {
        return "Hata: HTTP Hatası - " . $httpcode . " - Curl Hatası: " . $curl_error;
    }
}

function extract_domains_from_table($html) {
    $domains = [];
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Tablodaki domainleri çek
    $domainNodes = $xpath->query("//table/tbody/tr/td[2]");

    foreach ($domainNodes as $node) {
        $domains[] = [
            'domain' => trim($node->nodeValue)
        ];
    }

    return $domains;
}

function dusenDomainleriGetir() {
    $domainbulmaUrl = "https://www.domainbulma.com/"; //"Bugün Düşecek Domainler" URL'si
    $html = get_domainbulma_tables($domainbulmaUrl);

    if (is_string($html) && strpos($html, "Hata") === 0) {
        return $html; // Hata mesajını döndür
    }

    if ($html) {
        $domains = extract_domains_from_table($html);
        return $domains;
    } else {
        return "Hata: DomainBulma'dan veri alınamadı.";
    }
}

$dusenDomainler = dusenDomainleriGetir();

if (is_array($dusenDomainler)) {
    $dusenDomainler = array_slice($dusenDomainler, 0, 35); // İlk 10 domaini al
    foreach ($dusenDomainler as $domain) {
        echo "<p><i class='fas fa-unlink'></i> {$domain['domain']}</p>";
    }
} else {
    echo $dusenDomainler;
}
?>