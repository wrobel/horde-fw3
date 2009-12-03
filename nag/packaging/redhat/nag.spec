#
# $Horde: nag/packaging/redhat/nag.spec,v 1.4.12.5 2009-01-06 15:25:07 jan Exp $
#
# Copyright 2003-2009 The Horde Project (http://www.horde.org/)
#
# See the enclosed file COPYING for license information (GPL). If you
# did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
#
# This is the SPEC file for the Nag Red Hat 7.x (RPM v4) RPMs/SRPM.
#

%define apachedir /etc/httpd
%define apacheuser apache
%define apachegroup apache
%define contentdir /var/www

Summary: The Horde contact management application.
Name: nag
Version: 1.1
Release: 1
License: GPL
Group: Applications/Horde
Source: ftp://ftp.horde.org/pub/nag/nag-%{version}.tar.gz
Source1: nag.conf
Vendor: The Horde Project
URL: http://www.horde.org/
Packager: Brent J. Nordquist <bjn@horde.org>
BuildArch: noarch
BuildRoot: %{_tmppath}/nag-root
Requires: php >= 4.2.1
Requires: apache >= 1.3.22
Requires: horde >= 2.1
Prereq: /usr/bin/perl

%description
Nag is the Horde task list application. It stores todo items, things
due later this week, etc. It is very similar in functionality to the
Palm ToDo application.

The Horde Project writes web applications in PHP and releases them under
Open Source licenses.  For more information (including help with Nag)
please visit http://www.horde.org/.

%prep
%setup -q -n %{name}-%{version}

%build

%install
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{apachedir}/conf
cp -p %{SOURCE1} $RPM_BUILD_ROOT%{apachedir}/conf
mkdir -p $RPM_BUILD_ROOT%{contentdir}/html/horde/nag
cp -pR * $RPM_BUILD_ROOT%{contentdir}/html/horde/nag
cd $RPM_BUILD_ROOT%{contentdir}/html/horde/nag/config
for d in *.dist; do
	d0=`basename $d .dist`
	if [ ! -f "$d0" ]; then
		cp -p $d $d0
	fi
done

%clean
[ "$RPM_BUILD_ROOT" != "/" ] && rm -rf $RPM_BUILD_ROOT

%pre

%post
perl -pi -e 's/$/ index.php/ if (/DirectoryIndex\s.*index\.html/ && !/index\.php/);' %{apachedir}/conf/httpd.conf
grep -i 'Include.*nag.conf$' %{apachedir}/conf/httpd.conf >/dev/null 2>&1
if [ $? -eq 0 ]; then
	perl -pi -e 's/^#+// if (/Include.*nag.conf$/i);' %{apachedir}/conf/httpd.conf
else
	echo "Include %{apachedir}/conf/nag.conf" >>%{apachedir}/conf/httpd.conf
fi
# post-install instructions:
cat <<_EOF_
You must manually configure Nag and create any required database tables!
See "CONFIGURING Nag" in %{contentdir}/html/horde/nag/docs/INSTALL
You must also restart Apache with "service httpd restart"!
_EOF_

%postun
if [ $1 -eq 0 ]; then
	perl -pi -e 's/^/#/ if (/^Include.*nag.conf$/i);' %{apachedir}/conf/httpd.conf
	cat <<_EOF2_
You must restart Apache with "service httpd restart"!
_EOF2_
fi

%files
%defattr(-,root,root)
# Apache nag.conf file
%config %{apachedir}/conf/nag.conf
# Include top level with %dir so not all files are sucked in
%dir %{contentdir}/html/horde/nag
# Include top-level files by hand
%{contentdir}/html/horde/nag/*.php
# Include these dirs so that all files _will_ get sucked in
%{contentdir}/html/horde/nag/graphics
%{contentdir}/html/horde/nag/lib
%{contentdir}/html/horde/nag/locale
%{contentdir}/html/horde/nag/po
%{contentdir}/html/horde/nag/scripts
%{contentdir}/html/horde/nag/templates
# Mark documentation files with %doc and %docdir
%doc %{contentdir}/html/horde/nag/COPYING
%doc %{contentdir}/html/horde/nag/README
%docdir %{contentdir}/html/horde/nag/docs
%{contentdir}/html/horde/nag/docs
# Mark configuration files with %config and use secure permissions
# (note that .dist files are considered software; don't mark %config)
%attr(750,root,%{apachegroup}) %dir %{contentdir}/html/horde/nag/config
%defattr(640,root,%{apachegroup})
#%{contentdir}/html/horde/nag/config/.htaccess
%{contentdir}/html/horde/nag/config/*.dist
%config %{contentdir}/html/horde/nag/config/*.php

%changelog
* Sun Apr 27 2003 Brent J. Nordquist <bjn@horde.org> 1.1-1
- Updated for 1.1

* Tue Jun 25 2002 Brent J. Nordquist <bjn@horde.org>
- 1.0 release 1

