<?php

use App\Domain\Moderation\ReportContent;
use App\Enums\ItemStatus;
use App\Filament\Resources\ContentReports\Pages\ListContentReports;
use App\Filament\Resources\Items\Pages\CreateItem;
use App\Filament\Resources\Items\Pages\EditItem;
use App\Filament\Resources\Items\Pages\ListItems;
use App\Filament\Resources\UserModeration\Pages\ListUserModeration;
use App\Models\ContentReport;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Panoul de administrare, dincolo de prima randare.
 *
 * Un GET pe /admin/aprobari randează doar tab-ul implicit cu filtrele goale.
 * Tab-urile, filtrele și acțiunile trec prin Livewire, deci un test de rută nu
 * le atinge. Exact așa a ajuns în producție o pagină care cădea cu 500: în
 * Filament, argumentele closure-urilor se injectează după NUME — un `fn ($q)`
 * în loc de `fn ($query)` primea un Builder gol, fără model.
 *
 * Fiecare tab, filtru și acțiune se exercită aici, pe tabele cu date reale.
 */
beforeEach(function () {
    $this->seed();
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($this->admin);
});

function pendingUserWithAvatar(): User
{
    $user = User::factory()->pending()->create();
    $user->forceFill(['avatar_path' => 'avatars/test/'.$user->id])->save();

    return $user;
}

it('randează fiecare tab al cozii de aprobări, cu date', function (string $tab) {
    pendingUserWithAvatar();
    User::factory()->pending()->create();
    User::factory()->create(['username_status' => 'rejected']);

    Livewire::test(ListUserModeration::class)
        ->set('activeTab', $tab)
        ->assertOk()
        ->assertHasNoErrors();
})->with(['imagini', 'nume', 'respinse', 'toate']);

it('aplică filtrul de stare al numelui', function () {
    User::factory()->pending()->create();

    Livewire::test(ListUserModeration::class)
        ->filterTable('username_status', 'pending')
        ->assertOk();
});

it('aprobă, respinge numele și respinge imaginea din coadă', function () {
    $deAprobat = pendingUserWithAvatar();
    $numeRespins = User::factory()->pending()->create();
    $imagineRespinsa = pendingUserWithAvatar();

    Livewire::test(ListUserModeration::class)
        ->set('activeTab', 'toate')
        ->callTableAction('approve', $deAprobat)
        ->assertHasNoTableActionErrors()
        ->callTableAction('reject_username', $numeRespins)
        ->assertHasNoTableActionErrors()
        ->callTableAction('reject_avatar', $imagineRespinsa)
        ->assertHasNoTableActionErrors();

    expect($deAprobat->refresh()->username_status)->toBe('approved')
        ->and($numeRespins->refresh()->username_status)->toBe('rejected')
        ->and($imagineRespinsa->refresh()->avatar_status)->not->toBe('pending');
});

it('aprobă în bloc', function () {
    $users = User::factory()->pending()->count(2)->create();

    Livewire::test(ListUserModeration::class)
        ->set('activeTab', 'toate')
        ->callTableBulkAction('approve_all', $users)
        ->assertHasNoTableBulkActionErrors();

    expect($users->map->refresh()->pluck('username_status')->unique()->all())->toBe(['approved']);
});

it('randează fiecare tab al semnalărilor și aplică filtrele', function (string $tab) {
    $target = pendingUserWithAvatar();
    app(ReportContent::class)(User::factory()->create(), $target, 'avatar', 'ofensator');
    app(ReportContent::class)(User::factory()->create(), $target, 'username', 'impersonare');

    Livewire::test(ListContentReports::class)
        ->set('activeTab', $tab)
        ->assertOk()
        ->filterTable('status', 'open')
        ->assertOk()
        ->filterTable('subject_type', 'avatar')
        ->assertOk();
})->with(['ascunse', 'deschise', 'rezolvate', 'toate']);

