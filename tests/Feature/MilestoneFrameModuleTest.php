<?php

namespace Tests\Feature;

use App\Models\MilestoneFrame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MilestoneFrameModuleTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-token-123';
    private array $dirs = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.api_token' => $this->token]);
        $this->actingAs(User::factory()->create(['email' => 'admin@ngdapp']));
    }

    protected function tearDown(): void
    {
        foreach (array_unique($this->dirs) as $d) {
            if (File::exists($d)) {
                File::deleteDirectory($d);
            }
        }
        parent::tearDown();
    }

    private function base(int $gender, int $month): string
    {
        $dir = public_path('upload/milestone_frame/' . MilestoneFrame::genderSlug($gender) . '/' . $month);
        $this->dirs[] = $dir;

        return $dir;
    }

    private function webp(string $name): UploadedFile
    {
        $tmp = sys_get_temp_dir() . '/' . uniqid('ms_') . '.webp';
        $img = imagecreatetruecolor(16, 16);
        imagefill($img, 0, 0, imagecolorallocate($img, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
        imagewebp($img, $tmp);
        imagedestroy($img);

        return new UploadedFile($tmp, $name, 'image/webp', null, true);
    }

    public function test_full_flow(): void
    {
        $this->base(1, 1);

        // Create Boy · Month 1 with 2 frames (one has a thumbnail).
        $this->post('/milestone-frames', [
            'gender'         => 1,
            'month'          => 1,
            'frame_order'    => json_encode(['n0', 'n1']),
            'new_images'     => ['n0' => $this->webp('a.webp'), 'n1' => $this->webp('b.webp')],
            'new_thumbnails' => ['n0' => $this->webp('ta.webp')],
        ])->assertRedirect();

        $rec = MilestoneFrame::where('gender', 1)->where('month', 1)->first();
        $this->assertNotNull($rec);
        $this->assertCount(2, $rec->frames);
        $this->assertSame(0, $rec->frames[0]['frame_index']);
        $this->assertArrayNotHasKey('count', $rec->frames[0]);
        $this->assertTrue(File::exists(public_path('upload/milestone_frame/boy/1/frame/' . $rec->frames[0]['image_path'])));
        $this->assertTrue(File::exists(public_path('upload/milestone_frame/boy/1/frame_thumbnail/' . $rec->frames[0]['thumbnail_path'])));

        // Duplicate gender+month rejected.
        $this->post('/milestone-frames', [
            'gender'      => 1,
            'month'       => 1,
            'frame_order' => json_encode(['n0']),
            'new_images'  => ['n0' => $this->webp('c.webp')],
        ])->assertSessionHasErrors('gender');

        // API requires token.
        $this->postJson('/api/v1/getMonthlyFrameByCategoryId', ['category_id' => 1])->assertStatus(401);

        // API returns month-wise data for gender 1.
        $res = $this->postJson('/api/v1/getMonthlyFrameByCategoryId',
            ['category_id' => 1],
            ['Authorization' => 'Bearer ' . $this->token]
        );
        $res->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('category_name', 'Boy Month Collection')
            ->assertJsonPath('data.0.month', 1)
            ->assertJsonCount(2, 'data.0.frame')
            ->assertJsonCount(1, 'data.0.thumbnail');

        // API validation + empty.
        $this->postJson('/api/v1/getMonthlyFrameByCategoryId', ['category_id' => 3], ['Authorization' => 'Bearer ' . $this->token])->assertStatus(422);
        $this->postJson('/api/v1/getMonthlyFrameByCategoryId', ['category_id' => 2], ['Authorization' => 'Bearer ' . $this->token])->assertStatus(404);
    }

    public function test_gender_month_change_moves_files(): void
    {
        $this->base(1, 2);
        $this->base(2, 5);

        $this->post('/milestone-frames', [
            'gender'      => 1,
            'month'       => 2,
            'frame_order' => json_encode(['n0']),
            'new_images'  => ['n0' => $this->webp('x.webp')],
        ])->assertRedirect();

        $rec  = MilestoneFrame::where('gender', 1)->where('month', 2)->first();
        $path = $rec->frames[0]['image_path'];
        $this->assertTrue(File::exists(public_path("upload/milestone_frame/boy/2/frame/{$path}")));

        // Move the record to Girl · Month 5 (keep the frame).
        $this->put("/milestone-frames/{$rec->id}", [
            'gender'               => 2,
            'month'                => 5,
            'frame_order'          => json_encode(['e0']),
            'keep_image_paths'     => ['e0' => $path],
            'keep_thumbnail_paths' => ['e0' => ''],
        ])->assertRedirect();

        $this->assertFalse(File::exists(public_path("upload/milestone_frame/boy/2/frame/{$path}")));
        $this->assertTrue(File::exists(public_path("upload/milestone_frame/girl/5/frame/{$path}")));
        $this->assertSame(2, $rec->fresh()->gender);
        $this->assertSame(5, $rec->fresh()->month);
    }

    public function test_frame_reorder_reflects_in_api(): void
    {
        $rec = MilestoneFrame::create(['gender' => 1, 'month' => 3, 'frames' => [
            ['image_path' => 'a.webp', 'frame_index' => 0, 'thumbnail_path' => null],
            ['image_path' => 'b.webp', 'frame_index' => 1, 'thumbnail_path' => null],
            ['image_path' => 'c.webp', 'frame_index' => 2, 'thumbnail_path' => null],
        ]]);

        $this->post('/milestone-frames/order', ['id' => $rec->id, 'order' => ['c.webp', 'a.webp', 'b.webp']])
            ->assertJsonPath('success', true);

        $frames = $rec->fresh()->frames;
        $this->assertSame('c.webp', $frames[0]['image_path']);
        $this->assertSame(0, $frames[0]['frame_index']);

        $res = $this->postJson('/api/v1/getMonthlyFrameByCategoryId', ['category_id' => 1], ['Authorization' => 'Bearer ' . $this->token]);
        $urls = $res->json('data.0.frame');
        $this->assertStringContainsString('/c.webp', $urls[0]);
        $this->assertStringContainsString('/a.webp', $urls[1]);
    }

    public function test_api_encodes_spaces(): void
    {
        MilestoneFrame::create(['gender' => 2, 'month' => 7, 'frames' => [
            ['image_path' => 'frame one.webp', 'frame_index' => 0, 'thumbnail_path' => 'thumb one.webp'],
        ]]);

        $res = $this->postJson('/api/v1/getMonthlyFrameByCategoryId', ['category_id' => 2], ['Authorization' => 'Bearer ' . $this->token]);
        $res->assertStatus(200);
        $f = $res->json('data.0.frame.0');
        $t = $res->json('data.0.thumbnail.0');
        $this->assertStringNotContainsString(' ', $f);
        $this->assertStringContainsString('frame%20one.webp', $f);
        $this->assertStringContainsString('thumb%20one.webp', $t);
    }

    public function test_ajax_list_and_delete(): void
    {
        MilestoneFrame::create(['gender' => 1, 'month' => 9, 'frames' => [
            ['image_path' => 'z.webp', 'frame_index' => 0, 'thumbnail_path' => null],
        ]]);

        $this->get('/milestone-frames', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertStatus(200)
            ->assertJsonStructure(['table', 'pagination', 'total']);

        $rec = MilestoneFrame::where('gender', 1)->where('month', 9)->first();
        $this->delete("/milestone-frames/{$rec->id}", [], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
        $this->assertDatabaseMissing('milestone_frames', ['id' => $rec->id]);
    }
}
