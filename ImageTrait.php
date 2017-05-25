<?php

namespace App\Http\Controllers;

use File;
use Image;

// Version 2.1
trait ImageTrait {

	//where the images are to be saved
	private static $imagePath = '/public/uploads/images';	

	// max size of uploaded images
    private static $maxImageWidth = 2048;
    private static $maxImageHeight = 2048;

    // speciify the copies images that need to be generated for each product, and their sizes
    private $sizes = [
        'blog' => [
            array('name' => 'thumbnail', 'width' => 200, 'height' => 150),
            array('name' => 'preview', 'width' => 360, 'height' => 266),
            array('name' => 'small', 'width' => 82, 'height' => 65),
            array('name' => 'feature', 'width' => 845, 'height' => 447),

            array('name' => 'facebook', 'width' => 1200, 'height' => 628),
            array('name' => 'twitter', 'width' => 506, 'height' => 253),
            array('name' => 'linkedin', 'width' => 350, 'height' => 183),
            array('name' => 'g_plus', 'width' => 506, 'height' => 265 ),
        ],
        'videos' => [
            array('name' => 'thumbnail', 'width' => 170, 'height' => 130),
            array('name' => 'preview', 'width' => 340, 'height' => 260, 'constraint' => true),
            array('name' => 'feature', 'width' => 680, 'height' => 520, 'constraint' => true),
        ]
    ];
    
	 /**
    * Save all needed images from the original upload
    *
    * img: the image that was sent from the form request
    * objectname: where the images will be saved eg. 'posts', 'testimonials' etc.
    * prefix: a unique identifier to name the image, typically the id of the object

    example usage:

        // add the feature image
        if( !empty($request->file('image')) )
        {
            $object->image_name = $this->saveAllImages($request->file('image'), 'objects', $object->id);
            $object->save();
        }

    * returns: the name of the file
    */
    private function saveAllImages($img, $objectname, $prefix = NULL)
    {
        //save the original image
        $filename = $this->saveImage($img, $objectname, 'full', $prefix);

        // make copies in different sizes
        foreach($this->sizes[$objectname] as $size)
        {
            if( isset($size['constraint']))
                 $this->makeResizedImage($objectname, $size['name'], $filename, $size['width'], $size['height'], $size['constraint']);
            else
            {
                $this->makeResizedImage($objectname, $size['name'], $filename, $size['width'], $size['height']);
            }
        }

        return $filename;
    }

    /*
    * When a file is uploaded, save it with a unique name at the given path
    * The image is scaled down to a maximum size if needed.
    *
    * file: the incoming uploaded file
    * objectname: the folder where the file should be saved, eg. 'posts', 'testimonials'
    * dirname: the sub folder where the file should be saved, eg. 'thumbnail', 'preview'
    * prefix: the id of the object, useful when you want to overwrite the image each time it is saved for this user.
    * returns: the name of the file
    */
    public static function saveImage($file, $objectname, $dirname, $prefix = NULL)
    {
        $fullImagePath = base_path() . self::$imagePath . "/{$objectname}/{$dirname}";

        if($prefix != NULL)
        {
            // eg. 1.jpg, 55.png, etc.
            $filename = $prefix . '.' . $file->getClientOriginalExtension();
        }
        else
        {
            $filename = date("Y-m-d-H-i-s_") . str_replace(' ', '-', $file->getClientOriginalName());
        }

        $img = Image::make($file);
        $img->resize(self::$maxImageWidth, self::$maxImageHeight, function ($constraint){
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $img->save($fullImagePath .'/'. $filename);
        return $filename;
    }

    /*
    * Make a resized version of the uploaded image, and save it in a different directory.
    * The original image should be saved under the /full directory.
    *
    * objectname: the folder where the file should be saved, eg. 'posts', 'testimonials'
    * dirname: the sub folder where the file should be saved, eg. 'thumbnail', 'preview'
    * filename: the name of the image
    * width: the width of the new image to be created
    * height: the height of the new image to be created
    */
    public static function makeResizedImage($objectname, $dirname, $filename, $width, $height, $constraint = null)
    {
        $baseImagePath = base_path() . self::$imagePath;

        if($constraint)
        {
            Image::make($baseImagePath . "/{$objectname}/full/{$filename}")
            ->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->save($baseImagePath . "/{$objectname}/{$dirname}/{$filename}");
        }
        else
        {
            Image::make($baseImagePath . "/{$objectname}/full/{$filename}")
            ->fit($width, $height)
            ->save($baseImagePath . "/{$objectname}/{$dirname}/{$filename}");
        }
    }

    /*
    * Remove all images for this object
    *
    * objectname: the folder where the file should be saved, eg. 'posts', 'testimonials'
    * filename: the name of the image ( $object->image_name )
    */
    private function deleteAllImages($objectname, $filename)
    {
        $this->deleteImage($objectname, 'full', $filename);
        foreach($this->sizes[$objectname] as $size)
        {
            $this->deleteImage($objectname, $size['name'], $filename);
        }
    }

    /*
    * Remove a single image from the file heirarchy
    *
    * objectname: the folder where the file should be saved, eg. 'posts', 'testimonials'
    * dirname: the sub folder where the file should be saved, eg. 'thumbnail', 'preview'
    * filename: the name of the image
    */
    public static function deleteImage($objectname, $dirname, $filename)
    {
        $baseImagePath = base_path() . self::$imagePath;
        File::Delete($baseImagePath . "/{$objectname}/{$dirname}/{$filename}");
    }

}
