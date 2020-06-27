<?php

namespace RMS\WP2S\GitHub;

abstract class FileStatus {
    const LOCAL_ONLY       = 0;
    const BLOB_CREATED     = 1;
    const IN_COMMIT        = 2;
    const IN_TARGET_BRANCH = 3;
}
