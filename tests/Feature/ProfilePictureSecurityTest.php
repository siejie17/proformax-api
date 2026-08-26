<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfilePictureSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name' => 'member',
            'display_name' => 'Member',
            'level' => 10,
            'permissions' => [],
        ]);

        $this->user = User::create([
            'first_name' => 'Picture',
            'last_name' => 'Tester',
            'email' => 'picture.tester@example.test',
            'email_verified_at' => now(),
            'role_id' => $role->id,
            'password' => Hash::make('SecurePass1!'),
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_valid_image_is_canonicalized_and_saved_for_authenticated_user(): void
    {
        $dataUrl = 'data:image/png;base64,'.self::PNG_BASE64;

        $this->putJson('/api/user/update-profile-pic', [
            'profile_pic' => $dataUrl,
        ])
            ->assertOk()
            ->assertJsonPath('user.id', $this->user->id)
            ->assertJsonPath('user.profile_pic', $dataUrl);

        $this->assertSame($dataUrl, $this->user->fresh()->profile_pic);
    }

    public function test_arbitrary_string_and_svg_payloads_are_rejected(): void
    {
        $this->putJson('/api/user/update-profile-pic', [
            'profile_pic' => 'not-an-image',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile_pic');

        $this->putJson('/api/user/update-profile-pic', [
            'profile_pic' => 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile_pic');
    }

    public function test_declared_mime_must_match_detected_image_type(): void
    {
        $this->putJson('/api/user/update-profile-pic', [
            'profile_pic' => 'data:image/jpeg;base64,'.self::PNG_BASE64,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile_pic');
    }

    public function test_decoded_image_size_is_limited_to_two_megabytes(): void
    {
        $this->putJson('/api/user/update-profile-pic', [
            'profile_pic' => 'data:image/png;base64,'.base64_encode(str_repeat("\0", (2 * 1024 * 1024) + 1)),
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.profile_pic.0', 'The profile picture must not exceed 2 MB.');
    }

    public function test_image_dimensions_are_limited(): void
    {
        $this->putJson('/api/user/update-profile-pic', [
            'profile_pic' => 'data:image/png;base64,'.base64_encode($this->pngHeader(5000, 1)),
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.profile_pic.0',
                'The profile picture dimensions must not exceed 4096 by 4096 pixels or 16 megapixels.'
            );
    }

    private function pngHeader(int $width, int $height): string
    {
        $data = pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0);
        $chunk = 'IHDR'.$data;

        return "\x89PNG\r\n\x1a\n".pack('N', strlen($data)).$chunk.pack('N', crc32($chunk));
    }
}
