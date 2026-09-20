<?php

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class PinRateLimited extends RuntimeException {}
