<?php

namespace PHPIntel\Scanner;

use PHPIntel\Dumper\Dumper;
use PHPIntel\Intel\IntelBuilder;
use PHPIntel\Scanner\Iterator\ProjectIterator;
use PHPIntel\Logger\Logger;

use \Exception;

/*
* ProjectScanner
*/
class ProjectScanner
{
    protected $settings = null;

    public function __construct($settings=null)
    {
        if ($settings !== null) { $this->settings = $settings; }
    }

    public function scanAndDumpProject(IntelBuilder $intel, Dumper $dumper)
    {
        if (!isset($this->settings['include_dirs'])) { throw new Exception("Directories to scan not found.", 1); }

        $project_iterator = new ProjectIterator($this->settings);
        foreach($project_iterator as $path) {
            // for every file in the poject extract the intel and add it to the data store
            $entity_collection = $intel->extractFromFile($path);

            $dumper->replaceEntitiesInFile($entity_collection, $path);
        }

    }


}