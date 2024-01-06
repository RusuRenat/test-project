<?php

namespace App\Utils\Helpers;


use Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ImageHelper
{

    public static function generateBorderImage($image, $width, $height, $borderSize)
    {

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefilledrectangle($image, 0, 0, $width, $borderSize, $white); // upper
        imagefilledrectangle($image, $width - $borderSize, 0, $width, $height, $white); // right
        imagefilledrectangle($image, $width - $borderSize, $height - $borderSize, 0, $height, $white); // down
        imagefilledrectangle($image, 0, $height - $borderSize, $borderSize, 0, $white); // left

        $radius = $borderSize * 0.7;

        for ($y = 0; $y <= $height; $y++) {
            imagefilledellipse($image, $radius * 0.1, $y, $radius, $radius, $black);
            imagefilledellipse($image, $width - $radius * 0.1, $y, $radius, $radius, $black);
            $y += $radius * 1.5;
        }

        for ($x = 0; $x <= $width; $x++) {
            imagefilledellipse($image, $x, $radius * 0.1, $radius, $radius, $black);
            imagefilledellipse($image, $x, $height - $radius * 0.1, $radius, $radius, $black);
            $x += $radius * 1.5;
        }

        return $image;
    }

    /**
     * @throws Exception
     */
    public static function generateImage($originalFilePath, $finalFilePath, $newHeight, $newWidth, $compress, $fixed, $color, $rotate = 0)
    {

        try {

            if (!file_exists($originalFilePath)) {
                return false;
            }

            [$width, $height, $type] = getimagesize($originalFilePath);

            $oldImage = self::loadImage($originalFilePath, $type);

            $widthRate = $newWidth / $width;
            $heightRate = $newHeight / $height;

            if (!$newWidth && !$newHeight) {
                $imageScaled = $oldImage;
            } elseif ($widthRate > $heightRate) {
                $imageScaled = self::resizeImageToWidth($newWidth, $oldImage, $width, $height);
            } elseif ($heightRate > $widthRate) {
                $imageScaled = self::resizeImageToHeight($newHeight, $oldImage, $width, $height);
            } else {
                $imageScaled = self::resizeImageToHeight($newHeight, $oldImage, $width, $height);
            }

            if ($rotate > 0) {
                $imageScaled = imagerotate($imageScaled, $rotate * -90, 0);
            }

            if ($newWidth && $newHeight) {
                $imageCropped = self::cropAlign($imageScaled, $newWidth, $newHeight);
            } else {
                $imageCropped = $imageScaled;
            }

            FileHelper::makeDir(dirname($finalFilePath));

            if ($type == IMAGETYPE_JPEG) {
                imagejpeg($imageCropped, $finalFilePath, $compress ?? 75);
            } elseif ($type == IMAGETYPE_PNG) {
                imagepng($imageCropped, $finalFilePath, 9, PNG_ALL_FILTERS);
            }

            imagedestroy($imageCropped);

            return file_exists($finalFilePath) ? $finalFilePath : false;
        } catch (FileException $e) {
            throw new Exception(__FILE__ . ' | ' . __LINE__ . ' | ' . $e->getMessage());
        }

    }

    public static function loadImage($filename, $type)
    {

        if ($type == IMAGETYPE_JPEG) {
            $image = imagecreatefromjpeg($filename);
            imagesavealpha($image, true);
        } elseif ($type == IMAGETYPE_PNG) {
            $image = imagecreatefrompng($filename);
            imagesavealpha($image, true);
        } elseif ($type == IMAGETYPE_GIF) {
            $image = imagecreatefromgif($filename);
        } elseif ($type == IMAGETYPE_WEBP) {
            $image = imagecreatefromwebp($filename);
        }

        return $image ?? false;
    }

    public static function resizeImageToWidth($newWidth, $image, $width, $height)
    {
        $newHeight = $height * $newWidth / $width;
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagesavealpha($newImage, true);

        return $newImage;
    }

    public static function resizeImageToHeight($newHeight, $image, $width, $height)
    {
        $newWidth = $width * $newHeight / $height;
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagesavealpha($newImage, true);

        return $newImage;
    }

    public static function cropAlign($image, $cropWidth, $cropHeight, $horizontalAlign = 'center', $verticalAlign = 'top')
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $horizontalAlignPixels = self::calculatePixelsForAlign($width, $cropWidth, $horizontalAlign);
        $verticalAlignPixels = self::calculatePixelsForAlign($height, $cropHeight, $verticalAlign);

        return imageCrop($image,
            [
                'x' => $horizontalAlignPixels[0],
                'y' => $verticalAlignPixels[0],
                'width' => $horizontalAlignPixels[1],
                'height' => $verticalAlignPixels[1]
            ]
        );
    }

    public static function calculatePixelsForAlign($imageSize, $cropSize, $align): array
    {
        return match ($align) {
            'left', 'top' => [0, min($cropSize, $imageSize)],
            'right', 'bottom' => [max(0, $imageSize - $cropSize), min($cropSize, $imageSize)],
            'center', 'middle' => [
                max(0, floor(($imageSize / 2) - ($cropSize / 2))),
                min($cropSize, $imageSize),
            ],
            default => [0, $imageSize],
        };
    }

    /**
     * @throws Exception
     */
    public static function resizeImage($imagePath, $newWidth, $newHeight, $borderRadius = false)
    {

        try {

            [$oldWidth, $oldHeight, $type] = getimagesize($imagePath);
            $image = self::loadImage($imagePath, $type);

            $widthRate = $newWidth / $oldWidth;
            $heightRate = $newHeight / $oldHeight;

            if (!$newWidth && !$newHeight) {
//                $image = $image;
            } elseif ($widthRate > $heightRate) {
                $image = self::resizeImageToWidth($newWidth, $image, $oldWidth, $oldHeight);
            } elseif ($heightRate > $widthRate) {
                $image = self::resizeImageToHeight($newHeight, $image, $oldWidth, $oldHeight);
            } else {
                $image = self::resizeImageToHeight($newHeight, $image, $oldWidth, $oldHeight);
            }

            if ($borderRadius) {
                $image = self::imageCreateCorners($image, $newWidth, $newHeight, $type, $borderRadius);
            }

            return $image;
        } catch (FileException $e) {
            throw new Exception($e);
        }

    }

    /**
     * @throws Exception
     */
    public static function imageCreateCorners($image, $w, $h, $type, $radius)
    {

        $q = 10; # change this if you want
        $radius *= $q;

        # find unique color
        do {
            $r = random_int(0, 255);
            $g = random_int(0, 255);
            $b = random_int(0, 255);
        } while (imagecolorexact($image, $r, $g, $b) < 0);

        $nw = $w * $q;
        $nh = $h * $q;

        $img = imagecreatetruecolor($nw, $nh);
        $alphaColor = imagecolorallocatealpha($img, $r, $g, $b, 127);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefilledrectangle($img, 0, 0, $nw, $nh, $alphaColor);

        imagefill($img, 0, 0, $alphaColor);
        imagecopyresampled($img, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);

        imagearc($img, $radius - 1, $radius - 1, $radius * 2, $radius * 2, 180, 270, $alphaColor);
        imagefilltoborder($img, 0, 0, $alphaColor, $alphaColor);
        imagearc($img, $nw - $radius, $radius - 1, $radius * 2, $radius * 2, 270, 0, $alphaColor);
        imagefilltoborder($img, $nw - 1, 0, $alphaColor, $alphaColor);
        imagearc($img, $radius - 1, $nh - $radius, $radius * 2, $radius * 2, 90, 180, $alphaColor);
        imagefilltoborder($img, 0, $nh - 1, $alphaColor, $alphaColor);
        imagearc($img, $nw - $radius, $nh - $radius, $radius * 2, $radius * 2, 0, 90, $alphaColor);
        imagefilltoborder($img, $nw - 1, $nh - 1, $alphaColor, $alphaColor);
        imagealphablending($img, true);
        imagecolortransparent($img, $alphaColor);

        # resize image down
        $dest = imagecreatetruecolor($w, $h);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        imagefilledrectangle($dest, 0, 0, $w, $h, $alphaColor);
        imagecopyresampled($dest, $img, 0, 0, 0, 0, $w, $h, $nw, $nh);

        # output image
        $res = $dest;
        imagedestroy($image);
        imagedestroy($img);

        return $res;
    }

}
