<?php
function skipIfNoRemote(): void {
    if (getenv('RUN_REMOTE_TESTS') !== '1') {
        test()->markTestSkipped('Remote tests disabled. Set RUN_REMOTE_TESTS=1 to enable.');
    }
}
