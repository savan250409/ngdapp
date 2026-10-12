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

        // 2. Create a frame record with 2 frames (one with a thumbnail), drag order n0,n1.
        $res = $this->post('/frames', [
            'category_id'    => $category->id,
            'frame_order'    => json_encode(['n0', 'n1']),
            'new_images'     => ['n0' => $this->webp('a.webp'), 'n1' => $this->webp('b.webp')],
            'new_thumbnails' => ['n0' => $this->webp('ta.webp')],
        ]);
        $res->assertRedirect();

        $frame = Frame::where('category_id', $category->id)->first();
        $this->assertNotNull($frame);
        $this->assertCount(2, $frame->frames);
        $this->assertSame(0, $frame->frames[0]['frame_index']);
        $this->assertSame(1, $frame->frames[1]['frame_index']);
        $this->assertNotNull($frame->frames[0]['thumbnail_path']);
        $this->assertNull($frame->frames[1]['thumbnail_path']);
        // Count / Pro are no longer stored.
        $this->assertArrayNotHasKey('count', $frame->frames[0]);
        $this->assertArrayNotHasKey('is_pro', $frame->frames[0]);
        // Files landed on disk.
        $this->assertTrue(File::exists(public_path("upload/frame/{$name}/frame/" . $frame->frames[0]['image_path'])));
        $this->assertTrue(File::exists(public_path("upload/frame/{$name}/frame_thumbnail/" . $frame->frames[0]['thumbnail_path'])));

        // 3. One-record-per-category rule is enforced.
        $dup = $this->post('/frames', [
            'category_id' => $category->id,
            'frame_order' => json_encode(['n0']),
            'new_images'  => ['n0' => $this->webp('c.webp')],
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

    public function test_categories_ajax_list_renders(): void
    {
        FrameCategory::create(['category_name' => 'Ajax ' . uniqid(), 'status' => 1]);

        // The AJAX branch must render the full view (incl. the Index popup that needs
        // $allCategories) without error — this is what the reload() after delete calls.
        $res = $this->get('/frame-categories', ['X-Requested-With' => 'XMLHttpRequest']);

        $res->assertStatus(200)
            ->assertJsonStructure(['table', 'pagination', 'total']);
    }

    public function test_category_delete_via_ajax_returns_json(): void
    {
        $c = FrameCategory::create(['category_name' => 'DelAjax ' . uniqid(), 'status' => 1]);

        $this->delete("/frame-categories/{$c->id}", [], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('frame_categories', ['id' => $c->id]);
    }

    public function test_category_order_reflects_in_api(): void
    {
        // Three active categories, each with a frame so they appear in the API.
        $make = function (string $name, int $order) {
            $c = FrameCategory::create(['category_name' => $name, 'status' => 1, 'sort_order' => $order]);
            Frame::create(['category_id' => $c->id, 'frames' => [
                ['image_path' => 'x.webp', 'frame_index' => 0, 'thumbnail_path' => null, 'count' => 0, 'is_pro' => false],
            ]]);
            return $c;
        };
        $a = $make('Alpha ' . uniqid(), 1);
        $b = $make('Beta ' . uniqid(), 2);
        $cc = $make('Gamma ' . uniqid(), 3);

        // Reorder: Gamma, Alpha, Beta.
        $this->post('/frame-categories/order', ['order' => [$cc->id, $a->id, $b->id]])
            ->assertJsonPath('success', true);

        $this->assertSame(1, $cc->fresh()->sort_order);
        $this->assertSame(2, $a->fresh()->sort_order);
        $this->assertSame(3, $b->fresh()->sort_order);

        // API returns them in the saved sequence.
        $res = $this->getJson('/api/v1/getFrameCategories', ['Authorization' => 'Bearer ' . $this->token]);
        $res->assertStatus(200)
            ->assertJsonPath('data.0.category_name', $cc->category_name)
            ->assertJsonPath('data.1.category_name', $a->category_name)
            ->assertJsonPath('data.2.category_name', $b->category_name);

        // The internal sort_order is not exposed in the API payload.
        $this->assertArrayNotHasKey('sort_order', $res->json('data.0'));
    }

    public function test_frame_edit_keep_replace_remove_add_and_reindex(): void
    {
        $name = 'EditCat ' . uniqid();
        $this->track($name);

        $category = FrameCategory::create(['category_name' => $name, 'status' => 1]);

        // Seed a record with 2 frames via the store endpoint.
        $this->post('/frames', [
            'category_id'    => $category->id,
            'frame_order'    => json_encode(['n0', 'n1']),
            'new_images'     => ['n0' => $this->webp('one.webp'), 'n1' => $this->webp('two.webp')],
            'new_thumbnails' => ['n0' => $this->webp('t1.webp')],
        ])->assertRedirect();

        $frame    = Frame::where('category_id', $category->id)->first();
        $original = $frame->frames;
        $keepPath = $original[0]['image_path'];   // keep frame #0
        $keepThumb = $original[0]['thumbnail_path'];
        $dropPath = $original[1]['image_path'];   // drop frame #1

        // Edit: new frame first (n0), then kept frame #0 (e0); drop frame #1.
        $this->put("/frames/{$frame->id}", [
            'category_id'          => $category->id,
            'frame_order'          => json_encode(['n0', 'e0']),
            'keep_image_paths'     => ['e0' => $keepPath],
            'keep_thumbnail_paths' => ['e0' => $keepThumb],
            'new_images'           => ['n0' => $this->webp('new.webp')],
        ])->assertRedirect();

        $frame->refresh();
        $frames = $frame->frames;

        // 2 frames: new one first (index 0), kept one second (index 1).
        $this->assertCount(2, $frames);
        $this->assertSame(0, $frames[0]['frame_index']);
        $this->assertSame(1, $frames[1]['frame_index']);
        $this->assertSame($keepPath, $frames[1]['image_path']); // kept frame
        $this->assertArrayNotHasKey('count', $frames[0]);
        $this->assertArrayNotHasKey('is_pro', $frames[0]);

        // Dropped frame's file was deleted; kept + new files exist.
        $this->assertFalse(File::exists(public_path("upload/frame/{$name}/frame/{$dropPath}")));
        $this->assertTrue(File::exists(public_path("upload/frame/{$name}/frame/{$keepPath}")));
        $this->assertTrue(File::exists(public_path("upload/frame/{$name}/frame/" . $frames[0]['image_path'])));
    }

    public function test_frames_reorder_within_category_reflects_in_api(): void
    {
        $c = FrameCategory::create(['category_name' => 'Reorder ' . uniqid(), 'status' => 1]);
        Frame::create(['category_id' => $c->id, 'frames' => [
            ['image_path' => 'a.webp', 'frame_index' => 0, 'thumbnail_path' => null],
            ['image_path' => 'b.webp', 'frame_index' => 1, 'thumbnail_path' => null],
            ['image_path' => 'c.webp', 'frame_index' => 2, 'thumbnail_path' => null],
        ]]);

        // Reorder to c, a, b.
        $this->post('/frames/order', ['category_id' => $c->id, 'order' => ['c.webp', 'a.webp', 'b.webp']])
            ->assertJsonPath('success', true);

        $frames = Frame::where('category_id', $c->id)->first()->frames;
        $this->assertSame('c.webp', $frames[0]['image_path']);
        $this->assertSame(0, $frames[0]['frame_index']);
        $this->assertSame('a.webp', $frames[1]['image_path']);
        $this->assertSame('b.webp', $frames[2]['image_path']);

        // API returns frames in the new order.
        $res = $this->postJson('/api/v1/getFramesByCategoryId',
            ['category_id' => $c->id],
            ['Authorization' => 'Bearer ' . $this->token]
        );
        $res->assertStatus(200);
        $urls = $res->json('data.frame');
        $this->assertStringContainsString('/c.webp', $urls[0]);
        $this->assertStringContainsString('/a.webp', $urls[1]);
        $this->assertStringContainsString('/b.webp', $urls[2]);
    }

    public function test_api_excludes_active_categories_without_frames(): void
    {
        $withFrames = FrameCategory::create(['category_name' => 'Has ' . uniqid(), 'status' => 1]);
        Frame::create(['category_id' => $withFrames->id, 'frames' => [
            ['image_path' => 'x.webp', 'frame_index' => 0, 'thumbnail_path' => null],
        ]]);

        // Active category but empty frame record -> must be excluded.
        $empty = FrameCategory::create(['category_name' => 'Empty ' . uniqid(), 'status' => 1]);
        Frame::create(['category_id' => $empty->id, 'frames' => []]);

        $res = $this->getJson('/api/v1/getFrameCategories', ['Authorization' => 'Bearer ' . $this->token]);
        $res->assertStatus(200);

        $names = collect($res->json('data'))->pluck('category_name');
        $this->assertTrue($names->contains($withFrames->category_name));
        $this->assertFalse($names->contains($empty->category_name));
    }
}
