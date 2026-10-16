<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\Ngd001Frame;
use App\Models\Ngd001FrameCategory;
use App\Models\Ngd001MilestoneFrame;
use App\Models\User;
use App\Support\AppContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PaginationReturnTest extends TestCase
{
    use RefreshDatabase;

    private string $appCode = 'ngd001';
    private array $dirs = [];

    protected function setUp(): void
    {
        parent::setUp();
        App::firstOrCreate(['code' => $this->appCode], ['name' => 'NGD 001', 'status' => 1]);
        AppContext::set($this->appCode);
        $this->actingAs(User::factory()->create(['email' => 'ngd001@admin', 'app_code' => $this->appCode]));
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

    private function webp(string $name): UploadedFile
    {
        $tmp = sys_get_temp_dir() . '/' . uniqid('t_') . '.webp';
        $img = imagecreatetruecolor(16, 16);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 100, 50));
        imagewebp($img, $tmp);
        imagedestroy($img);

        return new UploadedFile($tmp, $name, 'image/webp', null, true);
    }

    public function test_frame_category_edit_on_second_page_returns_second_page(): void
    {
        // Create 15 categories (page 1: 10, page 2: 5)
        for ($i = 1; $i <= 15; $i++) {
            Ngd001FrameCategory::create([
                'category_name' => "Cat {$i}",
                'status'        => 1,
                'sort_order'    => $i,
            ]);
        }

        $target = Ngd001FrameCategory::where('category_name', 'Cat 12')->first();

        // 1. AJAX edit with page=2
        $ajaxRes = $this->putJson("/frame-categories/{$target->id}", [
            'category_name' => 'Cat 12 Updated',
            'status'        => 1,
            'page'          => 2,
        ]);
        $ajaxRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect', route('frame-categories.index', ['page' => 2]));

        // 2. HTTP PUT edit with page=2
        $httpRes = $this->put("/frame-categories/{$target->id}", [
            'category_name' => 'Cat 12 Updated Again',
            'status'        => 1,
            'page'          => 2,
        ]);
        $httpRes->assertRedirect(route('frame-categories.index', ['page' => 2]));
    }

    public function test_frame_category_delete_on_second_page_returns_second_page_or_bounces_if_empty(): void
    {
        // Create 12 categories (page 1: 10, page 2: 2)
        for ($i = 1; $i <= 12; $i++) {
            Ngd001FrameCategory::create([
                'category_name' => "Cat {$i}",
                'status'        => 1,
                'sort_order'    => $i,
            ]);
        }

        $cat12 = Ngd001FrameCategory::where('category_name', 'Cat 12')->first();
        $cat11 = Ngd001FrameCategory::where('category_name', 'Cat 11')->first();

        // Delete item 12 with page=2 (11 items remain, so page 2 still has 1 item)
        $res1 = $this->delete("/frame-categories/{$cat12->id}", ['page' => 2]);
        $res1->assertRedirect(route('frame-categories.index', ['page' => 2]));

        // Now delete item 11 with page=2 (10 items remain, page 2 is now empty, so redirects to page 1)
        $res2 = $this->delete("/frame-categories/{$cat11->id}", ['page' => 2]);
        $res2->assertRedirect(route('frame-categories.index'));
    }

    public function test_frame_edit_on_second_page_returns_second_page(): void
    {
        // Create 12 categories and 12 frames
        for ($i = 1; $i <= 12; $i++) {
            $cat = Ngd001FrameCategory::create([
                'category_name' => "FrameCat {$i}",
                'status'        => 1,
                'sort_order'    => $i,
            ]);
            Ngd001Frame::create([
                'category_id' => $cat->id,
                'frames'      => [
                    ['image_path' => "f{$i}.webp", 'frame_index' => 0, 'thumbnail_path' => null],
                ],
            ]);
        }

        // Check index on page 2: edit link must contain page=2
        $indexRes = $this->get('/frames?page=2');
        $indexRes->assertStatus(200);
        $indexRes->assertSee('page=2');

        $target = Ngd001Frame::orderBy('id', 'desc')->skip(10)->first();

        // 1. AJAX edit with page=2
        $ajaxRes = $this->putJson("/frames/{$target->id}", [
            'category_id'      => $target->category_id,
            'frame_order'      => json_encode(['e0']),
            'keep_image_paths' => ['e0' => $target->frames[0]['image_path']],
            'page'             => 2,
        ]);
        $ajaxRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect', route('frames.index', ['page' => 2]));

        // 2. HTTP PUT edit with page=2
        $httpRes = $this->put("/frames/{$target->id}", [
            'category_id'      => $target->category_id,
            'frame_order'      => json_encode(['e0']),
            'keep_image_paths' => ['e0' => $target->frames[0]['image_path']],
            'page'             => 2,
        ]);
        $httpRes->assertRedirect(route('frames.index', ['page' => 2]));
    }

    public function test_frame_delete_on_second_page_returns_second_page_or_page_one_if_empty(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $cat = Ngd001FrameCategory::create(['category_name' => "DelFrameCat {$i}", 'status' => 1]);
            Ngd001Frame::create([
                'category_id' => $cat->id,
                'frames'      => [['image_path' => "df{$i}.webp", 'frame_index' => 0, 'thumbnail_path' => null]],
            ]);
        }

        $all = Ngd001Frame::orderBy('id', 'desc')->get();
        $frame12 = $all[11];
        $frame11 = $all[10];

        // Delete 1st item on page 2 (11 remain -> stays on page 2)
        $res1 = $this->delete("/frames/{$frame12->id}", ['page' => 2]);
        $res1->assertRedirect(route('frames.index', ['page' => 2]));

        // Delete 2nd item on page 2 (10 remain -> falls back to page 1)
        $res2 = $this->delete("/frames/{$frame11->id}", ['page' => 2]);
        $res2->assertRedirect(route('frames.index'));
    }

    public function test_milestone_frame_edit_on_second_page_returns_second_page(): void
    {
        // Boy has 12 months -> 12 records
        for ($m = 1; $m <= 12; $m++) {
            Ngd001MilestoneFrame::create([
                'gender' => 1,
                'month'  => $m,
                'frames' => [['image_path' => "m{$m}.webp", 'frame_index' => 0, 'thumbnail_path' => null]],
            ]);
        }

        // View page 2
        $indexRes = $this->get('/milestone-frames?page=2');
        $indexRes->assertStatus(200);
        $indexRes->assertSee('page=2');

        $target = Ngd001MilestoneFrame::where('gender', 1)->where('month', 11)->first();

        // 1. AJAX edit with page=2
        $ajaxRes = $this->putJson("/milestone-frames/{$target->id}", [
            'gender'           => 1,
            'month'            => 11,
            'frame_order'      => json_encode(['e0']),
            'keep_image_paths' => ['e0' => 'm11.webp'],
            'page'             => 2,
        ]);
        $ajaxRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect', route('milestone-frames.index', ['page' => 2]));

        // 2. HTTP PUT edit with page=2
        $httpRes = $this->put("/milestone-frames/{$target->id}", [
            'gender'           => 1,
            'month'            => 11,
            'frame_order'      => json_encode(['e0']),
            'keep_image_paths' => ['e0' => 'm11.webp'],
            'page'             => 2,
        ]);
        $httpRes->assertRedirect(route('milestone-frames.index', ['page' => 2]));
    }

    public function test_milestone_frame_delete_on_second_page_returns_second_page_or_page_one_if_empty(): void
    {
        for ($m = 1; $m <= 12; $m++) {
            Ngd001MilestoneFrame::create([
                'gender' => 1,
                'month'  => $m,
                'frames' => [['image_path' => "ms{$m}.webp", 'frame_index' => 0, 'thumbnail_path' => null]],
            ]);
        }

        $rec12 = Ngd001MilestoneFrame::where('gender', 1)->where('month', 12)->first();
        $rec11 = Ngd001MilestoneFrame::where('gender', 1)->where('month', 11)->first();

        // Delete item 12 (11 remain -> page 2)
        $res1 = $this->delete("/milestone-frames/{$rec12->id}", ['page' => 2]);
        $res1->assertRedirect(route('milestone-frames.index', ['page' => 2]));

        // Delete item 11 (10 remain -> page 1)
        $res2 = $this->delete("/milestone-frames/{$rec11->id}", ['page' => 2]);
        $res2->assertRedirect(route('milestone-frames.index'));
    }
}
