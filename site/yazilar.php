<?php
declare(strict_types=1);

/* Analiz yazıları: GitHub deposundaki (OZET_DEPO) analiz/ klasöründen çekilir, sunucuda analiz/ klasöründe saklanır.
 * analiz/index.json: {"yazilar": {"adres.json": "sürüm", ...}}
 * analiz/adres.json: {"baslik": "…", "aciklama": "…", "tarih": "YYYY-AA-GG", "konu": "…", "govde": "<p>…</p>"}
 * Site en çok 5 dakikada bir yeni/güncellenen yazılara bakar; index.json'dan çıkarılan yazı sitede gösterilmez. */

const ANALIZ_ETIKETLER = '<p><h2><h3><ul><ol><li><strong><b><em><i><a><br><blockquote>';

function analiz_klasoru(): string { return KOK . '/analiz'; }

function analiz_esitle(): void {
    try {
        if (time() - (int) depo_meta('analiz_son') < OZET_ESITLE_SN) return;
        depo_meta_yaz('analiz_son', (string) time());
        $taban = 'https://raw.githubusercontent.com/' . OZET_DEPO . '/main/analiz/';
        $g = indir(['index' => $taban . 'index.json?t=' . time()]);
        $dizin = json_decode($g['index'] ?? '', true);
        if (!is_array($dizin['yazilar'] ?? null)) return;
        $kl = analiz_klasoru();
        if (!is_dir($kl)) @mkdir($kl, 0755, true);
        $gecerli = [];
        $istek = [];
        foreach ($dizin['yazilar'] as $ad => $surum) {
            if (!is_string($ad) || !preg_match('~^[a-z0-9][a-z0-9-]{2,119}\.json$~', $ad) || !is_scalar($surum)) continue;
            $gecerli[$ad] = (string) $surum;
            if (depo_meta('analiz_' . $ad) === (string) $surum && is_file($kl . '/' . $ad)) continue;
            if (count($istek) < 8) $istek[$ad] = $taban . $ad . '?t=' . time();
        }
        if ($istek) {
            $adlar = array_keys($istek);
            $govde = indir(array_values($istek));
            foreach ($adlar as $i => $ad) {
                $j = json_decode($govde[$i] ?? '', true);
                if (!is_array($j) || !is_string($j['baslik'] ?? null) || !is_string($j['govde'] ?? null)
                    || !preg_match('~^\d{4}-\d{2}-\d{2}$~', (string) ($j['tarih'] ?? '')) || strlen($govde[$i]) > 200000) continue;
                if (@file_put_contents($kl . '/' . $ad, $govde[$i], LOCK_EX) !== false) depo_meta_yaz('analiz_' . $ad, $gecerli[$ad]);
            }
        }
        @file_put_contents($kl . '/_dizin.json', json_encode(array_keys($gecerli)), LOCK_EX);
    } catch (Throwable $x) { depo_hata($x); }
}

/** adres => [başlık, açıklama, tarih, konu, HTML gövde]; en yeni önce. */
function analiz_yukle(): array {
    $kl = analiz_klasoru();
    $adlar = json_decode((string) @file_get_contents($kl . '/_dizin.json'), true);
    if (!is_array($adlar)) return [];
    $l = [];
    foreach ($adlar as $sira => $ad) {
        if (!is_string($ad) || !preg_match('~^[a-z0-9][a-z0-9-]{2,119}\.json$~', $ad)) continue;
        $j = json_decode((string) @file_get_contents($kl . '/' . $ad), true);
        if (!is_array($j) || !is_string($j['baslik'] ?? null) || !is_string($j['govde'] ?? null)) continue;
        $tarih = preg_match('~^\d{4}-\d{2}-\d{2}$~', (string) ($j['tarih'] ?? '')) ? $j['tarih'] : '2026-01-01';
        $l[substr($ad, 0, -5)] = [temiz($j['baslik']), temiz((string) ($j['aciklama'] ?? '')), $tarih, temiz((string) ($j['konu'] ?? 'Gündem')),
            strip_tags($j['govde'], ANALIZ_ETIKETLER), $sira];
    }
    uasort($l, fn($a, $b) => [$b[2], $a[5]] <=> [$a[2], $b[5]]);
    return $l;
}

function yazi_tarih(string $t): string {
    $ay = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    [$y, $m, $d] = array_map('intval', explode('-', $t) + [0, 0, 0]);
    return $d . ' ' . ($ay[$m] ?? '') . ' ' . $y;
}

analiz_esitle();
define('YAZILAR', analiz_yukle());
