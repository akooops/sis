<?php

namespace App\Traits;

use App\Models\Core\File;

trait HasFiles
{
    public static function bootHasFiles()
    {
        static::deleting(function ($model) {
            if ($model->file) {
                $model->file->update([
                    'model_type' => null,
                    'model_id' => null
                ]);
            }

            if($model->files){
                foreach($model->files as $file){
                    $file->update([
                        'model_type' => null,
                        'model_id' => null
                    ]);
                }
            }
        });
    }
}