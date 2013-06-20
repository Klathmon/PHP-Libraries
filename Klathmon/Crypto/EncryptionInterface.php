<?php
/**
 * Created by: Gregory Benner.
 * Date: 6/20/13
 */

namespace Klathmon\Crypto;

interface EncryptionInterface
{
    public function encrypt($data);

    public function decrypt($data);
}