it('închide, elimină imaginea și cere redenumirea din semnalări', function () {
    $target = pendingUserWithAvatar();
    $altul = User::factory()->create();

    $avatar = app(ReportContent::class)(User::factory()->create(), $target, 'avatar', 'ofensator');
    $nume = app(ReportContent::class)(User::factory()->create(), $target, 'username', 'impersonare');
    $deInchis = app(ReportContent::class)(User::factory()->create(), $altul, 'username', 'spam');

    Livewire::test(ListContentReports::class)
        ->set('activeTab', 'toate')
        ->callTableAction('dismiss', $deInchis)
        ->assertHasNoTableActionErrors()
        ->callTableAction('remove_avatar', $avatar)
        ->assertHasNoTableActionErrors()
        ->callTableAction('force_rename', $nume)
        ->assertHasNoTableActionErrors();

    expect(ContentReport::find($deInchis->id)->status)->not->toBe('open');
});

it('randează fiecare tab al băncii de itemi și aplică filtrele', function (string $tab) {
    Livewire::test(ListItems::class)
        ->set('activeTab', $tab)
        ->assertOk()
        ->filterTable('status', ItemStatus::Published->value)
        ->assertOk()
        ->filterTable('type', 'mcq')
        ->assertOk()
        ->filterTable('needs_review', true)
        ->assertOk()
        ->filterTable('needs_review', false)
        ->assertOk()
        ->filterTable('is_exam_holdout', true)
        ->assertOk()
        ->searchTable('ecuați')
        ->assertOk();
})->with(['suspecti', 'validare', 'ciorne', 'necalibrati', 'publicati', 'toti']);

it('retrage și republică un item din listă', function () {
    $item = Item::servable()->firstOrFail();

    Livewire::test(ListItems::class)
        ->set('activeTab', 'toti')
        ->callTableAction('retire', $item)
        ->assertHasNoTableActionErrors();

    expect($item->refresh()->status)->not->toBe(ItemStatus::Published);

    Livewire::test(ListItems::class)
        ->set('activeTab', 'toti')
        ->callTableAction('publish', $item)
        ->assertHasNoTableActionErrors();

    expect($item->refresh()->status)->toBe(ItemStatus::Published);
});

it('deschide formularele de creare și editare și salvează un item', function () {
    Livewire::test(CreateItem::class)->assertOk();

    $item = Item::servable()->firstOrFail();
    $before = ['answer' => $item->answer, 'explanation' => $item->explanation, 'hints' => $item->hintList()];

    Livewire::test(EditItem::class, ['record' => $item->getRouteKey()])
        ->assertOk()
        ->call('save')
        ->assertHasNoFormErrors();

    // Câmpurile ascunse (#[Hidden]) nu se pierd la o salvare fără modificări.
    $item->refresh();
    expect(['answer' => $item->answer, 'explanation' => $item->explanation, 'hints' => $item->hintList()])
        ->toBe($before);
});

it('publică în bloc', function () {
    $items = Item::servable()->limit(2)->get();
    $items->each(fn (Item $i) => $i->update(['status' => ItemStatus::Review]));

    Livewire::test(ListItems::class)
        ->set('activeTab', 'toti')
        ->callTableBulkAction('publish', $items)
        ->assertHasNoTableBulkActionErrors();

    expect($items->map->refresh()->every(fn (Item $i) => $i->status === ItemStatus::Published && $i->reviewed_by === $this->admin->id))
        ->toBeTrue();
});

it('nu lasă un elev în panou', function () {
    $this->actingAs(User::factory()->create(['role' => 'student']))
        ->get('/admin/aprobari')
        ->assertForbidden();
});

/**
 * Pagina /pulse răspunde 200 chiar și fără tabelele Pulse: cardurile se încarcă
 * separat, prin Livewire, și abia acolo cădeau cu „relation pulse_aggregates
 * does not exist". Migrația pachetului nu fusese publicată niciodată.
 */
it('randează fiecare card din Pulse', function (string $card) {
    Livewire::test($card)->assertOk();
})->with([
    'pulse.servers', 'pulse.usage', 'pulse.queues', 'pulse.cache', 'pulse.slow-queries',
    'pulse.exceptions', 'pulse.slow-requests', 'pulse.slow-jobs', 'pulse.slow-outgoing-requests',
]);
