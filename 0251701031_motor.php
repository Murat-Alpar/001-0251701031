<?php
declare(strict_types=1);
function adim(array $s, string $a): array {
    if ($s['bitti']) {
        return $s;
    }
    $x = $s['no'] % 10;
    $y = intdiv($s['no'], 10) % 10;
    if ($a === 'M') {
        if ($s['gunluk_musteri'] >= 5) {
            return $s;
        }
        $s['musteri_sira']++;
        $s['gunluk_musteri']++;
        $m = musteri($s['no'], $s['musteri_sira']);
        $o = olay($s['no'], $s['musteri_sira']);
        $u = $m['urun'];
        $adet = $s['yogunluk'] ? 2 : 1;
        $s['yogunluk'] = false;
        $fiyat = $s['urunler'][$u]['fiyat'];
        $indirim = 5 * (($y % 3) + 1);
        if ($m['tip'] === 'Öğrenci' || $m['tip'] === 'Burslu Öğrenci') {
            $birim = intdiv($fiyat * (100 - $indirim), 100);
        } else {
            $birim = $fiyat;
        }
        $tutar = $birim * $adet;
        if ($s['elektrik'] > 0) {
            $s['elektrik']--;
            $s['memnuniyet'] = max(0, $s['memnuniyet'] - 8);
            $s['seri'] = 0;
            $s['gunluk_ret']++;
            $s['log'][] = "👤 {$m['ad']} ({$m['tip']}, bütçe {$m['butce']} TL) {$s['urunler'][$u]['ad']} istiyor → Satış yok";
        } elseif ($s['urunler'][$u]['stok'] < $adet) {
            $s['memnuniyet'] = max(0, $s['memnuniyet'] - 4);
            $s['seri'] = 0;
            $s['gunluk_ret']++;
            $s['log'][] = "👤 {$m['ad']} ({$m['tip']}, bütçe {$m['butce']} TL) {$s['urunler'][$u]['ad']} istiyor → Satış yok";
        } elseif ($m['butce'] < $tutar) {
            $s['memnuniyet'] = max(0, $s['memnuniyet'] - 3);
            $s['seri'] = 0;
            $s['gunluk_ret']++;
            $s['ret_sayac'][$u]++;
            if ($s['ret_sayac'][$u] >= 2) {
                $s['urunler'][$u]['fiyat'] -= intdiv($s['urunler'][$u]['fiyat'], 10);
                $s['ret_sayac'][$u] = 0;
            }
            $s['log'][] = "👤 {$m['ad']} ({$m['tip']}, bütçe {$m['butce']} TL) {$s['urunler'][$u]['ad']} istiyor → Satış yok";
        } else {
            $s['urunler'][$u]['stok'] -= $adet;
            $s['kasa'] += $tutar;
            $s['memnuniyet'] = min(100, $s['memnuniyet'] + 2);
            $s['ret_sayac'][$u] = 0;
            $s['seri']++;
            if ($s['seri'] >= 3) {
                $s['kasa'] += 10;
                $s['seri'] = 0;
            }
            if (!$s['zam'][$u] && $s['urunler'][$u]['stok'] <= 3) {
                $s['urunler'][$u]['fiyat'] += 5;
                $s['zam'][$u] = true;
            }
            $s['log'][] = "👤 {$m['ad']} ({$m['tip']}, bütçe {$m['butce']} TL) {$s['urunler'][$u]['ad']} istiyor → Satış: {$adet} adet";
        }
        if ($o === 'elektrik') {
            $s['elektrik'] = ($x % 2 === 1) ? 2 : 1;
            $s['log'][] = "⚡ Elektrik kesintisi";
        } elseif ($o === 'yogunluk') {
            $s['yogunluk'] = true;
            $s['log'][] = "👥 Öğle yoğunluğu";
        } elseif ($o === 'tedarik') {
            $maxU = null;
            $maxStok = -1;
            foreach ($s['urunler'] as $uk => $uv) {
                if ($uv['stok'] > $maxStok) {
                    $maxStok = $uv['stok'];
                    $maxU = $uk;
                }
            }
            if ($maxU !== null) {
                $s['urunler'][$maxU]['stok'] = max(0, $s['urunler'][$maxU]['stok'] - 2);
            }
            $s['log'][] = "📦 Tedarik sorunu";
        } elseif ($o === 'bahsis') {
            $s['kasa'] += 20;
            $s['log'][] = "😊 Memnun müşteri";
        }
        if ($s['memnuniyet'] <= 0 || $s['kasa'] < 0) {
            $s['bitti'] = true;
            $s['kazandi'] = false;
        }
        return $s;
    }
    return $s;
}
