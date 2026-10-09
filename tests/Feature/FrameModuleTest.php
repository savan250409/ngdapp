<?php

namespace Tests\Feature;

use App\Models\Frame;
use App\Models\FrameCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FrameModuleTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-token-123';
    private array $madeDirs = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api_token' => $this->token]);
        // Admin panel routes are auth-protected; act as the admin user.
        $this->actingAs(User::factory()->create(['email' => 'admin@gmail.com']));
    }

    protected function tearDown(): void
    {
        // Remove any upload folders this test created.
        foreach (array_unique($this->madeDirs) as $name) {
            $dir = public_path('upload/frame/' . $name);
            if (File::exists($dir)) {
                File::deleteDirectory($dir);
            }
        }
        parent::tearDown();
    }

    /** Make a real .webp file on disk and wrap it as a test UploadedFile. */
    private function webp(string $name): UploadedFile
    {
        $tmp = sys_get_temp_dir() . '/' . uniqid('webp_') . '.webp';
        $img = imagecreatetruecolor(16, 16);
        imagefill($img, 0, 0, imagecolorallocate($img, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
        imagewebp($img, $tmp);
        imagedestroy($img);

        return new UploadedFile($tmp, $name, 'image/webp', null, true);
    }

    private function track(string $categoryName): void
    {
        $this->madeDirs[] = $categoryName;
    }

    public function test_full_module_flow(): void
    {
        $name = 'TestCat ' . uniqid();
        $this->track($name);

        // 1. Create a category with a thumbnail.
        $res = $this->post('/frame-categories', [
            'category_name'  => $name,
            'category_image' => $this->webp('cat.webp'),
            'status'         => 1,
        ]);
        $res->assertRedirect();

        $category = FrameCategory::where('category_name', $name)->first();
        $this->assertNotNull($category);
        $this->assertNotNull($category->category_image);
        $this->assertTrue(File::exists(public_path("upload/frame/{$name}/category_thumbnail_frame/{$category->category_image}")));

        // 2. Create a frame record with 2 frames (one with a thumbnail).
        $res = $this->post('/frames', [
            'category_id' => $category->id,
            'images'      => [$this->webp('a.webp'), $this->webp('b.webp')],
            'thumbnails'  => [$this->webp('ta.webp'), null],
            'counts'      => [5, 9],
            'is_pros'     => [1, 0],
        ]);
        $res->assertRedirect();

        $frame = Frame::where('category_id', $category->id)->first();
        $this->assertNotNull($frame);
        $this->assertCount(2, $frame->frames);
        $this->assertSame(0, $frame->frames[0]['frame_index']);
        $this->assertSame(1, $frame->frames[1]['frame_index']);
        $this->assertSame(5, $frame->frames[0]['count']);
        $this->assertTrue($frame->frames[0]['is_pro']);
        $this->assertFalse($frame->frames[1]['is_pro']);
        $this->assertNotNull($frame->frames[0]['thumbnail_path']);
        $this->assertNull($frame->frames[1]['thumbnail_path']);
        // Files landed on disk.
        $this->assertTrue(File::exists(public_path("upload/frame/{$name}/frame/" . $frame->frames[0]['image_path'])));
        $this->assertTrue(File::exists(public_path("upload/frame/{$name}/frame_thumbnail/" . $frame->frames[0]['thumbnail_path'])));

        // 3. One-record-per-category rule is enforced.
        $dup = $this->post('/frames', [
            'category_id' => $category->id,
            'images'      => [$this->webp('c.webp')],
        ]);
        $dup->assertSessionHasErrors('category_id');

        // 4. API: list categories (requires bearer token).
        $this->getJson('/api/v1/getFrameCategories')->assertStatus(401);

        $list = $this->getJson('/api/v1/getFrameCategories', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);
        $list->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.0.category_name', $name)
            ->assertJsonCount(2, 'data.0.frames');
        $this->assertStringContainsString('/upload/frame/', $list->json('data.0.frames.0.frame_full_url'));

        // 5. API: frames by category id.
        $byId = $this->postJson('/api/v1/getFramesByCategoryId',
            ['category_id' => $category->id],
            ['Authorization' => 'Bearer ' . $this->token]
        );
        $byId->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonCount(2, 'data.frame')
            ->assertJsonCount(2, 'data.thumbnail');

        // 6. API validation: missing id -> 422.
        $this->postJson('/api/v1/getFramesByCategoryId', [],
            ['Authorization' => 'Bearer ' . $this->token]
        )->assertStatus(422);

        // 7. Inactive category -> 403.
        $category->update(['status' => 0]);
        $this->postJson('/api/v1/getFramesByCategoryId',
            ['category_id' => $category->id],
            ['Authorization' => 'Bearer ' . $this->token]
        )->assertStatus(403);
        // Inactive is also excluded from the list.
        $this->getJson('/api/v1/getFrameCategories', ['Authorization' => 'Bearer ' . $this->token])
            ->assertStatus(404);
        $category->update(['status' => 1]);

        // 8. Toggle status endpoint.
        $toggle = $this->post("/frame-categories/{$category->id}/toggle-status");
        $toggle->assertJsonPath('status', 0);
        $this->assertSame(0, $category->fresh()->status);
        $category->update(['status' => 1]);

        // 9. Rename category moves its folder.
        $newName = 'Renamed ' . uniqid();
        $this->track($newName);
        $this->put("/frame-categories/{$category->id}", [
            'category_name' => $newName,
            'status'        => 1,
        ])->assertRedirect();
        $this->assertTrue(File::exists(public_path("upload/frame/{$newName}/frame")));
        $this->assertFalse(File::exists(public_path("upload/frame/{$name}")));

        // 10. Delete the category removes folder + cascades the frame record.
        $this->delete("/frame-categories/{$category->id}")->assertRedirect();
        $this->assertFalse(File::exists(public_path("upload/frame/{$newName}")));
        $this->assertDatabaseMissing('frames', ['category_id' => $category->id]);
        $this->assertDatabaseMissing('frame_categories', ['id' => $category->id]);
    }

    public function test_frame_edit_keep_replace_remove_add_and_reindex(): void
    {
        $name = 'EditCat ' . uniqid();
        $this->track($name);

        $category = FrameCategory::create(['category_name' => $name, 'status' => 1]);

        // Seed a record with 2 frames via the store endpoint.
        $this->post('/frames', [
            'category_id' => $category->id,
            'images'      => [$this->webp('one.webp'), $this->webp('two.webp')],
            'thumbnails'  => [$this->webp('t1.webp'), null],
            'counts'      => [1, 2],
            'is_pros'     => [0, 1],
        ])->assertRedirect();

        $frame    = Frame::where('category_id', $category->id)->first();
        $original = $frame->frames;
        $keepPath = $original[0]['image_path'];   // keep frame #0
        $keepThumb = $original[0]['thumbnail_path'];
        $dropPath = $original[1]['image_path'];   // drop frame #1

        // Edit: keep frame #0 (edit count), drop frame #1, add 1 new frame.
        $this->put("/frames/{$frame->id}", [
            'category_id'          => $category->id,
            'keep_image_paths'     => [0 => $keepPath],
            'keep_thumbnail_paths' => [0 => $keepThumb],
            'keep_counts'          => [0 => 42],
            'keep_is_pros'         => [0 => 1],
            'new_images'           => [$this->webp('new.webp')],
            'new_thumbnails'       => [null],
            'new_counts'           => [7],
            'new_is_pros'          => [0],
        ])->assertRedirect();

        $frame->refresh();
        $frames = $frame->frames;

        // 2 frames: new one first (index 0), kept one second (index 1).
        $this->assertCount(2, $frames);
        $this->assertSame(0, $frames[0]['frame_index']);
        $this->assertSame(1, $frames[1]['frame_index']);
        $this->assertSame(7, $frames[0]['count']);          // new frame
        $this->assertSame($keepPath, $frames[1]['image_path']); // kept frame
        $this->assertSame(42, $frames[1]['count']);         // kept frame's edited count
        $this->assertTrue($frames[1]['is_pro']);

        // Dropped frame's file was deleted; kept + new files exist.
        $this->assertFalse(File::exists(public_path("upload/frame/{$name}/frame/{$dropPath}")));
        $this->assertTrue(File::exists(public_path("upload/frame/{$name}/frame/{$keepPath}")));
        $this->assertTrue(File::exists(public_path("upload/frame/{$name}/frame/" . $frames[0]['image_path'])));
    }
}
