# Analiz yazıları

Site (7yirmi4.com) bu klasörü en çok 5 dakikada bir okur.

- `index.json`: `{"yazilar": {"adres.json": "sürüm"}}` — yeni yazı eklemek veya güncellemek için dosyayı ekleyip sürümü (ör. unix zamanı) değiştirin. Listeden çıkarılan yazı sitede görünmez.
- `adres.json`: `{"baslik", "aciklama", "tarih": "YYYY-AA-GG", "konu", "govde": "<p>…</p>"}`
- Sayfa adresi: `https://7yirmi4.com/?y=adres`
