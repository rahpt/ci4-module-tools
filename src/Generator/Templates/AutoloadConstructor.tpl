
    /**
     * __autoload_marker__
     */
    public function __construct()
    {
        parent::__construct();
        $this->scanModules();
    }

    private function scanModules()
    {
        $modulesPath = APPPATH . 'Modules';
        if (!is_dir($modulesPath)) return;

        $scanner = array_diff(scandir($modulesPath), ['.', '..']);
        foreach ($scanner as $folder) {
            if (!is_dir($modulesPath . '/' . $folder)) continue;
            
            $namespace = "__namespace__\\\\" . $folder;
            $path = $modulesPath . '/' . $folder . '/';
            
            if (!isset($this->psr4[$namespace])) {
                $this->psr4[$namespace] = $path;
            }
        }
    }
