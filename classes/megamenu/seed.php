<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_urise\megamenu;

/**
 * Example content for the mega menu, taken from the screen design (Figma "Megamenü").
 *
 * Topic links filter the catalogue by the values the topics of the design stand for,
 * within the audience of their community. Topics without such a value are search links.
 *
 * @package local_urise
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class seed {
    /** @var array Value of the audience filter (zgcommunities) per community tab. */
    public const COMMUNITIES = [
        'phd' => '2',
        'postdocs' => '3',
        'leaders' => '5',
        'staff' => '4',
        'students' => '6',
    ];

    /** @var array Catalogue filters of the topics. Topics without a matching filter value are search links. */
    public const TOPICS = [
        'Equality, Diversity & Inclusion' => ['kompetenzen' => ['24']],
        'IT Services & Tools' => ['kompetenzen' => ['38']],
        'Datenmanagement' => ['kompetenzen' => ['37']],
        'Fachspezifische Recherche' => ['reihenprogramm' => ['11']],
        'Funding Your PhD' => ['kompetenzen' => ['15']],
        'Good Scientific Practice' => ['kompetenzen' => ['12']],
        'Publizieren & wissenschaftlich kommunizieren' => ['kompetenzen' => ['9']],
        'Presentation Skills' => ['kompetenzen' => ['17']],
        'Wissenschaftliches Schreiben' => ['kompetenzen' => ['8']],
        'Wissenschaftskommunikation' => ['reihenprogramm' => ['14']],
        'Karriereplanung & berufliche Entwicklung' => ['kompetenzen' => ['16']],
        'Basisqualifizierung für Erstlehrende' => ['reihenprogramm' => ['1']],
        'Lehrkompetenzentwicklung' => ['kompetenzen' => ['1', '2', '3', '4', '5', '6', '7']],
        'Well-Being in Academia' => ['kompetenzen' => ['33']],
        'Academic English' => ['kompetenzen' => ['21']],
        'Positionierung & Netzwerken' => ['kompetenzen' => ['13']],
        'Zeit-, Projekt- und Selbstmanagement' => ['kompetenzen' => ['27', '28']],
        'Forschungskompetenz & akademische Exzellenz' => ['kompetenzen' => ['8', '9', '10', '11', '12', '13', '14', '15']],
        'Führung & Supervision' => ['kompetenzen' => ['39', '41', '42', '43', '44', '45']],
        'Funding Research' => ['kompetenzen' => ['15']],
        'Karriere & strategische Positionierung' => ['kompetenzen' => ['16']],
        'Kommunikation' => ['kompetenzen' => ['17', '18', '19', '20', '22', '23']],
        'Lehre' => ['kompetenzen' => ['1', '2', '3', '4', '5', '6', '7']],
        'Teamführung & -entwicklung' => ['kompetenzen' => ['41']],
        'Selbstführung' => ['kompetenzen' => ['42']],
        'Mitarbeitende fördern' => ['kompetenzen' => ['43']],
        'Strategische Planung & Umsetzung' => ['kompetenzen' => ['45']],
        'People & Collaboration' => ['kompetenzen' => ['25']],
        'Self-Leadership' => ['kompetenzen' => ['42']],
        'Drittmittelkoordination' => ['kompetenzen' => ['15']],
        'Eventmanagement' => ['kompetenzen' => ['26']],
        'Wissenschaftliches Arbeiten' => ['kompetenzen' => ['8']],
        'Präsentation' => ['kompetenzen' => ['17']],
        'Zeit- & Selbstmanagement' => ['kompetenzen' => ['27', '28']],
        'Sprachkenntnisse' => ['kompetenzen' => ['21']],
        'Recherche & Datenbanken' => ['reihenprogramm' => ['11']],
        'Open Access & Publizieren' => ['kompetenzen' => ['9', '10']],
    ];

    /**
     * Returns the example menu.
     *
     * @return array
     */
    public static function get(): array {
        $chips = fn(string $id) => self::chips("$id-chips", [
            'Arbeitnehmer*innenschutz',
            'Artificial Intelligence',
            'Digital Accessibility',
            'Equality, Diversity & Inclusion',
            'IT Services & Tools',
        ]);

        return [
            'enabled' => true,
            'attachurl' => menu::DEFAULTCATALOGUEURL,
            'catalogueurl' => menu::DEFAULTCATALOGUEURL,
            'tabs' => [
                [
                    'id' => 'overview',
                    'type' => 'overview',
                    'label' => ['de' => 'Gesamtes Angebot', 'en' => 'All offers'],
                    'link' => self::url('overview-link', 'Gesamtes Angebot', 'All offers', menu::DEFAULTCATALOGUEURL),
                    'color' => 'blue',
                    'icon' => 'grid',
                    'heading' => ['de' => 'Angebot nach Community', 'en' => 'Offers by community'],
                    'hint' => [
                        'de' => 'Wählen Sie links Communities für passende Veranstaltungen, '
                            . 'oder öffnen Sie direkt das gesamte Angebot.',
                        'en' => 'Choose a community on the left for matching events, or open all offers directly.',
                    ],
                    'blocks' => [
                        self::linkgrid('overview-topics', 'Themen für alle Communities', 'Topics for all communities', true, [
                            'Equality, Diversity & Inclusion',
                            'Digital Accessibility',
                            'Artificial Intelligence',
                            'IT Services & Tools',
                        ]),
                    ],
                    'cta' => self::url('overview-cta', 'Zum gesamten Angebot', 'All offers', menu::DEFAULTCATALOGUEURL),
                    'secondarylinks' => [
                        self::url('overview-howto', 'So nutzen Sie u:rise', 'How to use u:rise', '/course/index.php?categoryid=82'),
                        self::url('overview-faq', 'FAQs', 'FAQs', '/course/index.php?categoryid=83'),
                    ],
                ],
                self::community('phd', 'Doctoral Candidates', 'Doctoral Candidates', 'petrol', 'owl', 66, '', [
                    [
                        'id' => 'phd-phases',
                        'type' => 'cardrow',
                        'cards' => [
                            self::card('phd-phase1', 'Eingangsphase', 'Entry phase', 'Year 1', [
                                'Datenmanagement',
                                'Fachspezifische Recherche',
                                'Funding Your PhD',
                                'Good Scientific Practice',
                                'Literaturverwaltung',
                                'Navigating Your PhD',
                            ]),
                            self::card('phd-phase2', 'Forschungsphase', 'Research phase', 'Year 2', [
                                'Publizieren & wissenschaftlich kommunizieren',
                                'Presentation Skills',
                                'Wissenschaftliches Schreiben',
                                'Wissenschaftskommunikation',
                            ]),
                            self::card('phd-phase3', 'Abschlussphase', 'Final phase', 'Year 3+4', [
                                'Karriereplanung & berufliche Entwicklung',
                            ]),
                        ],
                    ],
                    self::linkgrid('phd-general', 'Empfohlen, unabhängig von der Phase', 'Recommended for every phase', true, [
                        'Basisqualifizierung für Erstlehrende',
                        'Lehrkompetenzentwicklung',
                        'Well-Being in Academia',
                        'Academic English',
                        'Positionierung & Netzwerken',
                        'Zeit-, Projekt- und Selbstmanagement',
                    ]),
                    $chips('phd'),
                ]),
                self::community('postdocs', 'PostDocs', 'PostDocs', 'yellow', 'briefcase', 67, '', [
                    self::linkgrid('postdocs-topics', '', '', false, [
                        'Forschungskompetenz & akademische Exzellenz',
                        'Führung & Supervision',
                        'Funding Research',
                        'Karriere & strategische Positionierung',
                        'Kommunikation',
                        'Lehre',
                        'Sichtbarkeit & Wirkung',
                        'Well-Being in Academia',
                    ]),
                    $chips('postdocs'),
                ]),
                self::community(
                    'leaders',
                    'Führungskräfte',
                    'Leaders',
                    'purple',
                    'person',
                    68,
                    'Angebote für alle, die an der Universität Wien Teams, Projekte oder Organisationseinheiten führen: '
                        . 'von der ersten Führungsrolle bis zur strategischen Leitung.',
                    [
                        self::linkgrid('leaders-topics', '', '', false, [
                            'Teamführung & -entwicklung',
                            'Selbstführung',
                            'Mitarbeitende fördern',
                            'Strategische Planung & Umsetzung',
                        ]),
                        $chips('leaders'),
                    ]
                ),
                self::community('staff', 'Allgemeines Universitätspersonal', 'General university staff', 'green', 'employee', 69, '',
                    [
                        self::linkgrid('staff-topics', '', '', false, [
                            'Future Work Skills',
                            'People & Collaboration',
                            'Self-Leadership',
                            'Drittmittelkoordination',
                            'Eventmanagement',
                        ]),
                        $chips('staff'),
                    ]
                ),
                self::community(
                    'students',
                    'Studierende',
                    'Students',
                    'teal',
                    'graduation',
                    70,
                    'Veranstaltungen, die das Studium begleiten und auf den Berufseinstieg vorbereiten: '
                        . 'wissenschaftliches Arbeiten, Präsentieren und Selbstorganisation.',
                    [
                        self::linkgrid('students-topics', '', '', false, [
                            'Wissenschaftliches Arbeiten',
                            'Präsentation',
                            'Zeit- & Selbstmanagement',
                            'Sprachkenntnisse',
                        ]),
                    ]
                ),
                self::community(
                    'library',
                    'Bibliotheksnutzer*innen',
                    'Library users',
                    'brown',
                    'library',
                    72,
                    'Schulungen und Services der Universitätsbibliothek: von der ersten Recherche bis zum '
                        . 'Open-Access-Publizieren, offen auch für die interessierte Öffentlichkeit.',
                    [
                        self::linkgrid('library-topics', '', '', false, [
                            'Recherche & Datenbanken',
                            'Literaturverwaltung',
                            'Open Access & Publizieren',
                        ]),
                    ]
                ),
                [
                    'id' => 'multipliers',
                    'type' => 'link',
                    'label' => ['de' => 'Studentische Multiplikator*innen', 'en' => 'Student multipliers'],
                    'color' => 'salmon',
                    'icon' => 'group',
                    'link' => [
                        'id' => 'multipliers-link',
                        'type' => 'filter',
                        'label' => ['de' => 'Studentische Multiplikator*innen', 'en' => 'Student multipliers'],
                        'filters' => ['zgcommunities' => ['8']],
                    ],
                ],
                [
                    'id' => 'ai',
                    'type' => 'link',
                    'label' => ['de' => 'Künstliche Intelligenz', 'en' => 'Artificial Intelligence'],
                    'color' => 'rose',
                    'icon' => 'brain',
                    'link' => self::search('ai-link', 'Künstliche Intelligenz'),
                ],
            ],
        ];
    }

    /**
     * A community tab with the standard right column.
     *
     * @param string $id
     * @param string $de
     * @param string $en
     * @param string $color
     * @param string $icon
     * @param int $categoryid Category of the community landing page.
     * @param string $intro
     * @param array $blocks
     * @return array
     */
    private static function community(
        string $id,
        string $de,
        string $en,
        string $color,
        string $icon,
        int $categoryid,
        string $intro,
        array $blocks
    ): array {
        return [
            'id' => $id,
            'type' => 'panel',
            'label' => ['de' => $de, 'en' => $en],
            'basefilter' => [
                'enabled' => isset(self::COMMUNITIES[$id]),
                'filters' => isset(self::COMMUNITIES[$id]) ? ['zgcommunities' => [self::COMMUNITIES[$id]]] : [],
            ],
            'color' => $color,
            'icon' => $icon,
            'heading' => ['de' => "Empfohlene Angebote für $de", 'en' => "Recommended for $en"],
            'intro' => ['de' => $intro],
            'blocks' => $blocks,
            'cta' => self::url(
                "$id-cta",
                "Zur Community Page $de",
                "To the $en community page",
                "/course/index.php?categoryid=$categoryid"
            ),
            'secondarylinks' => [
                self::url("$id-all", 'Gesamtes Angebot', 'All offers', menu::DEFAULTCATALOGUEURL),
                self::url("$id-faq", 'FAQs', 'FAQs', '/course/index.php?categoryid=83'),
            ],
        ];
    }

    /**
     * A card of a card row, e.g. a PhD phase.
     *
     * @param string $id
     * @param string $de
     * @param string $en
     * @param string $subtitle
     * @param string[] $topics
     * @return array
     */
    private static function card(string $id, string $de, string $en, string $subtitle, array $topics): array {
        return [
            'id' => $id,
            'title' => ['de' => $de, 'en' => $en],
            'subtitle' => ['de' => $subtitle],
            'links' => self::topics($id, $topics),
        ];
    }

    /**
     * A link grid.
     *
     * @param string $id
     * @param string $de
     * @param string $en
     * @param bool $boxed
     * @param string[] $topics
     * @return array
     */
    private static function linkgrid(string $id, string $de, string $en, bool $boxed, array $topics): array {
        return [
            'id' => $id,
            'type' => 'linkgrid',
            'title' => ['de' => $de, 'en' => $en],
            'boxed' => $boxed,
            'links' => self::topics($id, $topics),
        ];
    }

    /**
     * A chips block.
     *
     * @param string $id
     * @param string[] $topics
     * @return array
     */
    private static function chips(string $id, array $topics): array {
        return [
            'id' => $id,
            'type' => 'chips',
            'title' => ['de' => 'Weitere Schwerpunkte', 'en' => 'More topics'],
            'links' => self::topics($id, $topics),
        ];
    }

    /**
     * Links for a list of topics.
     *
     * @param string $prefix
     * @param string[] $topics
     * @return array
     */
    private static function topics(string $prefix, array $topics): array {
        $links = [];
        foreach (array_values($topics) as $index => $topic) {
            $id = "$prefix-$index";
            $links[] = isset(self::TOPICS[$topic])
                ? ['id' => $id, 'type' => 'filter', 'label' => ['de' => $topic], 'filters' => self::TOPICS[$topic]]
                : self::search($id, $topic);
        }
        return $links;
    }

    /**
     * A search link to the catalogue.
     *
     * @param string $id
     * @param string $topic
     * @return array
     */
    private static function search(string $id, string $topic): array {
        return ['id' => $id, 'type' => 'search', 'label' => ['de' => $topic], 'search' => $topic];
    }

    /**
     * A plain link.
     *
     * @param string $id
     * @param string $de
     * @param string $en
     * @param string $url
     * @return array
     */
    private static function url(string $id, string $de, string $en, string $url): array {
        return ['id' => $id, 'type' => 'url', 'label' => ['de' => $de, 'en' => $en], 'url' => $url];
    }
}
