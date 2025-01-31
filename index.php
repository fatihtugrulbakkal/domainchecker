<?php

function alanAdiBosMu($alanAdi, $uzanti) {
    $alanAdiTam = $alanAdi . $uzanti;
    $kayitlar = dns_get_record($alanAdiTam, DNS_A + DNS_AAAA + DNS_MX);
    return empty($kayitlar);
}

function kelimeListesiYukle($dosya) {
    $kelimeListesi = array();
    $kategori = "";
    $satirlar = file($dosya, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($satirlar as $satir) {
        if (strpos($satir, '[') !== false) {
            $kategori = trim($satir, "[]");
            $kelimeListesi[$kategori] = array();
        } elseif (!empty($kategori)) {
            $kelimeListesi[$kategori][] = trim($satir);
        }
    }

    return $kelimeListesi;
}

function alanAdiUret($kelimeListesi, $uzanti, $jenerikKelime = false, $kategori = "isim", $sablon = "sifat-isim", $tireEkle = false, $sayiEkle = false, $kelimeSayisi = 2, $dil = "tr") {
    $uretilenAlanAdi = "";
    $isJenerik = false;

    if ($jenerikKelime && isset($kelimeListesi[$kategori])) {
        $rastgeleKelime = $kelimeListesi[$kategori][array_rand($kelimeListesi[$kategori])];
        $uretilenAlanAdi = trim($rastgeleKelime);
        $isJenerik = true;
    } else {
        $sablonParcalari = explode("-", $sablon);

        foreach ($sablonParcalari as $kategori) {
            if (isset($kelimeListesi[$kategori])) {
                $rastgeleKelime = $kelimeListesi[$kategori][array_rand($kelimeListesi[$kategori])];
                $uretilenAlanAdi .= trim($rastgeleKelime);
            }

            if ($tireEkle && $dil == "tr") {
                $uretilenAlanAdi .= "-";
            }
        }

        if ($tireEkle && $dil == "tr") {
            $uretilenAlanAdi = rtrim($uretilenAlanAdi, "-");
        }

        if ($sayiEkle) {
            $uretilenAlanAdi .= rand(0, 99);
        }
    }

    return [
        'domain' => turkceKarakterleriCevir($uretilenAlanAdi) . $uzanti,
        'isJenerik' => $isJenerik
    ];
}

function turkceKarakterleriCevir($metin) {
    $turkce = array("ç", "ğ", "ı", "İ", "ö", "ş", "ü", "Ç", "Ğ", "I", "Ö", "Ş", "Ü");
    $ingilizce = array("c", "g", "i", "i", "o", "s", "u", "C", "G", "I", "O", "S", "U");
    return str_replace($turkce, $ingilizce, $metin);
}

$bosJenerikDomainler = [];
$kelimeDosyasi = 'kelimeler.txt'; // Varsayılan Türkçe

if (isset($_POST['islem'])) {
    if ($_POST['islem'] == 'sorgula') {
        $alanAdi = $_POST["alanadi"];
        $uzanti = $_POST["uzanti"];
        if (alanAdiBosMu($alanAdi, $uzanti)) {
            echo "<span class='bos'>$alanAdi$uzanti</span> BOŞTA";
        } else {
            echo "<span class='dolu'>$alanAdi$uzanti</span> DOLU";
        }
    } elseif ($_POST['islem'] == 'uret') {
        $tireEkle = isset($_POST['tire']) && $_POST['tire'] === 'true';
        $sayiEkle = isset($_POST['sayi']) && $_POST['sayi'] === 'true';
        $kelimeSayisi = isset($_POST['kelimeSayisi']) ? (int)$_POST['kelimeSayisi'] : 1;
        $secilenUzanti = isset($_POST['uzanti']) ? $_POST['uzanti'] : '.com';
        $sablonlar = ["sifat-isim", "isim-fiil", "isim-isim", "sifat-sifat"];
        $secilenSablon = isset($_POST['sablon']) ? $_POST['sablon'] : $sablonlar[array_rand($sablonlar)];
        $jenerikKelime = $kelimeSayisi === 1;
        $secilenKategori = isset($_POST['kategori']) ? $_POST['kategori'] : 'isim';
        $secilenDil = isset($_POST['dil']) ? $_POST['dil'] : 'tr';
        $kelimeDosyasi = ($secilenDil == 'en') ? 'kelimelereng.txt' : 'kelimeler.txt';
        $kelimeListesi = kelimeListesiYukle($kelimeDosyasi);

        if (empty($kelimeListesi)) {
            die("Kelimeler dosyası yüklenemedi veya boş!");
        }

        $uretilenAlanAdi = alanAdiUret($kelimeListesi, $secilenUzanti, $jenerikKelime, $secilenKategori, $secilenSablon, $tireEkle, $sayiEkle, $kelimeSayisi, $secilenDil);

        if (alanAdiBosMu($uretilenAlanAdi['domain'], '')) {
            echo "<span class='bos'>{$uretilenAlanAdi['domain']}</span> BOŞTA";
            if ($uretilenAlanAdi['isJenerik']) {
                $bosJenerikDomainler[] = $uretilenAlanAdi['domain'];

                // Dosyaya kaydet
                $dosyaAdi = "jenerik_domainler.txt";
                $dosya = fopen($dosyaAdi, "a");
                fwrite($dosya, $uretilenAlanAdi['domain'] . "\n");
                fclose($dosya);
            }
        } else {
            echo "<span class='dolu'>{$uretilenAlanAdi['domain']}</span> DOLU";
        }
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alan Adı Sorgulama ve Oluşturma Paneli</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-globe"></i> Alan Adı Paneli</h1>
        <div class="panel">
            <div class="bolum" id="sorgulamaBolumu">
                <h2><i class="fas fa-search"></i> Alan Adı Sorgula</h2>
                <form id="sorgulamaFormu">
                    <input type="text" id="alanAdi" placeholder="Alan adını girin (ör: example)" required>
                    <div class="uzantilar">
                        <label><input type="checkbox" name="uzantilar[]" value=".com" checked>.com</label>
                        <label><input type="checkbox" name="uzantilar[]" value=".net">.net</label>
                        <label><input type="checkbox" name="uzantilar[]" value=".org">.org</label>
                        <label><input type="checkbox" name="uzantilar[]" value=".com.tr">.com.tr</label>
                        <label><input type="checkbox" name="uzantilar[]" value=".web.tr">.web.tr</label>
                    </div>
                    <button type="submit">Sorgula</button>
                </form>
            </div>
            <div class="bolum" id="olusturmaBolumu">
                <h2><i class="fas fa-plus-circle"></i> Alan Adı Oluştur</h2>
                <div class="secenekler">
                    <label><input type="checkbox" id="tireEkle"> Tire (-) İçersin</label>
                    <label><input type="checkbox" id="sayiEkle"> Sayı İçersin</label>
                    <label>
                        Şablon Seçin:
                        <select id="sablonSecim">
                            <option value="sifat-isim">Sıfat-İsim</option>
                            <option value="isim-fiil">İsim-Fiil</option>
                            <option value="isim-isim">İsim-İsim</option>
                            <option value="sifat-sifat">Sıfat-Sıfat</option>
                        </select>
                    </label>
                    <label>
                        Kelime Sayısı:
                        <select id="kelimeSayisi">
                            <option value="1">1 (Jenerik)</option>
                            <option value="2" selected>2</option>
                            <option value="3">3</option>
                        </select>
                    </label>
                    <label id="jenerikLabel" style="display: none;"><input type="checkbox" id="jenerikKelime"> Tek Kelime (Jenerik)</label>
                    <label id="kategoriLabel" style="display: none;">
                        Kategori Seçin:
                        <select id="kategoriSecim">
                            <option value="isim">İsim</option>
                            <option value="sifat">Sıfat</option>
                            <option value="fiil">Fiil</option>
                        </select>
                    </label>
                    <label>
                        Uzantı Seçin:
                        <label><input type="radio" name="uzantiSecim" value=".com" checked>.com</label>
                        <label><input type="radio" name="uzantiSecim" value=".net">.net</label>
                        <label><input type="radio" name="uzantiSecim" value=".org">.org</label>
                    </label>
                    <label>
                        Dil Seçin:
                        <select id="dilSecim">
                            <option value="tr">Türkçe</option>
                            <option value="en">İngilizce</option>
                        </select>
                    </label>
                </div>
                <button id="alanAdiUretBtn">Oluştur</button>
            </div>
        </div>
        <div class="sonuclar">
            <h2><i class="fas fa-list-alt"></i> Sonuçlar</h2>
            <div id="sonucListesi"></div>
        </div>
        <div class="bolum" id="dusenDomainlerBolumu">
            <h2><i class="fas fa-hourglass-end"></i> Düşen Domainler</h2>
            <div id="dusenDomainListesi">
            </div>
        </div>
        <div class="bolum" id="bosJenerikDomainlerBolumu">
            <h2><i class="fas fa-star"></i> Boşta Jenerik Alan Adları</h2>
            <div id="bosJenerikDomainListesi"></div>
        </div>
    </div>
    <script>
        var bosJenerikDomainler = [];
        <?php
        $dosyaAdi = "jenerik_domainler.txt";
        $dosya = fopen($dosyaAdi, "r");
        if ($dosya) {
            while (($satir = fgets($dosya)) !== false) {
                $domain = trim($satir);
                if (!empty($domain)) {
                    echo "bosJenerikDomainler.push('" . addslashes($domain) . "');\n";
                }
            }
            fclose($dosya);
        }
        ?>
    </script>
    <script src="script.js"></script>
</body>
</html>