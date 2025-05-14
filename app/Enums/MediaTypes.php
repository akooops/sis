<?php

namespace App\Enums;

enum MediaTypes: string {
    case IMAGE = 'image'; 
    case VIDEO = 'video';
    case DOCUMENT = 'document';
    case AUDIO = 'audio';
}