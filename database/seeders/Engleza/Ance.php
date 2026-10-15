<?php

namespace Database\Seeders\Engleza;

use App\Enums\ItemType;

/**
 * Subiectul I (comprehensiunea scrisă) din testele ANCE de limba engleză,
 * 2022–2026 (12_len_test_{sb,ss}YY.pdf). Testul de bază din 2022 nu e publicat.
 *
 * Baremul ANCE la limbi străine nu dă chei: spune doar că răspunsul corect e
 * „în baza textului". Cheia de aici e deci cea pe care o impune textul, iar
 * explicația citează fraza care o justifică — exact ce cere baremul la
 * sarcina adevărat/fals.
 *
 * Textul testului are 400–450 de cuvinte și nu-l reproducem întreg: fiecare
 * item arată doar fraza (frazele) de care are nevoie, iar `source_ref` duce la
 * PDF-ul cu textul complet. Din același motiv lipsesc sarcinile VI (ordinea
 * ideilor) și VII (ideea principală): fără textul întreg nu au sens.
 *
 * Simularea nu poate folosi variante „cu alte date", ca la matematică — o
 * variantă ar fi un text inventat (§11b). Folosește în schimb două teste
 * întregi, ținute în afara antrenamentului.
 */
final class Ance
{
    private const ANCE = 'https://ance.gov.md/sites/default/files/';

    /** Testele rezervate simulării. */
    private const HOLDOUT = ['ss22', 'ss23'];

    /** @return list<array<string, mixed>> */
    public static function items(): array
    {
        return array_merge(...array_map(self::test(...), self::tests()));
    }

    /** @return list<array<string, mixed>> */
    private static function test(array $t): array
    {
        $items = [];

        foreach ($t['complete'] as $i => [$excerpt, $stem, $options, $correct, $hint, $explanation]) {
            $items[] = self::item($t, '1.'.($i + 1), 'comprehensiune-scrisa', 2, 1, ItemType::Mcq,
                self::mcqPayload($t, $excerpt, $stem, $options, true),
                ['correct' => [chr(ord('a') + $correct)]],
                [
                    'Găsește în fragment cuvintele-cheie din enunț și citește atent fraza în care apar.',
                    $hint,
                ],
                $explanation);
        }

        foreach ($t['words'] as $i => [$word, $sentence, $options, $correct, $hint, $explanation]) {
            $items[] = self::item($t, '2.'.($i + 1), 'vocabular-tematic', 2, 1, ItemType::Mcq,
                self::mcqPayload($t, $sentence, "In the text, the word ‘{$word}’ is closest in meaning to:", $options, true),
                ['correct' => [chr(ord('a') + $correct)]],
                [
                    'Înlocuiește cuvântul din frază cu fiecare variantă: sensul frazei trebuie să rămână același.',
                    $hint,
                ],
                $explanation);
        }

        $q = $t['questions'];
        $items[] = self::item($t, '3', 'comprehensiune-scrisa', 3, 4, ItemType::FillBlanks,
            [
                'passage_title' => self::passageTitle($t),
                'passage' => implode("\n\n", $q['excerpt']),
                'stem' => 'Briefly answer the questions below, choosing information from the text.',
                'blanks' => array_map(
                    fn (int $i, array $row) => ['key' => 'q'.($i + 1), 'label' => $row[0]],
                    array_keys($q['answers']), $q['answers'],
                ),
            ],
            [
                'values' => array_combine(
                    array_map(fn (int $i) => 'q'.($i + 1), array_keys($q['answers'])),
                    array_column($q['answers'], 1),
                ),
            ],
            [
                'Baremul cere un cuvânt sau o sintagmă TRANSCRISE din text, nu o propoziție a ta.',
                $q['hint'],
            ],
            'Răspunsurile, transcrise din text: '.implode('; ', array_map(
                fn (array $row) => "„{$row[1][0]}”", $q['answers'])).'.');

        $m = $t['halves'];
        $left = array_map(fn (int $i, string $text) => ['key' => 'l'.($i + 1), 'text' => $text],
            array_keys($m['left']), $m['left']);
        $right = array_map(fn (int $i, string $text) => ['key' => chr(ord('a') + $i), 'text' => $text],
            array_keys($m['right']), $m['right']);
        $items[] = self::item($t, '4', 'comprehensiune-scrisa', 3, 4, ItemType::Match,
            [
                'stem' => "Match the halves of the sentences about the text ‘{$t['title']}’. One is extra.",
                'left' => $left,
                'right' => $right,
            ],
            ['pairs' => array_combine(array_column($left, 'key'), $m['pairs'])],
            [
                'Fiecare jumătate din stânga trebuie să continue gramatical ȘI să spună ce spune textul.',
                $m['hint'],
            ],
            $m['explanation']);

        foreach ($t['tf'] as $i => [$statement, $true, $quote, $hint]) {
            $items[] = self::item($t, '5.'.($i + 1), 'comprehensiune-scrisa', 2, 1, ItemType::Mcq,
                self::mcqPayload($t, $quote, $statement, ['True', 'False'], false),
                ['correct' => [$true ? 'a' : 'b']],
                [
                    'Compară afirmația cu fragmentul cuvânt cu cuvânt: un singur detaliu schimbat o face falsă.',
                    $hint,
                ],
                ($true ? 'True.' : 'False.')." La examen, răspunsul se justifică transcriind fraza din text: „{$quote}”");
        }

        return $items;
    }

    private static function item(array $t, string $task, string $skill, int $difficulty, int $points,
        ItemType $type, array $payload, array $answer, array $hints, string $explanation): array
    {
        $file = "12_len_test_{$t['code']}";

        return [
            'ref' => self::ANCE."{$file}.pdf#item{$task}",
            'source' => "ance:{$file}",
            'holdout' => in_array($t['code'], self::HOLDOUT, true),
            'skill' => $skill,
            'type' => $type,
            'difficulty' => $difficulty,
            'points' => $points,
            'payload' => $payload,
            'answer' => $answer,
            'hints' => $hints,
            'explanation' => $explanation,
        ];
    }

    /** @param  list<string>  $options */
    private static function mcqPayload(array $t, string $excerpt, string $stem, array $options, bool $shuffle): array
    {
        return [
            'passage_title' => self::passageTitle($t),
            'passage' => $excerpt,
            'stem' => $stem,
            'shuffle' => $shuffle,
            'choices' => array_map(
                fn (int $i, string $text) => ['key' => chr(ord('a') + $i), 'text' => $text],
                array_keys($options), $options,
            ),
        ];
    }

