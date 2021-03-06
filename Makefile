CONFIGS=dimp hermes ingo imp kronolith mnemo nag passwd turba
UPDATE=$(CONFIGS) framework
FRAMEWORK=File Horde Net SyncML Text VFS XML bin data doc

SYMLINK = ../horde-cvs/framework/devtools/horde-fw-symlinks.php

TEST_PKGS = Auth Kolab_Format Kolab_Server Kolab_Storage Kolab_FreeBusy Kolab_Filter Date Share iCalendar VFS
TEST_APPS = turba kronolith

.PHONY: update
update:
	git checkout master
	cvs update -A -r FRAMEWORK_3
	for PKG in $(UPDATE);        \
	  do                         \
	  cd $$PKG;cvs update -A -r FRAMEWORK_3; cd ..;\
	done
	for BIT in $(FRAMEWORK);     \
	  do                         \
	  rm -rf lib/$$BIT*;         \
	done
	@php -c php.ini -d include_path="../lib:../pear/php:../horde-release/horde-webmail/pear/" -q $(SYMLINK) --src framework --dest lib > /dev/null
	cd config; for fl in *.dist;do cp $$fl $${fl/.dist};done;cd ..;\
	for PKG in $(CONFIGS); \
	  do                         \
	  cd $$PKG/config; for fl in *.dist;do cp $$fl $${fl/.dist};done;cd ../..;\
	done
	find . -name *.orig | xargs rm -f
	find . -name *.rej | xargs rm -f
	git add .
	git commit -a -m "Automatic CVS update."

.PHONY:refresh
refresh: select
	while hg qpush; do \
	  if [ -n "`hg status`" ]; then \
	    hg purge && hg qrefresh; \
	  fi; \
	done

.PHONY: test
test: clean-test $(TEST_PKGS:%=test-%) $(TEST_APPS:%=test-%)

.PHONY: clean-test
clean-test:
	rm -f log/test*.log

