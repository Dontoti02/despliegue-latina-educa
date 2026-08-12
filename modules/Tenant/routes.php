<?php

$submodulesPath = __DIR__ . '/Packages';

if (is_dir($submodulesPath)) {
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($submodulesPath),
    RecursiveIteratorIterator::LEAVES_ONLY
  );

  foreach ($iterator as $file) {
    if ($file->isFile() && $file->getFilename() === 'routes.php') {
      require $file->getPathname();
    }
  }
}
