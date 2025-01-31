document.addEventListener('DOMContentLoaded', function() {
    const sorgulamaFormu = document.getElementById('sorgulamaFormu');
    const alanAdiUretBtn = document.getElementById('alanAdiUretBtn');
    const sonucListesi = document.getElementById('sonucListesi');
    const kelimeSayisiSelect = document.getElementById('kelimeSayisi');
    const jenerikCheckbox = document.getElementById('jenerikKelime');
    const kategoriSelect = document.getElementById('kategoriSecim');
    const jenerikLabel = document.getElementById('jenerikLabel');
    const kategoriLabel = document.getElementById('kategoriLabel');
    const bosJenerikDomainListesi = document.getElementById('bosJenerikDomainListesi');
    const harfFiltresiRadio = document.getElementById('harfFiltresi');
    const kelimeFiltresiRadio = document.getElementById('kelimeFiltresi');
    const harfFiltresiAlani = document.getElementById('harfFiltresiAlani');
    const kelimeFiltresiAlani = document.getElementById('kelimeFiltresiAlani');

    sorgulamaFormu.addEventListener('submit', function(event) {
        event.preventDefault();

        const alanAdi = document.getElementById('alanAdi').value;
        const uzantilar = document.querySelectorAll('input[name="uzantilar[]"]:checked');
        let seciliUzantilar = [];
        uzantilar.forEach((uzanti) => {
            seciliUzantilar.push(uzanti.value);
        });

        if (seciliUzantilar.length === 0) {
            alert('Lütfen en az bir uzantı seçin!');
            return;
        }

        seciliUzantilar.forEach(uzanti => {
            sorgulaVeSonucuGoster(alanAdi, uzanti);
        });
    });

    alanAdiUretBtn.addEventListener('click', function() {
        uretVeSonucuGoster();
    });

    function sorgulaVeSonucuGoster(alanAdi, uzanti) {
        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `islem=sorgula&alanadi=${encodeURIComponent(alanAdi)}&uzanti=${encodeURIComponent(uzanti)}`
        })
        .then(response => response.text())
        .then(sonuc => {
            const p = document.createElement('p');
            p.innerHTML = sonuc;
            sonucListesi.appendChild(p);
        })
        .catch(error => {
            console.error('Sorgulama Hatası:', error);
            alert('Sorgulama Hatası Oluştu!');
        });
    }

    function uretVeSonucuGoster() {
        sonucListesi.innerHTML = ''; // Mevcut sonuçları temizle
        const tireEkle = document.getElementById('tireEkle').checked;
        const sayiEkle = document.getElementById('sayiEkle').checked;
        const kelimeSayisi = document.getElementById('kelimeSayisi').value;
        const secilenUzanti = document.querySelector('input[name="uzantiSecim"]:checked').value;
        const secilenSablon = document.getElementById('sablonSecim').value;
        const jenerikKelime = kelimeSayisi === '1';
        const secilenKategori = document.getElementById('kategoriSecim').value;
        const secilenDil = document.getElementById('dilSecim').value;
        const secilenHarfSayisi = document.getElementById('harfSayisi').value;
        const secilenKelimeIcerigi = document.getElementById('kelimeIcerigi').value;

        // Filtre türünü belirle
        let secilenFiltreTuru;
        if (harfFiltresiRadio.checked) {
            secilenFiltreTuru = 'harf';
        } else if (kelimeFiltresiRadio.checked) {
            secilenFiltreTuru = 'kelime';
        }

        fetch('index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `islem=uret&tire=${tireEkle}&sayi=${sayiEkle}&kelimeSayisi=${kelimeSayisi}&uzanti=${secilenUzanti}&sablon=${secilenSablon}&jenerik=${jenerikKelime}&kategori=${secilenKategori}&dil=${secilenDil}&harfSayisi=${secilenHarfSayisi}&filtreTuru=${secilenFiltreTuru}&kelimeIcerigi=${encodeURIComponent(secilenKelimeIcerigi)}`
        })
        .then(response => response.text())
        .then(sonuc => {
            const p = document.createElement('p');
            p.innerHTML = sonuc;
            sonucListesi.appendChild(p);
        })
        .catch(error => {
            console.error('Üretme Hatası:', error);
            alert('Alan adı üretilirken bir hata oluştu!');
        });
    }

    function dusenDomainleriListele() {
        fetch('dusendomain.php', {
            method: 'GET'
        })
        .then(response => response.text())
        .then(sonuc => {
            const dusenDomainListesi = document.getElementById('dusenDomainListesi');
            dusenDomainListesi.innerHTML = sonuc;
        })
        .catch(error => {
            console.error('Hata:', error);
            document.getElementById('dusenDomainListesi').innerHTML = "<p>Hata: Düşen domainler yüklenemedi.</p>";
        });
    }

    function jenerikDomainleriListele() {
        fetch('jenerik.php', {
            method: 'GET'
        })
        .then(response => response.text())
        .then(sonuc => {
            const bosJenerikDomainListesi = document.getElementById('bosJenerikDomainListesi');
            bosJenerikDomainListesi.innerHTML = sonuc;
        })
        .catch(error => {
            console.error('Hata:', error);
            document.getElementById('bosJenerikDomainListesi').innerHTML = "<p>Hata: Boşta jenerik alan adları yüklenemedi.</p>";
        });
    }

    // Sayfa yüklendiğinde ve her 60 saniyede bir düşen domainleri listele
    dusenDomainleriListele(); // Sayfa yüklendiğinde listeyi yükle
    setInterval(dusenDomainleriListele, 60000); // Her 60 saniyede bir listeyi güncelle

    // Sayfa yüklendiğinde ve her 60 saniyede bir jenerik domainleri listele
    jenerikDomainleriListele();
    setInterval(jenerikDomainleriListele, 60000);

    kelimeSayisiSelect.addEventListener('change', function() {
        if (kelimeSayisiSelect.value === '1') {
            jenerikCheckbox.checked = true;
            kategoriSelect.disabled = false;
            jenerikLabel.style.display = 'block';
            kategoriLabel.style.display = 'block';
        } else {
            jenerikCheckbox.checked = false;
            kategoriSelect.disabled = true;
            jenerikLabel.style.display = 'none';
            kategoriLabel.style.display = 'none';
        }
    });

    jenerikCheckbox.addEventListener('change', function() {
        if (jenerikCheckbox.checked) {
            kelimeSayisiSelect.value = '1';
            kategoriSelect.disabled = false;
            jenerikLabel.style.display = 'block';
            kategoriLabel.style.display = 'block';
        } else {
            kelimeSayisiSelect.value = '2';
            kategoriSelect.disabled = true;
            jenerikLabel.style.display = 'none';
            kategoriLabel.style.display = 'none';
        }
    });

    // Filtre Tipi Seçimine Göre Alanları Göster/Gizle
    harfFiltresiRadio.addEventListener('change', function() {
        harfFiltresiAlani.style.display = 'block';
        kelimeFiltresiAlani.style.display = 'none';
    });

    kelimeFiltresiRadio.addEventListener('change', function() {
        harfFiltresiAlani.style.display = 'none';
        kelimeFiltresiAlani.style.display = 'block';
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // ... diğer kodlar ...

    const olusturBtn = document.getElementById('olusturBtn');
    const durdurBtn = document.getElementById('durdurBtn');
    let intervalId = null;

    olusturBtn.addEventListener('click', function(event) {
        event.preventDefault(); // Sayfa yenilenmesini engelle

        if (intervalId !== null) {
            clearInterval(intervalId);
            intervalId = null;
        }

        intervalId = setInterval(async function() {
            try {
                const response = await fetch('jenerik.php?islem=uret');
                const data = await response.text();
                document.getElementById('bosJenerikDomainListesi').innerHTML = data;
            } catch (error) {
                console.error('Hata:', error);
                document.getElementById('bosJenerikDomainListesi').innerHTML = "<p>Hata: Jenerik alan adları alınamadı.</p>";
            }
        }, 1000); // Her bir saniyede bir istek gönder
    });

    durdurBtn.addEventListener('click', function() {
        clearInterval(intervalId);
        intervalId = null;
    });

    async function jenerikDomainleriListele() {
        try {
            const response = await fetch('jenerik.php?islem=listele');
            const data = await response.text();
            document.getElementById('bosJenerikDomainListesi').innerHTML = data;
        } catch (error) {
            console.error('Hata:', error);
            document.getElementById('bosJenerikDomainListesi').innerHTML = "<p>Hata: Jenerik alan adları yüklenemedi.</p>";
        }
    }

    // Sayfa yüklendiğinde ve her 60 saniyede bir jenerik domainleri listele
    jenerikDomainleriListele();
    setInterval(jenerikDomainleriListele, 60000);

    // ... diğer kodlar ...
});