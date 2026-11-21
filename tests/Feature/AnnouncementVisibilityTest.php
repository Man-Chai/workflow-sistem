<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_parent_only_sees_global_and_own_class_announcements(): void
    {
        $parent = User::where('email', 'dian@korlassync.test')->firstOrFail();
        $ownClassId = $parent->children()->value('class_id');
        $otherClass = ClassRoom::whereKeyNot($ownClassId)->firstOrFail();

        $ownAnnouncement = Announcement::where('class_id', $ownClassId)->firstOrFail();
        $otherAnnouncement = Announcement::where('class_id', $otherClass->id)->firstOrFail();
        $globalAnnouncement = Announcement::whereNull('class_id')
            ->whereNotNull('published_at')
            ->firstOrFail();

        $response = $this->actingAs($parent)->get(route('announcements.index'));

        $response->assertOk()
            ->assertSee($ownAnnouncement->title)
            ->assertSee($globalAnnouncement->title)
            ->assertDontSee($otherAnnouncement->title);
    }

    public function test_parent_cannot_open_announcement_from_another_class(): void
    {
        $parent = User::where('email', 'dian@korlassync.test')->firstOrFail();
        $otherClassId = ClassRoom::whereKeyNot($parent->children()->value('class_id'))->value('id');
        $announcement = Announcement::where('class_id', $otherClassId)
            ->whereNotNull('published_at')
            ->firstOrFail();

        $this->actingAs($parent)
            ->get(route('announcements.show', $announcement))
            ->assertForbidden();
    }

    public function test_parent_cannot_open_unpublished_announcement(): void
    {
        $parent = User::where('email', 'dian@korlassync.test')->firstOrFail();
        $announcement = Announcement::whereNull('published_at')->firstOrFail();

        $this->actingAs($parent)
            ->get(route('announcements.show', $announcement))
            ->assertNotFound();
    }

    public function test_unpublished_announcement_is_not_listed_for_parent(): void
    {
        $parent = User::where('email', 'dian@korlassync.test')->firstOrFail();
        $announcement = Announcement::whereNull('published_at')->firstOrFail();

        $this->actingAs($parent)
            ->get(route('announcements.index'))
            ->assertOk()
            ->assertDontSee($announcement->title);
    }

    public function test_coordinator_sees_only_global_and_own_class_announcements(): void
    {
        $coordinator = User::where('email', 'koordinator@korlassync.test')->firstOrFail();
        $ownClass = $coordinator->classesCoordinated()->firstOrFail();
        $otherClass = ClassRoom::where('id', '!=', $ownClass->id)->firstOrFail();

        $ownAnnouncement = Announcement::where('class_id', $ownClass->id)->firstOrFail();
        $otherAnnouncement = Announcement::where('class_id', $otherClass->id)->firstOrFail();

        $this->actingAs($coordinator)
            ->get(route('announcements.index'))
            ->assertOk()
            ->assertSee($ownAnnouncement->title)
            ->assertDontSee($otherAnnouncement->title);
    }

    public function test_admin_can_read_unpublished_internal_announcement(): void
    {
        $admin = User::where('email', 'admin@korlassync.test')->firstOrFail();
        $announcement = Announcement::whereNull('published_at')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('announcements.index', ['q' => 'Draf program semester']))
            ->assertOk()
            ->assertSee($announcement->title);

        $this->actingAs($admin)
            ->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSee('Draf program semester');
    }
}
