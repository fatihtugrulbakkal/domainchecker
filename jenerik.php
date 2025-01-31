<?php
function alanAdiBosMu($alanAdi, $uzanti) {
    $alanAdiTam = $alanAdi . $uzanti;
    return !checkdnsrr($alanAdiTam, "A");
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

function alanAdiUret($kelimeListesi, $uzanti, $jenerikKelime = false, $kategori = "isim", $sablon = "sifat-isim", $tireEkle = false, $sayiEkle = false, $kelimeSayisi = 2, $dil = "tr", $harfSayisi = 0, $filtreTuru = "harf", $kelimeIcerigi = "") {
    $uretilenAlanAdi = "";
    $isJenerik = false;

    if ($jenerikKelime && isset($kelimeListesi[$kategori])) {
        $uygunKelimeler = [];
        foreach ($kelimeListesi[$kategori] as $kelime) {
            $kelimeUzunlugu = mb_strlen($kelime, 'UTF-8');
            $eslesmeDurumu = ($filtreTuru == 'harf' && ($harfSayisi == 0 || $kelimeUzunlugu == $harfSayisi)) ||
                              ($filtreTuru == 'kelime' && strpos($kelime, $kelimeIcerigi) !== false);

            if ($eslesmeDurumu) {
                $uygunKelimeler[] = $kelime;
            }
        }

        if (!empty($uygunKelimeler)) {
            $rastgeleKelime = $uygunKelimeler[array_rand($uygunKelimeler)];
            $uretilenAlanAdi = trim($rastgeleKelime);
            $isJenerik = true;
        } else {
            return [
                'domain' => '',
                'isJenerik' => false,
                'error' => 'Belirtilen kriterlere uygun kelime bulunamadı.'
            ];
        }
    } else {
        // Şablon Tabanlı Üretim
        $sablonParcalari = explode("-", $sablon);
        $kelimeSayac = 0;

        foreach ($sablonParcalari as $kategori) {
            if (isset($kelimeListesi[$kategori]) && $kelimeSayac < $kelimeSayisi) {
                $uygunKelimeler = [];
                foreach ($kelimeListesi[$kategori] as $kelime) {
                    $kelimeUzunlugu = mb_strlen($kelime, 'UTF-8');
                    $eslesmeDurumu = ($filtreTuru == 'harf' && ($harfSayisi == 0 || $kelimeUzunlugu == $harfSayisi)) ||
                                      ($filtreTuru == 'kelime' && strpos($kelime, $kelimeIcerigi) !== false);

                    if ($eslesmeDurumu) {
                        $uygunKelimeler[] = $kelime;
                    }
                }

                if (!empty($uygunKelimeler)) {
                    $rastgeleKelime = $uygunKelimeler[array_rand($uygunKelimeler)];
                    $uretilenAlanAdi .= trim($rastgeleKelime);
                    $kelimeSayac++;
                } else {
                    return [
                        'domain' => '',
                        'isJenerik' => false,
                        'error' => 'Belirtilen kriterlere uygun kelime bulunamadı.'
                    ];
                }
            }

            if ($kelimeSayac < $kelimeSayisi && $tireEkle && $dil == "tr") {
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

    $alanAdi = ($dil == 'tr' ? turkceKarakterleriCevir($uretilenAlanAdi) : $uretilenAlanAdi) . $uzanti;

    return [
        'domain' => $alanAdi,
        'isJenerik' => $isJenerik,
        'kelime' => isset($rastgeleKelime) ? $rastgeleKelime : ''
    ];
}

function turkceKarakterleriCevir($metin) {
    $turkce = array("ç", "ğ", "ı", "İ", "ö", "ş", "ü", "Ç", "Ğ", "I", "Ö", "Ş", "Ü");
    $ingilizce = array("c", "g", "i", "i", "o", "s", "u", "C", "G", "I", "O", "S", "U");
    return str_replace($turkce, $ingilizce, $metin);
}

// Sabitler
define('DOSYA_ADI', 'jenerik_domainler.txt');
define('KELIME_DOSYASI', 'kelimeler.txt');
define('UZANTI', '.com');
define('SABLONLAR', ["sifat-isim", "isim-fiil", "isim-isim", "sifat-sifat"]);
define('JENERIK_KATEGORI', 'isim');
define('DIL', 'tr');

function yeniJenerikAlanAdiUret($kelimeListesi){
    $tireEkle = false;
    $sayiEkle = false;
    $kelimeSayisi = 1;
    $secilenUzanti = UZANTI;
    $secilenSablon = SABLONLAR[array_rand(SABLONLAR)];
    $jenerikKelime = $kelimeSayisi === 1;
    $secilenKategori = JENERIK_KATEGORI;
    $secilenDil = DIL;
    $secilenHarfSayisi = 0;
    $secilenFiltreTuru = 'harf';
    $secilenKelimeIcerigi = '';

    $alanAdi = alanAdiUret($kelimeListesi, $secilenUzanti, $jenerikKelime, $secilenKategori, $secilenSablon, $tireEkle, $sayiEkle, $kelimeSayisi, $secilenDil, $secilenHarfSayisi, $secilenFiltreTuru, $secilenKelimeIcerigi);

    return $alanAdi;
}

function bosAlanAdlariniListele() {
    $jenerikDomainler = [];

    if (file_exists(DOSYA_ADI)) {
        $dosya = fopen(DOSYA_ADI, "r");
        if ($dosya) {
            while (($satir = fgets($dosya)) !== false) {
                $domain = trim($satir);
                if (!empty($domain) && alanAdiBosMu($domain, '')) {
                    $jenerikDomainler[] = $domain;
                }
            }
            fclose($dosya);
        }
    }

    return $jenerikDomainler;
}

function jenerikAlanAdiEkle($domain) {
    $dosya = fopen(DOSYA_ADI, "a");
    if ($dosya) {
        fwrite($dosya, $domain . "\n");
        fclose($dosya);
        return true;
    }
    return false;
}

if (isset($_GET['islem']) && $_GET['islem'] == 'uret') {
    $kelimeListesi = kelimeListesiYukle(KELIME_DOSYASI);
    if (is_string($kelimeListesi)) {
        echo $kelimeListesi; // Hata mesajını döndür
    } else {
        $yeniAlanAdi = yeniJenerikAlanAdiUret($kelimeListesi);
        if (isset($yeniAlanAdi['domain']) && !empty($yeniAlanAdi['domain']) && alanAdiBosMu($yeniAlanAdi['domain'], '')) {
            if (jenerikAlanAdiEkle($yeniAlanAdi['domain'])) {
                // Ekleme başarılı
            } else {
                echo "<p>Hata: Alan adı dosyaya eklenemedi.</p>";
            }
        } else {
            if (isset($yeniAlanAdi['error'])) {
                echo "<p>Hata: " . htmlspecialchars($yeniAlanAdi['error']) . "</p>";
            } else {
                // Üretim hatası veya alan adı dolu
            }
        }
    }
    exit;

} elseif (isset($_GET['islem']) && $_GET['islem'] == 'listele') {
    $bosAlanAdlari = bosAlanAdlariniListele();
    if (!empty($bosAlanAdlari)) {
        echo "<ul>";
        foreach ($bosAlanAdlari as $domain) {
            echo "<li><i class='fas fa-star'></i> " . htmlspecialchars($domain) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Boşta jenerik alan adı bulunamadı.</p>";
    }
    exit;
}

// Diğer durumlarda HTML içeriğini döndür
?>
