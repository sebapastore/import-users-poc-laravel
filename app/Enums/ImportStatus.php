<?php

namespace App\Enums;

enum ImportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Finished = 'finished';
    case Failed = 'failed';
}
