<?php

function kod_keadaan_gigi(): array
{
    return [
        '0' => ['nama' => 'Sound', 'label_bm' => 'Gigi Sihat', 'warna' => '#2f9e44'],
        '1' => ['nama' => 'Caries', 'label_bm' => 'Karies', 'warna' => '#fa5252'],
        '2' => ['nama' => 'Missing', 'label_bm' => 'Gigi Hilang', 'warna' => '#adb5bd'],
        '3' => ['nama' => 'Filling', 'label_bm' => 'Tampalan', 'warna' => '#228be6'],
        '4' => ['nama' => 'For Xla', 'label_bm' => 'Untuk Cabutan', 'warna' => '#e8590c'],
        '5' => ['nama' => 'Impacted', 'label_bm' => 'Gigi Terimpak', 'warna' => '#7048e8'],
        '6' => ['nama' => 'Unerupt', 'label_bm' => 'Belum Tumbuh', 'warna' => '#fab005'],
    ];
}

function kod_gigi_sah(string $kod): bool
{
    return array_key_exists($kod, kod_keadaan_gigi());
}

function nama_keadaan_gigi(?string $kod): string
{
    $senarai = kod_keadaan_gigi();

    if ($kod === null || !isset($senarai[$kod])) {
        return '-';
    }

    return $senarai[$kod]['nama'] . ' (' . $senarai[$kod]['label_bm'] . ')';
}

function warna_keadaan_gigi(?string $kod): string
{
    $senarai = kod_keadaan_gigi();

    return isset($senarai[$kod]) ? $senarai[$kod]['warna'] : '#ced4da';
}

function set_gigi_dewasa(): array
{
    return array_merge(range(18, 11), range(21, 28), range(48, 41), range(31, 38));
}

function set_gigi_kanak(): array
{
    return array_merge(range(55, 51), range(61, 65), range(85, 81), range(71, 75));
}
