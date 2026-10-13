<?php

namespace Database\Seeders;

use App\Enums\ExamProfile;
use App\Enums\SubjectRole;
use App\Models\Subject;
use App\Models\SubjectProfile;
use Illuminate\Database\Seeder;

/**
 * Disciplinele de bacalaureat din Republica Moldova.
 *
 * `is_active` controlează ce apare în interfață. Doar matematica e activă la
 * start: platforma se lansează cu o disciplină făcută bine, nu cu opt goale.
 * Restul așteaptă banca de itemi.
 *
 * Rolul diferă pe profil: matematica e probă de profil la real, dar disciplină
 * la alegere la umanist.
 *
 * `icon` e un nume din setul din `resources/icons`, nu un emoji: steagurile
 * (🇷🇴, 🇬🇧) apar pe Windows ca două litere, nu ca steag. `IconTest` verifică
 * că fiecare nume de aici există în set.
 */
class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['matematica', 'Matematică', 'Математика', 'radical', '#4f7dff', true, [
                ExamProfile::Real->value => SubjectRole::Profil,
                ExamProfile::Umanist->value => SubjectRole::LaAlegere,
                ExamProfile::Arte->value => SubjectRole::LaAlegere,
                ExamProfile::Sport->value => SubjectRole::LaAlegere,
            ]],
            ['limba-romana', 'Limba și literatura română', null, 'book-open-text', '#f59e0b', true, [
                ExamProfile::Real->value => SubjectRole::Obligatoriu,
                ExamProfile::Umanist->value => SubjectRole::Obligatoriu,
                ExamProfile::Arte->value => SubjectRole::Obligatoriu,
                ExamProfile::Sport->value => SubjectRole::Obligatoriu,
            ]],
            ['istoria', 'Istoria românilor și universală', 'История', 'globe', '#a855f7', true, [
                ExamProfile::Umanist->value => SubjectRole::Profil,
                ExamProfile::Real->value => SubjectRole::LaAlegere,
            ]],
            ['limba-engleza', 'Limba engleză', null, 'languages', '#10b981', true, [
                ExamProfile::Real->value => SubjectRole::Obligatoriu,
                ExamProfile::Umanist->value => SubjectRole::Obligatoriu,
            ]],
            ['fizica', 'Fizică', 'Физика', 'atom', '#06b6d4', true, [
                ExamProfile::Real->value => SubjectRole::LaAlegere,
            ]],
            ['chimia', 'Chimie', 'Химия', 'flask-conical', '#ec4899', true, [
                ExamProfile::Real->value => SubjectRole::LaAlegere,
            ]],
            ['biologia', 'Biologie', 'Биология', 'dna', '#84cc16', true, [
                ExamProfile::Real->value => SubjectRole::LaAlegere,
            ]],
            ['informatica', 'Informatică', 'Информатика', 'laptop', '#6366f1', true, [
                ExamProfile::Real->value => SubjectRole::LaAlegere,
            ]],
            ['geografia', 'Geografie', 'География', 'map', '#14b8a6', true, [
                ExamProfile::Umanist->value => SubjectRole::LaAlegere,
                ExamProfile::Real->value => SubjectRole::LaAlegere,
            ]],
            // Segmentul alolingv: disciplină de sine stătătoare, nu traducerea
            // celei pentru vorbitori nativi. Concurență aproape zero în MD.
            ['limba-romana-alolingvi', 'Limba română (alolingvi)', 'Румынский язык', 'messages-square', '#f97316', true, [
                ExamProfile::Real->value => SubjectRole::Obligatoriu,
                ExamProfile::Umanist->value => SubjectRole::Obligatoriu,
            ]],
        ];

        foreach ($subjects as $i => [$slug, $name, $nameRu, $icon, $color, $active, $profiles]) {
            $subject = Subject::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'name_ru' => $nameRu,
                'icon' => $icon,
                'color' => $color,
                'is_active' => $active,
                'sort_order' => $i,
            ]);

            foreach ($profiles as $profile => $role) {
                SubjectProfile::updateOrCreate(
                    ['subject_id' => $subject->id, 'profile' => $profile],
                    ['role' => $role],
                );
            }
        }
    }
}
