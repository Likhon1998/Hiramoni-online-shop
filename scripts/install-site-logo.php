<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$src = $argv[1] ?? null;
if (! $src || ! is_file($src)) {
    fwrite(STDERR, "Usage: php scripts/install-site-logo.php <source-image>\n");
    exit(1);
}

$data = file_get_contents($src);
$im = imagecreatefromstring($data);
if (! $im instanceof GdImage) {
    fwrite(STDERR, "Could not read image.\n");
    exit(1);
}

$w = imagesx($im);
$h = imagesy($im);

$isCanvas = static function (int $r, int $g, int $b): bool {
    // Solid black canvases from some exports.
    if ($r <= 28 && $g <= 28 && $b <= 28) {
        return true;
    }
    // Near-white padding around this emblem.
    if ($r >= 245 && $g >= 245 && $b >= 245) {
        return true;
    }

    return false;
};

$minX = $w;
$minY = $h;
$maxX = -1;
$maxY = -1;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if ($isCanvas($r, $g, $b)) {
            continue;
        }
        $minX = min($minX, $x);
        $minY = min($minY, $y);
        $maxX = max($maxX, $x);
        $maxY = max($maxY, $y);
    }
}

if ($maxX < 0) {
    fwrite(STDERR, "No emblem content found.\n");
    exit(1);
}

$pad = 14;
$minX = max(0, $minX - $pad);
$minY = max(0, $minY - $pad);
$maxX = min($w - 1, $maxX + $pad);
$maxY = min($h - 1, $maxY + $pad);
$cw = $maxX - $minX + 1;
$ch = $maxY - $minY + 1;
$cx = $minX + $cw / 2.0;
$cy = $minY + $ch / 2.0;
$radius = max($cw, $ch) / 2.0;

$exportCircle = function (GdImage $im, float $cx, float $cy, float $radius, int $size, string $dest) use ($isCanvas): void {
    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
    imagefilledrectangle($out, 0, 0, $size - 1, $size - 1, $transparent);

    $scale = $size / (2 * $radius);
    $srcW = imagesx($im);
    $srcH = imagesy($im);
    $edge = ($size / 2.0) - 0.6;

    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $dx = ($x + 0.5) - ($size / 2.0);
            $dy = ($y + 0.5) - ($size / 2.0);
            $dist = sqrt($dx * $dx + $dy * $dy);
            if ($dist > $edge + 1.4) {
                continue;
            }

            $sx = $cx + ($dx / $scale);
            $sy = $cy + ($dy / $scale);
            $ix = (int) round($sx);
            $iy = (int) round($sy);
            if ($ix < 0 || $iy < 0 || $ix >= $srcW || $iy >= $srcH) {
                continue;
            }

            $rgb = imagecolorat($im, $ix, $iy);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            // Outside the painted emblem ring: keep transparent even if still in square.
            // Soft anti-alias at circle edge only.
            $a = 0;
            if ($dist > $edge) {
                $a = (int) min(127, max(0, (($dist - $edge) / 1.4) * 127));
            }

            $col = imagecolorallocatealpha($out, $r, $g, $b, $a);
            imagesetpixel($out, $x, $y, $col);
        }
    }

    $dir = dirname($dest);
    if (! is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    imagepng($out, $dest, 6);
    imagedestroy($out);
};

$brandDir = storage_path('app/public/branding');
if (! is_dir($brandDir)) {
    mkdir($brandDir, 0775, true);
}

$logoRel = 'branding/hiramoni-logo.png';
$favRel = 'branding/hiramoni-favicon.png';

$exportCircle($im, $cx, $cy, $radius, 1024, storage_path('app/public/'.$logoRel));
$exportCircle($im, $cx, $cy, $radius, 512, storage_path('app/public/'.$favRel));
$exportCircle($im, $cx, $cy, $radius, 192, public_path('images/hiramoni-logo-192.png'));
$exportCircle($im, $cx, $cy, $radius, 512, public_path('images/hiramoni-logo-512.png'));
$exportCircle($im, $cx, $cy, $radius, 64, public_path('favicon-64.png'));
$exportCircle($im, $cx, $cy, $radius, 32, public_path('favicon-32.png'));
$exportCircle($im, $cx, $cy, $radius, 48, public_path('favicon.png'));
$exportCircle($im, $cx, $cy, $radius, 64, public_path('images/favicon-64.png'));
$exportCircle($im, $cx, $cy, $radius, 32, public_path('images/favicon-32.png'));

copy(storage_path('app/public/'.$logoRel), public_path('images/hiramoni-logo-round.png'));
copy(storage_path('app/public/'.$logoRel), public_path('images/hiramoni-logo.png'));

$settings = App\Models\SiteSetting::query()->first();
if ($settings) {
    $settings->forceFill([
        'logo_path' => $logoRel,
        'favicon_path' => $favRel,
    ])->save();
}

echo "bbox={$minX},{$minY} → {$maxX},{$maxY}\n";
echo "logo={$logoRel}\nfavicon={$favRel}\n";
echo 'bytes='.filesize(storage_path('app/public/'.$logoRel))."\n";

imagedestroy($im);