.PHONY: $(TEST_PKGS:%=test-%)
$(TEST_PKGS:%=test-%):
	@echo
	@echo "TESTING framework/$(@:test-%=%)"
	@echo "===================================="
	@echo
	@php -c php.ini -q $(SYMLINK) --src framework --dest lib --pkg framework/$(@:test-%=%) 
	@PHP_FILES=`find framework/$(@:test-%=%)/ -name '*.php'`; \
	if [ -n "$$PHP_FILES" ]; then \
	  rm -f log/$@-syntax.log; \
	  for TEST in $$PHP_FILES; do \
	    php -l -f $$TEST | tee -a log/$@-syntax.log | grep "^No syntax errors detected in" > /dev/null || SYNTAX="$$SYNTAX $$TEST"; \
	  done; \
	  if [ -n "$$SYNTAX" ]; then \
	    echo "FAIL: Syntax errors in files: $$SYNTAX"; \
	  else \
	    echo "OK: Syntax checks."; \
	  fi; \
	fi
	@SIMPLE_TESTS=`find framework/$(@:test-%=%)/ -name '*.phpt' | xargs -L 1 -r dirname | sort | uniq`; \
	if [ -n "$$SIMPLE_TESTS" ]; then \
	  rm -f log/$@-simple.log; \
	  for TEST in $$SIMPLE_TESTS; do \
	    pear -c lib/.pearrc run-tests $$TEST/*.phpt | tee -a log/$@-simple.log | grep "^FAIL " | sed -e 's/FAIL.*\(\[.*\]\)/FAIL: \1/'; \
	  done; \
	fi
	@ALL_TESTS=`find framework/$(@:test-%=%)/ -name AllTests.php | xargs -L 1 -r dirname | sort | uniq`; \
	if [ -n "$$ALL_TESTS" ]; then \
	  CWD=`pwd`; \
	  rm -f log/$@-phpunit.log; \
	  for TEST in $$ALL_TESTS; do \
	    cd $$TEST && phpunit -d include_path=".:$$CWD/lib:$$CWD/../horde-release/horde-webmail/pear:/usr/share/php5:/usr/share/php" -d log_errors=1 -d error_log="$$CWD/log/php-errors.log" AllTests.php | tee -a $$CWD/log/$@-phpunit.log | grep "^OK" > /dev/null || PHPUNIT="FAIL"; \
	    cd $$CWD; \
	  done; \
	  if [ -n "$$PHPUNIT" ]; then \
	    echo "FAIL: Some phpunit tests failed!"; \
	  else \
	    echo "OK: PHPUnit checks."; \
	  fi; \
	fi

.PHONY: $(TEST_APPS:%=test-%)
$(TEST_APPS:%=test-%):
	@echo
	@echo "TESTING $(@:test-%=%)"
	@echo "===================================="
	@echo
	@SIMPLE_TESTS=`find $(@:test-%=%)/ -name *.phpt | xargs -L 1 -r dirname | sort | uniq`; \
	if [ -n "$$SIMPLE_TESTS" ]; then \
	  for TEST in $$SIMPLE_TESTS; do \
	    pear -c lib/.pearrc run-tests $$TEST/*.phpt | tee -a log/$@-simple.log | grep "^FAIL " | sed -e 's/FAIL.*\(\[.*\]\)/FAIL: \1/'; \
	  done; \
	fi
	@ALL_TESTS=`find $(@:test-%=%)/ -name AllTests.php | xargs -L 1 -r dirname | sort | uniq`; \
	if [ -n "$$ALL_TESTS" ]; then \
	  CWD=`pwd`; \
	  for TEST in $$ALL_TESTS; do \
	    cd $$TEST && pear run-tests -u; \
	    cd $$CWD; \
	  done; \
	fi

.PHONY: $(TEST_PKGS:%=reltest-%)
$(TEST_PKGS:%=reltest-%):
	@echo
	@echo "RELEASE TESTING framework/$(@:test-%=%)"
	@echo "============================================"
	@echo
	rm -rf tmp/pear
	mkdir tmp/pear
	pear config-create `pwd`/tmp/pear `pwd`/tmp/pear/.pearrc > /dev/null
	pear -c tmp/pear/.pearrc install -o PEAR
	tmp/pear/pear/pear -c tmp/pear/.pearrc channel-discover pear.horde.org
	rm -rf framework/$(@:reltest-%=%).*.tgz
	CWD=`pwd` && cd framework/$(@:reltest-%=%) && $$CWD/tmp/pear/pear/pear -c tmp/pear/.pearrc package package.xml
	tmp/pear/pear/pear -c tmp/pear/.pearrc install Log #FIXME: Remove later once we have a new framework/Horde release.
	tmp/pear/pear/pear -c tmp/pear/.pearrc install --force channel://pear.horde.org/Group-0.1.0 #FIXME: Remove later once we have a new framework/Group release.
	tmp/pear/pear/pear -c tmp/pear/.pearrc install --force channel://pear.horde.org/Horde_Notification-0.0.2 #FIXME: Just required for testing Kolab_Storage.
	tmp/pear/pear/pear -c tmp/pear/.pearrc install --alldeps --force framework/$(@:reltest-%=%)/$(@:reltest-%=%)-*.tgz
	tmp/pear/pear/pear -c tmp/pear/.pearrc config-set php_bin "`tmp/pear/pear/pear -c tmp/pear/.pearrc config-get php_bin` -d include_path=\"`pwd`/tmp/pear/pear/php\""
	tmp/pear/pear/pear -c tmp/pear/.pearrc channel-discover pear.phpunit.de
	tmp/pear/pear/pear -c tmp/pear/.pearrc install channel://pear.phpunit.de/PHPUnit-3.3.0
	sed -i -e "s#-d include_path.*\$$#-c `pwd`/php.reltest.ini#" tmp/pear/pear/phpunit
	@PHP_FILES=`find tmp/pear/pear/php/Horde/ -name '*.php'`; \
	if [ -n "$$PHP_FILES" ]; then \
	  rm -f log/$@-syntax.log; \
	  for TEST in $$PHP_FILES; do \
	    php -l -f $$TEST | tee -a log/$@-syntax.log | grep "^No syntax errors detected in" > /dev/null || SYNTAX="$$SYNTAX $$TEST"; \
	  done; \
	  if [ -n "$$SYNTAX" ]; then \
	    echo "FAIL: Syntax errors in files: $$SYNTAX"; \
	  else \
	    echo "OK: Syntax checks."; \
	  fi; \
	fi
	@SIMPLE_TESTS=`find tmp/pear/pear/tests/$(@:reltest-%=%) -name '*.phpt' | xargs -L 1 -r dirname | sort | uniq`; \
	if [ -n "$$SIMPLE_TESTS" ]; then \
	  rm -f log/$@-simple.log; \
	  for TEST in $$SIMPLE_TESTS; do \
	    tmp/pear/pear/pear -c tmp/pear/.pearrc run-tests $$TEST/*.phpt | tee -a log/$@-simple.log | grep "^FAIL " | sed -e 's/FAIL.*\(\[.*\]\)/FAIL: \1/'; \
	  done; \
	fi
	@ALL_TESTS=`find tmp/pear/pear/tests/$(@:reltest-%=%) -name AllTests.php | xargs -L 1 -r dirname | sort | uniq`; \
	if [ -n "$$ALL_TESTS" ]; then \
	  CWD=`pwd`; \
	  rm -f log/$@-phpunit.log; \
	  for TEST in $$ALL_TESTS; do \
	    cd $$TEST && /usr/bin/php -c $$CWD/php.reltest.ini $$CWD/tmp/pear/pear/phpunit -d include_path=".:$$CWD/tmp/pear/pear/php" -d log_errors=1 -d error_log="$$CWD/log/php-errors.log" AllTests.php | tee -a $$CWD/log/$@-phpunit.log | grep "^OK" > /dev/null || PHPUNIT="FAIL"; \
	    cd $$CWD; \
	  done; \
	  if [ -n "$$PHPUNIT" ]; then \
	    echo "FAIL: Some phpunit tests failed!"; \
	  else \
	    echo "OK: PHPUnit checks."; \
	  fi; \
	fi

.PHONY: tags
tags:
	rm -f TAGS
	find . -name '*.php' | xargs etags -a
