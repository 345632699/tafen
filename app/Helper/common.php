<?php

function response_format($data,$status=1,$msg='success',$code = 200){
    $res = [];
    $res['data'] = $data;
    $res['status'] = $status;
    $res['msg'] = $msg;
//    return json_encode($res,JSON_UNESCAPED_UNICODE);
    return response()->json(['response' => $res], $code,[],JSON_UNESCAPED_UNICODE);
}

function upload($request, $file)
{
//    if (!is_dir(public_path() . '/order_return/')) {
//        mkdir(public_path() . '/order_return/', 0777, true);
//    }
    // 获取文件相关信息
    $originalName = $file->getClientOriginalName(); // 文件原名
    $ext = $file->getClientOriginalExtension();     // 扩展名
    $realPath = $file->getRealPath();   //临时文件的绝对路径
    $type = $file->getClientMimeType();     // image/jpeg

    // 上传文件
    $filename = str_random(8) . '_' . time() . $originalName;
    $destinationPath = public_path() . '/order_return/';
    // 使用我们新建的uploads本地存储空间（目录）
    $file->move($destinationPath, $filename);
    $size = $file->getClientSize();
    $mbsize = $size / 1048576;
    $totaltsize = substr($mbsize, 0, 4);

    if ($totaltsize > 15) {
        echo \GuzzleHttp\json_encode('false');
    }

    $input = ['path' => "/order_return/$filename", 'size' => "$totaltsize", 'file_display' => "$originalName"];

    return $input;
}