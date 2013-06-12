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
    $path    = __DIR__ . DIRECTORY_SEPARATOR . 'Packages' . DIRECTORY_SEPARATOR;
    $library = explode('\\', $class);

    if ($library[0] == __NAMESPACE__) {
        foreach ($library as $package) {
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