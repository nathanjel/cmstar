SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cms_entry`
--

CREATE TABLE `cms_entry` (
  `cmsid` bigint(20) NOT NULL,
  `site` smallint(6) NOT NULL,
  `pathl` varchar(60) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `lang` smallint(4) NOT NULL,
  `typepath` varchar(60) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sortid` int(11) NOT NULL,
  `name` char(250) COLLATE utf8_unicode_ci NOT NULL,
  `lname` char(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `published` tinyint(4) NOT NULL DEFAULT '0',
  `level` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=REDUNDANT;

ALTER TABLE `cms_entry`
  ADD PRIMARY KEY (`cmsid`),
  ADD UNIQUE KEY `site` (`site`,`pathl`,`lang`),
  ADD KEY `level` (`level`),
  ADD KEY `site_2` (`site`,`lang`,`lname`(10));

ALTER TABLE `cms_entry`
  MODIFY `cmsid` bigint(20) NOT NULL AUTO_INCREMENT;

INSERT INTO `cms_entry` (`cmsid`, `site`, `pathl`, `lang`, `typepath`, `sortid`, `name`, `lname`, `published`, `level`) VALUES 
(NULL, '1', '10', '127', '10', '0', 'Main', 'Main', '1', '0');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cms_fields`
--

CREATE TABLE `cms_fields` (
  `cmsid` bigint(20) NOT NULL,
  `fname` char(30) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ptyp` char(1) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'a',
  `value` mediumtext COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=REDUNDANT;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cms_history_changes`
--

CREATE TABLE `cms_history_changes` (
  `user` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `change` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cmsid` bigint(20) NOT NULL,
  `site` smallint(6) NOT NULL,
  `lang` smallint(6) NOT NULL,
  `pathl` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `field` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `old` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `new` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(15) CHARACTER SET ascii COLLATE ascii_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cms_history_login`
--

CREATE TABLE `cms_history_login` (
  `user` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `login` datetime NOT NULL,
  `logout` datetime DEFAULT NULL,
  `succ` tinyint(4) NOT NULL DEFAULT '0',
  `ip` varchar(20) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cms_lang`
--

CREATE TABLE `cms_lang` (
  `lang` smallint(4) NOT NULL,
  `sortkey` tinyint(4) NOT NULL,
  `ename` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `lname` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `iso6391` varchar(2) COLLATE utf8_unicode_ci NOT NULL,
  `iso6392t` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `iso6392b` varchar(3) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Zrzut danych tabeli `cms_lang`
--

INSERT INTO `cms_lang` (`lang`, `sortkey`, `ename`, `lname`, `iso6391`, `iso6392t`, `iso6392b`) VALUES
(1, 0, 'Abkhaz', 'аҧсуа', 'ab', 'abk', 'abk'),
(2, 0, 'Afar', 'Afaraf', 'aa', 'aar', 'aar'),
(3, 0, 'Afrikaans', 'Afrikaans', 'af', 'afr', 'afr'),
(4, 0, 'Akan', 'Akan', 'ak', 'aka', 'aka'),
(5, 0, 'Albanian', 'Shqip', 'sq', 'sqi', 'alb'),
(6, 0, 'Amharic', 'አማርኛ', 'am', 'amh', 'amh'),
(7, 0, 'Arabic', 'العربية', 'ar', 'ara', 'ara'),
(8, 0, 'Aragonese', 'Aragonés', 'an', 'arg', 'arg'),
(9, 0, 'Armenian', 'Հայերեն', 'hy', 'hye', 'arm'),
(10, 0, 'Assamese', 'অসমীয়া', 'as', 'asm', 'asm'),
(11, 0, 'Avaric', 'авар мацӀ, магӀарул мацӀ', 'av', 'ava', 'ava'),
(12, 0, 'Avestan', 'avesta', 'ae', 'ave', 'ave'),
(13, 0, 'Aymara', 'aymar aru', 'ay', 'aym', 'aym'),
(14, 0, 'Azerbaijani', 'azərbaycan dili', 'az', 'aze', 'aze'),
(15, 0, 'Bambara', 'bamanankan', 'bm', 'bam', 'bam'),
(16, 0, 'Bashkir', 'башҡорт теле', 'ba', 'bak', 'bak'),
(17, 0, 'Basque', 'euskara, euskera', 'eu', 'eus', 'baq'),
(18, 0, 'Belarusian', 'Беларуская', 'be', 'bel', 'bel'),
(19, 0, 'Bengali', 'বাংলা', 'bn', 'ben', 'ben'),
(20, 0, 'Bihari', 'भोजपुरी', 'bh', 'bih', 'bih'),
(21, 0, 'Bislama', 'Bislama', 'bi', 'bis', 'bis'),
(22, 0, 'Bosnian', 'bosanski jezik', 'bs', 'bos', 'bos'),
(23, 0, 'Breton', 'brezhoneg', 'br', 'bre', 'bre'),
(24, 0, 'Bulgarian', 'български език', 'bg', 'bul', 'bul'),
(25, 0, 'Burmese', 'ဗမာစာ', 'my', 'mya', 'bur'),
(26, 0, 'Catalan; Valencian', 'Català', 'ca', 'cat', 'cat'),
(27, 0, 'Chamorro', 'Chamoru', 'ch', 'cha', 'cha'),
(28, 0, 'Chechen', 'нохчийн мотт', 'ce', 'che', 'che'),
(29, 0, 'Chichewa; Chewa; Nyanja', 'chiCheŵa, chinyanja', 'ny', 'nya', 'nya'),
(30, 0, 'Chinese', '中文 (Zhōngwén), 汉语, 漢語', 'zh', 'zho', 'chi'),
(31, 0, 'Chuvash', 'чӑваш чӗлхи', 'cv', 'chv', 'chv'),
(32, 0, 'Cornish', 'Kernewek', 'kw', 'cor', 'cor'),
(33, 0, 'Corsican', 'corsu, lingua corsa', 'co', 'cos', 'cos'),
(34, 0, 'Cree', 'ᓀᐦᐃᔭᐍᐏᐣ', 'cr', 'cre', 'cre'),
(35, 0, 'Croatian', 'hrvatski', 'hr', 'hrv', 'hrv'),
(36, 1, 'Czech', 'česky, čeština', 'cs', 'ces', 'cze'),
(37, 0, 'Danish', 'dansk', 'da', 'dan', 'dan'),
(38, 0, 'Divehi; Dhivehi; Maldivian;', 'ދިވެހި', 'dv', 'div', 'div'),
(39, 1, 'Dutch', 'Nederlands, Vlaams', 'nl', 'nld', 'dut'),
(40, 0, 'Dzongkha', 'རྫོང་ཁ', 'dz', 'dzo', 'dzo'),
(41, 2, 'English', 'English', 'en', 'eng', 'eng'),
(42, 0, 'Esperanto', 'Esperanto', 'eo', 'epo', 'epo'),
(43, 0, 'Estonian', 'eesti, eesti keel', 'et', 'est', 'est'),
(44, 0, 'Ewe', 'Eʋegbe', 'ee', 'ewe', 'ewe'),
(45, 0, 'Faroese', 'føroyskt', 'fo', 'fao', 'fao'),
(46, 0, 'Fijian', 'vosa Vakaviti', 'fj', 'fij', 'fij'),
(47, 0, 'Finnish', 'suomi, suomen kieli', 'fi', 'fin', 'fin'),
(48, 2, 'French', 'français, langue française', 'fr', 'fra', 'fre'),
(49, 0, 'Fula; Fulah; Pulaar; Pular', 'Fulfulde, Pulaar, Pular', 'ff', 'ful', 'ful'),
(50, 0, 'Galician', 'Galego', 'gl', 'glg', 'glg'),
(51, 0, 'Georgian', 'ქართული', 'ka', 'kat', 'geo'),
(52, 2, 'German', 'Deutsch', 'de', 'deu', 'ger'),
(53, 0, 'Greek, Modern', 'Ελληνικά', 'el', 'ell', 'gre'),
(54, 0, 'Guaraní', 'Avañe\'ẽ', 'gn', 'grn', 'grn'),
(55, 0, 'Gujarati', 'ગુજરાતી', 'gu', 'guj', 'guj'),
(56, 0, 'Haitian; Haitian Creole', 'Kreyòl ayisyen', 'ht', 'hat', 'hat'),
(57, 0, 'Hausa', 'Hausa, هَوُسَ', 'ha', 'hau', 'hau'),
(58, 0, 'Hebrew (modern)', 'עברית', 'he', 'heb', 'heb'),
(59, 0, 'Herero', 'Otjiherero', 'hz', 'her', 'her'),
(60, 0, 'Hindi', 'हिन्दी, हिंदी', 'hi', 'hin', 'hin'),
(61, 0, 'Hiri Motu', 'Hiri Motu', 'ho', 'hmo', 'hmo'),
(62, 1, 'Hungarian', 'Magyar', 'hu', 'hun', 'hun'),
(63, 0, 'Interlingua', 'Interlingua', 'ia', 'ina', 'ina'),
(64, 0, 'Indonesian', 'Bahasa Indonesia', 'id', 'ind', 'ind'),
(65, 0, 'Interlingue', 'Originally called Occidental; then Interlingue after WWII', 'ie', 'ile', 'ile'),
(66, 0, 'Irish', 'Gaeilge', 'ga', 'gle', 'gle'),
(67, 0, 'Igbo', 'Asụsụ Igbo', 'ig', 'ibo', 'ibo'),
(68, 0, 'Inupiaq', 'Iñupiaq, Iñupiatun', 'ik', 'ipk', 'ipk'),
(69, 0, 'Ido', 'Ido', 'io', 'ido', 'ido'),
(70, 0, 'Icelandic', 'Íslenska', 'is', 'isl', 'ice'),
(71, 1, 'Italian', 'Italiano', 'it', 'ita', 'ita'),
(72, 0, 'Inuktitut', 'ᐃᓄᒃᑎᑐᑦ', 'iu', 'iku', 'iku'),
(73, 0, 'Japanese', '日本語 (にほんご)', 'ja', 'jpn', 'jpn'),
(74, 0, 'Javanese', 'basa Jawa', 'jv', 'jav', 'jav'),
(75, 0, 'Kalaallisut, Greenlandic', 'kalaallisut, kalaallit oqaasii', 'kl', 'kal', 'kal'),
(76, 0, 'Kannada', 'ಕನ್ನಡ', 'kn', 'kan', 'kan'),
(77, 0, 'Kanuri', 'Kanuri', 'kr', 'kau', 'kau'),
(78, 0, 'Kashmiri', 'कश्मीरी, كشميري‎', 'ks', 'kas', 'kas'),
(79, 0, 'Kazakh', 'Қазақ тілі', 'kk', 'kaz', 'kaz'),
(80, 0, 'Khmer', 'ភាសាខ្មែរ', 'km', 'khm', 'khm'),
(81, 0, 'Kikuyu, Gikuyu', 'Gĩkũyũ', 'ki', 'kik', 'kik'),
(82, 0, 'Rwanda', 'Ikinyarwanda', 'rw', 'kin', 'kin'),
(83, 0, 'Kirghiz, Kyrgyz', 'кыргыз тили', 'ky', 'kir', 'kir'),
(84, 0, 'Komi', 'коми кыв', 'kv', 'kom', 'kom'),
(85, 0, 'Kongo', 'KiKongo', 'kg', 'kon', 'kon'),
(86, 0, 'Korean', '한국어 (韓國語), 조선어 (朝鮮語)', 'ko', 'kor', 'kor'),
(87, 0, 'Kurdish', 'Kurdî, كوردی‎', 'ku', 'kur', 'kur'),
(88, 0, 'Kwanyama, Kuanyama', 'Kuanyama', 'kj', 'kua', 'kua'),
(89, 0, 'Latin', 'latine, lingua latina', 'la', 'lat', 'lat'),
(90, 0, 'Luxembourgish, Letzeburgesch', 'Lëtzebuergesch', 'lb', 'ltz', 'ltz'),
(91, 0, 'Ganda', 'Ganda', 'lg', 'lug', 'lug'),
(92, 0, 'Limburgish, Limburgan, Limburger', 'Limburgs', 'li', 'lim', 'lim'),
(93, 0, 'Lingala', 'Lingála', 'ln', 'lin', 'lin'),
(94, 0, 'Lao', 'ພາສາລາວ', 'lo', 'lao', 'lao'),
(95, 0, 'Lithuanian', 'lietuvių kalba', 'lt', 'lit', 'lit'),
(96, 0, 'Luba-Katanga', '', 'lu', 'lub', 'lub'),
(97, 0, 'Latvian', 'latviešu valoda', 'lv', 'lav', 'lav'),
(98, 0, 'Manx', 'Gaelg, Gailck', 'gv', 'glv', 'glv'),
(99, 0, 'Macedonian', 'македонски јазик', 'mk', 'mkd', 'mac'),
(100, 0, 'Malagasy', 'Malagasy fiteny', 'mg', 'mlg', 'mlg'),
(101, 0, 'Malay', 'bahasa Melayu, بهاس ملايو‎', 'ms', 'msa', 'may'),
(102, 0, 'Malayalam', 'മലയാളം', 'ml', 'mal', 'mal'),
(103, 0, 'Maltese', 'Malti', 'mt', 'mlt', 'mlt'),
(104, 0, 'Māori', 'te reo Māori', 'mi', 'mri', 'mao'),
(105, 0, 'Marathi (Marāṭhī)', 'मराठी', 'mr', 'mar', 'mar'),
(106, 0, 'Marshallese', 'Kajin M̧ajeļ', 'mh', 'mah', 'mah'),
(107, 0, 'Mongolian', 'монгол', 'mn', 'mon', 'mon'),
(108, 0, 'Nauru', 'Ekakairũ Naoero', 'na', 'nau', 'nau'),
(109, 0, 'Navajo, Navaho', 'Diné bizaad, Dinékʼehǰí', 'nv', 'nav', 'nav'),
(110, 0, 'Norwegian Bokmål', 'Norsk bokmål', 'nb', 'nob', 'nob'),
(111, 0, 'North Ndebele', 'isiNdebele', 'nd', 'nde', 'nde'),
(112, 0, 'Nepali', 'नेपाली', 'ne', 'nep', 'nep'),
(113, 0, 'Ndonga', 'Owambo', 'ng', 'ndo', 'ndo'),
(114, 0, 'Norwegian Nynorsk', 'Norsk nynorsk', 'nn', 'nno', 'nno'),
(115, 0, 'Norwegian', 'Norsk', 'no', 'nor', 'nor'),
(116, 0, 'Nuosu', 'ꆈꌠ꒿ Nuosuhxop', 'ii', 'iii', 'iii'),
(117, 0, 'South Ndebele', 'isiNdebele', 'nr', 'nbl', 'nbl'),
(118, 0, 'Occitan', 'Occitan', 'oc', 'oci', 'oci'),
(119, 0, 'Ojibwe, Ojibwa', 'ᐊᓂᔑᓈᐯᒧᐎᓐ', 'oj', 'oji', 'oji'),
(120, 0, 'Old Church Slavonic, Church Slavic, Church Slavonic, Old Bulgarian, Old Slavonic', 'ѩзыкъ словѣньскъ', 'cu', 'chu', 'chu'),
(121, 0, 'Oromo', 'Afaan Oromoo', 'om', 'orm', 'orm'),
(122, 0, 'Oriya', 'ଓଡ଼ିଆ', 'or', 'ori', 'ori'),
(123, 0, 'Ossetian, Ossetic', 'ирон æвзаг', 'os', 'oss', 'oss'),
(124, 0, 'Panjabi, Punjabi', 'ਪੰਜਾਬੀ, پنجابی‎', 'pa', 'pan', 'pan'),
(125, 0, 'Pāli', 'पाऴि', 'pi', 'pli', 'pli'),
(126, 0, 'Persian', 'فارسی', 'fa', 'fas', 'per'),
(127, 2, 'Polish', 'polski', 'pl', 'pol', 'pol'),
(128, 0, 'Pashto, Pushto', 'پښتو', 'ps', 'pus', 'pus'),
(129, 1, 'Portuguese', 'Português', 'pt', 'por', 'por'),
(130, 0, 'Quechua', 'Runa Simi, Kichwa', 'qu', 'que', 'que'),
(131, 0, 'Romansh', 'rumantsch grischun', 'rm', 'roh', 'roh'),
(132, 0, 'Rundi', 'Ikirundi', 'rn', 'run', 'run'),
(133, 0, 'Romanian, Moldavian, Moldovan', 'română', 'ro', 'ron', 'rum'),
(134, 2, 'Russian', 'русский язык', 'ru', 'rus', 'rus'),
(135, 0, 'Sanskrit (Saṁskṛta)', 'संस्कृतम्', 'sa', 'san', 'san'),
(136, 0, 'Sardinian', 'sardu', 'sc', 'srd', 'srd'),
(137, 0, 'Sindhi', 'सिन्धी, سنڌي، سندھی‎', 'sd', 'snd', 'snd'),
(138, 0, 'Northern Sami', 'Davvisámegiella', 'se', 'sme', 'sme'),
(139, 0, 'Samoan', 'gagana fa\'a Samoa', 'sm', 'smo', 'smo'),
(140, 0, 'Sango', 'yângâ tî sängö', 'sg', 'sag', 'sag'),
(141, 0, 'Serbian', 'српски језик', 'sr', 'srp', 'srp'),
(142, 0, 'Scottish Gaelic; Gaelic', 'Gàidhlig', 'gd', 'gla', 'gla'),
(143, 0, 'Shona', 'chiShona', 'sn', 'sna', 'sna'),
(144, 0, 'Sinhala, Sinhalese', 'සිංහල', 'si', 'sin', 'sin'),
(145, 1, 'Slovak', 'slovenčina', 'sk', 'slk', 'slo'),
(146, 0, 'Slovene', 'slovenščina', 'sl', 'slv', 'slv'),
(147, 0, 'Somali', 'Soomaaliga, af Soomaali', 'so', 'som', 'som'),
(148, 0, 'Southern Sotho', 'Sesotho', 'st', 'sot', 'sot'),
(149, 1, 'Spanish; Castilian', 'español, castellano', 'es', 'spa', 'spa'),
(150, 0, 'Sundanese', 'Basa Sunda', 'su', 'sun', 'sun'),
(151, 0, 'Swahili', 'Kiswahili', 'sw', 'swa', 'swa'),
(152, 0, 'Swati', 'SiSwati', 'ss', 'ssw', 'ssw'),
(153, 1, 'Swedish', 'svenska', 'sv', 'swe', 'swe'),
(154, 0, 'Tamil', 'தமிழ்', 'ta', 'tam', 'tam'),
(155, 0, 'Telugu', 'తెలుగు', 'te', 'tel', 'tel'),
(156, 0, 'Tajik', 'тоҷикӣ, toğikī, تاجیکی‎', 'tg', 'tgk', 'tgk'),
(157, 0, 'Thai', 'ไทย', 'th', 'tha', 'tha'),
(158, 0, 'Tigrinya', 'ትግርኛ', 'ti', 'tir', 'tir'),
(159, 0, 'Tibetan Standard, Tibetan, Central', 'བོད་ཡིག', 'bo', 'bod', 'tib'),
(160, 0, 'Turkmen', 'Türkmen, Түркмен', 'tk', 'tuk', 'tuk'),
(161, 0, 'Tagalog', 'Wikang Tagalog, ᜏᜒᜃᜅ᜔ ᜆᜄᜎᜓᜄ᜔', 'tl', 'tgl', 'tgl'),
(162, 0, 'Tswana', 'Setswana', 'tn', 'tsn', 'tsn'),
(163, 0, 'Tonga (Tonga Islands)', 'faka Tonga', 'to', 'ton', 'ton'),
(164, 0, 'Turkish', 'Türkçe', 'tr', 'tur', 'tur'),
(165, 0, 'Tsonga', 'Xitsonga', 'ts', 'tso', 'tso'),
(166, 0, 'Tatar', 'татарча, tatarça, تاتارچا‎', 'tt', 'tat', 'tat'),
(167, 0, 'Twi', 'Twi', 'tw', 'twi', 'twi'),
(168, 0, 'Tahitian', 'Reo Tahiti', 'ty', 'tah', 'tah'),
(169, 0, 'Uighur, Uyghur', 'Uyƣurqə, ئۇيغۇرچە‎', 'ug', 'uig', 'uig'),
(170, 1, 'Ukrainian', 'українська', 'uk', 'ukr', 'ukr'),
(171, 0, 'Urdu', 'اردو', 'ur', 'urd', 'urd'),
(172, 0, 'Uzbek', 'O\'zbek, Ўзбек, أۇزبېك‎', 'uz', 'uzb', 'uzb'),
(173, 0, 'Venda', 'Tshivenḓa', 've', 'ven', 'ven'),
(174, 0, 'Vietnamese', 'Tiếng Việt', 'vi', 'vie', 'vie'),
(175, 0, 'Volapük', 'Volapük', 'vo', 'vol', 'vol'),
(176, 0, 'Walloon', 'Walon', 'wa', 'wln', 'wln'),
(177, 0, 'Welsh', 'Cymraeg', 'cy', 'cym', 'wel'),
(178, 0, 'Wolof', 'Wollof', 'wo', 'wol', 'wol'),
(179, 0, 'Western Frisian', 'Frysk', 'fy', 'fry', 'fry'),
(180, 0, 'Xhosa', 'isiXhosa', 'xh', 'xho', 'xho'),
(181, 0, 'Yiddish', 'ייִדיש', 'yi', 'yid', 'yid'),
(182, 0, 'Yoruba', 'Yorùbá', 'yo', 'yor', 'yor'),
(183, 0, 'Zhuang, Chuang', 'Saɯ cueŋƅ, Saw cuengh', 'za', 'zha', 'zha'),
(184, 0, 'Zulu', 'isiZulu', 'zu', 'zul', 'zul');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cms_last_login`
--

CREATE TABLE `cms_last_login` (
  `user` varchar(100) COLLATE utf8_bin NOT NULL,
  `lsl` datetime NOT NULL,
  `lul` datetime NOT NULL,
  `lcl` datetime NOT NULL,
  `llo` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cms_pathlt`
--

CREATE TABLE `cms_pathlt` (
  `cmsid` bigint(20) NOT NULL,
  `siteid` smallint(11) NOT NULL,
  `pathl` char(60) COLLATE utf8_bin NOT NULL,
  `lang` smallint(6) NOT NULL,
  `pathltc` varchar(1024) COLLATE utf8_bin NOT NULL,
  `pathlt` varchar(1024) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin ROW_FORMAT=REDUNDANT;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cms_pictures`
--

CREATE TABLE `cms_pictures` (
  `cmsid` bigint(20) NOT NULL,
  `key` char(4) COLLATE utf8_unicode_ci NOT NULL,
  `id` int(11) NOT NULL,
  `file` char(250) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `desc` varchar(2048) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=REDUNDANT;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cms_relation`
--

CREATE TABLE `cms_relation` (
  `code` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `sort` int(11) NOT NULL,
  `lsite` smallint(6) NOT NULL,
  `rsite` smallint(6) NOT NULL,
  `left` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `right` char(60) COLLATE utf8_unicode_ci NOT NULL,
  `llang` tinyint(4) NOT NULL DEFAULT '0',
  `rlang` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=REDUNDANT;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cms_site`
--

CREATE TABLE `cms_site` (
  `site` smallint(6) NOT NULL,
  `name` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `default` tinyint(4) NOT NULL,
  `defaultlang` tinyint(4) NOT NULL,
  `route` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `dbe` tinyint(4) NOT NULL,
  `resourcealtersrc` text CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `fulldbe` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Zrzut danych tabeli `cms_site`
--

INSERT INTO `cms_site` (`site`, `name`, `description`, `default`, `defaultlang`, `route`, `dbe`, `resourcealtersrc`, `fulldbe`) VALUES
(1, 'Site', 'Site CRM', 1, 127, '10', 1, '', 1),
(9999, 'Admin', 'CMS Administrator', 0, 0, '40', 1, '', 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `cms_sitelang`
--

CREATE TABLE `cms_sitelang` (
  `site` smallint(6) NOT NULL,
  `lang` smallint(6) NOT NULL,
  `callkey` varchar(250) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `sitebase` varchar(250) COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Zrzut danych tabeli `cms_sitelang`
--

INSERT INTO `cms_sitelang` (`site`, `lang`, `callkey`, `sitebase`) VALUES
(1, 127, 'site\\.example\\.com\\/', 'site.example.com/'),
(9999, 127, '', '');

--
-- Indeksy dla zrzutów tabel
--

--
-- Indexes for table `cms_fields`
--
ALTER TABLE `cms_fields`
  ADD KEY `cmsid` (`cmsid`),
  ADD KEY `fname0` (`fname`(10)),
  ADD KEY `value0` (`value`(20)),
  ADD KEY `namevalue` (`fname`(10),`value`(20));

--
-- Indexes for table `cms_history_changes`
--
ALTER TABLE `cms_history_changes`
  ADD KEY `objectKey1` (`site`,`lang`,`pathl`),
  ADD KEY `objectKey2` (`cmsid`);

--
-- Indexes for table `cms_history_login`
--
ALTER TABLE `cms_history_login`
  ADD KEY `user` (`user`);

--
-- Indexes for table `cms_lang`
--
ALTER TABLE `cms_lang`
  ADD PRIMARY KEY (`lang`);

--
-- Indexes for table `cms_last_login`
--
ALTER TABLE `cms_last_login`
  ADD UNIQUE KEY `user` (`user`);

--
-- Indexes for table `cms_pathlt`
--
ALTER TABLE `cms_pathlt`
  ADD UNIQUE KEY `siteid` (`siteid`,`pathl`,`lang`),
  ADD KEY `cmsid` (`cmsid`),
  ADD KEY `pathltc` (`siteid`,`pathltc`(160)),
  ADD KEY `pathlt` (`siteid`,`pathlt`(160));

--
-- Indexes for table `cms_pictures`
--
ALTER TABLE `cms_pictures`
  ADD KEY `cmsid` (`cmsid`);

--
-- Indexes for table `cms_relation`
--
ALTER TABLE `cms_relation`
  ADD KEY `left` (`code`,`lsite`,`left`),
  ADD KEY `right` (`code`,`rsite`,`right`),
  ADD KEY `code` (`code`);

--
-- Indexes for table `cms_site`
--
ALTER TABLE `cms_site`
  ADD PRIMARY KEY (`site`);

--
-- Indexes for table `cms_sitelang`
--
ALTER TABLE `cms_sitelang`
  ADD UNIQUE KEY `site_2` (`site`,`lang`),
  ADD KEY `site` (`site`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
