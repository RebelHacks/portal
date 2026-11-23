<?php

namespace App\Service;

// UploadStorage could not store a file. The cause is in getPrevious().
class UploadFailed extends \RuntimeException
{
}
