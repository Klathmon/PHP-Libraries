<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/12/13
 */

namespace Klathmon;

class Logger
{
    private $fileName, $maxSize, $filePointer;

    public function __construct($fileName = 'log.txt', $maxSize = 10485760)
    {
        $this->fileName = $fileName;
        $this->maxSize  = $maxSize;

        $this->splitFiles();

        $this->filePointer = fopen($this->fileName, 'a');
    }

    public function write($data)
    {
        $dateTime = new \DateTime('now');

        $message = '[' . $dateTime->format('l F jS, Y h:i:s A e') . '] ';
        $message .= $data . "\n";

        fwrite($this->filePointer, $message);
    }

    public function close()
    {
        $this->__destruct();
    }


    public function __destruct()
    {
        fclose($this->filePointer);
    }

    private function splitFiles()
    {
        if (file_exists($this->fileName)) {
            $size = filesize($this->fileName);

            if ($size >= $this->maxSize) {
                //I need to move this file and make room for the new one...
                $counter = 0;
                do {
                    $counter++;
                    $newFileName = $this->fileName . '.' . $counter;
                } while (file_exists($newFileName));
                //Keep going till we find a file that does not exist

                for ($x = $counter; $x != 0; $x--) {
                    $oldFileName = ($x - 1 != 0 ? $this->fileName . '.' . ($x - 1) : $this->fileName);
                    $newFileName = $this->fileName . '.' . ($x);
                    if (!rename($oldFileName, $newFileName)) //Rename all the files to their old name + 1
                    {
                        throw new \Exception('Error Renaming file!');
                    }
                }

                @unlink($this->fileName); //Remove the (old)newest file to make a fresh slate for the new(er) log file.
            }
        }
    }
}
