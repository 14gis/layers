
pest:
	./vendor/bin/pest --exclude-group=remote

pest-remote:
	@RUN_REMOTE_TESTS=1 ./vendor/bin/pest --group=remote

