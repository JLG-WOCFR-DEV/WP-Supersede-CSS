<?php declare(strict_types=1);

use SSC\Infra\Rest\UserPreferencesController;
use SSC\Support\UserPreferences;

final class UserPreferencesControllerTest extends WP_UnitTestCase
{
    private UserPreferencesController $controller;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new UserPreferencesController();
        $this->userId = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->userId);
        delete_user_meta($this->userId, UserPreferences::META_KEY_UTILITIES_EDITOR_MODE);
    }

    public function test_get_preferences_defaults_to_simple(): void
    {
        $request = new WP_REST_Request('GET', '/ssc/v1/user-preferences');

        $response = $this->controller->getPreferences($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('utilities_editor_mode', $data);
        $this->assertSame(UserPreferences::MODE_SIMPLE, $data['utilities_editor_mode']);
    }

    public function test_update_preferences_persists_mode(): void
    {
        $request = new WP_REST_Request('POST', '/ssc/v1/user-preferences');
        $request->set_param('utilities_editor_mode', 'expert');

        $response = $this->controller->updatePreferences($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertSame(UserPreferences::MODE_EXPERT, $data['utilities_editor_mode']);
        $this->assertSame(
            UserPreferences::MODE_EXPERT,
            get_user_meta($this->userId, UserPreferences::META_KEY_UTILITIES_EDITOR_MODE, true)
        );
    }

    public function test_update_preferences_normalizes_invalid_value(): void
    {
        update_user_meta($this->userId, UserPreferences::META_KEY_UTILITIES_EDITOR_MODE, UserPreferences::MODE_EXPERT);

        $request = new WP_REST_Request('POST', '/ssc/v1/user-preferences');
        $request->set_param('utilities_editor_mode', 'unexpected');

        $response = $this->controller->updatePreferences($request);

        $this->assertInstanceOf(WP_REST_Response::class, $response);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertSame(UserPreferences::MODE_SIMPLE, $data['utilities_editor_mode']);
        $this->assertSame(
            UserPreferences::MODE_SIMPLE,
            get_user_meta($this->userId, UserPreferences::META_KEY_UTILITIES_EDITOR_MODE, true)
        );
    }
}
