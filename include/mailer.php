<?php

function hantar_email_smtp($conn, $kepada, $subjek, $kandungan_html, &$ralat = null)
{
    $host = tetapan($conn, 'smtp_host', 'smtp.gmail.com');
    $port = (int)tetapan($conn, 'smtp_port', '587');
    $pengguna = tetapan($conn, 'smtp_email', '');
    $kata_laluan = str_replace(' ', '', tetapan($conn, 'smtp_app_password', ''));
    $nama_pengirim = tetapan($conn, 'smtp_nama_pengirim', 'Klinik Pergigian');

    if ($pengguna === '' || $kata_laluan === '') {
        $ralat = 'Tetapan SMTP belum lengkap.';
        return false;
    }

    $soket = @fsockopen($host, $port, $errno, $errstr, 15);
    if (!$soket) {
        $ralat = "Gagal sambung ke $host:$port ($errstr)";
        return false;
    }

    stream_set_timeout($soket, 15);

    $baca = function () use ($soket) {
        $data = '';
        while ($baris = fgets($soket, 515)) {
            $data .= $baris;
            if (isset($baris[3]) && $baris[3] === ' ') {
                break;
            }
        }
        return $data;
    };

    $hantar = function ($arahan, $jangkaan) use ($soket, $baca, &$ralat) {
        if ($arahan !== null) {
            fwrite($soket, $arahan . "\r\n");
        }
        $balasan = $baca();
        $kod = (int)substr($balasan, 0, 3);
        if (!in_array($kod, (array)$jangkaan, true)) {
            $ralat = 'SMTP: ' . trim($balasan);
            return false;
        }
        return true;
    };

    $langkah = $hantar(null, 220)
        && $hantar('EHLO klinik.local', 250)
        && $hantar('STARTTLS', 220);

    if (!$langkah) {
        fclose($soket);
        return false;
    }

    if (!stream_socket_enable_crypto($soket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        $ralat = 'Gagal mulakan TLS.';
        fclose($soket);
        return false;
    }

    $sempadan = 'bat-' . md5(uniqid('', true));
    $dari = "$nama_pengirim <$pengguna>";

    $kepala = "From: $dari\r\n";
    $kepala .= "To: <$kepada>\r\n";
    $kepala .= 'Subject: =?UTF-8?B?' . base64_encode($subjek) . "?=\r\n";
    $kepala .= "MIME-Version: 1.0\r\n";
    $kepala .= "Content-Type: multipart/alternative; boundary=\"$sempadan\"\r\n\r\n";
    $kepala .= "--$sempadan\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n";
    $kepala .= strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $kandungan_html)) . "\r\n\r\n";
    $kepala .= "--$sempadan\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n";
    $kepala .= $kandungan_html . "\r\n\r\n--$sempadan--\r\n";

    $kandungan_selamat = preg_replace('/^\./m', '..', $kepala);

    $berjaya = $hantar('EHLO klinik.local', 250)
        && $hantar('AUTH LOGIN', 334)
        && $hantar(base64_encode($pengguna), 334)
        && $hantar(base64_encode($kata_laluan), 235)
        && $hantar("MAIL FROM:<$pengguna>", 250)
        && $hantar("RCPT TO:<$kepada>", [250, 251])
        && $hantar('DATA', 354)
        && $hantar($kandungan_selamat . "\r\n.", 250);

    $hantar('QUIT', [221, 250]);
    fclose($soket);

    return $berjaya;
}
