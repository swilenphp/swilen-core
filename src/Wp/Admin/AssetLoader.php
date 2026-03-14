<?php

namespace Swilen\Wp\Admin;

class AssetLoader
{
    private bool $isDev;
    private string $devUrl = 'http://localhost:5173';
    private string $manifestPath;
    private string $assetsUrl;

    public function __construct(bool $isDev = false)
    {
        $this->isDev = $isDev;
        $this->manifestPath = dirname(__DIR__, 4) . '/public/wp-admin/assets/.vite/manifest.json';
        $this->assetsUrl = '/wp-admin/assets/';
    }

    public function render(): void
    {
        if ($this->isDev) {
            echo "<script type=\"module\" src=\"{$this->devUrl}/@vite/client\"></script>";
            echo "<script type=\"module\" src=\"{$this->devUrl}/src/main.ts\"></script>";
        } else {
            $this->renderProductionAssets();
        }
    }

    private function renderProductionAssets(): void
    {
        if (!file_exists($this->manifestPath)) {
            return;
        }

        $manifest = json_decode(file_get_contents($this->manifestPath), true);
        
        // Find main entry point
        foreach ($manifest as $key => $file) {
            if (isset($file['isEntry']) && $file['isEntry']) {
                $src = $file['file'];
                echo "<script type=\"module\" src=\"{$this->assetsUrl}{$src}\"></script>";
                
                if (isset($file['css'])) {
                    foreach ($file['css'] as $css) {
                        echo "<link rel=\"stylesheet\" href=\"{$this->assetsUrl}{$css}\">";
                    }
                }
            }
        }
    }
}
