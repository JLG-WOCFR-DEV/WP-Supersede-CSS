<?php declare(strict_types=1);

namespace SSC\Admin;

if (!defined('ABSPATH')) { exit; }

final class ModuleRegistry
{
    public const BASE_SLUG = 'supersede-css-jlg';

    /**
     * Returns the manifest describing all admin modules.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function modules(): array
    {
        return [
            'dashboard' => [
                'slug'      => '',
                'page_slug' => self::BASE_SLUG,
                'label'     => __('Dashboard', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\Dashboard',
                'group'     => 'fundamentals',
                'assets'    => [
                    'styles' => [
                        [
                            'path' => 'assets/css/dashboard.css',
                        ],
                    ],
                ],
            ],
            'utilities' => [
                'slug'      => 'utilities',
                'page_slug' => self::BASE_SLUG . '-utilities',
                'label'     => __('Utilities (Éditeur CSS)', 'supersede-css-jlg'),
                'menu_label' => __('Utilities', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\Utilities',
                'group'     => 'fundamentals',
                'requires_codemirror' => true,
                'assets'    => [
                    'scripts' => [
                        [
                            'path'         => 'assets/js/utilities.js',
                            'deps'         => ['jquery', 'wp-i18n', 'ssc-codemirror-css'],
                            'translations' => true,
                        ],
                    ],
                    'styles'  => [
                        [
                            'path' => 'assets/css/utilities.css',
                        ],
                    ],
                ],
            ],
            'tokens' => [
                'slug'      => 'tokens',
                'page_slug' => self::BASE_SLUG . '-tokens',
                'label'     => __('Tokens Manager', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\Tokens',
                'group'     => 'fundamentals',
                'assets'    => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/tokens.js',
                        ],
                    ],
                    'styles'  => [
                        [
                            'path' => 'assets/css/tokens.css',
                        ],
                    ],
                ],
            ],
            'preset' => [
                'slug'      => 'preset',
                'page_slug' => self::BASE_SLUG . '-preset',
                'label'     => __('Preset Designer', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\PresetDesigner',
                'group'     => 'fundamentals',
                'assets'    => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/preset-designer.js',
                        ],
                    ],
                ],
            ],
            'layout-builder' => [
                'slug'      => 'layout-builder',
                'page_slug' => self::BASE_SLUG . '-layout-builder',
                'label'     => __('Maquettage de Page', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\PageLayoutBuilder',
                'group'     => 'visual-builders',
                'assets'    => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/page-layout-builder.js',
                        ],
                    ],
                    'styles'  => [
                        [
                            'path' => 'assets/css/page-layout-builder.css',
                        ],
                    ],
                ],
            ],
            'grid' => [
                'slug'      => 'grid',
                'page_slug' => self::BASE_SLUG . '-grid',
                'label'     => __('Grid Editor', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\GridEditor',
                'group'     => 'visual-builders',
                'assets'    => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/grid-editor.js',
                        ],
                    ],
                ],
            ],
            'shadow' => [
                'slug'      => 'shadow',
                'page_slug' => self::BASE_SLUG . '-shadow',
                'label'     => __('Shadow Editor', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\ShadowEditor',
                'group'     => 'visual-builders',
                'assets'    => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/shadow-editor.js',
                        ],
                        [
                            'handle' => 'ssc-sortable',
                            'path'   => 'assets/js/Sortable.min.js',
                            'deps'   => [],
                        ],
                    ],
                ],
            ],
            'gradient' => [
                'slug'      => 'gradient',
                'page_slug' => self::BASE_SLUG . '-gradient',
                'label'     => __('Gradient Editor', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\GradientEditor',
                'group'     => 'visual-builders',
                'assets'    => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/gradient-editor.js',
                        ],
                    ],
                    'styles'  => [
                        [
                            'path' => 'assets/css/gradient-editor.css',
                        ],
                    ],
                ],
            ],
            'typography' => [
                'slug'      => 'typography',
                'page_slug' => self::BASE_SLUG . '-typography',
                'label'     => __('Typographie Fluide', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\TypographyEditor',
                'group'     => 'visual-builders',
                'assets'    => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/typography-editor.js',
                        ],
                    ],
                    'styles'  => [
                        [
                            'path' => 'assets/css/typography-editor.css',
                        ],
                    ],
                ],
            ],
            'clip-path' => [
                'slug'      => 'clip-path',
                'page_slug' => self::BASE_SLUG . '-clip-path',
                'label'     => __('Découpe (Clip-Path)', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\ClipPathEditor',
                'group'     => 'visual-builders',
                'assets'    => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/clip-path-editor.js',
                        ],
                    ],
                    'styles'  => [
                        [
                            'path' => 'assets/css/clip-path-editor.css',
                        ],
                    ],
                ],
            ],
            'filters' => [
                'slug'      => 'filters',
                'page_slug' => self::BASE_SLUG . '-filters',
                'label'     => __('Filtres & Verre', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\FilterEditor',
                'group'     => 'visual-builders',
                'assets'    => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/filter-editor.js',
                        ],
                    ],
                    'styles'  => [
                        [
                            'path' => 'assets/css/filter-editor.css',
                        ],
                    ],
                ],
            ],
            'anim' => [
                'slug'      => 'anim',
                'page_slug' => self::BASE_SLUG . '-anim',
                'label'     => __('Animation Studio', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\AnimationStudio',
                'group'     => 'effects',
                'assets'    => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/animation-studio.js',
                        ],
                    ],
                    'styles'  => [
                        [
                            'path' => 'assets/css/animation-studio.css',
                        ],
                    ],
                ],
            ],
            'effects' => [
                'slug'          => 'effects',
                'page_slug'     => self::BASE_SLUG . '-effects',
                'label'         => __('Effets Visuels', 'supersede-css-jlg'),
                'class'         => '\\SSC\\Admin\\Pages\\VisualEffects',
                'group'         => 'effects',
                'enqueue_media' => true,
                'assets'        => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/visual-effects.js',
                        ],
                    ],
                    'styles'  => [
                        [
                            'path' => 'assets/css/visual-effects.css',
                        ],
                    ],
                ],
            ],
            'tron' => [
                'slug'      => 'tron',
                'page_slug' => self::BASE_SLUG . '-tron',
                'label'     => __('Tron Grid', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\TronGrid',
                'group'     => 'effects',
                'assets'    => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/tron-grid.js',
                        ],
                    ],
                ],
            ],
            'avatar' => [
                'slug'          => 'avatar',
                'page_slug'     => self::BASE_SLUG . '-avatar',
                'label'         => __('Avatar Glow', 'supersede-css-jlg'),
                'class'         => '\\SSC\\Admin\\Pages\\AvatarGlow',
                'group'         => 'effects',
                'enqueue_media' => true,
                'assets'        => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/effects-avatar.js',
                        ],
                    ],
                ],
            ],
            'ecg' => [
                'slug'          => 'ecg',
                'page_slug'     => self::BASE_SLUG . '-ecg',
                'label'         => __('ECG / Battement de Cœur', 'supersede-css-jlg'),
                'class'         => '\\SSC\\Admin\\Pages\\EcgEffects',
                'group'         => 'effects',
                'enqueue_media' => true,
                'assets'        => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/effects-ecg.js',
                        ],
                    ],
                    'styles'  => [
                        [
                            'path' => 'assets/css/visual-effects.css',
                        ],
                    ],
                ],
            ],
            'scope' => [
                'slug'      => 'scope',
                'page_slug' => self::BASE_SLUG . '-scope',
                'label'     => __('Scope Builder', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\ScopeBuilder',
                'group'     => 'tools',
                'assets'    => [
                    'scripts' => [
                        [
                            'path' => 'assets/js/scope-builder.js',
                        ],
                    ],
                ],
            ],
            'css-viewer' => [
                'slug'      => 'css-viewer',
                'page_slug' => self::BASE_SLUG . '-css-viewer',
                'label'     => __('Visualiseur CSS', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\CssViewer',
                'group'     => 'tools',
            ],
            'import' => [
                'slug'      => 'import',
                'page_slug' => self::BASE_SLUG . '-import',
                'label'     => __('Import/Export', 'supersede-css-jlg'),
                'menu_label' => __('Import / Export', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\ImportExport',
                'group'     => 'tools',
                'assets'    => [
                    'scripts' => [
                        [
                            'path'         => 'assets/js/import-export.js',
                            'deps'         => ['jquery', 'wp-i18n'],
                            'translations' => true,
                        ],
                    ],
                ],
            ],
            'css-performance' => [
                'slug'      => 'css-performance',
                'page_slug' => self::BASE_SLUG . '-css-performance',
                'label'     => __('Analyse Performance CSS', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\CssPerformance',
                'group'     => 'tools',
            ],
            'debug-center' => [
                'slug'      => 'debug-center',
                'page_slug' => self::BASE_SLUG . '-debug-center',
                'label'     => __('Debug Center', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\DebugCenter',
                'group'     => 'tools',
                'assets'    => [
                    'scripts' => [
                        [
                            'path'         => 'assets/js/debug-center.js',
                            'deps'         => ['jquery', 'wp-i18n'],
                            'translations' => true,
                        ],
                    ],
                ],
            ],
            'device-lab' => [
                'slug'      => 'device-lab',
                'page_slug' => self::BASE_SLUG . '-device-lab',
                'label'     => __('Device Lab', 'supersede-css-jlg'),
                'class'     => '\\SSC\\Admin\\Pages\\DeviceLab',
                'group'     => 'tools',
                'assets'    => [
                    'scripts' => [
                        [
                            'handle' => 'ssc-device-lab',
                            'path'   => 'assets/js/device-lab.js',
                            'deps'   => [],
                        ],
                    ],
                    'styles'  => [
                        [
                            'handle' => 'ssc-device-lab-style',
                            'path'   => 'assets/css/device-lab.css',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Returns the ordered list of menu groups.
     *
     * @return array<string, string>
     */
    public static function menuGroups(): array
    {
        return [
            'fundamentals'  => __('Fondamentaux', 'supersede-css-jlg'),
            'visual-builders' => __('Générateurs Visuels', 'supersede-css-jlg'),
            'effects'       => __('Effets & Animations', 'supersede-css-jlg'),
            'tools'         => __('Outils & Maintenance', 'supersede-css-jlg'),
        ];
    }

    /**
     * Returns modules indexed by their page slug.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function modulesByPage(): array
    {
        $by_page = [];
        foreach (self::modules() as $module) {
            $by_page[$module['page_slug']] = $module;
        }

        return $by_page;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function submenuModules(): array
    {
        return array_values(array_filter(self::modules(), static fn(array $module): bool => $module['slug'] !== ''));
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function groupedMenu(): array
    {
        $groups = self::menuGroups();
        $grouped = [];

        foreach ($groups as $key => $label) {
            $grouped[$label] = [];
        }

        foreach (self::modules() as $module) {
            $group_key = $module['group'] ?? null;
            if ($group_key === null || !isset($groups[$group_key])) {
                continue;
            }

            $group_label = $groups[$group_key];
            if (!isset($grouped[$group_label])) {
                $grouped[$group_label] = [];
            }

            $grouped[$group_label][$module['page_slug']] = $module['label'];
        }

        return $grouped;
    }

    public static function findByPageSlug(string $page_slug): ?array
    {
        return self::modulesByPage()[$page_slug] ?? null;
    }
}
