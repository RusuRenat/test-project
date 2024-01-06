<?php

namespace App\Utils\Helpers;

use Symfony\Component\HttpFoundation\Request;

class RequestHelper
{

    public static function getRequestData(Request $request, ?array $data): Request
    {

        if ($data) {
            foreach ($data as $key => $value) {

                switch ($key) {
                    case 0 :
                        $request->request->set('id', $value);
                        break;
                    case 1 :
                        $request->request->set('sort', $value);
                        break;
                    case 2 :
                        $request->request->set('limit', $value);
                        break;
                    case 3 :
                        $request->request->set('categoriesId', $value);
                        break;
                    case 4 :
                        $request->request->set('type', $value);
                        break;
                }

            }
        }

        return $request;
    }
}