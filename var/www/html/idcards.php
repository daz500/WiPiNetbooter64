<?php
header('Content-type: image/png'); // mudado para PNG, pois você usa imagepng

$name = $_GET["name"] ?? '';
$mode = $_GET["mode"] ?? '';
$lang = $_GET["lang"] ?? '';

$overlay = imagecreatetruecolor(644, 1020);
imagesavealpha($overlay, true);
$colour = imagecolorallocatealpha($overlay, 0, 0, 0, 127);
imagefill($overlay, 0, 0, $colour);

$out = imagecreatetruecolor(644, 1020);
imagesavealpha($out, true);
$colour = imagecolorallocatealpha($out, 0, 0, 0, 127);
imagefill($out, 0, 0, $colour);

// === Corrigido: cores válidas
$textcolour = imagecolorallocate($overlay, 0, 0, 0); // preto seguro

// === Corrigido: ângulo definido
$angle = 0;

$jptext = '/var/www/html/cardimages/'.$mode.'/jptext.png';
$iconpath = '/var/www/html/cardimages/icons/'.$mode.'/'.$lang.'/';
$includefile = 'cards/'.$mode.'/'.$name.'.printdata.php';
$path = '/boot/config/cards/'.$mode.'/';
$cardpath = 'cardimages/'.$mode.'/';

// Incluir dados do cartão
if (file_exists($includefile)) {
    include $includefile;
}

// Criar imagem base do cartão
if (isset($card) && file_exists($cardpath.$card)) {
    $cardimage = imagecreatefrompng($cardpath.$card);
} else {
    // fallback seguro
    $cardimage = imagecreatetruecolor(644, 1020);
    imagesavealpha($cardimage, true);
    $trans = imagecolorallocatealpha($cardimage, 0, 0, 0, 127);
    imagefill($cardimage, 0, 0, $trans);
}

// Caminho da fonte
$font_path = 'img/Kosugi-Regular_mod.ttf';

// === Gerar keyimage de forma segura
$keyimage = null;
if (isset($keyfilename) && file_exists($keyfilename)) {
    $keyimage = imagecreatefrompng($keyfilename);
} else {
    // fallback: imagem transparente
    $iconsize = $iconsize ?? 36;
    $keyimage = imagecreatetruecolor($iconsize, $iconsize);
    imagesavealpha($keyimage, true);
    $trans = imagecolorallocatealpha($keyimage, 0, 0, 0, 127);
    imagefill($keyimage, 0, 0, $trans);
}

// Posicionamento
$nameleft = 0;
$namesize = 60;
$left = 230;
$hpos = $left;
$carkeysize = 28;
$keyleft = 520;
$keytop = 70;
$areasize = 28;
$racersize = 28;
$iconsize = $iconsize ?? 36;

// Ajuste vertical dependendo do modo
if ($mode == "idas") {
    $nametop = 160;
    $carline1top = 205;
    $carline2top = 241;
    $areatop = 255;
    $racertop = 300;
} elseif ($mode == "id2" || $mode === "id3") {
    $nametop = 162;
    $carline1top = 210;
    $carline2top = 246;
    $areatop = 265;
    $racertop = 310;
}

// === Desenhar textos
if (isset($drivername)) {
    imagettftext($overlay, $namesize, $angle, $nameleft, $nametop, $textcolour, $font_path, $drivername);
}
if (isset($keyno)) {
    imagettftext($cardimage, $carkeysize, $angle, $keyleft, $nametop, $textcolour, $font_path, $keyno);
}
if (isset($carline1)) {
    imagettftext($cardimage, $carkeysize, $angle, $left, $carline1top, $textcolour, $font_path, $carline1);
}
if (isset($carline2)) {
    imagettftext($cardimage, $carkeysize, $angle, $left, $carline2top, $textcolour, $font_path, $carline2);
}

// === Combinar imagens
imagecopyresampled($out, $cardimage, 0, 0, 0, 0, 644, 1020, 644, 1020);
imagecopyresampled($out, $overlay, 230, 0, 0, 0, 322, 1020, 644, 1020);
imagecopyresampled($out, $keyimage, ($keyleft-$iconsize), ($nametop-30), 0, 0, $iconsize, $iconsize, 24, 24);

// === JP text overlay seguro
if ($lang === 'jp' && isset($jptext) && file_exists($jptext)) {
    $jptextimage = imagecreatefrompng($jptext);
    if ($mode == 'idas') imagecopyresampled($out, $jptextimage, 49, 85, 0, 0, 166, 212, 166, 212);
    if ($mode == 'id2') imagecopyresampled($out, $jptextimage, 54, 88, 0, 0, 164, 266, 164, 266);
    if ($mode == 'id3') imagecopyresampled($out, $jptextimage, 47, 88, 0, 0, 167, 266, 167, 266);
    imagedestroy($jptextimage);
}

// === Imagem final
imagepng($out);
imagedestroy($out);
imagedestroy($overlay);
imagedestroy($cardimage);
imagedestroy($keyimage);
?>

