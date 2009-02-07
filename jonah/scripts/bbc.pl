#!/usr/bin/perl
=cut

Copyright (c) 2001 Malte Starostik <malte@kde.org>

Unlimited use, modification and distribution granted as long as the above
copyright statement and this sentence remain intact.

=cut

use strict;
use LWP::Simple;

print <<'EOT';
<?xml version="1.0" encoding="iso-8859-1"?>
<!DOCTYPE rss PUBLIC "-//Netscape Communications//DTD RSS 0.91//EN"
	"http://my.netscape.com/publish/formats/rss-0.91.dtd">
<rss version="0.91">
<channel>
<title>BBC News Headlines</title>
<description>News from the BBC</description>
<language>en-gb</language>
EOT

my ($secnum, $section, $headline, $url);
foreach (split /\r?\n/, get "http://tickers.bbc.co.uk/tickerdata/story2.dat")
{
	$secnum = $1, $section = '' if (/^STORY ([\d+])/ && $1 != $secnum);
	if (/^HEADLINE (.+)/)
	{
		next if $1 =~ /Last update/;
		$headline = $1;
		$headline =~ s/&/&amp;/g;
		$headline =~ s/</&lt;/g;
		$headline =~ s/>/&gt;/g;
		$headline =~ s/"/&quot;/g;
		$section = $headline, $section =~ s/\s*\d+ (Ja|Fe|Ma|Ap|Ju|Au|Se|Oc|No|De)\S+ \d+$// unless $section;
	}
	if (/^URL (.+)/)
	{
		$url = $1, $url =~ s/&/&amp;/g;
		print <<EOT
<item>
<title>$headline ($section)</title>
<link>$url</link>
</item>
EOT
	}
}

print <<'EOT';
</channel>
</rss>
EOT
