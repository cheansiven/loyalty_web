<?php

if (!function_exists('checkDirectories')) {

    function checkDirectories($path)
    {
        if ($path == null || empty($path)) {
            return false;
        }

        $expPath = explode("/", $path);

        $newPath = "";
        if (!empty($expPath)) {
            foreach ($expPath as $key => $value) {

                if ($newPath == "" || empty($newPath)) {
                    $newPath = $value;
                } else {
                    $newPath .= "/" . $value;
                }

                if (!is_dir($newPath)) {
                    createDir($newPath);
                }
            }
        }
        return $newPath;
    }
}

if (!function_exists('createDir')) {
    function createDir($name)
    {
        if($name =="") return;
        File::makeDirectory($name);
    }

}

if (!function_exists('successMessage')) {
    function successMessage($data)
    {
        return [STATUS => SUCCESS, MESSAGE => $data];
    }
}

if (!function_exists('errorMessage')) {
    function errorMessage($data)
    {
        return [STATUS => ERROR, MESSAGE => $data];
    }
}

if (!function_exists('validateUrl')) {
    function validateUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('checkEmptyValue')) {
    function checkEmptyValue($value)
    {
        if (empty($value) || $value == null || $value == "") {
            return false;
        }

        return true;
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }
}
