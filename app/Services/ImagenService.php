<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Comprime la foto (RNF-03): se vuelve a generar con GD (elimina metadatos y código oculto)
 * y se guarda como JPG de máximo 1200 px de ancho y 1 MB en storage/app/public/objetos.
 * La validación de tipo y tamaño la hace el Form Request (StoreObjetoRequest).
 */
class ImagenService
{
    public static function guardar(UploadedFile $archivo): string
    {
        $ruta = $archivo->getRealPath();
        $img = $archivo->getMimeType() === 'image/png' ? imagecreatefrompng($ruta) : imagecreatefromjpeg($ruta);
        $w = imagesx($img);
        $h = imagesy($img);
        $nw = min($w, 1200);
        $nh = (int) round($h * $nw / $w);
        $lienzo = imagecreatetruecolor($nw, $nh);
        imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
        imagecopyresampled($lienzo, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $calidad = 80;
        do {
            ob_start();
            imagejpeg($lienzo, null, $calidad);
            $binario = ob_get_clean();
            $calidad -= 10;
        } while (strlen($binario) > 1024 * 1024 && $calidad >= 40);

        $nombre = 'objetos/obj_' . now()->format('Ymd_His') . '_' . Str::random(10) . '.jpg';
        Storage::disk('public')->put($nombre, $binario);
        return $nombre;
    }
}
