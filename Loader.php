<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/11/2013
 *
 * This is the AutoLoader for my library. Include this file once and it will automatically include any files in the
 * library that you need, when you call them. (It will silently fail if the class is not found to allow other
 * AutoLoaders to work with it)
 */


namespace Klathmon;

spl_autoload_register(__NAMESPACE__ . '\AutoLoader');

function AutoLoader($class)
{
    $namespaceArray = explode('\\', $class);

    $library = array_shift($namespaceArray);

    if ($library == __NAMESPACE__) {
        //Any of my classes.
        $path = __DIR__ . DIRECTORY_SEPARATOR . 'Klathmon' . DIRECTORY_SEPARATOR;
    } elseif ($library == 'Imagine') {
        //Imagine image processing system.
        $path = __DIR__ . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR;
    } elseif ($library == 'Cpdf' || $library == 'Cezpdf') {
        //Cpdf PDF Library.
        $path           = __DIR__ . DIRECTORY_SEPARATOR . 'Cpdf' . DIRECTORY_SEPARATOR;
        $namespaceArray = array($library); //Cpdf doesn't use namespaces, so just reset the array so the for will run.
    } else {
        $path = '';
    }


    if ($path != '') {
        foreach ($namespaceArray as $package) {
            $directory = $path . $package;
            $file      = $path . $package . '.php';
            if (is_dir($directory)) {
                $path = $directory . DIRECTORY_SEPARATOR;;
            } elseif (is_file($file)) {
                include($file);
            }
        }
    }
}