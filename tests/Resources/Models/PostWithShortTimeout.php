<?php

namespace Blendbyte\FilamentResourceLock\Tests\Resources\Models;

class PostWithShortTimeout extends Post
{
    protected int $lockTimeout = 10; // 10 seconds
}
