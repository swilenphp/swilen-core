<?php

// require_once __DIR__ . '/../../vendor/autoload.php';

class Vite
{
    private string $manifestPath;
    private string $baseUrl;
    private string $devHost = 'http://localhost:9010';
    private bool $isDev;
    private static ?array $manifest = null;

    public function __construct(string $manifestPath, string $baseUrl)
    {
        $this->manifestPath = $manifestPath;
        $this->baseUrl = rtrim($baseUrl, '/');
        // Si no existe el manifest, estamos en desarrollo (Vite corriendo)
        $this->isDev = !file_exists($this->manifestPath);
    }

    private function getManifest(): array
    {
        if (self::$manifest === null && !$this->isDev) {
            $content = file_get_contents($this->manifestPath);
            self::$manifest = json_decode($content, true) ?: [];
        }
        return self::$manifest ?? [];
    }

    public function render(array $entries): void
    {
        if ($this->isDev) {
            echo "<script type='module' src='{$this->devHost}/@vite/client'></script>\n";
            foreach ($entries as $entry) {
                echo "<script type='module' src='{$this->devHost}/{$entry}'></script>\n";
            }
            return;
        }

        $manifest = $this->getManifest();
        foreach ($entries as $entry) {
            if (!isset($manifest[$entry])) continue;

            $file = $manifest[$entry]['file'];

            echo "<script type='module' src='{$this->baseUrl}/dist/{$file}'></script>\n";

            if (isset($manifest[$entry]['css'])) {
                foreach ($manifest[$entry]['css'] as $cssFile) {
                    echo "<link rel='stylesheet' href='{$this->baseUrl}/dist/{$cssFile}'>\n";
                }
            }
        }
    }
}

$vite = new Vite(
    __DIR__ . '/assets/.vite/manifest.json',
    '/wp-admin/assets'
);

function build_admin_menu_json()
{
    global $menu, $submenu;

    // Provide the default WordPress menu structure if not populated
    $default_menu = [
        2  => ['Dashboard', 'read', 'index.php', '', 'menu-top menu-top-first menu-icon-dashboard', 'menu-dashboard', 'dashicons-dashboard'],
        4  => ['', 'read', 'separator1', '', 'wp-menu-separator'],
        5  => ['Posts', 'edit_posts', 'edit.php', '', 'menu-top menu-icon-post', 'menu-posts', 'dashicons-admin-post'],
        10 => ['Media', 'upload_files', 'upload.php', '', 'menu-top menu-icon-media', 'menu-media', 'dashicons-admin-media'],
        15 => ['Links', 'manage_links', 'link-manager.php', '', 'menu-top menu-icon-links', 'menu-links', 'dashicons-admin-links'],
        20 => ['Pages', 'edit_pages', 'edit.php?post_type=page', '', 'menu-top menu-icon-page', 'menu-pages', 'dashicons-admin-page'],
        25 => ['Comments', 'edit_posts', 'edit-comments.php', '', 'menu-top menu-icon-comments', 'menu-comments', 'dashicons-admin-comments'],
        59 => ['', 'read', 'separator2', '', 'wp-menu-separator'],
        60 => ['Appearance', 'switch_themes', 'themes.php', '', 'menu-top menu-icon-appearance', 'menu-appearance', 'dashicons-admin-appearance'],
        65 => ['Plugins', 'activate_plugins', 'plugins.php', '', 'menu-top menu-icon-plugins', 'menu-plugins', 'dashicons-admin-plugins'],
        70 => ['Users', 'list_users', 'users.php', '', 'menu-top menu-icon-users', 'menu-users', 'dashicons-admin-users'],
        75 => ['Tools', 'edit_posts', 'tools.php', '', 'menu-top menu-icon-tools', 'menu-tools', 'dashicons-admin-tools'],
        80 => ['Settings', 'manage_options', 'options-general.php', '', 'menu-top menu-icon-settings', 'menu-settings', 'dashicons-admin-settings'],
        99 => ['', 'read', 'separator-last', '', 'wp-menu-separator'],
    ];

    $default_submenu = [
        'index.php' => [
            ['Home', 'read', 'index.php'],
            ['Updates', 'update_core', 'update-core.php']
        ],
        'edit.php' => [
            ['All Posts', 'edit_posts', 'edit.php'],
            ['Add New', 'edit_posts', 'post-new.php'],
            ['Categories', 'manage_categories', 'edit-tags.php?taxonomy=category'],
            ['Tags', 'manage_post_tags', 'edit-tags.php?taxonomy=post_tag']
        ]
    ];

    $admin_menu = !empty($menu) ? $menu : $default_menu;
    $admin_submenu = !empty($submenu) ? $submenu : $default_submenu;

    // Ensure array is sorted by keys
    ksort($admin_menu);

    $formatted_menu = [];
    $current_group = [];

    foreach ($admin_menu as $pos => $item) {
        if (!empty($item[4]) && strpos($item[4], 'wp-menu-separator') !== false) {
            if (!empty($current_group)) {
                $formatted_menu[] = $current_group;
                $current_group = [];
            }
            continue;
        }

        $menu_slug = $item[2] ?? '';
        $formatted_item = [
            'id' => sanitize_title($item[0] ?? $menu_slug),
            'title' => $item[0] ?? '',
            'slug' => $menu_slug,
            'icon' => map_dashicon_to_primeicon($item[6] ?? ''),
            'classes' => $item[4] ?? '',
            'submenu' => []
        ];

        if (isset($admin_submenu[$menu_slug])) {
            foreach ($admin_submenu[$menu_slug] as $sub) {
                $formatted_item['submenu'][] = [
                    'title' => $sub[0] ?? '',
                    'slug' => $sub[2] ?? '',
                ];
            }
        }

        $current_group[] = $formatted_item;
    }

    if (!empty($current_group)) {
        $formatted_menu[] = $current_group;
    }

    return json_encode($formatted_menu);
}

