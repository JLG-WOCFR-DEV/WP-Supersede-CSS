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
    }
}
