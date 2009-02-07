#!/usr/bin/php -q
<?php
/**
 * $Horde: jonah/scripts/create_initial_feeds.php,v 1.3 2006/09/10 17:50:40 chuck Exp $
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

// No user auth.
@define('AUTH_HANDLER', true);

// Find the base file path of Horde.
@define('HORDE_BASE', dirname(__FILE__) . '/../..');

// Find the base file path of Jonah.
@define('JONAH_BASE', dirname(__FILE__) . '/..');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/CLI.php';

// Make sure no one runs this from the web.
if (!Horde_CLI::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
Horde_CLI::init();

// Now load the Registry and setup conf, etc.
$registry = &Registry::singleton();
$registry->pushApp('jonah', false);

// Include needed libraries.
require_once JONAH_BASE . '/lib/base.php';
require_once JONAH_BASE . '/lib/News.php';

/* Make sure there's no compression. */
@ob_end_clean();

$news = Jonah_News::factory();

$channels = <<<EOC
INSERT INTO jonah_channels VALUES (1,'Salon.com',1,NULL,86400,'http://www.salon.com/feed/RDF/salon_use.rdf','Salon.com','','salon.gif',0);
INSERT INTO jonah_channels VALUES (2,'Top Stories',1,NULL,86400,'http://p.moreover.com/cgi-local/page?index_topstories+rss',NULL,'','moreover.gif',0);
INSERT INTO jonah_channels VALUES (3,'Slashdot',1,NULL,86400,'http://slashdot.org/slashdot.rdf',NULL,'','slashdotlg.gif',0);
INSERT INTO jonah_channels VALUES (4,'Motley Fool',1,NULL,86400,'http://www.fool.com/xml/foolnews_rss091.xml',NULL,'','motleyfool.gif',0);
INSERT INTO jonah_channels VALUES (5,'RootPrompt',1,NULL,86400,'http://www.rootprompt.org/rss/',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (6,'Wired News',1,NULL,86400,'http://www.wired.com/news_drop/netcenter/netcenter.rdf',NULL,'','netcenterb.gif',0);
INSERT INTO jonah_channels VALUES (7,'Linux Center News',1,NULL,86400,'http://www.linux-center.org/news/lc-en.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (8,'Advogato',1,NULL,86400,'http://www.advogato.org/rss/articles.xml',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (9,'Wide Open News',1,NULL,86400,'http://www.wideopen.com/wideopen.rdf',NULL,'','wide_open_logo_sm.gif',0);
INSERT INTO jonah_channels VALUES (10,'freshmeat.net',1,NULL,86400,'http://freshmeat.net/backend/fm.rdf',NULL,'','fm.mini.jpg',0);
INSERT INTO jonah_channels VALUES (11,'LinuxToday',1,NULL,86400,'http://linuxtoday.com/backend/linuxtoday.xml',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (12,'The Linux Game Tome',1,NULL,86400,'http://happypenguin.org/html/news.rdf',NULL,'','button.jpg',0);
INSERT INTO jonah_channels VALUES (13,'Segfault.org',1,NULL,86400,'http://segfault.org/stories.xml',NULL,'','segvnowsjr.gif',0);
INSERT INTO jonah_channels VALUES (14,'KDE Dot News',1,NULL,86400,'http://dot.kde.org/rdf',NULL,'','kdedotnews_88x31.gif',0);
INSERT INTO jonah_channels VALUES (15,'GNOME News',1,NULL,86400,'http://news.gnome.org/gnome-news/rdf',NULL,'','gnome-logo-icon.gif',0);
INSERT INTO jonah_channels VALUES (16,'Mozilla Dot Org',1,NULL,86400,'http://www.mozilla.org/news.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (17,'Gildot',1,NULL,86400,'http://www.gildot.org/gildot.rdf',NULL,'','gildot.gif',0);
INSERT INTO jonah_channels VALUES (18,'MozillaZine',1,NULL,86400,'http://www.mozillazine.org/contents.rdf',NULL,'','mynetscape88.gif',0);
INSERT INTO jonah_channels VALUES (19,'DominoPower Magazine',1,NULL,86400,'http://www.dominopower.com/shares/userland-rss/channeldata.xml',NULL,'','DominoPowerRSSLogo.gif',0);
INSERT INTO jonah_channels VALUES (20,'Multiagent Systems',1,NULL,86400,'http://www.multiagent.com/mynetscape.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (21,'Python Dot Org',1,NULL,86400,'http://www.python.org/channews.rdf',NULL,'','PythonPowered.gif',0);
INSERT INTO jonah_channels VALUES (22,'Linux Weekly News',1,NULL,86400,'http://lwn.net/headlines/rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (23,'Perl News',1,NULL,86400,'http://www.news.perl.org/perl-news-short.rdf',NULL,'','perl-news-small.gif',0);
INSERT INTO jonah_channels VALUES (24,'linux-kernel mailing list',1,NULL,86400,'http://www.mail-archive.com/linux-kernel@vger.kernel.org/maillist.rdf',NULL,'','mail-archive.gif',0);
INSERT INTO jonah_channels VALUES (25,'osOpinion',1,NULL,86400,'http://www.osopinion.com/OSOlinks2.xml',NULL,'','OSOlogotext.gif',0);
INSERT INTO jonah_channels VALUES (26,'Security Focus',1,NULL,86400,'http://www.securityfocus.com/topnews?type=rss',NULL,'','securityfocus.gif',0);
INSERT INTO jonah_channels VALUES (27,'linuxplanet',1,NULL,86400,'http://www.linuxplanet.com/rss/',NULL,'','linuxplanet.gif',0);
INSERT INTO jonah_channels VALUES (28,'Heise Newsticker',1,NULL,86400,'http://www.heise.de/newsticker/heise.rdf',NULL,'','heise.gif',0);
INSERT INTO jonah_channels VALUES (29,'TELEPOLIS',1,NULL,86400,'http://www.telepolis.de/news.rdf',NULL,'','heise.gif',0);
INSERT INTO jonah_channels VALUES (30,'FreeBSD diary',1,NULL,86400,'http://www.freebsddiary.org/news.php',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (31,'BSD today',1,NULL,86400,'http://www.bsdtoday.com/backend/bt.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (32,'Zend',1,NULL,86400,'http://www.zend.com/rss.php',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (33,'The Register',1,NULL,86400,'http://www.theregister.co.uk/tonys/slashdot.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (34,'La passerelle française des technologies PHP',1,NULL,86400,'http://www.phpindex.com/rss/phpindex_news.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (35,'Free XML Tools',1,NULL,86400,'http://www.garshol.priv.no/download/xmltools/tools-rss.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (36,'Helsingin Sanomat',1,NULL,86400,'http://siirto.helsinginsanomat.fi/aukio/HS-Tuoreet-Top5.xml',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (37,'PEAR',1,NULL,86400,'http://pear.php.net/rss.php',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (38,'NPR',1,NULL,86400,'http://xml.newsisfree.com/feeds/49/2449.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (39,'InternetNews.com',1,NULL,86400,'http://headlines.internet.com/internetnews/top-news/news.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (40,'CNET Tech News',1,NULL,86400,'http://trainedmonkey.com/news/rss.php?s=31',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (41,'NewsFactor Network',1,NULL,86400,'http://www.newsfactor.com/perl/syndication/rssfull.pl',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (42,'BBC Business',1,NULL,86400,'http://xml.newsisfree.com/feeds/79/79.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (43,'Newsweek',1,NULL,86400,'http://p.moreover.com/cgi-local/page?o=rss&s=Newsweek',NULL,'','moreover.gif',0);
INSERT INTO jonah_channels VALUES (44,'USA Today Front Page',1,NULL,86400,'http://xml.newsisfree.com/feeds/43/1843.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (45,'USA Today Newswire',1,NULL,86400,'http://xml.newsisfree.com/feeds/42/1842.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (46,'USA Today Entertainment',1,NULL,86400,'http://xml.newsisfree.com/feeds/47/1847.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (47,'USA Today Sports',1,NULL,86400,'http://xml.newsisfree.com/feeds/45/1845.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (48,'USA Today Money',1,NULL,86400,'http://xml.newsisfree.com/feeds/46/1846.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (49,'Political Wire',1,NULL,86400,'http://politicalwire.com/headlines.xml',NULL,'','politicalwire.gif',0);
INSERT INTO jonah_channels VALUES (50,'The Onion',1,NULL,86400,'http://xml.newsisfree.com/feeds/47/2447.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (51,'Politech',1,NULL,86400,'http://xml.newsisfree.com/feeds/91/591.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (52,'ZDNet Tech News',1,NULL,86400,'http://xml.newsisfree.com/feeds/55/55.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (53,'IDG InfoWorld',1,NULL,86400,'http://xml.newsisfree.com/feeds/06/1806.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (54,'E-Commerce Times',1,NULL,86400,'http://www.ecommercetimes.com/perl/syndication/rssfull.pl',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (55,'Geeknews',1,NULL,86400,'http://www.teoti.net/geeknews/backend.php',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (56,'Ananova News',1,NULL,86400,'http://xml.newsisfree.com/feeds/35/1635.xml',NULL,'','newsisfree.gif',1053444057);
INSERT INTO jonah_channels VALUES (57,'Ananova Sport',1,NULL,86400,'http://xml.newsisfree.com/feeds/38/1638.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (58,'Ananova Business',1,NULL,86400,'http://xml.newsisfree.com/feeds/39/1639.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (59,'Disinformation',1,NULL,86400,'http://xml.newsisfree.com/feeds/19/1719.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (60,'Moreover Business',1,NULL,86400,'http://p.moreover.com/cgi-local/page?index_topbusiness+rss',NULL,'','moreover.gif',0);
INSERT INTO jonah_channels VALUES (61,'Beyond 2000 Daily Science News',1,NULL,86400,'http://www.beyond2000.com/b2k.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (62,'BBC SciTech',1,NULL,86400,'http://xml.newsisfree.com/feeds/63/63.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (63,'Nature Science Update',1,NULL,86400,'http://xml.newsisfree.com/feeds/19/2319.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (64,'NASA Earth Observatory',1,NULL,86400,'http://earthobservatory.nasa.gov/eo.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (65,'Baseball Primer',1,NULL,86400,'http://www.baseballprimer.com/primer_daily.xml',NULL,'',NULL,1054479805);
INSERT INTO jonah_channels VALUES (66,'Human Resources News',1,NULL,86400,'http://p.moreover.com/cgi-local/page?index_humanresources+rss',NULL,'','moreover.gif',0);
INSERT INTO jonah_channels VALUES (67,'Kuro5hin',1,NULL,86400,'http://www.kuro5hin.org/backend.rdf',NULL,'','kuro5hin.png',0);
INSERT INTO jonah_channels VALUES (68,'Digital Photography Review',1,NULL,86400,'http://www.dpreview.com/news/dpr.rdf',NULL,'','dpreview.gif',0);
INSERT INTO jonah_channels VALUES (69,'PDABuzz.com',1,NULL,86400,'http://www.pdabuzz.com/netscape.txt',NULL,'','pdabuzz.gif',0);
INSERT INTO jonah_channels VALUES (70,'PalmStation.com',1,NULL,86400,'http://www.palmstation.com/palmstation.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (71,'Handheldnews.com',1,NULL,86400,'http://xml.newsisfree.com/feeds/11/2011.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (72,'Wall Street Transcript',1,NULL,86400,'http://www.twst.com/mynetscape/main.rdf',NULL,'','twst.gif',0);
INSERT INTO jonah_channels VALUES (73,'ChannelSeven.com Marketing',1,NULL,86400,'http://www.channelseven.com/channelseven.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (74,'MovieComments.com',1,NULL,86400,'http://www.moviecomments.com/recentrev_rss.php',NULL,'','moviecomments.gif',0);
INSERT INTO jonah_channels VALUES (75,'AP Tech News',1,NULL,86400,'http://xml.newsisfree.com/feeds/66/1466.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (76,'AP Politics',1,NULL,86400,'http://xml.newsisfree.com/feeds/67/1467.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (77,'AP World News',1,NULL,86400,'http://xml.newsisfree.com/feeds/69/1469.xml',NULL,'','newsisfree.gif',1054482608);
INSERT INTO jonah_channels VALUES (78,'AP Business',1,NULL,86400,'http://xml.newsisfree.com/feeds/68/1468.xml',NULL,'','newsisfree.gif',1054482603);
INSERT INTO jonah_channels VALUES (79,'AP Entertainment',1,NULL,86400,'http://xml.newsisfree.com/feeds/70/1470.xml','','','newsisfree.gif',1054482215);
INSERT INTO jonah_channels VALUES (80,'AP Sports',1,NULL,86400,'http://xml.newsisfree.com/feeds/71/1471.xml',NULL,'','newsisfree.gif',1054482619);
INSERT INTO jonah_channels VALUES (81,'AP Science',1,NULL,86400,'http://xml.newsisfree.com/feeds/72/1472.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (82,'AP Health',1,NULL,86400,'http://xml.newsisfree.com/feeds/73/1473.xml',NULL,'','newsisfree.gif',0);
INSERT INTO jonah_channels VALUES (83,'OpenBSD Journal',1,NULL,86400,'http://www.undeadly.org/cgi?action=rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (84,'APPS.KDE.com',1,NULL,86400,'http://apps.kde.com/news/apps.kde.com.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (85,'Arstechnica',1,NULL,86400,'http://arstechnica.com/etc/rdf/ars.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (86,'eFilmCritic',1,NULL,86400,'http://efilmcritic.com/fo.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (87,'English Amiga News',1,NULL,86400,'http://www.amiga-news.de/en/backends/news/index.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (88,'FreshPorts - The place for bsd ports',1,NULL,86400,'http://www.freshports.org/news.php3',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (89,'PcLinuxOnline',1,NULL,86400,'http://www.pclinuxonline.com/backend.php',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (90,'Php Classes',1,NULL,86400,'http://www.phpclasses.org/channels.xml',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (91,'Prolinux',1,NULL,86400,'http://www.pl-forum.de/backend/pro-linux.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (92,'Slashdot Apache',1,NULL,86400,'http://slashdot.org/apache.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (93,'Slashdot Books',1,NULL,86400,'http://slashdot.org/books.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (94,'Slashdot Features',1,NULL,86400,'http://slashdot.org/features.rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (95,'Christian Science Monitor - Top Stories',1,NULL,86400,'http://www.csmonitor.com/rss/top.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (96,'Christian Science Monitor - World',1,NULL,86400,'http://www.csmonitor.com/rss/world.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (97,'Christian Science Monitor -  USA',1,NULL,86400,'http://www.csmonitor.com/rss/usa.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (98,'Christian Science Monitor -  Commentary',1,NULL,86400,'http://www.csmonitor.com/rss/commentary.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (99,'Christian Science Monitor -  Work/Money',1,NULL,86400,'http://www.csmonitor.com/rss/wam.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (100,'Christian Science Monitor -  Learning',1,NULL,86400,'http://www.csmonitor.com/rss/learning.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (101,'Christian Science Monitor -  Living',1,NULL,86400,'http://www.csmonitor.com/rss/living.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (102,'Christian Science Monitor -  Sci/Tech',1,NULL,86400,'http://www.csmonitor.com/rss/scitech.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (103,'Christian Science Monitor -  Arts/Leisure',1,NULL,86400,'http://www.csmonitor.com/rss/arts.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (104,'Christian Science Monitor -  Books',1,NULL,86400,'http://www.csmonitor.com/rss/books.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (105,'Christian Science Monitor -  The Home Forum',1,NULL,86400,'http://www.csmonitor.com/rss/homeforum.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (106,'Christian Science Monitor -  All Stories',1,NULL,86400,'http://www.csmonitor.com/rss/csm.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (107,'Christian Writers -  All Stories',1,NULL,86400,'http://ChristianWriters.com/newsfeeds/main.php',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (108,'Motivational Quote of the Day',1,NULL,86400,'http://www.quotationspage.com/data/mqotd.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (109,'Word of the Day',1,NULL,86400,'http://dictionary.reference.com/wordoftheday/wotd.rss',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (110,'NTFS.org',1,NULL,86400,'http://www.ntfs.org/modules.php?modname=backend&action=rdf',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (111,'SourceForge.net - Front Page News',1,NULL,86400,'http://sourceforge.net/export/rss2_sfnews.php?feed','SourceForge.net - Front Page News','',NULL,0);
INSERT INTO jonah_channels VALUES (112,'SourceForge.net - Site Wide News',1,NULL,86400,'http://sourceforge.net/export/rss2_sfnews.php?rss_allnews=1',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (113,'SourceForge.net - Site Wide File Releases',1,NULL,86400,'http://sourceforge.net/export/rss2_sffiles.php?feed','SourceForge.net - Site Wide File Releases','',NULL,0);
INSERT INTO jonah_channels VALUES (114,'SourceForge.net - Site Status',1,NULL,86400,'http://sourceforge.net/export/rss2_sitestatus.php?feed','SourceForge.net - Site Status','',NULL,0);
INSERT INTO jonah_channels VALUES (115,'SourceForge.net - Compile Farm Status',1,NULL,86400,'http://sourceforge.net/export/rss2_sitestatus.php?cfstatus=1','SourceForge.net - Compile Farm Status','',NULL,0);
INSERT INTO jonah_channels VALUES (116,'SourceForge.net - Newly Registered Projects',1,NULL,86400,'http://sourceforge.net/export/rss2_sfnewprojects.php?feed',NULL,'',NULL,0);
INSERT INTO jonah_channels VALUES (117,'SourceForge.net - Top Projects By Download (This Week)',1,NULL,86400,'http://sourceforge.net/export/rss2_sftopstats.php?feed=downloads_weekly','SourceForge.net - Top Projects By Download (This Week)','',NULL,0);
INSERT INTO jonah_channels VALUES (118,'SourceForge.net - Top Projects By Download (All Time)',1,NULL,86400,'http://sourceforge.net/export/rss2_sftopstats.php?feed=downloads_all','SourceForge.net - Top Projects By Download (All Time)','',NULL,0);
INSERT INTO jonah_channels VALUES (119,'SourceForge.net - Top Projects By Activity (This Week)',1,NULL,86400,'http://sourceforge.net/export/rss2_sftopstats.php?feed=mostactive_weekly','SourceForge.net - Top Projects By Activity (This Week)','',NULL,0);
INSERT INTO jonah_channels VALUES (120,'SourceForge.net - Top Projects By Activity (All Time)',1,NULL,86400,'http://sourceforge.net/export/rss2_sftopstats.php?feed=mostactive_all','SourceForge.net - Top Projects By Activity (All Time)','',NULL,0);
INSERT INTO jonah_channels VALUES (121,'Lessig Blog',1,NULL,86400,'http://cyberlaw.stanford.edu/lessig/blog/index.rdf','Lessig Blog','',NULL,0);
EOC;

$channels = explode("\n", trim($channels));
foreach ($channels as $channel) {
    $channel = trim($channel);
    $channel = str_replace('INSERT INTO jonah_channels VALUES (', '', $channel);
    $channel = preg_replace('/,\d+\);/', '', $channel);
    $channel = explode(',', $channel);
    $info = array(
        'channel_name' => substr($channel[1], 1, -1),
        'channel_type' => JONAH_EXTERNAL_CHANNEL,
        'channel_url' => substr($channel[5], 1, -1),
        'channel_img' => $channel[8] == 'NULL' ? null : substr($channel[8], 1, -1),
    );
    $news->saveChannel($info);
}