function sanitize_title($title)
{
    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim(strip_tags($title))));
}

function map_dashicon_to_primeicon($icon)
{
    if (empty($icon) || $icon === 'none') return 'pi-circle';
    if (strpos($icon, 'dashicons-') !== 0) {
        return 'pi-box';
    }

    $map = [
        'dashicons-dashboard' => 'pi-home',
        'dashicons-admin-post' => 'pi-file-edit',
        'dashicons-admin-media' => 'pi-images',
        'dashicons-admin-links' => 'pi-link',
        'dashicons-admin-page' => 'pi-file',
        'dashicons-admin-comments' => 'pi-comments',
        'dashicons-admin-appearance' => 'pi-palette',
        'dashicons-admin-plugins' => 'pi-box',
        'dashicons-admin-users' => 'pi-users',
        'dashicons-admin-tools' => 'pi-wrench',
        'dashicons-admin-settings' => 'pi-cog',
    ];

    return $map[$icon] ?? 'pi-circle-fill';
}

$state = [
    'menu' => json_decode(build_admin_menu_json(), true)
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FluxPress Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <script id="swilen-state" type="application/json">
        <?php echo json_encode($state); ?>
    </script>
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--p-surface-900);
            padding-right: 10px;
        }

        #admin-skeleton {
            display: flex;
            min-height: 100vh;
        }

        #sidebar {
            padding: 0 1rem;
            background-color: var(--p-surface-900);
            color: #fff;
        }

        #sidebar .p-tieredmenu,
        #sidebar .p-menu {
            border: none !important;
            gap: 0.3rem !important;
        }

        #sidebar .p-tieredmenu-root-list {
            gap: 0.3rem !important;
        }

        #sidebar .p-tieredmenu-separator {
            padding: 1rem 0;
        }

        #content-area {
            flex: 1;
            display: flex;
            background-color: var(--p-surface-950);
            border-radius: 14px;
            overflow: hidden;
        }

        #header {
            height: 52px;
            color: #fff;
            display: flex;
            align-items: center;
            border-bottom: var(--p-surface-900) 1px solid;
            padding: 0 20px;
            font-size: 13px;
        }

        #main-content {
            flex: 1;
            padding: 20px;
        }

        #footer {
            height: 52px;
            border-top: var(--p-surface-900) 1px solid;
            padding: 0 20px;
            display: flex;
            align-items: center;
            color: #646970;
            font-size: 13px;
        }

        .p-datatable {
            border-radius: 8px;
            overflow: hidden;
        }
    </style>

    <?php $vite->render(['src/main.ts']); ?>
</head>

<body>
    <div id="admin-skeleton">
        <div id="sidebar">
            <div style="padding: 20px; font-size: 14px; font-weight: bold; display: flex; align-items: center; gap: 10px;">
                <svg fill="#ffffff" viewBox="0 0 24 24" id="edit" width="32" height="32" class="icon flat-color">
                    <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                    <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                    <g id="SVGRepo_iconCarrier">
                        <path id="secondary" d="M21,22H3a1,1,0,0,1,0-2H21a1,1,0,0,1,0,2Z" style="fill: #2ca9bc;"></path>
                        <path id="primary" d="M20.71,3.29a2.93,2.93,0,0,0-2.2-.84,3.25,3.25,0,0,0-2.17,1L7.46,12.29a1.16,1.16,0,0,0-.25.43L6,16.72A1,1,0,0,0,7,18a.9.9,0,0,0,.28,0l4-1.17a1.16,1.16,0,0,0,.43-.25l8.87-8.88a3.25,3.25,0,0,0,1-2.17A2.91,2.91,0,0,0,20.71,3.29Z" style="fill: #ffffff;"></path>
                    </g>
                </svg>
                <span>FastWord</span>
            </div>
        </div>
        <div style="padding: 10px 0; width: 100%; height: 100vh;">
            <div id="content-area" style="width: 100%; height: 100%; display: flex; flex-direction: column;">
                <div id="header">
                    WordPress Admin Header
                </div>
                <div id="main-content">
                    <div id="admin-app"></div>
                </div>
                <div id="footer">
                    <p>Thank you for creating with FastWord.</p>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('vue-event', (event) => {
            console.log('PHP Shell received event from Vue:', event.detail);
        });
    </script>
</body>

</html>
