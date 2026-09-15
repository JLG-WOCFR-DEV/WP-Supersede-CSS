<?php

declare(strict_types=1);

final class AdminMenuAccessTest extends WP_UnitTestCase
{
    /**
     * @return list<string>
     */
    private function requiredModuleSlugs(): array
    {
        return [
            'supersede-css-jlg-tokens',
            'supersede-css-jlg-utilities',
            'supersede-css-jlg-typography',
        ];
    }

    public function test_token_utility_typography_slugs_are_allowed_for_admin(): void
    {
        wp_set_current_user(self::factory()->user->create(['role' => 'administrator']));
        set_current_screen('dashboard');

        global $menu, $submenu, $_registered_pages, $_parent_pages, $plugin_page, $pagenow, $parent_file;

        $menu = [];
        $submenu = [];
        $_registered_pages = [];
        $_parent_pages = [];
        $parent_file = '';
        $pagenow = 'admin.php';

        $admin = new \SSC\Admin\Admin();
        $admin->menu();
        $admin->hideExtraSubmenuItems();

        $parent = \SSC\Admin\ModuleRegistry::BASE_SLUG;
        $this->assertArrayHasKey($parent, $submenu);

        $registeredSlugs = [];
        foreach ($submenu[$parent] as $item) {
            $registeredSlugs[] = $item[2];
        }

        foreach ($this->requiredModuleSlugs() as $slug) {
            $this->assertContains(
                $slug,
                $registeredSlugs,
                $slug . ' must remain in $submenu so admin.php?page=' . $slug . ' matches a registered page.'
            );
        }

        foreach ($this->requiredModuleSlugs() as $slug) {
            $plugin_page = $slug;
            $parent_file = '';
            $_GET['page'] = $slug;

            $this->assertTrue(
                user_can_access_admin_page(),
                'Administrator must be allowed to open ' . $slug
            );
        }

        $visibleSlugs = $this->visibleSubmenuSlugs($parent);
        $this->assertContains(
            $parent,
            $visibleSlugs,
            'The parent dashboard slug should remain a visible submenu child.'
        );

        foreach ($this->requiredModuleSlugs() as $slug) {
            $this->assertNotContains(
                $slug,
                $visibleSlugs,
                $slug . ' must not appear as a visible wp-admin submenu child; nav-tab/subsubsub is the navigation.'
            );
        }
    }

    /**
     * @return list<string>
     */
    private function visibleSubmenuSlugs(string $parent): array
    {
        global $submenu;

        $visible = [];
        foreach ($submenu[$parent] as $item) {
            $title = isset($item[0]) ? trim(wp_strip_all_tags((string) $item[0])) : '';
            $classes = isset($item[4]) ? (string) $item[4] : '';
            if ($title === '') {
                continue;
            }
            if (preg_match('/(?:^|\s)(?:hidden|hide-if-js)(?:\s|$)/', $classes)) {
                continue;
            }
            $visible[] = $item[2];
        }

        return $visible;
    }
}