    private static function passageTitle(array $t): string
    {
        $year = '20'.substr($t['code'], 2);
        $session = str_starts_with($t['code'], 'sb') ? 'sesiunea de bază' : 'sesiunea suplimentară';

        return "{$t['title']} — fragment din textul testului BAC {$year}, {$session} (ANCE)";
    }

    /** @return list<array<string, mixed>> */
    private static function tests(): array
    {
        return [
            [
                'code' => 'sb26',
                'title' => 'The Sport That Cleans the World',
                'complete' => [
                    ['Instead of scoring goals or running races, competitors collect and sort as much litter as possible within a limited time.',
                        'According to the text, participants … during the spogomi competition.',
                        ['take part in races and try to score points', 'clean public places without a fixed time limit', 'pick up and sort as much rubbish as they can'], 2,
                        '„Collect and sort as much litter as possible" — „litter" și „rubbish" înseamnă același lucru.',
                        'Concurenții adună și sortează cât mai mult gunoi într-un timp limitat. Cursele sunt exact ce NU face sportul („instead of… running races").'],
                    ['The sport started in Japan as a local environmental project, but it has quickly grown into an international competition.',
                        'According to the text, spogomi began as …',
                        ['a community-based environmental initiative.', 'an international competition.', 'a sport for professional athletes.'], 0,
                        'Atenție la timp: ce a fost la ÎNCEPUT („started as") și ce a devenit DUPĂ.',
                        'A început ca „a local environmental project"; competiția internațională e ce a devenit ulterior.'],
                    ['Running is not allowed, so success depends on teamwork and smart planning rather than on speed alone.',
                        'According to the text, success in the competition mainly depends on …',
                        ['the physical strength of each player.', 'working together and planning carefully.', 'finishing the task in the shortest time possible.'], 1,
                        '„Teamwork and smart planning" — care variantă le parafrazează?',
                        '„Teamwork and smart planning" = „working together and planning carefully". Viteza e exclusă explicit („rather than on speed").'],
                    ['One well-known event took place at Hackney Marshes, where teams competed for the chance to represent the UK at future international competitions.',
                        'According to the text, teams took part in the event at Hackney Marshes in order to …',
                        ['organise international competitions in the future.', 'be selected to compete for the UK at global competitions.', 'practise for local competitions later on.'], 1,
                        '„The chance to represent the UK" — ce înseamnă să reprezinți o țară la o competiție?',
                        'Echipele concurau pentru șansa de a reprezenta Marea Britanie la competițiile internaționale, adică pentru a fi selectate.'],
                ],
                'words' => [
                    ['harmful', 'For example, cigarette butts often bring higher scores because they are small and harmful to the environment.',
                        ['unpleasant', 'serious', 'damaging'], 2,
                        '„Harm" = rău, pagubă; „harmful" = care face rău.',
                        '„Harmful to the environment" = care dăunează mediului, deci „damaging".'],
                    ['demanding', 'Participants now know that the sport is more demanding than it looks.',
                        ['exciting', 'difficult', 'popular'], 1,
                        'Fraza următoare din text spune că jucătorii merg distanțe lungi și se apleacă de sute de ori.',
                        '„Demanding" = care cere mult efort, deci „difficult".'],
                ],
                'questions' => [
                    'excerpt' => [
                        'They have one hour to collect rubbish and twenty minutes to sort it carefully.',
                        'For example, cigarette butts often bring higher scores because they are small and harmful to the environment.',
                        'The biggest surprise came from the UK.',
                        'By the 2025 SPOGOMI World Cup in Tokyo, the number of participating teams had increased to over 30.',
                    ],
                    'answers' => [
                        ['How much time do spogomi players have to collect rubbish during the competition?', ['one hour', 'an hour', '1 hour']],
                        ['Which type of litter gives teams more points in the competition?', ['cigarette butts']],
                        ['Which country achieved unexpected success in the first SPOGOMI World Cup?', ['the UK', 'UK', 'the United Kingdom', 'Britain', 'Great Britain']],
                        ['How many teams took part in the 2025 World Cup?', ['over 30', 'more than 30']],
                    ],
                    'hint' => 'La prima întrebare atenție: o oră pentru adunat, douăzeci de minute pentru sortat. La ultima, „over 30" nu e același lucru cu „30".',
                ],
                'halves' => [
                    'left' => ['An environmental campaigner wanted', 'Groups of three players are given', 'Participants understand that', 'Although spogomi is quite new,'],
                    'right' => ['the activity is harder than they expected at first.', 'to win a prize at the end of the competition.', 'gloves, bags, and a map of the area.', 'to make clean-up work more interesting for people.', 'it is already becoming popular worldwide.'],
                    'pairs' => ['d', 'c', 'a', 'e'],
                    'hint' => 'Kenichi Mamitsuka „wanted to make clean-up activities more engaging"; participanții primesc „gloves, bags, and a map".',
                    'explanation' => '1–d (Mamitsuka voia să facă strângerea gunoiului mai atractivă), 2–c (echipamentul primit), 3–a („the sport is more demanding than it looks"), 4–e (sportul se răspândește în lume). În plus e b: textul nu vorbește despre premii.',
                ],
                'tf' => [
                    ['The word spogomi is formed by combining the English word sport with the Japanese word meaning game.', false,
                        'The name combines the English word sport with the Japanese word gomi, which means rubbish.',
                        'Ce înseamnă „gomi" în japoneză?'],
                    ['A score of more than 9,000 points was enough for the UK team to win the 2023 competition.', true,
                        'The British team, called The North Will Rise Again, collected 83.7 kilograms of litter, scored over 9,000 points, and won the world title.',
                        '„Scored over 9,000 points, and won the world title."'],
                    ['It is possible for players to run during spogomi competitions.', false,
                        'Running is not allowed, so success depends on teamwork and smart planning rather than on speed alone.',
                        '„Not allowed" = interzis.'],
                ],
            ],
            [
                'code' => 'ss26',
                'title' => 'More Than a Holiday',
                'complete' => [
                    ['When 19-year-old Emma from the UK finished secondary school, she wanted to travel and learn about life in another country before starting university.',
                        'According to the text, Emma decided to travel because she wanted to …',
                        ['earn money abroad before beginning the next stage of her education.', 'gain new experiences before beginning her university studies.', 'take a gap year after graduating from university.'], 1,
                        'Emma termina liceul („secondary school"), nu universitatea.',
                        'Voia să cunoască viața din altă țară înainte de universitate — experiențe noi. Munca era neplătită, iar universitatea abia urma.'],
                    ['This form of travel combines holidays with unpaid work, allowing participants to contribute to community projects and discover a new country.',
                        'According to the text, volunteer tourism gives travellers the opportunity to …',
                        ['spend their holidays in comfortable hotels.', 'film beautiful landscapes for educational projects.', 'visit new places while helping local communities.'], 2,
                        '„Contribute to community projects and discover a new country" — două lucruri în același timp.',
                        'Voluntariatul combină vacanța cu munca pentru comunitate: descoperi o țară și ajuți oamenii de acolo.'],
                    ['Through its network, travellers can stay with host families and help on organic farms in exchange for accommodation and meals.',
                        'According to the text, organisations like WWOOF help travellers by …',
                        ['finding well-paid jobs in different countries all over the world.', 'organising guided tours to popular tourist attractions around the world.', 'finding places where they can work for food and accommodation.'], 2,
                        '„In exchange for accommodation and meals" — munca nu e plătită în bani.',
                        'Călătorii lucrează la ferme în schimbul cazării și al mesei; nu există salariu.'],
                    ['For example, a volunteer who spends only a few days teaching children may not stay long enough to make a lasting difference to their education.',
                        'According to the text, some people criticise volunteer tourism because …',
                        ['it may have little long-term impact on the community.', 'it is only available in some countries in Europe.', 'it often costs more than young travellers can afford.'], 0,
                        '„A lasting difference" = o schimbare care durează.',
                        'Criticii spun că un voluntar care stă doar câteva zile nu schimbă ceva durabil — impact mic pe termen lung.'],
                ],
                'words' => [
                    ['ordinary', 'Looking back, Emma says she learnt more about Italian culture in three weeks than she would have learnt as an ordinary tourist.',
                        ['typical', 'general', 'daily'], 0,
                        'Un „ordinary tourist" e turistul obișnuit, ca majoritatea.',
                        '„Ordinary" = obișnuit, tipic: turistul care vizitează doar obiectivele.'],
                    ['arrangement', 'For many young travellers, this arrangement makes international travel more affordable and offers a more authentic experience than staying in hotels.',
                        ['permission', 'argument', 'plan'], 2,
                        '„This arrangement" se referă la înțelegerea de dinainte: muncă în schimbul cazării.',
                        '„Arrangement" = înțelegere, plan stabilit dinainte.'],
                ],
                'questions' => [
                    'excerpt' => [
                        'Looking back, Emma says she learnt more about Italian culture in three weeks than she would have learnt as an ordinary tourist.',
                        'She spent her mornings harvesting tomatoes and feeding chickens.',
                        'The organisation operates in more than 130 countries and attracts thousands of participants each year.',
                        'In Costa Rica, volunteers help protect sea turtles and restore natural habitats.',
                    ],
                    'answers' => [
                        ['How long did Emma stay in Italy?', ['three weeks', '3 weeks', 'for three weeks']],
                        ['What two jobs did Emma do on the farm?', ['harvesting tomatoes and feeding chickens', 'harvesting tomatoes, feeding chickens', 'feeding chickens and harvesting tomatoes']],
                        ['How many countries are included in the WWOOF network?', ['more than 130', 'over 130']],
                        ['What animals do volunteers help protect in Costa Rica?', ['sea turtles', 'turtles']],
                    ],
                    'hint' => 'A doua întrebare cere DOUĂ activități — scrie-le pe amândouă, legate cu „and".',
                ],
                'halves' => [
                    'left' => ['Volunteers also give local residents', 'There are cases in which local experts can perform', 'Volunteer tourism attracts people', 'Taking part in volunteer projects is likely to'],
                    'right' => ['who are looking for more purposeful travel experiences.', 'change the way people see the world.', 'spend more time travelling than working.', 'certain tasks more effectively.', 'the opportunity to discover other cultures.'],
                    'pairs' => ['e', 'd', 'a', 'b'],
                    'hint' => '„Give someone the opportunity to…" e o construcție fixă; „perform tasks" — a îndeplini sarcini.',
                    'explanation' => '1–e (și localnicii învață despre cultura voluntarilor), 2–d („trained professionals from the community could perform the same work more effectively"), 3–a (tinerii caută experiențe dincolo de vizitarea obiectivelor), 4–b („to see the world from a new perspective"). În plus e c.',
                ],
                'tf' => [
                    ['The villages that Emma explored in Italy were located far away.', false,
                        'In the afternoons, she explored nearby villages.',
                        '„Nearby" = din apropiere.'],
                    ['Young people represent one of the largest groups involved in volunteer tourism worldwide.', true,
                        'According to international tourism organisations, millions of people participate in volunteer-based travel every year, and young adults represent one of the largest groups.',
                        '„Young adults represent one of the largest groups."'],
                    ['Many volunteers notice no change in the way they communicate with others.', false,
                        'Many participants report improving their communication skills.',
                        '„Improving their communication skills" — e o schimbare sau nu?'],
                ],
            ],
            [
                'code' => 'sb25',
                'title' => 'Taking a Break from Social Media',
                'complete' => [
                    ['Many people are addicted to their phones and constantly reply to notifications.',
                        'According to the text, people often respond immediately to …',
                        ['phone calls from friends.', 'letters delivered by post.', 'messages received on their digital devices.'], 2,
                        '„Notifications" sunt notificările de pe telefon.',
                        'Oamenii răspund constant la notificări — mesajele primite pe dispozitive.'],
                    ['A study led by Sarah Woodruff, a Canadian professor, explored the effects of a two-week detox on young adults. It showed significant improvements in their mental health.',
                        'According to the text, … has positively influenced many young adults’ mental well-being.',
                        ['taking short breaks from social media', 'stealing time from meaningful activities', 'using TikTok and Instagram more often'], 0,
                        '„A two-week detox" = o pauză de două săptămâni de la rețelele sociale.',
                        'Detoxul de două săptămâni a îmbunătățit sănătatea mintală a tinerilor.'],
                    ['Another helpful strategy is to turn off notifications for all social media apps to avoid distractions and check them less often.',
                        'According to the text, if you want to stop checking your social media accounts all the time, you should …',
                        ['reset your password every day.', 'stay constantly connected to your phone.', 'turn off notifications for social media apps.'], 2,
                        '„Check them less often" — ce strategie duce la asta?',
                        'Textul recomandă oprirea notificărilor, ca să verifici aplicațiile mai rar.'],
                    ['A social media detox gives people an opportunity to reflect on their relationship with online platforms. Users should decide whether social media helps them to achieve their main life goals or steals their time from more meaningful activities.',
                        'According to the text, people who use online platforms …',
                        ['need to share the results of their improved lifestyle.', 'should reflect on the impact of social media on their lives.', 'must avoid those who do not use social networking sites.'], 1,
                        '„Reflect on their relationship with online platforms" — care variantă spune același lucru?',
                        'Utilizatorii ar trebui să se gândească dacă rețelele îi ajută sau le fură timpul — adică la impactul lor.'],
                ],
                'words' => [
                    ['average', 'In the USA, the average adult spends approximately two hours a day on social media.',
                        ['typical', 'unusual', 'special'], 0,
                        '„The average adult" — adultul obișnuit, de mijloc.',
                        '„Average" = mediu, tipic; „unusual" și „special" spun opusul.'],
                    ['quit', 'After all, the goal is not to quit completely but just to stay in control.',
                        ['delay', 'leave', 'damage'], 1,
                        '„To quit" social media = a renunța la ea.',
                        '„Quit" = a renunța, a părăsi — „leave".'],
                ],
                'questions' => [
                    'excerpt' => [
                        'In the USA, the average adult spends approximately two hours a day on social media.',
                        'In fact, Google searches for ‘social media detox’ have increased by 60 percent in recent months.',
                        'A study led by Sarah Woodruff, a Canadian professor, explored the effects of a two-week detox on young adults.',
                        'Additionally, joining forces with friends or ‘detox buddies’ can make the process less isolating and more encouraging.',
                    ],
                    'answers' => [
                        ['How much time does the average American adult spend on social media every day?', ['approximately two hours', 'two hours', '2 hours', 'approximately two hours a day', 'two hours a day']],
                        ['What phrase has become more popular in recent Google searches?', ['social media detox', '‘social media detox’']],
                        ['How long did the digital detox last in Woodruff’s study on young adults?', ['two weeks', '2 weeks', 'two-week']],
                        ['Who can support a person during a social media detox?', ['friends', 'detox buddies', 'friends or detox buddies', 'friends or ‘detox buddies’']],
                    ],
                    'hint' => 'La a treia întrebare, „a two-week detox" ascunde răspunsul: cât a durat?',
                ],
                'halves' => [
                    'left' => ['Too much social media use', 'Many people realise how', 'The early stages of a social media detox may', 'Various screen-free activities'],
                    'right' => ['addictive online platforms can be.', 'can be a good substitute for scrolling online.', 'is linked to sleep issues and poorer concentration.', 'is the main life goal.', 'seem hard and uncomfortable.'],
                    'pairs' => ['c', 'a', 'e', 'b'],
                    'hint' => '„The first days of detox can be tough"; cititul și sportul înlocuiesc timpul pe rețele.',
                    'explanation' => '1–c („chronic sleep loss and lower intellectual performance"), 2–a (dependența de telefon), 3–e („the first days of detox can be tough"), 4–b (cititul, sportul și timpul în aer liber înlocuiesc rețelele). În plus e d.',
                ],
                'tf' => [
                    ['Multiple studies have found that overuse of screen time can limit people’s activities that require deep thinking and imagination.', true,
                        'Numerous studies have also shown that too much screen time can prevent individuals from doing activities that require more mental effort and creativity.',
                        '„Mental effort and creativity" = gândire și imaginație.'],
                    ['The research on teenage girls showed that avoiding social media made them feel worse about their appearance.', false,
                        'She observed that a three-day break from social media reduced the girls’ feelings of body shame.',
                        '„Reduced the girls’ feelings of body shame" — s-au simțit mai bine sau mai rău?'],
                    ['Many people who took part in digital detox studies reported that their experience had become less challenging and actually pleasant after several days.', true,
                        'But the good news is that most participants in detox studies found that after a few days it became easier and even enjoyable.',
                        '„Easier and even enjoyable" = „less challenging and actually pleasant".'],
                ],
            ],
            [
                'code' => 'ss25',
                'title' => 'Urban Gardening Is Making City Life Better',
                'complete' => [
                    ['According to the United Nations, by 2050, nearly 70% of the world’s population will live in cities. As a result, traditional farming may find it hard to provide fresh and affordable food for city residents.',
                        'According to the text, urban gardening will become more important in the future because …',
                        ['city population is growing, so fresh food will be harder to supply.', 'more people are choosing to live in the countryside.', 'traditional farming is no longer possible in the cities today.'], 0,
                        '„As a result" leagă cauza (orașele cresc) de efect.',
                        'Populația orașelor crește, iar agricultura tradițională va asigura greu hrană proaspătă. Textul spune „may find it hard", nu „no longer possible".'],
                    ['Plants take in carbon dioxide and give out oxygen, which reduces pollution and makes the air feel fresher.',
                        'According to the text, trees and plants improve city environment by …',
                        ['making them warmer.', 'removing harmful gases.', 'stopping strong winds.'], 1,
                        '„Take in carbon dioxide" — plantele absorb un gaz.',
                        'Plantele absorb dioxidul de carbon și reduc poluarea — elimină gaze nocive.'],
                    ['Finally, gardening is good for mental health and it lifts people’s mood. Simple activities such as planting, watering and harvesting are relaxing and help to reduce stress.',
                        'According to the text, daily life can become better because urban gardening …',
                        ['helps city residents to develop their time management skills.', 'encourages friendly competition between neighbours.', 'lowers residents’ stress levels and improves their mental health.'], 2,
                        '„Reduce stress" și „mental health" apar chiar în fragment.',
                        'Grădinăritul e relaxant, reduce stresul și ajută sănătatea mintală.'],
                    ['Because of limited space, pollution and a lack of resources, it is often difficult for city residents to maintain large gardens.',
                        'Although urban gardening has many advantages, it can be difficult to maintain in cities mainly because of …',
                        ['opposition from local governments.', 'limited space, pollution and a lack of resources.', 'strict building regulations in most cities.'], 1,
                        '„Because of…" introduce cauza.',
                        'Cauzele sunt spațiul limitat, poluarea și lipsa resurselor. Guvernele, dimpotrivă, susțin proiectele.'],
                ],
                'words' => [
                    ['affordable', 'As a result, traditional farming may find it hard to provide fresh and affordable food for city residents.',
                        ['cheap', 'valuable', 'processed'], 0,
                        '„Afford" = a-ți permite (financiar).',
                        '„Affordable" = pe care ți-l permiți, accesibil ca preț — „cheap".'],
                    ['lack', 'Because of limited space, pollution and a lack of resources, it is often difficult for city residents to maintain large gardens.',
                        ['number', 'use', 'shortage'], 2,
                        '„A lack of resources" e o problemă: resursele lipsesc.',
                        '„Lack" = lipsă, insuficiență — „shortage".'],
                ],
                'questions' => [
                    'excerpt' => [
                        'According to the United Nations, by 2050, nearly 70% of the world’s population will live in cities.',
                        'It encourages local residents to grow food in public areas such as bus stops and pavements.',
                        'Similarly, in 2011, the Green Bronx Machine project was launched in New York City in the USA by Stephen Ritz, a teacher from the Bronx.',
                        'Currently, about 15% of the world’s food is grown in cities.',
                    ],
                    'answers' => [
                        ['When will 70% of the people in the world be living in cities?', ['by 2050', '2050', 'in 2050']],
                        ['What kind of places are chosen to grow food in the Incredible Edible project in the UK?', ['public areas', 'public areas such as bus stops and pavements', 'bus stops and pavements']],
                        ['Who started a project that aimed at turning schools into indoor farms in New York City?', ['Stephen Ritz', 'Stephen Ritz, a teacher from the Bronx', 'a teacher from the Bronx']],
                        ['What percentage of the world’s food is grown in cities now?', ['about 15%', '15%', 'about 15 %', '15 %', '15 percent']],
                    ],
                    'hint' => 'La a doua întrebare, „what kind of places" cere tipul de locuri: „public areas".',
                ],
                'halves' => [
                    'left' => ['Many people are starting to grow food', 'Growing food at home helps people to eat', 'Innovative techniques allow plants to grow without', 'If local leaders and communities work together on urban gardening,'],
                    'right' => ['fresh vegetables instead of processed meals.', 'soil, so urban gardening is possible even in small urban places.', 'leads to a healthier diet and lifestyle.', 'in small city spaces such as balconies and rooftops.', 'cities will become greener and healthier.'],
                    'pairs' => ['d', 'a', 'b', 'e'],
                    'hint' => 'Hidroponia permite plantelor să crească „without soil"; c nu se leagă gramatical de niciun început.',
                    'explanation' => '1–d (balcoane, acoperișuri și chiar pereți), 2–a (produse proaspete în loc de mâncare procesată), 3–b (agricultura verticală și hidroponia, „without soil"), 4–e (concluzia textului). În plus e c: are nevoie de un subiect la singular.',
                ],
                'tf' => [
                    ['In 2011, the Green Bronx Machine initiative was introduced to encourage school students to grow food outdoors.', false,
                        'It has helped to transform city schools into indoor farms.',
                        '„Indoor" = în interior.'],
                    ['In Australia, an urban gardening project transforms unused land into productive gardens.', true,
                        'Meanwhile, in Melbourne, Australia, the 3000 Acres project aims at turning empty land into useful gardens.',
                        '„Empty land" = teren nefolosit.'],
                    ['Over the next two decades, the amount of food grown in cities may be twice as much as it is now.', true,
                        'It is possible that this number will double over the next 20 years.',
                        '„Double" = de două ori mai mult; „two decades" = 20 de ani.'],
                ],
            ],
            [
                'code' => 'sb24',
                'title' => 'A Different Approach to Art',
                'complete' => [
                    ['Everyone knows about traditional forms of visual art such as paintings on canvas, drawings with pencil on paper, and sculptures made out of stone, wood, or metal.',
                        'According to the text, popular forms of art such as canvas paintings, pencil drawings, and sculptures …',
                        ['are only found in museums.', 'are appreciated by French artists.', 'are widely recognised by people.'], 2,
                        '„Everyone knows about…" — ce spune asta despre aceste forme de artă?',
                        'Dacă toată lumea le cunoaște, sunt larg recunoscute. Muzeele nu apar în text.'],
                    ['In one piece, for example, a little ballerina appears to wear a flowing skirt when the water is turned on.',
                        'According to the text, in one piece, for example, a little ballerina seems to wear a flowing skirt …',
                        ['when the statue starts melting.', 'when the water starts running.', 'when the temperature drops.'], 1,
                        '„When the water is turned on" = când se pornește apa.',
                        'Fusta „apare" când curge apa fântânii. Statuile care se topesc sunt ale altei artiste.'],
                    ['She makes hundreds of small ice figures and places them in cities around the world. The fact that the statues melt and slowly disappear is intended to motivate people to reflect on their lives.',
                        'According to the text, Brazilian artist Néle Azevedo … to encourage people to enjoy their lives.',
                        ['makes lots of ice statues', 'creates digital art installations', 'paints on city walls'], 0,
                        '„Hundreds of small ice figures."',
                        'Néle Azevedo face sute de figurine de gheață, care se topesc și îi fac pe oameni să se gândească la viața lor.'],
                    ['These artworks, shaped in human form, are filled with soil and wheat seeds that require daily care.',
                        'According to the text, French artist Mathilde Roussel presents her sculptures in the form of …',
                        ['abstract shapes.', 'human bodies.', 'natural landscapes.'], 1,
                        '„Shaped in human form."',
                        'Sculpturile din iarbă au formă umană.'],
                ],
                'words' => [
                    ['message', 'These pieces are not only interesting to look at but also carry an important message about the need to clean up our oceans and keep them free of pollution.',
                        ['report', 'letter', 'idea'], 2,
                        'O sculptură nu trimite o scrisoare — transmite un gând.',
                        '„Carry a message" = a transmite o idee.'],
                    ['stress', 'These works of art inspire us to appreciate the world in new ways and stress the importance of caring for our planet.',
                        ['emphasise', 'influence', 'grow'], 0,
                        'Aici „stress" e verb: „stress the importance of…"',
                        '„To stress the importance" = a sublinia importanța — „emphasise".'],
                ],
                'questions' => [
                    'excerpt' => [
                        'Polish sculptor Małgorzata Chodakowska uses water and bronze to make fountains that come alive with water.',
                        'Ice sculptures, which date back to 17th-century China, are usually made to last through the winter.',
                        'They transform lost shoes, used tyres, old pieces of plastic and metal into sculptures and installations that look like beautiful sea creatures.',
                        'By using organic elements to create human figures, Roussel invites us to consider the importance of our health and diet.',
                    ],
                    'answers' => [
                        ['What materials does Małgorzata Chodakowska combine to create her unique fountains?', ['water and bronze', 'bronze and water']],
                        ['Where did ice sculptures first appear?', ['in China', 'China', 'in 17th-century China', '17th-century China']],
                        ['What do the Portuguese artists’ statues look like?', ['beautiful sea creatures', 'sea creatures', 'like beautiful sea creatures']],
                        ['Which artist draws people’s attention to how healthy they are and what they eat?', ['Mathilde Roussel', 'Roussel']],
                    ],
                    'hint' => '„Date back to" = datează din; „health and diet" = cât de sănătoși suntem și ce mâncăm.',
                ],
                'halves' => [
                    'left' => ['Some sculptors use water', 'Néle Azevedo’s statues', 'It is necessary to look after', 'Some Portuguese artists'],
                    'right' => ['are made of the solid form of water.', 'is not visually pleasing.', 'use waste materials for their creative work.', 'to design amazing works of art.', 'Roussel’s artworks on a daily basis.'],
                    'pairs' => ['d', 'a', 'e', 'c'],
                    'hint' => 'Gheața e „the solid form of water"; sculpturile lui Roussel „require daily care".',
                    'explanation' => '1–d (apa ca material de artă), 2–a (statuile de gheață), 3–e („require daily care"), 4–c (artiștii portughezi folosesc deșeuri de pe plaje). În plus e b: textul spune opusul („visually pleasing").',
                ],
                'tf' => [
                    ['The Portuguese artists’ creations have been displayed only in Portugal.', false,
                        'Their art has been on show in exhibitions in Portugal, Spain, and France.',
                        'Numără țările din frază.'],
                    ['The sculptures created from waste materials make people aware of ecological issues.', true,
                        'These pieces are not only interesting to look at but also carry an important message about the need to clean up our oceans and keep them free of pollution.',
                        '„Clean up our oceans" — e o problemă ecologică?'],
                    ['Art pieces made out of such materials as living plants last forever.', false,
                        'Like humans, Roussel’s works begin life, grow and die.',
                        '„Begin life, grow and die."'],
                ],
            ],
            [
                'code' => 'ss24',
                'title' => 'The Open University',
                'complete' => [
                    ['Students do not have to go to classes on a daily basis. Instead, they study from home using books, videos, CDs and the Internet.',
                        'According to the text, if you study at the Open University, …',
                        ['you must be physically present in a classroom.', 'you don’t have to attend classes every day.', 'you must be a UK citizen.'], 1,
                        '„On a daily basis" = în fiecare zi.',
                        'Studenții nu sunt obligați să meargă zilnic la cursuri; învață de acasă.'],
                    ['Moreover, the university helps around 10,000 disabled students by giving them the special tools they need, such as computer software for students who cannot see well.',
                        'According to the text, the Open University is the world leader in providing … to disabled students.',
                        ['TV sets and paper', 'Internet services', 'specialised support and tools'], 2,
                        '„The special tools they need."',
                        'Universitatea le dă studenților cu dizabilități instrumentele de care au nevoie și atenție în plus.'],
                    ['Meanwhile, David has already received his Bachelor of Law degree and is now working towards his next Open University qualification. Despite David’s age, studying has become his way of life.',
                        'According to the text, getting a degree has become David’s …',
                        ['lifestyle.', 'limit.', 'age.'], 0,
                        '„Way of life."',
                        '„Way of life" = „lifestyle".'],
                    ['The Open University is also ideal for people who are working. Approximately 70% of students work full-time.',
                        'According to the text, about 70% of the students at the Open University … while studying.',
                        ['live on campus', 'work in a team', 'are employed full-time'], 2,
                        '„Work full-time" = au un loc de muncă cu normă întreagă.',
                        '„Work full-time" = „are employed full-time".'],
                ],
                'words' => [
                    ['intend', 'She is proud of her achievement and intends to continue her studies.',
                        ['plan', 'propose', 'suggest'], 0,
                        '„Intends to continue" — își propune pentru sine, nu sugerează altcuiva.',
                        '„Intend" = a intenționa, a plănui.'],
                    ['self-study', 'Additionally, students did self-study and some coursework.',
                        ['group study', 'independent study', 'part-time study'], 1,
                        '„Self-" = singur, pe cont propriu.',
                        '„Self-study" = studiu individual, independent.'],
                ],
                'questions' => [
                    'excerpt' => [
                        'One of the largest British universities is the Open University (OU), which had about 151,840 students in 2022.',
                        'The Open University was established in 1967 and was the first university in the world dedicated to distance learning.',
                        'Kathryn, who is from Cumbernauld, is 19 years old and David, who is from Leven in Fife, is 89.',
                        'Meanwhile, David has already received his Bachelor of Law degree and is now working towards his next Open University qualification.',
                    ],
                    'answers' => [
                        ['How many students studied at the Open University in 2022?', ['about 151,840', '151,840', '151840', '151 840']],
                        ['When was the Open University founded?', ['in 1967', '1967']],
                        ['Who is the youngest graduate of the Open University?', ['Kathryn O’Donnell', 'Kathryn O\'Donnell', 'Kathryn']],
                        ['What degree has David Cunningham already received?', ['Bachelor of Law', 'Bachelor of Law degree', 'his Bachelor of Law degree']],
                    ],
                    'hint' => '„Established" = „founded"; cea mai tânără absolventă are 19 ani.',
                ],
                'halves' => [
                    'left' => ['Educational TV programmes served', 'A high-quality university education', 'Technology gives the possibility', 'The Open University aims at providing'],
                    'right' => ['as a requirement for advanced technological skills.', 'all people with equal educational opportunities.', 'attracts people from all over the world.', 'to study from home.', 'as a source of information and a tool for teaching about 50-60 years ago.'],
                    'pairs' => ['e', 'c', 'd', 'b'],
                    'hint' => 'Ideea universității a venit din popularitatea televiziunii în anii 1960–1970; peste 25 000 de studenți trăiesc în afara Marii Britanii.',
                    'explanation' => '1–e (programele TV educative din anii 1960–1970), 2–c (studenți din toată lumea), 3–d (studiul de acasă), 4–b („equal opportunities"). În plus e a.',
                ],
                'tf' => [
                    ['Kathryn O’Donnell is willing to continue her studies at the Open University.', true,
                        'She is proud of her achievement and intends to continue her studies.',
                        '„Intends to continue her studies."'],
                    ['David Cunningham has already given up studying at the Open University.', false,
                        'Meanwhile, David has already received his Bachelor of Law degree and is now working towards his next Open University qualification.',
                        '„Is now working towards his next… qualification" — a renunțat?'],
                    ['Online courses give students the opportunity to study at their own pace, wherever and whenever they prefer.', true,
                        'Distance learning gives these students the flexibility to study where and when they like, at their own speed.',
                        '„At their own speed" = „at their own pace".'],
                ],
            ],
            [
                'code' => 'sb23',
                'title' => 'A New “Zero-Carbon” City Project',
                'complete' => [
                    ['A desert is not the first location that comes to mind when you think of an eco-friendly city.',
                        'According to the text, people do not consider a desert to be a place for …',
                        ['a prestigious city.', 'a green location.', 'a noisy city.'], 1,
                        '„Eco-friendly" și „green" sunt sinonime.',
                        'Deșertul nu e primul loc la care te gândești pentru un oraș ecologic — un loc „verde".'],
                    ['When the city is finished, it will be the home to more than 50,000 people.',
                        'According to the text, when the construction of Masdar City …, it will be the home to more than 50,000 residents.',
                        ['is improved;', 'is announced;', 'is completed.'], 2,
                        '„When the city is finished."',
                        '„Finished" = „completed".'],
                    ['It will be surrounded by walls to prevent noise and dust from entering.',
                        'According to the text, the city will be walled to …',
                        ['protect residents from noise and water.', 'prevent noise and dust from entering.', 'prevent light and sound from entering.'], 1,
                        'Caută ce anume țin zidurile afară.',
                        'Zidurile opresc zgomotul și praful.'],
                    ['It might become an influential model for other cities that are ready to take action against environmental challenges.',
                        'According to the text, Masdar City might become … for other cities.',
                        ['a powerful example;', 'a useless project;', 'a popular environment.'], 0,
                        '„An influential model."',
                        '„Influential model" = un exemplu puternic, care influențează.'],
                ],
                'words' => [
                    ['global', 'Moreover, companies, such as General Electric in the USA and SunTech, a Chinese solar panel manufacturer, have joined to make Masdar a reality. This has turned into a truly global project.',
                        ['worldwide', 'ordinary', 'attractive'], 0,
                        'Proiectul adună companii din SUA și din China.',
                        '„Global" = mondial — „worldwide".'],
                    ['development', 'Second, recycling is a top priority to the development of the city.',
                        ['management', 'direction', 'progress'], 2,
                        'Orașul e în construcție și crește.',
                        '„Development" = dezvoltare, progres.'],
                ],
                'questions' => [
                    'excerpt' => [
                        'Masdar City project will cost $22 billion to build and will use many different forms of energy, including solar and wind.',
                        'Second, recycling is a top priority to the development of the city.',
                        'Moreover, companies, such as General Electric in the USA and SunTech, a Chinese solar panel manufacturer, have joined to make Masdar a reality.',
                        'Masdar City is expected to be built until 2030.',
                    ],
                    'answers' => [
                        ['How much will Masdar City project cost?', ['$22 billion', '22 billion dollars', '22 billion', '$ 22 billion']],
                        ['What is a top priority to the improvement of the city?', ['recycling']],
                        ['What companies are involved in Masdar City project?', ['General Electric and SunTech', 'General Electric, SunTech', 'SunTech and General Electric']],
                        ['When is the city expected to be built?', ['until 2030', 'by 2030', '2030', 'in 2030']],
                    ],
                    'hint' => 'La a treia întrebare sunt DOUĂ companii, una din SUA și una din China.',
                ],
                'halves' => [
                    'left' => ['Abu Dhabi is known as a', 'Masdar City will become an', 'The project was planned to decrease', 'Bright students of the world'],
                    'right' => ['will do research in alternative energy.', 'both practical and attractive.', 'environmentally friendly community.', 'gigantic oil producer.', 'people’s influence on natural surroundings.'],
                    'pairs' => ['d', 'c', 'e', 'a'],
                    'hint' => '„An" cere un cuvânt care începe cu vocală; „reduce the environmental impact of human activity".',
                    'explanation' => '1–d („one of the largest oil producers in the world"), 2–c (o comunitate verde), 3–e (reducerea impactului omului asupra mediului), 4–a (institutul recrutează cei mai buni studenți pentru cercetare în energie alternativă). În plus e b.',
                ],
                'tf' => [
                    ['A new solar energy factory will generate most of the energy for the whole world.', false,
                        'This plant will produce most of the energy for the whole area.',
                        '„The whole area" sau „the whole world"?'],
                    ['Recycled water will be used to irrigate plants.', true,
                        'What is more, water will be recycled and used again and again; for example, in the irrigation of crops.',
                        '„Irrigation of crops."'],
                    ['The city will give people great opportunities for business growth.', true,
                        'It will work as an ideal place to develop different businesses.',
                        '„An ideal place to develop different businesses."'],
                ],
            ],
            [
                'code' => 'ss23',
                'title' => 'Famous Food Chain',
                'complete' => [
                    ['In the 1930s, two brothers, Richard and Maurice McDonald, left their hometown of New York and went to Hollywood. They had no ambition to become movie stars but they thought Hollywood was a good place to make money.',
                        'According to the text, the McDonald brothers went to … to set up a business.',
                        ['New York', 'Hollywood', 'London'], 1,
                        'New York e orașul pe care l-au PĂRĂSIT.',
                        'Au plecat din New York la Hollywood, ca să facă bani.'],
                    ['They set up a business selling snacks to the employees of the film companies: the cameramen, the technicians, the make-up artists, etc.',
                        'According to the text, they sold their snacks to …',
                        ['the Hollywood stars.', 'passers-by.', 'the employees of the film companies.'], 2,
                        'Cameramani, tehnicieni, machiori — cine sunt ei?',
                        'Clienții erau angajații studiourilor de film, nu vedetele.'],
                    ['For over 70 years, McDonald’s got bigger and bigger but all this suddenly changed in 2013.',
                        'According to the text, their business prospered …',
                        ['more than 70 years.', 'from time to time.', 'more than 60 years.'], 0,
                        '„For over 70 years."',
                        '„Over 70 years" = mai mult de 70 de ani.'],
                    ['McDonald’s management admit that they have a lot of work to do to convince consumers that their products are environmentally friendly and of good quality.',
                        'According to the text, McDonald’s management work on promoting …',
                        ['cheap products.', 'products of good quality.', 'the Big Mac.'], 1,
                        'Ce vor să-i convingă pe clienți despre produse?',
                        'Conducerea vrea să-i convingă pe clienți că produsele sunt ecologice și de calitate.'],
                ],
                'words' => [
                    ['customers', 'They operated from a mobile van with a very limited menu of hamburgers, French fries, soft drinks and apple pies, all served as quickly as possible. This proved to be very popular with the customers.',
                        ['employees', 'technicians', 'clients'], 2,
                        'Cine cumpără mâncarea?',
                        '„Customers" = clienți.'],
                    ['massive', '40% of McDonald’s restaurants are in America, its massive home market, and it is here that the crisis is particularly bad.',
                        ['central', 'huge', 'important'], 1,
                        '„Massive" descrie mărimea.',
                        '„Massive" = uriaș — „huge".'],
                ],
                'questions' => [
                    'excerpt' => [
                        'In 1948, the brothers opened a restaurant, the first of many.',
                        'In 1974, the first McDonald’s restaurants reached Britain and they can now be found in nearly every town in the country.',
                        '40% of McDonald’s restaurants are in America, its massive home market, and it is here that the crisis is particularly bad.',
                        'Apart from quality issues, the two essential elements of a fast food outlet are speed and low prices.',
                    ],
                    'answers' => [
                        ['When did the McDonald brothers open their first restaurant?', ['in 1948', '1948']],
                        ['Where can McDonald’s be found in Britain?', ['in nearly every town', 'nearly every town', 'in nearly every town in the country', 'every town']],
                        ['What percentage of restaurants does the company own in the USA?', ['40%', '40 %', '40 percent']],
                        ['What are the most important things in fast food service?', ['speed and low prices', 'low prices and speed']],
                    ],
                    'hint' => '„The two essential elements" = cele mai importante două lucruri.',
                ],
                'halves' => [
                    'left' => ['The golden M sign', 'The McDonald’s chain has', 'Customers have not been pleased', 'McDonald’s restaurants offer a'],
                    'right' => ['great variety of food now.', 'with the service at McDonald’s.', 'any eco-friendly products.', 'spread all over the world.', 'stands for McDonald’s.'],
                    'pairs' => ['e', 'd', 'b', 'a'],
                    'hint' => '„Has spread" e un present perfect; „pleased with" e o construcție fixă.',
                    'explanation' => '1–e (M-ul auriu), 2–d („has spread all over America and gradually into the wider world"), 3–b (clienții se plâng de servire), 4–a („a bigger choice of things to eat"). În plus e c.',
                ],
                'tf' => [
                    ['The McDonalds started their business in Hollywood.', true,
                        'They set up a business selling snacks to the employees of the film companies: the cameramen, the technicians, the make-up artists, etc.',
                        'Unde lucrau companiile de film?'],
                    ['The younger generation of America tends to buy eco-friendly food products.', true,
                        'Many younger Americans prefer other food outlets which promote a ‘green’ image by focusing on organic food and locally sourced products.',
                        '„Organic food and locally sourced products."'],
                    ['McDonald’s will disappear in a few years.', false,
                        'The big M is not going to disappear!',
                        'Ultima propoziție a textului.'],
                ],
            ],
            [
                'code' => 'ss22',
                'title' => 'The Fresh Water Issue',
                'complete' => [
                    ['Surprisingly, all the rivers and lakes of the world together contain less than a quarter of 1% of the total amount of available fresh water!',
                        'According to the text, less than 0.25% of the total amount of available fresh water is …',
                        ['in the glaciers of Antarctica.', 'in all the rivers and lakes of the world.', 'deep underground.'], 1,
                        '„A quarter of 1%" = 0,25%.',
                        'Râurile și lacurile lumii au împreună sub un sfert de procent din apa dulce.'],
                    ['Also, many homes and businesses have inefficient or broken systems that leak and waste large amounts of water. If they are not repaired, these leaks can cause serious problems.',
                        'According to the text, fixing a leak …',
                        ['saves a large amount of water.', 'creates big problems.', 'contributes to being more wasteful.'], 0,
                        'Problemele apar dacă scurgerile NU sunt reparate.',
                        'Instalațiile stricate risipesc multă apă; reparate, o economisesc.'],
                    ['One way is to simply become more water-conscious and less wasteful.',
                        'According to the text, one of the ways to save water is to …',
                        ['believe it will always be available.', 'have green laws.', 'become more economical with it.'], 2,
                        '„Less wasteful" = mai econom.',
                        'Să fim atenți la apă și să risipim mai puțin.'],
                    ['Another good way to save water is to find new and improved ways to clean ‘dirty’ water.',
                        'According to the text, in order to help conserve water, people need to find better ways to …',
                        ['clean used water.', 'reduce the population.', 'run out of water.'], 0,
                        '„Dirty water" e apa deja folosită.',
                        'Trebuie găsite metode mai bune de a curăța apa folosită.'],
                ],
                'words' => [
                    ['businesses', 'Also, many homes and businesses have inefficient or broken systems that leak and waste large amounts of water.',
                        ['companies', 'goods', 'systems'], 0,
                        '„Homes and businesses" — case și…?',
                        '„Businesses" = firme, companii.'],
                    ['find', 'Another good way to save water is to find new and improved ways to clean ‘dirty’ water.',
                        ['reuse', 'happen', 'discover'], 2,
                        'Metodele noi încă nu există: trebuie…',
                        '„Find new ways" = a descoperi metode noi.'],
                ],
                'questions' => [
                    'excerpt' => [
                        'Approximately 70% of the world’s water is frozen in the snow and glaciers of Antarctica.',
                        'With that number of people, our water supplies could possibly run out.',
                        'Even today in many developing countries, only a very small percentage of people have daily access to clean drinking water.',
                        'Careless overuse is one of the primary reasons the world’s water supply is decreasing.',
                    ],
                    'answers' => [
                        ['How much of the Earth’s water is stored in the snow and glaciers of Antarctica?', ['approximately 70%', '70%', '70 %', 'approximately 70 %']],
                        ['What could happen to the world’s water supplies if the population increases?', ['run out', 'they could run out', 'could possibly run out', 'they could possibly run out']],
                        ['Where do very few people enjoy safe drinking water today?', ['in many developing countries', 'developing countries', 'in developing countries']],
                        ['What is the key reason for the decrease of the world’s water supply?', ['careless overuse', 'overuse']],
                    ],
                    'hint' => '„Primary reasons" = motivele principale; „run out" = a se termina.',
                ],
                'halves' => [
                    'left' => ['There are various ideas of how to', 'Inefficient water systems of some companies can', 'Reused water could be', 'There is a small amount of'],
                    'right' => ['suitable for farming.', 'live on Earth.', 'lead to water waste.', 'fresh water on Earth.', 'save water.'],
                    'pairs' => ['e', 'c', 'a', 'd'],
                    'hint' => '„There are many ways… to conserve water"; apa refolosită poate servi „for industrial or farming purposes".',
                    'explanation' => '1–e (moduri de a economisi apa), 2–c (instalațiile care risipesc apa), 3–a (apa tratată, pentru agricultură), 4–d (doar 3% din apă e dulce). În plus e b.',
                ],
                'tf' => [
                    ['According to the text, today there are too many people who use a huge amount of water in their everyday life.', true,
                        'In general, too many people use too much water every day.',
                        '„Too many people use too much water every day."'],
                    ['According to the text, if you want to become more economical with water, you can leave the water running when you wash the dishes.', false,
                        'Fill in the sink with enough water to rinse the soap off the dishes, but don’t leave the water running!',
                        '„Don’t leave the water running!"'],
                    ['According to the text, collecting water is a complex and expensive method to save fresh water for different purposes.', false,
                        'This is a simple and inexpensive solution that will save fresh water for other important uses.',
                        '„Simple and inexpensive."'],
                ],
            ],
        ];
    }
}
