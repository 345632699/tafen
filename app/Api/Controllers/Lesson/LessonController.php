<?php

namespace App\Api\Controllers\Lesson;

use App\Api\Controllers\BaseController;
use App\Model\Lesson;


class LessonController extends BaseController
{
    public function getLessonList()
    {
        $online = Lesson::where('type', 1)->get();
        $downline = Lesson::where('type', 2)->get();
        $data['online'] = $online;
        $data['downline'] = $downline;
        return response_format($data);
    }
}
