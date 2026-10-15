<?php

namespace Database\Seeders\Eseu;

/**
 * Subiectele II (eseu, 40 p.) și III (articol, 30 p.) din testele ANCE de
 * limba engleză, 2022–2026. Cerința e textul din test; criteriile de conținut
 * sunt cele din baremul aceluiași test (12_len_barem_{sesiune}.pdf).
 */
final class Engleza
{
    private const CONTENT_II = 'Conținutul (competența pragmatică)';

    private const CONTENT_III = 'Conținutul (competența culturală și pragmatică)';

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return [
            // ==================== 2026 ====================
            self::essay('ss26', <<<'TXT'
                Many people believe that managing time effectively is an important life skill. What are the main benefits and/or challenges of good time management?

                When writing your essay, consider the following:
                • Write about how effective time management can help people in different areas of life.
                • Discuss three benefits and/or challenges of time management.
                • Support your ideas with two specific examples from your own experience, something you have read or something you have seen in the media.
                • Express your opinion on the importance of time management.
                • Suggest two ways people can manage their time effectively.

                Follow the structure of an essay: introduction, body, conclusion.
                TXT, 'Time management', [
                self::c('a', self::CONTENT_II, 'Două domenii ale vieții în care gestionarea timpului contează (școală, muncă, familie, sănătate, sport…)', [[0, 'niciunul'], [2, 'unul'], [4, 'două']]),
                self::c('b', self::CONTENT_II, 'Trei beneficii și/sau provocări ale gestionării timpului', [[0, 'niciuna'], [2, 'una'], [4, 'două'], [6, 'trei']]),
                self::c('c', self::CONTENT_II, 'Două exemple relevante (experiență proprie, lecturi, media)', [[0, 'niciunul'], [2, 'unul'], [4, 'două']]),
                self::c('d', self::CONTENT_II, 'Opinia proprie despre importanța gestionării timpului', [[0, 'lipsește'], [2, 'formulată clar']]),
                self::c('e', self::CONTENT_II, 'Două modalități de a-ți gestiona timpul', [[0, 'niciuna'], [2, 'una'], [4, 'două']]),
            ], Rubrici::enEseu26()),

            self::article('ss26', 100, 110, <<<'TXT'
                Your school newspaper has asked you to write an article about a cultural tradition or festival in an English-speaking country.

                In your article, consider the following:
                • Name the cultural tradition/festival and the English-speaking country where it is celebrated.
                • Present three important facts about the tradition/festival.
                • Write about two activities associated with it.
                • Provide two reasons why this tradition/festival is important.

                Follow the structure of an article: title, introduction, body, conclusion.
                TXT, 'A tradition or festival', [
                self::c('a', self::CONTENT_III, 'Numești tradiția sau festivalul', [[0, 'nu'], [1, 'da']]),
                self::c('b', self::CONTENT_III, 'Numești țara anglofonă (sau orașul) în care se sărbătorește', [[0, 'nu'], [1, 'da']]),
                self::c('c', self::CONTENT_III, 'Trei fapte relevante: origine, semnificație, perioadă, simboluri…', [[0, 'niciunul'], [2, 'unul'], [4, 'două'], [6, 'trei']]),
                self::c('d', self::CONTENT_III, 'Două activități sau obiceiuri asociate', [[0, 'niciuna'], [2, 'una'], [4, 'două']]),
                self::c('e', self::CONTENT_III, 'Două motive pentru care tradiția e importantă', [[0, 'niciunul'], [2, 'unul'], [4, 'două']]),
            ], Rubrici::enArticol26()),

            self::essay('sb26', <<<'TXT'
                Many people believe that Artificial Intelligence (AI) improves their lives, while others argue that it has negative effects. What are the advantages and disadvantages of using Artificial Intelligence in different areas of life?

                When writing your essay, consider the following:
                • Write about the use of AI in various fields of human activity.
                • Discuss three advantages and/or disadvantages of using AI.
                • Provide two specific examples (from your own experience, what you have read, or what you have seen in the media) to illustrate your ideas.
                • Express your opinion on AI.
                • Give two recommendations on how AI can be used responsibly.

                Follow the structure of an essay: introduction, body, conclusion.
                TXT, 'Artificial Intelligence', [
                self::c('a', self::CONTENT_II, 'Două domenii în care se folosește inteligența artificială', [[0, 'niciunul'], [2, 'unul'], [4, 'două']]),
                self::c('b', self::CONTENT_II, 'Trei avantaje și/sau dezavantaje ale IA', [[0, 'niciunul'], [2, 'unul'], [4, 'două'], [6, 'trei']]),
                self::c('c', self::CONTENT_II, 'Două exemple pertinente (experiență, societate, media)', [[0, 'niciunul'], [2, 'unul'], [4, 'două']]),
                self::c('d', self::CONTENT_II, 'Opinia proprie despre IA', [[0, 'lipsește'], [2, 'formulată']]),
                self::c('e', self::CONTENT_II, 'Două recomandări pentru folosirea responsabilă a IA', [[0, 'niciuna'], [2, 'una'], [4, 'două']]),
            ], Rubrici::enEseu26()),

            self::article('sb26', 100, 110, <<<'TXT'
                Your school newspaper or magazine has asked you to write an article about a writer/poet from an English-speaking country.

                In your article, consider the following:
                • Name the author and the English-speaking country they represent.
                • Present three facts about their life and career.
                • Give two reasons why this author's works are important today.
                • Suggest two lessons young people can learn from this author and their works.

                Follow the structure of an article: title, introduction, body, conclusion.
                TXT, 'A writer or poet', [
                self::c('a', self::CONTENT_III, 'Numești autorul (scriitor sau poet)', [[0, 'nu'], [1, 'da']]),
                self::c('b', self::CONTENT_III, 'Numești țara anglofonă', [[0, 'nu'], [1, 'da']]),
                self::c('c', self::CONTENT_III, 'Trei fapte despre viața și activitatea autorului', [[0, 'niciunul'], [2, 'unul'], [4, 'două'], [6, 'trei']]),
                self::c('d', self::CONTENT_III, 'Două elemente care arată importanța operei', [[0, 'niciunul'], [2, 'unul'], [4, 'două']]),
                self::c('e', self::CONTENT_III, 'Două învățăminte, lecții sau valori', [[0, 'niciuna'], [2, 'una'], [4, 'două']]),
            ], Rubrici::enArticol26()),

            // ==================== 2025 ====================
            self::essay('ss25', <<<'TXT'
                Zoos have existed for a long time, but they remain a topic of debate. Some people believe that zoos can be useful in protecting wild animals, while others think that zoos are cruel and should be closed down. What are the advantages and/or disadvantages of keeping animals in zoos?

                When writing your essay, consider the following:
                • Explain why animals are important for the planet/people.
                • Discuss the advantages and/or disadvantages of keeping animals in zoos.
                • Provide two examples to support your ideas (e.g., from your experience, something you saw in the news or read in a book, or what zoos usually do).
                • Express your personal opinion about zoos.
                • Justify your point of view.

                Follow the structure of an essay: introduction, body, conclusion.
                TXT, 'Zoos', self::oldEssay(
                'Importanța animalelor pentru planetă și oameni (echilibrul ecosistemelor, lanțul trofic…)',
                'Avantaje și/sau dezavantaje ale grădinilor zoologice',
            ), Rubrici::enEseu2425()),

            self::article('ss25', 90, 100, <<<'TXT'
                Your school newspaper/magazine has asked you to write an article about a well-known person from an English-speaking country who made/has made a significant impact in her/his field.

                Consider the following:
                • Name the person and the English-speaking country she/he represents.
                • Provide a detailed description of her/his activities.
                • Explain the impact of this person's activities on people/their country/the world.
                • Write why you have chosen this particular person.

                Follow the structure of an article: title, introduction, body, conclusion.
                TXT, 'A well-known person', self::oldArticle(
                'Prezinți o persoană reală, cunoscută, dintr-o țară anglofonă (nu un personaj fictiv)',
                'Numești țara anglofonă în care persoana și-a desfășurat activitatea',
                'Descrii activitatea persoanei',
                'Explici impactul activității ei asupra oamenilor, țării sau lumii',
                'Explici de ce ai ales-o, din perspectivă personală',
            ), Rubrici::enArticol2425()),

            self::essay('sb25', <<<'TXT'
                In today's globalised world, people from different cultures interact with each other more often than ever before. Thus, understanding cultural diversity has become an important part of modern life. What are the benefits and/or challenges of learning about various cultures?

                When writing your essay, consider the following:
                • Write about the importance of exploring other cultures in today's world.
                • Discuss the benefits and/or challenges of learning about different cultures.
                • Provide two examples to support your ideas.
                • Express your personal opinion on discovering and understanding diverse cultures.
                • Justify your point of view.

                Follow the structure of an essay: introduction, body, conclusion.
                TXT, 'Cultural diversity', self::oldEssay(
                'Importanța explorării altor culturi',
                'Beneficiile și/sau provocările explorării altor culturi',
            ), Rubrici::enEseu2425()),

            self::article('sb25', 90, 100, <<<'TXT'
                Your school newspaper/magazine has asked you to write an article about a famous building in an English-speaking country.

                Consider the following:
                • Name the building and the English-speaking country where it is located.
                • Provide a detailed description of this building.
                • Explain what this building means to the country, its people and/or the world.
                • Write why you have chosen this particular building.

                Follow the structure of an article: title, introduction, body, conclusion.
                TXT, 'A famous building', self::oldArticle(
                'Prezinți o clădire dintr-o țară anglofonă',
                'Numești țara anglofonă',
                'Descrii clădirea',
                'Explici importanța clădirii',
                'Explici alegerea, din perspectivă personală',
            ), Rubrici::enArticol2425()),

            // ==================== 2024 ====================
            self::essay('ss24', <<<'TXT'
                Nowadays people often say that the art of speaking is important in various aspects of life. What are the benefits and/or challenges of developing strong speaking skills?

                When writing your essay, consider the following:
                • Write about the importance of the art of speaking in today's world.
                • Discuss some benefits and/or difficulties of developing good speaking skills.
                • Provide two examples to support the ideas presented in your essay.
                • Express your opinion about the art of speaking.
                • Justify your point of view.

                Follow the structure of an essay: introduction, body, conclusion.
                TXT, 'The art of speaking', self::oldEssay(
                'Importanța artei comunicării',
                'Beneficiile și/sau dificultățile dezvoltării artei comunicării',
            ), Rubrici::enEseu2425()),

            self::article('ss24', 90, 100, <<<'TXT'
                A school website has asked its readers to write an article about a free-time activity that is popular in an English-speaking country.

                Consider the following:
                • Name a free-time activity and the English-speaking country where it is popular.
                • Give a detailed description of the free-time activity you have chosen.
                • Explain the impact of this activity on the country and/or the world.
                • Express your opinion about this free-time activity. Ground your opinion.

                Follow the structure of an article: title, introduction, body, conclusion.
                TXT, 'A free-time activity', self::oldArticle(
                'Numești activitatea',
                'Numești țara anglofonă',
                'Descrii activitatea',
                'Explici impactul activității',
                'Formulezi și explici atitudinea ta personală',
            ), Rubrici::enArticol2425()),

            self::essay('sb24', <<<'TXT'
                These days, people are discussing the impact of learning new skills on personal and/or professional development. What are the benefits and/or challenges of learning new skills?

                When writing your essay, consider the following:
                • Write about the importance of personal and/or professional development in adapting to the changes of today's world.
                • Discuss the key advantages and/or main difficulties of learning new skills.
                • Provide two examples to support your ideas about learning new skills.
                • Express your opinion about why learning new skills is essential in today's world.
                • Justify your point of view.

                Follow the structure of an essay: introduction, body, conclusion.
                TXT, 'Learning new skills', self::oldEssay(
                'Importanța dezvoltării personale și/sau profesionale pentru adaptarea la schimbări',
                'Beneficiile și/sau provocările învățării unor competențe noi',
            ), Rubrici::enEseu2425()),

            self::article('sb24', 90, 100, <<<'TXT'
                Your school newspaper/magazine has asked you to write an article about a writer/poet from an English-speaking country.

                Consider the following:
                • Name the writer/poet and the English-speaking country he/she represents.
                • Present some facts about his/her literary works and/or activity.
                • Write about the writer's/poet's contribution to society.
                • Explain why you would recommend reading this author's works.

                Follow the structure of an article: title, introduction, body, conclusion.
                TXT, 'A writer or poet', self::oldArticle(
                'Numești autorul',
                'Numești țara anglofonă',
                'Prezinți aspecte ale operei și/sau activității autorului',
                'Arăți contribuția autorului la viața și cultura societății',
                'Formulezi recomandarea, cu atitudinea ta personală',
            ), Rubrici::enArticol2425()),

            // ==================== 2023 ====================
            self::essay('ss23', <<<'TXT'
                Some people say that technological progress is always good, while others think it has negative effects too. Explain your choice, using specific reasons and details.

                When writing your essay, consider the following:
                • Write about the role of technology in people's lives.
                • Identify positive and/or negative sides of technological progress.
                • Provide two examples from your experience, literature, etc. to illustrate the changes in the quality of life.
                • Express your opinion on this issue.
                • Justify your point of view.

                Follow the structure of an essay: introduction, body, conclusion.
                TXT, 'Technological progress', self::oldEssay(
                'Descrii fenomenul: rolul tehnologiei în viața oamenilor',
                'Avantaje și/sau dezavantaje ale progresului tehnologic',
            ), Rubrici::enEseu2223()),

            self::article('ss23', 90, 100, <<<'TXT'
                Your school newspaper / magazine has asked you to write an article about an English-speaking country you would like to visit.

                Consider the following:
                • Name the English-speaking country you would like to visit.
                • Describe some specific features of this country.
                • Explain why this country is worth visiting.
                • Express your opinion.
                • Justify your choice.

                Write an article, following the structure: title, introduction, body, conclusion.
                TXT, 'A country to visit', self::oldArticle(
                'Identifici tema de cultură și civilizație',
                'Prezinți țara anglofonă',
                'Descrii trăsături relevante ale țării',
                'Explici importanța țării pentru locuitori sau turiști',
                'Îți exprimi atitudinea față de vizitarea țării',
            ), Rubrici::enArticol2223()),

            self::essay('sb23', <<<'TXT'
                Some films are designed to make people think, while other films are designed to entertain people. Discuss both types and give your opinion.

                When writing your essay, consider the following:
                • Write about the role of films in people's lives.
                • Explain the influence of different types of films on people's minds/behaviour.
                • Provide two examples to support the ideas you present in your essay.
                • Express your personal opinion on this issue.
                • Justify your point of view.

                Follow the structure of an essay: introduction, body, conclusion.
                TXT, 'Films', self::oldEssay(
                'Descrii fenomenul: rolul filmelor în viața oamenilor',
                'Avantaje și/sau dezavantaje: influența tipurilor de filme',
            ), Rubrici::enEseu2223()),

            self::article('sb23', 90, 100, <<<'TXT'
                Your school newspaper / magazine has asked you to write an article about a famous person from the past of the English-speaking world.

                Consider the following:
                • Name the famous person and the country he / she was from.
                • Describe his / her activities.
                • Explain his / her contribution to the development of the country / world.
                • Write what you think about this personality.
                • Justify your choice.

                Follow the structure of an article: title, introduction, body, conclusion.
                TXT, 'A famous person from the past', self::oldArticle(
                'Identifici tema de cultură și civilizație',
                'Prezinți personalitatea din trecut și numești țara anglofonă',
                'Descrii activitatea personalității',
                'Explici aportul ei la dezvoltarea domeniului, a țării sau a lumii',
                'Îți exprimi atitudinea față de activitatea ei',
            ), Rubrici::enArticol2223()),

            // ==================== 2022 ====================
            self::essay('ss22', <<<'TXT'
                Today more and more companies are employing people who are good at working with others. What are the advantages and disadvantages of teamwork?

                When writing your essay, consider the following:
                • Describe what teamwork is;
                • Identify the pros and/or cons of teamwork;
                • Provide two examples from your experience, reading, etc. to support the advantages and/or disadvantages that you present in your essay;
                • Express your opinion on working in a team;
                • Justify your opinion on the impact of teamwork on businesses, people and their activities.

                Follow the structure of an essay: introduction, body, conclusion.
                TXT, 'Teamwork', self::oldEssay(
                'Descrii fenomenul: ce este lucrul în echipă',
                'Avantaje și/sau dezavantaje ale lucrului în echipă',
            ), Rubrici::enEseu2223()),

            self::article('ss22', 90, 100, <<<'TXT'
                Your school magazine has asked you to write an article about a well-known monument or a famous building situated in an English-speaking country. Consider the following:
                • Present the monument/building and identify its location;
                • Describe this monument/building in as much detail as possible;
                • Explain the role/importance/value of this monument/building for the country and its people;
                • Write what you think about this monument/building, ground your opinion.

                Write an article, following the structure: title, introduction, body, conclusion.
                TXT, 'A monument or building', self::oldArticle(
                'Identifici țara anglofonă',
                'Prezinți monumentul sau clădirea',
                'Descrii monumentul sau clădirea',
                'Explici importanța monumentului pentru poporul țării sau pentru lume',
                'Îți exprimi atitudinea personală',
            ), Rubrici::enArticol2223()),
        ];
    }

    // ==================== constructori ====================

    /** Criteriile de conținut la eseu, 2022–2025: 1 + 4 + 6 + 4 + 2 + 3 = 20. */
    private static function oldEssay(string $b, string $c): array
    {
        return [
            self::c('a', self::CONTENT_II, 'Identifici tema', [[0, 'nu'], [1, 'da']]),
            self::c('b', self::CONTENT_II, $b, [[0, 'lipsește'], [2, 'parțial: un aspect'], [4, 'detaliat: două sau mai multe aspecte']]),
            self::c('c', self::CONTENT_II, $c, [[0, 'niciunul'], [3, 'unul sau două'], [6, 'trei sau patru']]),
            self::c('d', self::CONTENT_II, 'Două exemple relevante', [[0, 'niciunul'], [2, 'unul'], [4, 'două']]),
            self::c('e', self::CONTENT_II, 'Opinia proprie', [[0, 'lipsește'], [2, 'exprimată']]),
            self::c('f', self::CONTENT_II, 'Opinia e justificată prin atitudini, experiențe, fapte', [[0, 'nu'], [3, 'da']]),
        ];
    }

    /** Criteriile de conținut la articol, 2022–2025: 1 + 1 + 6 + 4 + 4 = 16. */
    private static function oldArticle(string $a, string $b, string $c, string $d, string $e): array
    {
        return [
            self::c('a', self::CONTENT_III, $a, [[0, 'nu'], [1, 'da']]),
            self::c('b', self::CONTENT_III, $b, [[0, 'nu'], [1, 'da']]),
            self::c('c', self::CONTENT_III, $c, [[0, 'lipsește'], [3, 'parțial: un aspect'], [6, 'detaliat: două sau mai multe aspecte']]),
            self::c('d', self::CONTENT_III, $d, [[0, 'lipsește'], [2, 'parțial: un aspect'], [4, 'detaliat: două sau mai multe aspecte']]),
            self::c('e', self::CONTENT_III, $e, [[0, 'lipsește'], [2, 'parțial'], [4, 'detaliat']]),
        ];
    }

    /** @param  list<array{0: int, 1: string}>  $options */
    private static function c(string $key, string $group, string $label, array $options): array
    {
        return ['key' => $key, 'group' => $group, 'label' => $label, 'options' => $options];
    }

    private static function essay(string $session, string $task, string $title, array $content, array $language): array
    {
        return [
            'session' => $session,
            'kind' => 'eseu',
            'title' => $title,
            'task' => $task,
            'words' => [180, 200],
            'total' => 40,
            'criteria' => [...$content, ...Rubrici::enSemanticaEseu()],
            ...$language,
        ];
    }

    private static function article(string $session, int $min, int $max, string $task, string $title, array $content, array $language): array
    {
        return [
            'session' => $session,
            'kind' => 'articol',
            'title' => $title,
            'task' => $task,
            'words' => [$min, $max],
            'total' => 30,
            'criteria' => [...$content, ...Rubrici::enSemanticaArticol()],
            ...$language,
        ];
    }
}
