##
# IPP Project Task 2 Makefile
# Author: Zbynek Krivka, v2024-02-04
#

# Unlike Merlin, dev-container with VSC gives username as "vscode", so change LOGIN to your login explicitly (be aware of no additional spaces)
LOGIN=$(USER)
TEMP_DIR=temp
TASK=2
STUDENT_DIR=student
SCRIPT=interpret.php

all: check

pack: student/*
	cd $(STUDENT_DIR) && zip -r $(LOGIN).zip  * -x __MACOSX/* .git/* && mv $(LOGIN).zip ../

submit:
	zip -r xsleza26.zip student ipp2.drawio.png readme2.md

check: pack vendor
	./is_it_ok.sh $(LOGIN).zip $(TEMP_DIR) $(TASK)

run-help: interpret.php
	if [ "${HOSTNAME}" = "merlin.fit.vutbr.cz" ]; then php8.3 $(SCRIPT) --help; else php $(SCRIPT) --help; fi

vendor: composer.phar
	if [ "${HOSTNAME}" = "merlin.fit.vutbr.cz" ]; then php8.3 $< install; else php $< install; fi

clean:
	$(RM) *.zip is_it_ok.log
	$(RM) -r $(TEMP_DIR)

